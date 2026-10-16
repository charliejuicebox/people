<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Feedback;
use App\Models\Praise;
use App\Models\QuarterlyReview;
use App\Models\User;
use App\Support\Quarter;
use Illuminate\Database\Seeder;

/**
 * DUMMY DATA ONLY. Made-up people for demos and testing.
 * The real roster is loaded with `php artisan people:import-roster` after the CTO review.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departments = collect(['Client Experience', 'Tech Team', 'Growth', 'Experience Team', 'Admin'])
            ->mapWithKeys(fn ($name) => [$name => Department::firstOrCreate(['name' => $name])]);

        $make = function (string $id, string $name, string $entity, string $dept, string $title, string $role, ?User $lead = null, array $extra = []) use ($departments) {
            $domain = $entity === 'AU' ? 'example-au.test' : 'example-id.test';

            return User::create([
                'employee_id' => $id,
                'name' => $name,
                'email' => str($name)->lower()->replace(' ', '.').'@'.$domain,
                'entity' => $entity,
                'department_id' => $departments[$dept]->id,
                'job_title' => $title,
                'team_lead_id' => $lead?->id,
                'role' => $role,
                'employment_type' => $entity === 'AU' ? 'permanent_full_time' : 'PKWTT',
                'start_date' => now()->subMonths(rand(6, 60))->startOfMonth(),
                'sign_in_provider' => $entity === 'AU' ? 'google' : 'microsoft',
                'locale' => $entity === 'ID' ? 'id' : 'en',
            ] + $extra);
        };

        // Leadership, HR, admin
        $director = $make('D001', 'Dana Director', 'AU', 'Admin', 'Managing Director', 'director');
        $hrAu = $make('H001', 'Harper Hughes', 'AU', 'Admin', 'People & Culture', 'hr', $director);
        $hrId = $make('H002', 'Hana Putri', 'ID', 'Admin', 'HR Manager', 'hr', $director);
        $make('A001', 'Alex Admin', 'AU', 'Admin', 'Systems Admin', 'admin', $director);

        // Team leads (some AU leads manage ID people)
        $techLead = $make('L001', 'Tom Tanaka', 'AU', 'Tech Team', 'Tech Lead', 'team_lead', $director);
        $cxLead = $make('L002', 'Chloe Carter', 'AU', 'Client Experience', 'Head of Client Experience', 'team_lead', $director);
        $growthLead = $make('L003', 'Gede Wirawan', 'ID', 'Growth', 'Growth Lead', 'team_lead', $director);
        $xpLead = $make('L004', 'Erin Ellis', 'AU', 'Experience Team', 'Creative Director', 'team_lead', $director);

        $pkwt = fn () => ['employment_type' => 'PKWT', 'contract_end_date' => now()->addMonths(rand(2, 10))->endOfMonth()];

        $people = [
            $make('E001', 'Putu Adi', 'ID', 'Tech Team', 'Back End Developer', 'employee', $techLead, $pkwt()),
            $make('E002', 'Kadek Sari', 'ID', 'Tech Team', 'Front End Developer', 'employee', $techLead),
            $make('E003', 'Sam Nguyen', 'AU', 'Tech Team', 'Full Stack Developer', 'employee', $techLead),
            $make('E004', 'Wayan Dharma', 'ID', 'Tech Team', 'QA Engineer', 'employee', $techLead, $pkwt()),
            $make('E005', 'Priya Shah', 'AU', 'Tech Team', 'Product Owner', 'employee', $techLead),
            $make('E006', 'Mia Brooks', 'AU', 'Client Experience', 'Account Manager', 'employee', $cxLead),
            $make('E007', 'Komang Ayu', 'ID', 'Client Experience', 'Project Manager', 'employee', $cxLead, $pkwt()),
            $make('E008', 'Jack Wilson', 'AU', 'Client Experience', 'Project Manager', 'employee', $cxLead),
            $make('E009', 'Made Surya', 'ID', 'Growth', 'SEO Specialist', 'employee', $growthLead),
            $make('E010', 'Nyoman Lestari', 'ID', 'Growth', 'PPC Specialist', 'employee', $growthLead, $pkwt()),
            $make('E011', 'Olivia Reid', 'AU', 'Growth', 'Digital Strategist', 'employee', $growthLead),
            $make('E012', 'Ketut Rahayu', 'ID', 'Experience Team', 'UI Designer', 'employee', $xpLead),
            $make('E013', 'Lucy Park', 'AU', 'Experience Team', 'UX Planner', 'employee', $xpLead),
            $make('E014', 'Agus Pratama', 'ID', 'Experience Team', 'Graphic Designer', 'employee', $xpLead, $pkwt()),
            $make('E015', 'Indah Permata', 'ID', 'Admin', 'Finance Officer', 'employee', $hrId),
        ];

        $current = Quarter::current();
        $previous = $current->previous();

        // Last quarter: most reviews done and acknowledged.
        foreach ($people as $i => $person) {
            if ($i === 9) {
                continue; // one person missed last quarter
            }
            $concern = $i === 3 ? 'Test coverage on the last two releases was below the agreed standard, and two bugs reached the client.' : null;
            QuarterlyReview::create([
                'employee_id' => $person->id,
                'reviewer_id' => $person->team_lead_id,
                'year' => $previous->year,
                'quarter' => $previous->quarter,
                'status' => 'acknowledged',
                'make_it_matter_rating' => 'meets',
                'make_it_matter_comment' => 'Consistently careful with the details on client work.',
                'work_as_one_rating' => $i % 3 === 0 ? 'exceeds' : 'meets',
                'work_as_one_comment' => 'Works well across the Perth and Bali teams.',
                'embrace_change_rating' => 'meets',
                'embrace_change_comment' => 'Picked up the new tooling quickly.',
                'went_well' => 'Delivered the main projects on time.',
                'concerns' => $concern,
                'expectations_next_quarter' => $concern
                    ? 'Every release has automated tests for new features, and no regressions reach the client.'
                    : 'Keep the current standard and take the lead on one internal improvement.',
                'overall_rating' => $concern ? 'developing' : 'meets',
                'self_reflection' => 'A busy quarter. I want more time for professional development.',
                'discussed_on' => $previous->end()->subDays(10),
                'shared_at' => $previous->end()->subDays(10),
                'acknowledged_at' => $previous->end()->subDays(8),
            ]);
        }

        // This quarter: a mix of statuses.
        foreach (array_slice($people, 0, 7) as $i => $person) {
            QuarterlyReview::create([
                'employee_id' => $person->id,
                'reviewer_id' => $person->team_lead_id,
                'year' => $current->year,
                'quarter' => $current->quarter,
                'status' => ['acknowledged', 'shared', 'draft'][$i % 3],
                'went_well' => 'Strong quarter so far.',
                'expectations_next_quarter' => $i % 3 === 2 ? null : 'Mentor a newer team member.',
                'overall_rating' => $i % 3 === 2 ? null : 'meets',
                'shared_at' => $i % 3 === 2 ? null : now()->subDays(6),
                'acknowledged_at' => $i % 3 === 0 ? now()->subDays(3) : null,
            ]);
        }

        Feedback::create([
            'author_id' => $people[2]->id, 'recipient_id' => $people[0]->id, 'value_tag' => 'work_as_one',
            'body' => 'Thanks for jumping on the API issue with me late on Thursday. Pairing across Perth and Bali worked really well.',
        ]);
        Feedback::create([
            'author_id' => $cxLead->id, 'recipient_id' => $people[6]->id, 'value_tag' => 'make_it_matter',
            'body' => 'The status reports for the client were clear and on time all month. One thing to tighten: flag scope changes the same day.',
        ]);

        Praise::create([
            'author_id' => $people[5]->id, 'recipient_id' => $people[11]->id, 'value_tag' => 'make_it_matter',
            'message' => 'The new homepage designs blew the client away. Amazing attention to detail!',
            'slack_error' => 'Slack not configured',
        ]);
        Praise::create([
            'author_id' => $growthLead->id, 'recipient_id' => $people[10]->id, 'value_tag' => 'embrace_change',
            'message' => 'Rebuilt the reporting dashboard with the new tools in record time.',
            'slack_error' => 'Slack not configured',
        ]);
    }
}
