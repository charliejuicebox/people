<?php

namespace Tests\Feature;

use App\Models\Praise;
use Illuminate\Support\Facades\Http;

class PraiseAndFeedbackTest extends PeopleTestCase
{
    public function test_praise_posts_to_the_juicebox_channel_with_mentions(): void
    {
        config(['people.slack.bot_token' => 'xoxb-test', 'people.slack.praise_channel' => '#juicebox']);
        Http::fake([
            'slack.com/api/users.lookupByEmail' => Http::response(['ok' => true, 'user' => ['id' => 'U123']]),
            'slack.com/api/chat.postMessage' => Http::response(['ok' => true]),
        ]);

        $from = $this->person('employee');
        $to = $this->person('employee', null, 'ID');

        $this->actingAs($from)->post(route('praise.store'), [
            'recipient_id' => $to->id, 'value_tag' => 'work_as_one', 'message' => 'Brilliant teamwork on the launch!',
        ])->assertRedirect();

        $this->assertNotNull(Praise::first()->slack_posted_at);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'chat.postMessage')
            && $request['channel'] === '#juicebox'
            && str_contains($request['text'], 'Work as One / Bekerja Sebagai Satu'));
    }

    public function test_praise_is_saved_even_when_slack_is_not_configured(): void
    {
        config(['people.slack.bot_token' => null]);
        Http::fake();

        $this->actingAs($this->person())->post(route('praise.store'), [
            'recipient_id' => $this->person()->id, 'value_tag' => 'make_it_matter', 'message' => 'Fantastic attention to detail.',
        ]);

        $this->assertSame('Slack not configured', Praise::first()->slack_error);
        Http::assertNothingSent();
    }

    public function test_cannot_praise_or_give_feedback_to_yourself(): void
    {
        $me = $this->person();

        $this->actingAs($me)->post(route('praise.store'), ['recipient_id' => $me->id, 'value_tag' => 'make_it_matter', 'message' => 'I am great at this'])
            ->assertSessionHasErrors('recipient_id');
        $this->actingAs($me)->post(route('feedback.store'), ['recipient_id' => $me->id, 'body' => 'I am great at this'])
            ->assertSessionHasErrors('recipient_id');
    }
}
