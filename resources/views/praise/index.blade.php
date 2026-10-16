<x-layouts.app :title="__('Praise')">
    <h1 class="text-2xl font-semibold mb-6">{{ __('Praise') }}</h1>
    <div class="grid gap-6 md:grid-cols-3">
        <form method="POST" action="{{ route('praise.store') }}" class="card p-5 space-y-4 self-start">
            @csrf
            <h2 class="font-medium">{{ __('Give praise') }}</h2>
            <p class="muted">{{ __('Praise is public and posts to :channel on Slack.', ['channel' => $channel]) }}</p>
            <div>
                <label class="label">{{ __('To') }}</label>
                <select name="recipient_id" class="input" required>
                    <option value="">{{ __('Choose a person') }}</option>
                    @foreach ($people as $p)<option value="{{ $p->id }}" @selected(old('recipient_id') == $p->id)>{{ $p->name }} ({{ $p->entity }})</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('Which value did they show?') }}</label>
                <div class="space-y-2">
                    @foreach ($values as $key => $names)
                        <label class="flex items-start gap-2 text-sm rounded-lg border border-stone-200 p-2 has-[:checked]:border-juice-500 has-[:checked]:bg-juice-50">
                            <input type="radio" name="value_tag" value="{{ $key }}" required @checked(old('value_tag') === $key) class="mt-1">
                            <span><strong>{{ $names['en'] }}</strong> <span class="muted">/ {{ $names['id'] }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="label">{{ __('Message') }}</label>
                <textarea name="message" rows="4" class="input" required minlength="10">{{ old('message') }}</textarea>
            </div>
            <button class="btn-primary w-full justify-center">{{ __('Send praise') }}</button>
        </form>

        <div class="md:col-span-2 space-y-3">
            @forelse ($praise as $p)
                <div class="card p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm"><strong>{{ $p->recipient->name }}</strong> <span class="badge bg-stone-100 text-stone-600">{{ $p->recipient->entity }}</span> <span class="muted">{{ __('from') }} {{ $p->author?->name }}</span></p>
                        <x-value-badge :value="$p->value_tag" />
                    </div>
                    <p class="mt-2 text-sm">{{ $p->message }}</p>
                    <p class="mt-2 text-xs text-stone-400">{{ $p->created_at->diffForHumans() }}
                        @if ($p->slack_posted_at) · {{ __('posted to Slack') }} @elseif ($p->slack_error) · <span title="{{ $p->slack_error }}">{{ __('not posted to Slack') }}</span> @endif
                    </p>
                </div>
            @empty
                <div class="card p-6 muted">{{ __('No praise yet. Be the first!') }}</div>
            @endforelse
            {{ $praise->links() }}
        </div>
    </div>
</x-layouts.app>
