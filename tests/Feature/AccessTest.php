<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\QuarterlyReview;
use App\Support\Quarter;

class AccessTest extends PeopleTestCase
{
    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_team_lead_sees_their_reports_but_not_other_teams(): void
    {
        $lead = $this->person('team_lead');
        $report = $this->person('employee', $lead, 'ID');
        $otherLead = $this->person('team_lead');
        $other = $this->person('employee', $otherLead);

        $this->actingAs($lead)->get(route('people.show', $report))->assertOk();
        $this->actingAs($lead)->get(route('people.show', $other))->assertForbidden();
    }

    public function test_hr_and_directors_see_everyone_in_both_entities(): void
    {
        $lead = $this->person('team_lead');
        $idPerson = $this->person('employee', $lead, 'ID');

        foreach (['hr', 'director'] as $role) {
            $this->actingAs($this->person($role))->get(route('people.show', $idPerson))->assertOk();
        }
    }

    public function test_admin_cannot_read_performance_records(): void
    {
        $lead = $this->person('team_lead');
        $report = $this->person('employee', $lead);

        $this->actingAs($this->person('admin'))->get(route('people.show', $report))->assertForbidden();
    }

    public function test_employees_cannot_see_colleagues(): void
    {
        $lead = $this->person('team_lead');
        $a = $this->person('employee', $lead);
        $b = $this->person('employee', $lead);

        $this->actingAs($a)->get(route('people.show', $b))->assertForbidden();
        $this->actingAs($a)->get(route('people.show', $a))->assertOk();
    }

    public function test_employee_cannot_see_a_draft_review_about_them(): void
    {
        $lead = $this->person('team_lead');
        $report = $this->person('employee', $lead);
        $q = Quarter::current();
        $review = QuarterlyReview::create(['employee_id' => $report->id, 'reviewer_id' => $lead->id, 'year' => $q->year, 'quarter' => $q->quarter]);

        $this->actingAs($report)->get(route('reviews.show', $review))->assertForbidden();
        $this->actingAs($lead)->get(route('reviews.show', $review))->assertOk();
    }

    public function test_only_hr_directors_and_admin_see_the_audit_log(): void
    {
        $this->actingAs($this->person('employee'))->get(route('audit.index'))->assertForbidden();
        $this->actingAs($this->person('team_lead'))->get(route('audit.index'))->assertForbidden();
        $this->actingAs($this->person('hr'))->get(route('audit.index'))->assertOk();
    }

    public function test_feedback_visibility(): void
    {
        $lead = $this->person('team_lead');
        $recipient = $this->person('employee', $lead);
        $author = $this->person('employee');
        $stranger = $this->person('employee');
        $feedback = Feedback::create(['author_id' => $author->id, 'recipient_id' => $recipient->id, 'body' => 'Great work on the launch']);

        $this->assertTrue($feedback->visibleTo($recipient));
        $this->assertTrue($feedback->visibleTo($author));
        $this->assertTrue($feedback->visibleTo($lead));
        $this->assertFalse($feedback->visibleTo($stranger));
    }
}
