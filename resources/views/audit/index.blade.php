<x-layouts.app :title="__('Audit log')">
    <div class="flex items-end justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('Audit log') }}</h1>
            <p class="muted">{{ __('Every sign-in, view, change, share, acknowledgement and export. Entries cannot be edited or deleted.') }}</p>
        </div>
        <a href="{{ route('audit.export', request()->query()) }}" class="btn-secondary">{{ __('Export CSV') }}</a>
    </div>
    <form class="card p-4 mb-4 grid gap-3 md:grid-cols-5">
        <select name="action" class="input"><option value="">{{ __('All actions') }}</option>@foreach ($actions as $a)<option @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select>
        <input name="user" value="{{ request('user') }}" placeholder="{{ __('User email') }}" class="input">
        <input type="date" name="from" value="{{ request('from') }}" class="input">
        <input type="date" name="to" value="{{ request('to') }}" class="input">
        <button class="btn-secondary justify-center">{{ __('Filter') }}</button>
    </form>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-stone-500">
                <tr><th class="px-4 py-2 font-medium">{{ __('When') }}</th><th class="px-4 py-2 font-medium">{{ __('Who') }}</th><th class="px-4 py-2 font-medium">{{ __('Action') }}</th><th class="px-4 py-2 font-medium">{{ __('Record') }}</th><th class="px-4 py-2 font-medium">{{ __('Details') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($logs as $log)
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap text-stone-600">{{ $log->created_at->timezone(config('people.timezone'))->format('j M Y H:i:s') }}</td>
                        <td class="px-4 py-2">{{ $log->user?->name ?? '—' }}</td>
                        <td class="px-4 py-2"><code class="text-xs">{{ $log->action }}</code></td>
                        <td class="px-4 py-2 text-stone-600">{{ $log->subjectLabel() }}</td>
                        <td class="px-4 py-2 text-xs text-stone-500">{{ $log->meta ? json_encode($log->meta) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.app>
