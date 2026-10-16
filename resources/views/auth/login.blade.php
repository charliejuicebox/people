<x-layouts.app :title="__('Sign in')">
    <div class="mx-auto max-w-md pt-12">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-semibold tracking-tight"><span class="text-juice-600">JB</span> People</h1>
            <p class="muted mt-2">{{ __('Quarterly reviews, feedback and praise for the Juicebox group.') }}</p>
        </div>

        <div class="card p-6 space-y-3">
            <a href="{{ route('auth.redirect', 'google') }}" class="btn-secondary w-full justify-center">
                {{ __('Sign in with Google') }} <span class="muted">(AU)</span>
            </a>
            <a href="{{ route('auth.redirect', 'microsoft') }}" class="btn-secondary w-full justify-center">
                {{ __('Sign in with Microsoft') }} <span class="muted">(ID)</span>
            </a>
            @error('login')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        @if ($devUsers->isNotEmpty())
            <div class="card p-6 mt-6 border-dashed border-amber-300 bg-amber-50/50">
                <p class="text-sm font-medium text-amber-900">{{ __('Demo sign-in (local only, dummy data)') }}</p>
                <form method="POST" action="{{ route('dev-login') }}" class="mt-3 flex gap-2">
                    @csrf
                    <select name="user_id" class="input">
                        @foreach ($devUsers->groupBy('role') as $role => $users)
                            <optgroup label="{{ str($role)->replace('_', ' ')->title() }}">
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} · {{ $u->entity }} · {{ $u->department?->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <button class="btn-primary">{{ __('Go') }}</button>
                </form>
            </div>
        @endif
    </div>
</x-layouts.app>
