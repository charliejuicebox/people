<x-layouts.app :title="__('People')">
    <div class="flex items-end justify-between mb-6">
        <h1 class="text-3xl md:text-4xl font-medium">{{ $user->seesEveryone() ? __('People') : __('My team') }}</h1>
        @if ($user->seesEveryone())
            <form class="flex gap-2">
                <select name="entity" class="input w-auto" onchange="this.form.submit()">
                    <option value="">{{ __('All entities') }}</option>
                    @foreach (\App\Models\User::ENTITIES as $code => $name)
                        <option value="{{ $code }}" @selected(request('entity') === $code)>{{ $code }}</option>
                    @endforeach
                </select>
                <select name="department" class="input w-auto" onchange="this.form.submit()">
                    <option value="">{{ __('All departments') }}</option>
                    @foreach (\App\Models\Department::orderBy('name')->get() as $d)
                        <option value="{{ $d->id }}" @selected((int) request('department') === $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-stone-500">
                <tr>
                    <th class="px-5 py-2 font-medium">{{ __('Name') }}</th>
                    <th class="px-5 py-2 font-medium">{{ __('Role') }}</th>
                    <th class="px-5 py-2 font-medium">{{ __('Department') }}</th>
                    <th class="px-5 py-2 font-medium">{{ __('Team lead') }}</th>
                    <th class="px-5 py-2 font-medium">{{ __('Contract') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($people as $person)
                    <tr>
                        <td class="px-5 py-3"><a href="{{ route('people.show', $person) }}" class="font-medium hover:underline">{{ $person->name }}</a> <span class="badge bg-stone-100 text-stone-600">{{ $person->entity }}</span></td>
                        <td class="px-5 py-3 text-stone-600">{{ $person->job_title }}</td>
                        <td class="px-5 py-3 text-stone-600">{{ $person->department?->name }}</td>
                        <td class="px-5 py-3 text-stone-600">{{ $person->teamLead?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-stone-600">
                            {{ $person->employment_type }}
                            @if ($person->contract_end_date)
                                <span class="muted">· {{ __('ends') }} {{ $person->contract_end_date->format('j M Y') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-6 muted">{{ __('Nobody here yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
