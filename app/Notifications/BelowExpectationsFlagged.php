<?php

namespace App\Notifications;

use App\Models\QuarterlyReview;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BelowExpectationsFlagged extends Notification
{
    public function __construct(public QuarterlyReview $review) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Review flagged: below expectations without a prior concern ({$this->review->employee->name})")
            ->line("{$this->review->reviewer?->name} rated {$this->review->employee->name} below expectations for {$this->review->period()->label()}.")
            ->line('The previous quarterly review did not record a concern. The lead confirmed the expectation was communicated. Please check in.')
            ->action('Open review', route('reviews.show', $this->review));
    }
}
