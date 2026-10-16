<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialIdentity;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Sign in with Google (AU) or Microsoft Entra ID (ID).
 *
 * Only people already on the roster can sign in; accounts are never created from a login.
 * Each sign-in identity is linked to the person's internal ID, so the 2027 move to Microsoft
 * is a matter of linking a new identity, with no data migration.
 */
class SocialLoginController extends Controller
{
    private const PROVIDERS = ['google', 'microsoft'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        $remote = Socialite::driver($provider)->user();
        $email = strtolower((string) $remote->getEmail());

        $identity = SocialIdentity::where('provider', $provider)
            ->where('provider_user_id', $remote->getId())
            ->first();

        $user = $identity?->user ?? User::whereRaw('lower(email) = ?', [$email])->first();

        if (! $user || ! $user->active || $user->left_at) {
            Audit::log('login_denied', null, ['provider' => $provider, 'email' => $email]);

            return redirect()->route('login')->withErrors([
                'login' => __('Your account is not on the JB People roster. Please contact HR.'),
            ]);
        }

        $identity ??= $user->identities()->make([
            'provider' => $provider,
            'provider_user_id' => $remote->getId(),
        ]);
        $identity->fill(['email' => $email, 'last_login_at' => now()])->save();

        Auth::login($user);
        request()->session()->regenerate();
        Audit::log('login', $user, ['provider' => $provider]);

        return redirect()->intended(route('dashboard'));
    }
}
