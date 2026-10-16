<?php

namespace App\Console\Commands;

use App\Models\QuarterlyReview;
use App\Models\User;
use App\Notifications\ReviewReminder;
use App\Support\Quarter;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Nudges team leads about missing quarterly reviews, and employees about reviews
 * waiting for their acknowledgement. Runs on weekdays in business hours only.
 */
class SendReviewReminders extends Command
{
    protected $signature = 'people:send-review-reminders {--force : Ignore the business-hours check}';

    protected $description = 'Remind leads about missing quarterly reviews and employees about unacknowledged ones';

    public function handle(): int
    {
        $now = CarbonImmutable::now(config('people.timezone'));
        $hours = config('people.business_hours');

        if (! $this->option('force') && ($now->isWeekend() || $now->hour < $hours['start'] || $now->hour >= $hours['end'])) {
            $this->info('Outside business hours, no reminders sent.');

            return self::SUCCESS;
        }

        $quarter = Quarter::current($now);
        $daysLeft = (int) $now->diffInDays($quarter->end(), false);
        $sent = 0;

        if ($daysLeft <= config('people.review_reminder_days_before_quarter_end')) {
            $sent += $this->remindLeads($quarter, $daysLeft);
        }

        $sent += $this->remindEmployees($now);

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }

    private function remindLeads(Quarter $quarter, int $daysLeft): int
    {
        $sent = 0;

        User::active()->whereHas('directReports', fn ($q) => $q->active())->each(function (User $lead) use ($quarter, $daysLeft, &$sent) {
            $missing = $lead->directReports()->active()->get()->filter(fn (User $person) => ! QuarterlyReview::where([
                'employee_id' => $person->id, 'year' => $quarter->year, 'quarter' => $quarter->quarter,
            ])->whereIn('status', ['shared', 'acknowledged'])->exists());

            if ($missing->isEmpty()) {
                return;
            }

            $lead->notify(new ReviewReminder(
                "{$missing->count()} {$quarter->label()} review(s) still to share ({$daysLeft} days left)",
                $missing->map(fn (User $p) => $p->name)->values()->all(),
            ));
            $sent++;
        });

        return $sent;
    }

    private function remindEmployees(CarbonImmutable $now): int
    {
        $sent = 0;

        QuarterlyReview::with('employee')->where('status', 'shared')
            ->where('shared_at', '<=', $now->subDays(5))
            ->each(function (QuarterlyReview $review) use (&$sent) {
                $review->employee->notify(new ReviewReminder(
                    "Please acknowledge your {$review->period()->label()} review",
                    ['Your team lead shared it on '.$review->shared_at->format('j M').'. Read it, add any comments and acknowledge it.'],
                ));
                $sent++;
            });

        return $sent;
    }
}
