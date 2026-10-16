<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use LogicException;
use Mockery;

class AuthAndAuditTest extends PeopleTestCase
{
    private function fakeSocialite(string $provider, string $id, string $email): void
    {
        $remote = (new SocialiteUser)->map(['id' => $id, 'email' => $email, 'name' => 'Remote']);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($remote);
        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    public function test_roster_member_signs_in_and_identity_is_linked(): void
    {
        $user = $this->person();
        $this->fakeSocialite('google', 'g-1', strtoupper($user->email));

        $this->get(route('auth.callback', 'google'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_identities', ['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'g-1']);
    }

    public function test_microsoft_identity_links_to_the_same_person_after_the_2027_move(): void
    {
        $user = $this->person();
        $user->identities()->create(['provider' => 'google', 'provider_user_id' => 'g-1']);
        $this->fakeSocialite('microsoft', 'm-1', $user->email);

        $this->get(route('auth.callback', 'microsoft'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(2, $user->identities()->count());
    }

    public function test_people_not_on_the_roster_cannot_sign_in(): void
    {
        $this->fakeSocialite('google', 'g-x', 'stranger@example.test');

        $this->get(route('auth.callback', 'google'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertTrue(AuditLog::where('action', 'login_denied')->exists());
    }

    public function test_leavers_cannot_sign_in(): void
    {
        $user = $this->person();
        $user->update(['left_at' => now()->subDay()]);
        $this->fakeSocialite('google', 'g-2', $user->email);

        $this->get(route('auth.callback', 'google'));
        $this->assertGuest();
    }

    public function test_dev_login_is_disabled_outside_local(): void
    {
        config(['people.dev_login' => true]); // APP_ENV is "testing"
        $user = $this->person();

        $this->post(route('dev-login'), ['user_id' => $user->id])->assertNotFound();
        $this->assertGuest();
    }

    public function test_audit_log_is_append_only(): void
    {
        $log = AuditLog::create(['action' => 'test']);

        $this->expectException(LogicException::class);
        $log->update(['action' => 'changed']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = AuditLog::create(['action' => 'test']);

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_viewing_a_profile_is_audited(): void
    {
        $hr = $this->person('hr');
        $person = $this->person();

        $this->actingAs($hr)->get(route('people.show', $person));
        $this->assertTrue(AuditLog::where(['action' => 'viewed_profile', 'subject_id' => $person->id, 'user_id' => $hr->id])->exists());
    }
}
