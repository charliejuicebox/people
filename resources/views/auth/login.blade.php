<x-layouts.app :title="__('Sign in')">
    <div class="grid gap-10 md:grid-cols-2 md:pt-10">
        <div class="card-dark p-8 md:p-10 flex flex-col justify-between min-h-80">
            <x-wordmark class="text-3xl" />
            <div>
                <p class="text-stone-400 text-sm">{{ __('Juicebox People') }}</p>
                <h1 class="text-4xl md:text-5xl font-medium leading-[1.05] mt-2">{{ __('Quarterly reviews, feedback and praise for the Juicebox group.') }}</h1>
            </div>
        </div>

        <div class="space-y-6 self-center">
            <div class="space-y-3">
                <p class="eyebrow">{{ __('Sign in') }}</p>
                <a href="{{ route('auth.redirect', 'google') }}" class="btn-secondary w-full justify-center py-3">
                    {{ __('Sign in with Google') }} <span class="muted">(AU)</span>
                </a>
                <a href="{{ route('auth.redirect', 'microsoft') }}" class="btn-secondary w-full justify-center py-3">
                    {{ __('Sign in with Microsoft') }} <span class="muted">(ID)</span>
                </a>
                @error('login')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            @if ($devUsers->isNotEmpty())
                <div class="card p-5 border-dashed border-olive-500 bg-olive-50">
                    <p class="text-sm font-medium">{{ __('Demo sign-in (local only, dummy data)') }}</p>
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
    </div>
</x-layouts.app>
