<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}JB People</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    @auth
        <header class="bg-white border-b border-stone-200">
            <div class="mx-auto max-w-6xl px-4 flex h-14 items-center gap-6">
                <a href="{{ route('dashboard') }}" class="font-semibold tracking-tight">
                    <span class="text-juice-600">JB</span> People
                </a>
                <nav class="flex gap-1 text-sm">
                    @php($nav = [
                        'dashboard' => __('Dashboard'),
                        'people.index' => auth()->user()->seesEveryone() ? __('People') : __('My team'),
                        'feedback.index' => __('Feedback'),
                        'praise.index' => __('Praise'),
                    ])
                    @foreach ($nav as $routeName => $label)
                        @if ($routeName !== 'people.index' || auth()->user()->seesEveryone() || auth()->user()->hasReports())
                            <a href="{{ route($routeName) }}"
                               class="rounded-md px-3 py-1.5 {{ request()->routeIs(str_replace('.index', '.*', $routeName)) ? 'bg-juice-50 text-juice-700 font-medium' : 'text-stone-600 hover:text-stone-900' }}">{{ $label }}</a>
                        @endif
                    @endforeach
                    @if (in_array(auth()->user()->role, ['hr', 'director', 'admin']))
                        <a href="{{ route('audit.index') }}" class="rounded-md px-3 py-1.5 {{ request()->routeIs('audit.*') ? 'bg-juice-50 text-juice-700 font-medium' : 'text-stone-600 hover:text-stone-900' }}">{{ __('Audit log') }}</a>
                    @endif
                </nav>
                <div class="ml-auto flex items-center gap-4 text-sm">
                    <div class="flex rounded-md border border-stone-200 overflow-hidden text-xs">
                        @foreach (['en' => 'EN', 'id' => 'ID'] as $code => $label)
                            <a href="{{ route('locale', $code) }}" class="px-2 py-1 {{ app()->getLocale() === $code ? 'bg-stone-800 text-white' : 'text-stone-600' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <span class="text-stone-600">{{ auth()->user()->name }} <span class="badge bg-stone-100 text-stone-600">{{ auth()->user()->entity }}</span></span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-stone-500 hover:text-stone-900">{{ __('Sign out') }}</button></form>
                </div>
            </div>
        </header>
    @endauth

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
