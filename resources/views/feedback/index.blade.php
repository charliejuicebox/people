<x-layouts.app :title="__('Feedback')">
    <h1 class="text-2xl font-semibold mb-6">{{ __('Feedback') }}</h1>
    <div class="grid gap-6 md:grid-cols-3">
        <form method="POST" action="{{ route('feedback.store') }}" class="card p-5 space-y-4 md:col-span-1 self-start">
            @csrf
            <h2 class="font-medium">{{ __('Give feedback') }}</h2>
            <p class="muted">{{ __('Feedback is named. Their team lead, HR and directors can also see it.') }}</p>
            <div>
                <label class="label">{{ __('To') }}</label>
                <select name="recipient_id" class="input" required>
                    <option value="">{{ __('Choose a person') }}</option>
                    @foreach ($people as $p)<option value="{{ $p->id }}" @selected(old('recipient_id') == $p->id)>{{ $p->name }} ({{ $p->entity }})</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('Value (optional)') }}</label>
                <select name="value_tag" class="input">
                    <option value="">—</option>
                    @foreach ($values as $key => $names)<option value="{{ $key }}" @selected(old('value_tag') === $key)>{{ $names[app()->getLocale()] }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('Feedback') }}</label>
                <textarea name="body" rows="5" class="input" required minlength="10" placeholder="{{ __('Be specific: what happened, the impact, and what to keep doing or change.') }}">{{ old('body') }}</textarea>
            </div>
            <button class="btn-primary w-full justify-center">{{ __('Send feedback') }}</button>
        </form>

        <div class="md:col-span-2 space-y-6">
            @foreach ([__('Received') => [$received, 'author'], __('Given') => [$given, 'recipient']] as $heading => [$items, $who])
                <div class="card p-5">
                    <h2 class="font-medium mb-3">{{ $heading }}</h2>
                    @forelse ($items as $f)
                        <div class="py-3 border-b border-stone-100 last:border-0 text-sm">
                            <p class="muted">{{ $who === 'author' ? __('From') : __('To') }} {{ $f->{$who}?->name }} · {{ $f->created_at->format('j M Y') }} <x-value-badge :value="$f->value_tag" /></p>
                            <p class="mt-1 whitespace-pre-line">{{ $f->body }}</p>
                        </div>
                    @empty
                        <p class="muted">{{ __('Nothing yet.') }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
