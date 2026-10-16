<?php

namespace App\Channels;

use App\Services\Slack;
use Illuminate\Notifications\Notification;

/**
 * Sends a notification as a Slack DM, looked up by the person's email.
 * Notifications opt in with a toSlackDm() method; skipped when Slack isn't configured.
 */
class SlackDmChannel
{
    public function __construct(private Slack $slack) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $this->slack->enabled() || blank($notifiable->email ?? null) || ! method_exists($notification, 'toSlackDm')) {
            return;
        }

        rescue(fn () => $this->slack->dm($notifiable->email, $notification->toSlackDm($notifiable)), report: true);
    }
}
