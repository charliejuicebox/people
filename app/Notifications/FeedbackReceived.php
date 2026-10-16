<?php

namespace App\Notifications;

use App\Models\Feedback;
use App\Channels\SlackDmChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackReceived extends Notification
{
    public function __construct(public Feedback $feedback) {}

    public function via(object $notifiable): array
    {
        return ['mail', SlackDmChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have new feedback / Anda menerima umpan balik baru')
            ->greeting("Hi {$notifiable->firstName()},")
            ->line("{$this->feedback->author?->name} has given you feedback in JB People.")
            ->action('Read feedback', route('feedback.index'));
    }

    public function toSlackDm(object $notifiable): string
    {
        return ":speech_balloon: {$this->feedback->author?->name} gave you feedback: ".route('feedback.index');
    }
}
