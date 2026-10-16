<?php

namespace App\Notifications;

use App\Models\QuarterlyReview;
use App\Channels\SlackDmChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewShared extends Notification
{
    public function __construct(public QuarterlyReview $review) {}

    public function via(object $notifiable): array
    {
        return ['mail', SlackDmChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your {$this->review->period()->label()} review is ready / Review Anda sudah siap")
            ->greeting("Hi {$notifiable->firstName()},")
            ->line("{$this->review->reviewer?->name} has shared your {$this->review->period()->label()} quarterly review.")
            ->line('Please read it, add your comments and acknowledge it after you have discussed it together.')
            ->action('Open my review', route('reviews.show', $this->review));
    }

    public function toSlackDm(object $notifiable): string
    {
        return ":memo: Your {$this->review->period()->label()} review is ready to read and acknowledge: ".route('reviews.show', $this->review);
    }
}
