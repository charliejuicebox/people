<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\QuarterlyReview;
use App\Notifications\BelowExpectationsFlagged;
use App\Notifications\ReviewShared;
use App\Support\Quarter;
use Illuminate\Support\Facades\Notification;

class ReviewFlowTest extends PeopleTestCase
{
    public function test_lead_writes_shares_and_employee_acknowledges(): void
    {
        Notification::fake();
        $lead = $this->person('team_lead');
        $report = $this->person('employee', $lead, 'ID');

        $this->actingAs($lead)->post(route('reviews.start', $report))->assertRedirect();
        $review = QuarterlyReview::firstOrFail();

        $this->actingAs($lead)->put(route('reviews.update', $review), [
            'overall_rating' => 'meets',
            'expectations_next_quarter' => 'Lead the next sprint planning.',
            'make_it_matter_rating' => 'exceeds',
        ])->assertRedirect();

        $this->actingAs($lead)->post(route('reviews.share', $review))->assertRedirect();
        $this->assertSame('shared', $review->fresh()->status);
        Notification::assertSentTo($report, ReviewShared::class);

        // Employee can't change the lead's rating.
        $this->actingAs($report)->put(route('reviews.update', $review), ['self_reflection' => 'Good quarter', 'overall_rating' => 'exceeds']);
        $this->assertSame('meets', $review->fresh()->overall_rating);
        $this->assertSame('Good quarter', $review->fresh()->self_reflection);

        $this->actingAs($report)->post(route('reviews.acknowledge', $review), ['employee_comment' => 'Agreed'])->assertRedirect();
        $this->assertTrue($review->fresh()->isAcknowledged());

        // Acknowledged reviews are locked.
        $this->actingAs($lead)->put(route('reviews.update', $review), ['overall_rating' => 'below'])->assertForbidden();

        $this->assertEqualsCanonicalizing(
            ['created', 'updated', 'shared', 'updated', 'acknowledged'],
            AuditLog::where('subject_type', QuarterlyReview::class)->pluck('action')->all(),
        );
    }

    public function test_nobody_reviews_themselves_and_employees_cannot_start_reviews(): void
    {
        $lead = $this->person('team_lead');
        $report = $this->person('employee', $lead);

        $this->actingAs($report)->post(route('reviews.start', $report))->assertForbidden();
        $this->actingAs($report)->post(route('reviews.start', $lead))->assertForbidden();
    }

    public function test_cannot_share_without_rating_and_expectations(): void
    {
        $lead = $this->person('team_lead');
        $report = $this->person('employee', $lead);
        $this->actingAs($lead)->post(route('reviews.start', $report));
        $review = QuarterlyReview::firstOrFail();

        $this->actingAs($lead)->post(route('reviews.share', $review))->assertSessionHasErrors('share');
        $this->assertTrue($review->fresh()->isDraft());
    }

    public function test_below_expectations_without_prior_concern_needs_confirmation_and_flags_hr(): void
    {
        Notification::fake();
        $hr = $this->person('hr');
        $lead = $this->person('team_lead');
        $report = $this->person('employee', $lead);
        $q = Quarter::current();
        $review = QuarterlyReview::create([
            'employee_id' => $report->id, 'reviewer_id' => $lead->id, 'year' => $q->year, 'quarter' => $q->quarter,
            'overall_rating' => 'below', 'expectations_next_quarter' => 'Meet deadlines.',
        ]);

        $this->actingAs($lead)->post(route('reviews.share', $review))->assertSessionHasErrors('share');
        $this->assertTrue($review->fresh()->isDraft());

        $this->actingAs($lead)->post(route('reviews.share', $review), ['confirm_expectation_communicated' => 1]);
        $review->refresh();
        $this->assertTrue($review->isShared());
        $this->assertNotNull($review->flagged_to_hr_at);
        Notification::assertSentTo($hr, BelowExpectationsFlagged::class);
    }

    public function test_below_expectations_after_a_raised_concern_needs_no_extra_step(): void
    {
        Notification::fake();
        $lead = $this->person('team_lead');
        $report = $this->person('employee', $lead);
        $q = Quarter::current();
        $p = $q->previous();
        QuarterlyReview::create([
            'employee_id' => $report->id, 'year' => $p->year, 'quarter' => $p->quarter, 'status' => 'acknowledged',
            'concerns' => 'Deadlines missed on two projects.',
        ]);
        $review = QuarterlyReview::create([
            'employee_id' => $report->id, 'reviewer_id' => $lead->id, 'year' => $q->year, 'quarter' => $q->quarter,
            'overall_rating' => 'below', 'expectations_next_quarter' => 'Meet deadlines.',
        ]);

        $this->assertFalse($review->needsNoSurprisesConfirmation());
        $this->actingAs($lead)->post(route('reviews.share', $review))->assertSessionHasNoErrors();
        Notification::assertNotSentTo($lead, BelowExpectationsFlagged::class);
        Notification::assertCount(1); // only ReviewShared to the employee
    }
}
