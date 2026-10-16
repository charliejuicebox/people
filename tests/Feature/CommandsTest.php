<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use App\Notifications\ReviewReminder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CommandsTest extends PeopleTestCase
{
    public function test_roster_import_creates_people_and_links_leads(): void
    {
        $csv = tempnam(sys_get_temp_dir(), 'roster');
        file_put_contents($csv, implode("\n", [
            'employee_id,full_name,work_email,entity,department,job_title,team_lead_email,role,employment_type,start_date,contract_end_date,sign_in',
            'L1,Lead Person,lead@example.test,AU,Tech Team,Tech Lead,,team_lead,permanent_full_time,2024-01-01,,google',
            'E1,Team Member,member@example.test,ID,Growth,SEO Specialist,lead@example.test,employee,PKWT,2025-01-06,2026-12-31,microsoft',
        ]));

        $this->artisan('people:import-roster', ['file' => $csv])->assertSuccessful();

        $member = User::where('employee_id', 'E1')->first();
        $this->assertSame('lead@example.test', $member->teamLead->email);
        $this->assertSame('id', $member->locale);
        $this->assertSame('2026-12-31', $member->contract_end_date->toDateString());
    }

    public function test_roster_import_rejects_bad_rows_and_imports_nothing(): void
    {
        $csv = tempnam(sys_get_temp_dir(), 'roster');
        file_put_contents($csv, implode("\n", [
            'employee_id,full_name,work_email,entity,department,job_title,team_lead_email,role,employment_type,start_date,contract_end_date,sign_in',
            'E1,Good Row,good@example.test,AU,Tech Team,Dev,,employee,,,,google',
            'E2,Bad Row,not-an-email,NZ,Tech Team,Dev,,boss,,,,okta',
        ]));

        $this->artisan('people:import-roster', ['file' => $csv])->assertFailed();
        $this->assertSame(0, User::count());
    }

    public function test_reminders_only_go_out_in_business_hours(): void
    {
        Notification::fake();
        $lead = $this->person('team_lead');
        $this->person('employee', $lead);

        Carbon::setTestNow(Carbon::create(2026, 12, 20, 21, 0, 0, 'Australia/Perth')); // Sunday night
        $this->artisan('people:send-review-reminders')->assertSuccessful();
        Notification::assertNothingSent();

        Carbon::setTestNow(Carbon::create(2026, 12, 15, 9, 0, 0, 'Australia/Perth')); // Tuesday morning, 16 days left
        $this->artisan('people:send-review-reminders')->assertSuccessful();
        Notification::assertSentTo($lead, ReviewReminder::class);
    }

    public function test_feedback_older_than_two_years_is_pruned(): void
    {
        $a = $this->person();
        $b = $this->person();
        $old = Feedback::create(['author_id' => $a->id, 'recipient_id' => $b->id, 'body' => 'old feedback']);
        $old->forceFill(['created_at' => now()->subYears(3)])->save();
        Feedback::create(['author_id' => $a->id, 'recipient_id' => $b->id, 'body' => 'new feedback']);

        $this->artisan('people:prune-records')->assertSuccessful();
        $this->assertSame(['new feedback'], Feedback::pluck('body')->all());
    }
}
