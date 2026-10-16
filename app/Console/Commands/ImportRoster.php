<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Imports or updates people from a roster CSV (see docs/roster-template.csv).
 * Matches on employee_id. Team leads are linked by email in a second pass.
 */
class ImportRoster extends Command
{
    protected $signature = 'people:import-roster {file} {--dry-run}';

    protected $description = 'Create or update people from a roster CSV';

    private const COLUMNS = [
        'employee_id', 'full_name', 'work_email', 'entity', 'department', 'job_title', 'team_lead_email',
        'role', 'employment_type', 'start_date', 'contract_end_date', 'sign_in',
    ];

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
        });

        Audit::log('roster_imported', null, ['rows' => count($rows)]);
        $this->info(count($rows).' people imported or updated.');

        return self::SUCCESS;
    }
}
