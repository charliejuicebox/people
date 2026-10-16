<?php

namespace App\Notifications;

use App\Channels\SlackDmChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReminder extends Notification
{
    /** @param  array<int, string>  $lines */
    public function __construct(public string $subject, public array $lines) {}

    public function via(object $notifiable): array
    {
        return ['mail', SlackDmChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject)->greeting("Hi {$notifiable->firstName()},");

        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        return $mail->action('Open JB People', route('dashboard'));
    }

    public function toSlackDm(object $notifiable): string
    {
        return ":alarm_clock: {$this->subject}\n• ".implode("\n• ", $this->lines)."\n".route('dashboard');
    }
}
