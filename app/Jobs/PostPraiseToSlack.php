<?php

namespace App\Jobs;

use App\Models\Praise;
use App\Services\Slack;
use App\Support\Values;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostPraiseToSlack implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Praise $praise) {}

    public function handle(Slack $slack): void
    {
        $praise = $this->praise->loadMissing(['author', 'recipient']);

        if (! $slack->enabled()) {
            Log::info("Praise #{$praise->id} not posted: SLACK_BOT_TOKEN is not set.");
            $praise->update(['slack_error' => 'Slack not configured']);

            return;
        }

        try {
            $to = $slack->mentionFor($praise->recipient->email) ?? '*'.$praise->recipient->name.'*';
            $from = $slack->mentionFor($praise->author->email) ?? $praise->author->name;
            $value = Values::EMOJI[$praise->value_tag].' *'.Values::bilingual($praise->value_tag).'*';

            $text = ":tada: {$to} received praise from {$from}\n{$value}\n>".str_replace("\n", "\n>", $praise->message);

            $slack->post(config('people.slack.praise_channel'), $text);
            $praise->update(['slack_posted_at' => now(), 'slack_error' => null]);
        } catch (Throwable $e) {
            $praise->update(['slack_error' => substr($e->getMessage(), 0, 250)]);

            throw $e;
        }
    }
}
