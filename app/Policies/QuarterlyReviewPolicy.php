<?php

namespace App\Policies;

use App\Models\QuarterlyReview;
use App\Models\User;

class QuarterlyReviewPolicy
{
    /** Leads write reviews for their reports. HR and directors can step in. Nobody reviews themselves. */
    public function create(User $user, User $employee): bool
    {
        return $user->id !== $employee->id && ($user->leads($employee) || $user->seesEveryone());
    }

    public function view(User $user, QuarterlyReview $review): bool
    {
        if ($user->id === $review->employee_id) {
            // The employee sees the review once the lead has shared it.
            return ! $review->isDraft();
        }

        return $user->canSeeRecordsOf($review->employee);
    }

    public function updateAsLead(User $user, QuarterlyReview $review): bool
    {
        // The lead's part is locked once shared, so the employee acknowledges exactly what they read.
        return $review->isDraft()
            && $user->id !== $review->employee_id
            && ($user->id === $review->reviewer_id || $user->leads($review->employee) || $user->seesEveryone());
    }

    public function share(User $user, QuarterlyReview $review): bool
    {
        return $review->isDraft() && $this->updateAsLead($user, $review);
    }

    public function updateAsEmployee(User $user, QuarterlyReview $review): bool
    {
        return $user->id === $review->employee_id && $review->isShared();
    }

    public function acknowledge(User $user, QuarterlyReview $review): bool
    {
        return $user->id === $review->employee_id && $review->isShared();
    }
}
