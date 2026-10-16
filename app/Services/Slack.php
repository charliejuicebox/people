<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Minimal Slack Web API client (chat.postMessage, users.lookupByEmail).
 * Needs a bot token with chat:write and users:read.email scopes.
 */
class Slack
{
    public function enabled(): bool
    {
        return filled(config('people.slack.bot_token'));
    }

    /** "<@U123>" when the email matches a Slack user, otherwise null. */
    public function mentionFor(string $email): ?string
    {
        $response = $this->call('users.lookupByEmail', ['email' => $email], asForm: true);

        return ($response['ok'] ?? false) ? '<@'.$response['user']['id'].'>' : null;
    }

    public function post(string $channel, string $text, array $blocks = []): void
    {
        $response = $this->call('chat.postMessage', array_filter([
            'channel' => $channel,
            'text' => $text,
            'blocks' => $blocks ?: null,
            'unfurl_links' => false,
        ]));

        if (! ($response['ok'] ?? false)) {
            throw new RuntimeException('Slack error: '.($response['error'] ?? 'unknown'));
        }
    }

    /** Direct message to a person, looked up by email. Silently skipped if they're not on Slack. */
    public function dm(string $email, string $text): bool
    {
        $lookup = $this->call('users.lookupByEmail', ['email' => $email], asForm: true);

        if (! ($lookup['ok'] ?? false)) {
            Log::info('Slack DM skipped: no Slack user for '.$email);

            return false;
        }

        $this->post($lookup['user']['id'], $text);

        return true;
    }

    private function call(string $method, array $payload, bool $asForm = false): array
    {
        $request = Http::withToken(config('people.slack.bot_token'))->timeout(10);
        $request = $asForm ? $request->asForm() : $request->asJson();

        return $request->post("https://slack.com/api/{$method}", $payload)->json() ?? [];
    }
}
