<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class PeopleTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function person(string $role = 'employee', ?User $lead = null, string $entity = 'AU'): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'employee_id' => 'T'.$n,
            'name' => "Person {$n}",
            'email' => "person{$n}@example.test",
            'entity' => $entity,
            'department_id' => Department::firstOrCreate(['name' => 'Tech Team'])->id,
            'role' => $role,
            'team_lead_id' => $lead?->id,
            'sign_in_provider' => $entity === 'AU' ? 'google' : 'microsoft',
        ]);
    }
}
