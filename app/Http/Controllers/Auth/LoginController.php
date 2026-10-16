<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login', [
            'devUsers' => self::devLoginEnabled()
                ? User::active()->with('department')->orderBy('role')->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Audit::log('logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Demo-only sign-in as any dummy user. Hard-disabled outside APP_ENV=local. */
    public function devLogin(Request $request): RedirectResponse
    {
        abort_unless(self::devLoginEnabled(), 404);

        $user = User::active()->findOrFail($request->integer('user_id'));
        Auth::login($user);
        $request->session()->regenerate();
        Audit::log('login', $user, ['provider' => 'dev']);

        return redirect()->route('dashboard');
    }

    public static function devLoginEnabled(): bool
    {
        return app()->environment('local') && (bool) config('people.dev_login');
    }
}
