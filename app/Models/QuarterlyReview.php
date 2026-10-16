<?php

namespace App\Models;

use App\Support\Quarter;
use App\Support\Values;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuarterlyReview extends Model
{
    public const STATUSES = ['draft', 'shared', 'acknowledged'];

    public const LEAD_FIELDS = [
        'make_it_matter_rating', 'make_it_matter_comment',
        'work_as_one_rating', 'work_as_one_comment',
        'embrace_change_rating', 'embrace_change_comment',
        'went_well', 'concerns', 'expectations_next_quarter', 'flexibility_check',
        'overall_rating', 'discussed_on',
    ];

    public const EMPLOYEE_FIELDS = ['self_reflection', 'employee_comment'];

    protected $fillable = [
        'employee_id', 'reviewer_id', 'year', 'quarter', 'status',
        ...self::LEAD_FIELDS,
        ...self::EMPLOYEE_FIELDS,
        'shared_at', 'acknowledged_at', 'expectation_communicated_confirmed', 'flagged_to_hr_at',
    ];

    protected function casts(): array
    {
        return [
            'shared_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'flagged_to_hr_at' => 'datetime',
            'discussed_on' => 'date',
            'expectation_communicated_confirmed' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function period(): Quarter
    {
        return new Quarter($this->year, $this->quarter);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isShared(): bool
    {
        return $this->status === 'shared';
    }

    public function isAcknowledged(): bool
    {
        return $this->status === 'acknowledged';
    }

    /** Previous quarter's review for the same person, if any. */
    public function previousReview(): ?self
    {
        $prev = $this->period()->previous();

        return self::where('employee_id', $this->employee_id)
            ->where('year', $prev->year)->where('quarter', $prev->quarter)->first();
    }

    /**
     * "No surprises": a below-expectations rating should follow a review that already raised a concern.
     */
    public function needsNoSurprisesConfirmation(): bool
    {
        if ($this->overall_rating !== 'below') {
            return false;
        }

        $previous = $this->previousReview();

        return ! $previous || ! $previous->isAcknowledged() || blank($previous->concerns);
    }

    public static function valueKeys(): array
    {
        return Values::keys();
    }
}
