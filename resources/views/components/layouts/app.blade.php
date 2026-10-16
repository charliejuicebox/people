<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}Juicebox People</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased flex flex-col">
    @auth
        <header class="bg-black text-white">
            <div class="mx-auto max-w-6xl px-4 flex h-16 items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-baseline gap-2">
                    <x-wordmark class="text-xl" /> <span class="text-stone-400 text-sm">people</span>
                </a>
                <nav class="flex gap-5 text-sm">
                    @php($nav = [
                        'dashboard' => __('Dashboard'),
                        'people.index' => auth()->user()->seesEveryone() ? __('People') : __('My team'),
                        'feedback.index' => __('Feedback'),
                        'praise.index' => __('Praise'),
                    ])
                    @foreach ($nav as $routeName => $label)
                        @if ($routeName !== 'people.index' || auth()->user()->seesEveryone() || auth()->user()->hasPeopleToSee())
                            <a href="{{ route($routeName) }}"
                               class="py-1.5 border-b {{ request()->routeIs(str_replace('.index', '.*', $routeName)) ? 'border-white text-white' : 'border-transparent text-stone-400 hover:text-white' }}">{{ $label }}</a>
                        @endif
                    @endforeach
                    @if (in_array(auth()->user()->role, ['hr', 'director', 'admin']))
                        <a href="{{ route('audit.index') }}" class="py-1.5 border-b {{ request()->routeIs('audit.*') ? 'border-white text-white' : 'border-transparent text-stone-400 hover:text-white' }}">{{ __('Audit log') }}</a>
                    @endif
                </nav>
                <div class="ml-auto flex items-center gap-4 text-sm">
                    <div class="flex rounded-full border border-stone-700 overflow-hidden text-xs">
                        @foreach (['en' => 'EN', 'id' => 'ID'] as $code => $label)
                            <a href="{{ route('locale', $code) }}" class="px-2 py-1 {{ app()->getLocale() === $code ? 'bg-white text-black' : 'text-stone-400 hover:text-white' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <span class="text-stone-300">{{ auth()->user()->name }} <span class="badge bg-stone-800 text-stone-300">{{ auth()->user()->entity }}</span></span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-stone-400 hover:text-white">{{ __('Sign out') }}</button></form>
                </div>
            </div>
        </header>
    @endauth

    <main class="mx-auto w-full max-w-6xl px-4 py-10 flex-1">
        @if (session('status'))
            <div class="mb-6 rounded-lg bg-black px-4 py-3 text-sm text-white">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="mx-auto w-full max-w-6xl px-4 py-6 flex justify-between text-xs text-stone-500">
        <span>{{ __('Brave ideas build winning brands') }}</span>
        <x-wordmark class="text-sm text-stone-900" />
    </footer>
</body>
</html>
