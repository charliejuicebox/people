<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use App\Models\Praise;
use App\Models\QuarterlyReview;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Console\Command;

/**
 * Retention (decided 16 Oct 2026): anytime feedback is kept 2 years; formal records
 * (quarterly reviews) are kept for the length of employment plus 7 years.
 */
class PruneRecords extends Command
{
    protected $signature = 'people:prune-records {--dry-run}';

    protected $description = 'Delete records past their retention period';

    public function handle(): int
    {
        $feedbackCutoff = now()->subYears(config('people.retention.feedback_years'));
        $formalCutoff = now()->subYears(config('people.retention.formal_years_after_leaving'));

        $feedback = Feedback::where('created_at', '<', $feedbackCutoff);
        $leavers = User::whereNotNull('left_at')->where('left_at', '<', $formalCutoff)->pluck('id');
        $reviews = QuarterlyReview::whereIn('employee_id', $leavers);
        $praise = Praise::whereIn('recipient_id', $leavers);

        $counts = ['feedback' => $feedback->count(), 'reviews' => $reviews->count(), 'praise' => $praise->count()];
        $this->table(['Type', 'To delete'], collect($counts)->map(fn ($n, $k) => [$k, $n])->values());

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $feedback->delete();
        $reviews->delete();
        $praise->delete();
        Audit::log('retention_prune', null, $counts);

        return self::SUCCESS;
    }
}
