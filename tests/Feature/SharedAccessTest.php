<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Feedback;
use App\Models\QuarterlyReview;
use App\Models\RecordAccess;
use App\Support\Quarter;

class SharedAccessTest extends PeopleTestCase
{
    public function test_hr_can_give_someone_else_access_and_it_is_audited(): void
    {
        $lead = $this->person('team_lead');
        $person = $this->person('employee', $lead, 'ID');
        $accountLead = $this->person('team_lead');
        $hr = $this->person('hr');

        $this->actingAs($accountLead)->get(route('people.show', $person))->assertForbidden();

        $this->actingAs($hr)->post(route('people.access.grant', $person), ['user_id' => $accountLead->id, 'reason' => 'AU account lead'])
            ->assertRedirect();

        $this->assertDatabaseHas('record_access', ['person_id' => $person->id, 'user_id' => $accountLead->id, 'granted_by' => $hr->id]);
        $this->assertTrue(AuditLog::where('action', 'granted_access')->where('subject_id', $person->id)->exists());
        $this->actingAs($accountLead)->get(route('people.show', $person))->assertOk()->assertSee('AU account lead');
    }

    public function test_shared_viewer_can_read_reviews_and_feedback_but_not_edit_or_share(): void
    {
        $lead = $this->person('team_lead');
        $person = $this->person('employee', $lead, 'ID');
        $viewer = $this->person('team_lead');
        RecordAccess::create(['person_id' => $person->id, 'user_id' => $viewer->id]);

        $q = Quarter::current();
        $review = QuarterlyReview::create(['employee_id' => $person->id, 'reviewer_id' => $lead->id, 'year' => $q->year, 'quarter' => $q->quarter, 'status' => 'draft']);
        $feedback = Feedback::create(['author_id' => $lead->id, 'recipient_id' => $person->id, 'body' => 'Great sprint']);

        $this->assertTrue($viewer->can('view', $review));
        $this->assertTrue($feedback->visibleTo($viewer));
        $this->assertFalse($viewer->can('updateAsLead', $review));
        $this->assertFalse($viewer->can('share', $review));
        $this->assertFalse($viewer->can('create', [QuarterlyReview::class, $person]));
        $this->actingAs($viewer)->get(route('dashboard'))->assertSee($person->name)->assertSee('Shared with you');
        $this->actingAs($viewer)->get(route('people.index'))->assertSee($person->name);
    }

    public function test_only_hr_and_directors_manage_access(): void
    {
        $lead = $this->person('team_lead');
        $person = $this->person('employee', $lead);
        $other = $this->person('employee');

        foreach ([$lead, $this->person('admin'), $other] as $user) {
            $this->actingAs($user)->post(route('people.access.grant', $person), ['user_id' => $other->id])->assertForbidden();
        }
        $this->assertDatabaseCount('record_access', 0);
    }

    public function test_removing_access_takes_it_away(): void
    {
        $person = $this->person('employee', $this->person('team_lead'));
        $viewer = $this->person('employee');
        $grant = RecordAccess::create(['person_id' => $person->id, 'user_id' => $viewer->id]);

        $this->actingAs($this->person('director'))->delete(route('people.access.revoke', [$person, $grant]))->assertRedirect();

        $this->actingAs($viewer->fresh())->get(route('people.show', $person))->assertForbidden();
        $this->assertTrue(AuditLog::where('action', 'revoked_access')->exists());
    }
}
