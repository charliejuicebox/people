<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\RecordAccess;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Imports or updates people from a roster CSV (see docs/roster-template.csv).
 * Matches on employee_id. Team leads are linked by email in a second pass.
 * Optional column viewable_by_emails (separated by ;) gives those people read access
 * to the person's reviews and feedback, on top of the team lead.
 */
class ImportRoster extends Command
{
    protected $signature = 'people:import-roster {file} {--dry-run}';

    protected $description = 'Create or update people from a roster CSV';

    private const COLUMNS = [
        'employee_id', 'full_name', 'work_email', 'entity', 'department', 'job_title', 'team_lead_email',
        'role', 'employment_type', 'start_date', 'contract_end_date', 'sign_in',
    ];

    private const ROSTER_REASON = 'From the roster';

    public function handle(): int
    {
        $handle = fopen($this->argument('file'), 'r');
        if (! $handle) {
            $this->error('Cannot open file.');

            return self::FAILURE;
        }

        $header = array_map(fn ($h) => trim(strtolower($h)), fgetcsv($handle));
        $missing = array_diff(self::COLUMNS, $header);
        if ($missing) {
            $this->error('Missing columns: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $rows = [];
        $line = 1;
        $errors = [];
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($values)) === 0) {
                continue;
            }
            $row = array_combine($header, array_pad($values, count($header), ''));
            $validator = Validator::make($row, [
                'employee_id' => ['required'],
                'full_name' => ['required'],
                'work_email' => ['required', 'email'],
                'entity' => ['required', Rule::in(['AU', 'ID'])],
                'department' => ['required'],
                'role' => ['required', Rule::in(User::ROLES)],
                'sign_in' => ['required', Rule::in(['google', 'microsoft'])],
                'start_date' => ['nullable', 'date'],
                'contract_end_date' => ['nullable', 'date'],
                'team_lead_email' => ['nullable', 'email'],
            ]);
            if ($validator->fails()) {
                $errors[] = "Line {$line}: ".implode(' ', $validator->errors()->all());
            }
            $rows[] = $row;
        }
        fclose($handle);

        if ($errors) {
            $this->error("Roster not imported. Fix these rows:\n".implode("\n", $errors));

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info(count($rows).' row(s) are valid. Nothing imported (dry run).');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                User::updateOrCreate(['employee_id' => $row['employee_id']], [
                    'name' => $row['full_name'],
                    'email' => strtolower($row['work_email']),
                    'entity' => $row['entity'],
                    'department_id' => Department::firstOrCreate(['name' => trim($row['department'])])->id,
                    'job_title' => $row['job_title'] ?: null,
                    'role' => $row['role'],
                    'employment_type' => $row['employment_type'] ?: null,
                    'start_date' => $row['start_date'] ?: null,
                    'contract_end_date' => $row['contract_end_date'] ?: null,
                    'sign_in_provider' => $row['sign_in'],
                    'locale' => $row['entity'] === 'ID' ? 'id' : 'en',
                    'active' => true,
                ]);
            }

            foreach ($rows as $row) {
                $lead = $row['team_lead_email'] ? User::where('email', strtolower($row['team_lead_email']))->first() : null;
                if ($row['team_lead_email'] && ! $lead) {
                    $this->warn("No team lead found with email {$row['team_lead_email']} (for {$row['full_name']}).");
                }
                User::where('employee_id', $row['employee_id'])->update(['team_lead_id' => $lead?->id]);
            }

            foreach ($rows as $row) {
                if (! array_key_exists('viewable_by_emails', $row)) {
                    continue;
                }
                $person = User::where('employee_id', $row['employee_id'])->first();
                $viewerIds = collect(preg_split('/[;,]/', $row['viewable_by_emails']))
                    ->map(fn ($e) => strtolower(trim($e)))->filter()
                    ->map(function ($email) use ($row) {
                        $viewer = User::where('email', $email)->first();
                        if (! $viewer) {
                            $this->warn("No one found with email {$email} (viewable by, for {$row['full_name']}).");
                        }

                        return $viewer?->id;
                    })
                    ->filter()
                    // The person and their team lead already have access.
                    ->reject(fn ($id) => $id === $person->id || $id === $person->team_lead_id)
                    ->unique();

                // The roster is the source of truth for roster-granted access; grants added in the app stay.
                RecordAccess::where('person_id', $person->id)->where('reason', self::ROSTER_REASON)
                    ->whereNotIn('user_id', $viewerIds)->delete();
                foreach ($viewerIds as $viewerId) {
                    RecordAccess::firstOrCreate(
                        ['person_id' => $person->id, 'user_id' => $viewerId],
                        ['reason' => self::ROSTER_REASON],
                    );
                }
            }
        });

        Audit::log('roster_imported', null, ['rows' => count($rows)]);
        $this->info(count($rows).' people imported or updated.');

        return self::SUCCESS;
    }
}
