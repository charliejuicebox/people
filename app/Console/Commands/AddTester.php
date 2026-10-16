<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Adds one person who can sign in with their real Google or Microsoft account.
 * Used on staging, where everyone else is dummy data.
 */
class AddTester extends Command
{
    protected $signature = 'people:add-tester {email} {name}
        {--role=director : employee, team_lead, director, hr or admin}
        {--entity= : AU or ID (default: from the email domain)}
        {--department=Admin}';

    protected $description = 'Add a person who can sign in with their real work account';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $entity = $this->option('entity') ?: (str_ends_with($email, '.au') ? 'AU' : 'ID');

        $validator = Validator::make(
            ['email' => $email, 'role' => $this->option('role'), 'entity' => $entity],
            [
                'email' => ['required', 'email'],
                'role' => ['required', Rule::in(User::ROLES)],
                'entity' => ['required', Rule::in(['AU', 'ID'])],
            ],
        );
        if ($validator->fails()) {
            $this->error(implode(' ', $validator->errors()->all()));

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => $email], [
            'employee_id' => User::where('email', $email)->value('employee_id') ?? 'T'.substr(md5($email), 0, 6),
            'name' => $this->argument('name'),
            'entity' => $entity,
            'department_id' => Department::firstOrCreate(['name' => $this->option('department')])->id,
            'role' => $this->option('role'),
            'sign_in_provider' => $entity === 'AU' ? 'google' : 'microsoft',
            'locale' => $entity === 'ID' ? 'id' : 'en',
            'active' => true,
        ]);

        Audit::log('tester_added', $user, ['role' => $user->role]);
        $this->info("{$user->name} can now sign in with {$user->sign_in_provider} as {$user->role}.");

        return self::SUCCESS;
    }
}
