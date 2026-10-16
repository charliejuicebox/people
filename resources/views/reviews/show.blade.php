@php
    $asLead = $user->can('updateAsLead', $review);
    $asEmployee = $user->can('updateAsEmployee', $review);
    $isEmployee = $user->id === $review->employee_id;
    $field = fn ($name) => old($name, $review->{$name});
@endphp
<x-layouts.app :title="__(':name, :quarter review', ['name' => $review->employee->name, 'quarter' => $review->period()->label()])">
    <div class="flex items-start justify-between mb-6">
        <div>
            <p class="muted"><a href="{{ route('people.show', $review->employee) }}" class="hover:text-juice-700">{{ $review->employee->name }}</a> · {{ $review->employee->department?->name }} · {{ $review->employee->entity }}</p>
            <h1 class="text-2xl font-semibold">{{ __('Quarterly review') }} / {{ __('Tinjauan Kuartalan') }} · {{ $review->period()->label() }}</h1>
            <p class="muted">{{ __('Team lead') }}: {{ $review->reviewer?->name ?? '—' }}</p>
        </div>
        <x-status-badge :review="$review" class="text-sm" />
    </div>

    @if ($previous && $asLead)
        <details class="card p-4 mb-6 text-sm">
            <summary class="cursor-pointer font-medium">{{ __('Last quarter (:quarter): expectations set and concerns raised', ['quarter' => $previous->period()->label()]) }}</summary>
            <div class="mt-3 grid gap-4 md:grid-cols-2">
                <div><p class="label">{{ __('Expectations for this quarter') }}</p><p class="whitespace-pre-line text-stone-700">{{ $previous->expectations_next_quarter ?: '—' }}</p></div>
                <div><p class="label">{{ __('Concerns') }}</p><p class="whitespace-pre-line text-stone-700">{{ $previous->concerns ?: '—' }}</p></div>
            </div>
        </details>
    @endif

    <form method="POST" action="{{ route('reviews.update', $review) }}" id="review-form">
        @csrf @method('PUT')
        <fieldset @disabled(! $asLead) class="space-y-6">
            <div class="card p-6">
                <h2 class="font-medium mb-1">{{ __('Our values') }} / Nilai-nilai kami</h2>
                <p class="muted mb-4">{{ __('How did :name show each value this quarter? Use specific examples.', ['name' => $review->employee->firstName()]) }}</p>
                <div class="space-y-5">
                    @foreach ($values as $key => $names)
                        <div class="grid gap-3 md:grid-cols-4">
                            <div>
                                <p class="font-medium text-sm">{{ $names['en'] }}</p>
                                <p class="muted text-xs">{{ $names['id'] }}</p>
                                <p class="text-xs text-stone-500 mt-1">{{ \App\Support\Values::DESCRIPTIONS[$key] }}</p>
                            </div>
                            <div>
                                <select name="{{ $key }}_rating" class="input">
                                    <option value="">{{ __('Rating') }}</option>
                                    @foreach ($ratings as $rk => $rl)
                                        <option value="{{ $rk }}" @selected($field("{$key}_rating") === $rk)>{{ $rl[app()->getLocale()] ?? $rl['en'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <textarea name="{{ $key }}_comment" rows="3" class="input md:col-span-2" placeholder="{{ __('Examples') }}">{{ $field("{$key}_comment") }}</textarea>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card p-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="label">{{ __('What went well') }} / Apa yang berjalan baik</label>
                    <textarea name="went_well" rows="5" class="input">{{ $field('went_well') }}</textarea>
                </div>
                <div>
                    <label class="label">{{ __('Concerns') }} / Hal yang perlu diperhatikan</label>
                    <textarea name="concerns" rows="5" class="input" placeholder="{{ __('Anything not meeting expectations. Be clear and specific so nothing is a surprise later.') }}">{{ $field('concerns') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="label">{{ __('Clear expectations for next quarter') }} / Ekspektasi untuk kuartal berikutnya <span class="text-red-600">*</span></label>
                    <textarea name="expectations_next_quarter" rows="4" class="input">{{ $field('expectations_next_quarter') }}</textarea>
                </div>
                <div>
                    <label class="label">{{ __('Flexibility / WFH check (optional)') }}</label>
                    <textarea name="flexibility_check" rows="3" class="input" placeholder="{{ __('Is the current flexible or WFH arrangement working? Any change agreed?') }}">{{ $field('flexibility_check') }}</textarea>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="label">{{ __('Overall rating') }} / Penilaian keseluruhan <span class="text-red-600">*</span></label>
                        <select name="overall_rating" class="input">
                            <option value="">—</option>
                            @foreach ($ratings as $rk => $rl)
                                <option value="{{ $rk }}" @selected($field('overall_rating') === $rk)>{{ $rl['en'] }} / {{ $rl['id'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('Discussed together on') }}</label>
                        <input type="date" name="discussed_on" value="{{ old('discussed_on', $review->discussed_on?->format('Y-m-d')) }}" class="input">
                    </div>
                </div>
            </div>
        </fieldset>

        @if (! $review->isDraft() || $isEmployee)
            <fieldset @disabled(! $asEmployee) class="card p-6 mt-6 space-y-5">
                <h2 class="font-medium">{{ __(':name\'s reflection', ['name' => $review->employee->firstName()]) }} / Refleksi karyawan</h2>
                <div>
                    <label class="label">{{ __('Self-reflection on the quarter') }}</label>
                    <textarea name="self_reflection" rows="4" class="input">{{ $field('self_reflection') }}</textarea>
                </div>
                <div>
                    <label class="label">{{ __('Comments on this review') }}</label>
                    <textarea name="employee_comment" rows="3" class="input">{{ $field('employee_comment') }}</textarea>
                </div>
            </fieldset>
        @endif

        @if ($asLead || $asEmployee)
            <div class="mt-4 flex justify-end"><button class="btn-secondary">{{ __('Save') }}</button></div>
        @endif
    </form>

    @can('share', $review)
        <form method="POST" action="{{ route('reviews.share', $review) }}" class="card p-5 mt-6 flex items-center justify-between gap-4"
              onsubmit="return document.getElementById('review-form').checkValidity()">
            @csrf
            <div class="text-sm">
                <p class="font-medium">{{ __('Ready to share with :name?', ['name' => $review->employee->firstName()]) }}</p>
                <p class="muted">{{ __('Save your changes first. Once shared, they can read it, add their reflection and acknowledge it.') }}</p>
                @if ($review->needsNoSurprisesConfirmation())
                    <label class="mt-3 flex items-start gap-2 text-amber-900 bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <input type="checkbox" name="confirm_expectation_communicated" value="1" class="mt-0.5">
                        <span>{{ __('This is a below-expectations rating and last quarter\'s review raised no concern. I confirm I have clearly told :name what was expected and where they fell short. HR will be notified.', ['name' => $review->employee->firstName()]) }}</span>
                    </label>
                @endif
            </div>
            <button class="btn-primary shrink-0">{{ __('Share review') }}</button>
        </form>
    @endcan

    @can('acknowledge', $review)
        <form method="POST" action="{{ route('reviews.acknowledge', $review) }}" class="card p-5 mt-6 border-blue-200 bg-blue-50">
            @csrf
            <p class="font-medium text-sm">{{ __('Acknowledge this review') }} / Konfirmasi tinjauan ini</p>
            <p class="muted mb-3">{{ __('Acknowledging means you have read and discussed this review. It does not mean you agree with everything. Add a comment if you want to.') }}</p>
            <textarea name="employee_comment" rows="2" class="input mb-3" placeholder="{{ __('Optional comment') }}">{{ $review->employee_comment }}</textarea>
            <button class="btn-primary">{{ __('I have read and discussed this review') }}</button>
        </form>
    @endcan

    <div class="mt-6 muted text-xs space-x-3">
        @if ($review->shared_at)<span>{{ __('Shared') }} {{ $review->shared_at->timezone(config('people.timezone'))->format('j M Y H:i') }}</span>@endif
        @if ($review->acknowledged_at)<span>{{ __('Acknowledged') }} {{ $review->acknowledged_at->timezone(config('people.timezone'))->format('j M Y H:i') }}</span>@endif
        @if ($review->flagged_to_hr_at)<span class="text-amber-700">{{ __('Flagged to HR') }} {{ $review->flagged_to_hr_at->timezone(config('people.timezone'))->format('j M Y') }}</span>@endif
    </div>
</x-layouts.app>
