<x-layouts.app :title="$person->name">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl md:text-4xl font-medium">{{ $person->name }}</h1>
            <p class="muted">{{ $person->job_title }} · {{ $person->department?->name }} · {{ $person->entityName() }}</p>
            <p class="muted">{{ __('Team lead') }}: {{ $person->teamLead?->name ?? '—' }}
                @if ($accessGrants->isNotEmpty()) · {{ __('Also visible to') }} {{ $accessGrants->pluck('user.name')->join(', ') }} @endif
                @if ($person->contract_end_date) · {{ $person->employment_type }}, {{ __('contract ends') }} {{ $person->contract_end_date->format('j M Y') }} @endif
            </p>
        </div>
        @if ($canReview)
            <form method="POST" action="{{ route('reviews.start', $person) }}">@csrf
                <button class="btn-primary">{{ __('Open this quarter\'s review') }}</button>
            </form>
        @endif
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        <div class="card p-5 md:col-span-1">
            <h2 class="font-medium mb-3">{{ __('Quarterly reviews') }}</h2>
            @forelse ($reviews as $review)
                <a href="{{ route('reviews.show', $review) }}" class="flex items-center justify-between py-2 border-b border-stone-100 last:border-0 text-sm hover:underline">
                    <span>{{ $review->period()->label() }}</span><x-status-badge :review="$review" />
                </a>
            @empty
                <p class="muted">{{ __('No reviews yet.') }}</p>
            @endforelse
        </div>

        <div class="card p-5 md:col-span-2">
            <h2 class="font-medium mb-3">{{ __('Feedback') }}</h2>
            @forelse ($feedback as $f)
                <div class="py-3 border-b border-stone-100 last:border-0 text-sm">
                    <p class="muted">{{ $f->author?->name }} · {{ $f->created_at->format('j M Y') }} <x-value-badge :value="$f->value_tag" /></p>
                    <p class="mt-1 whitespace-pre-line">{{ $f->body }}</p>
                </div>
            @empty
                <p class="muted">{{ __('No feedback yet.') }}</p>
            @endforelse

            <h2 class="font-medium mt-6 mb-3">{{ __('Praise') }}</h2>
            @forelse ($praise as $p)
                <div class="py-3 border-b border-stone-100 last:border-0 text-sm">
                    <p class="muted">{{ $p->author?->name }} · {{ $p->created_at->format('j M Y') }} <x-value-badge :value="$p->value_tag" /></p>
                    <p class="mt-1">{{ $p->message }}</p>
                </div>
            @empty
                <p class="muted">{{ __('No praise yet.') }}</p>
            @endforelse
        </div>
    </div>

    @if (auth()->user()->managesAccess() || $accessGrants->isNotEmpty())
        <div class="card p-5 mt-6">
            <h2 class="font-medium">{{ __('Who can see :name\'s reviews', ['name' => $person->firstName()]) }}</h2>
            <p class="muted mb-4">{{ __('The team lead writes the review. People added here can read :name\'s reviews and feedback but can\'t edit or share them.', ['name' => $person->firstName()]) }}</p>
            <div class="divide-y divide-stone-100 text-sm">
                <div class="flex items-center justify-between py-2">
                    <span><strong>{{ $person->teamLead?->name ?? '—' }}</strong> <span class="muted">· {{ __('Team lead') }}</span></span>
                </div>
                @foreach ($accessGrants as $grant)
                    <div class="flex items-center justify-between py-2">
                        <span><strong>{{ $grant->user->name }}</strong> <span class="badge bg-stone-100 text-stone-600">{{ $grant->user->entity }}</span>
                            @if ($grant->reason)<span class="muted">· {{ $grant->reason }}</span>@endif
                            <span class="muted text-xs">· {{ __('added by :name', ['name' => $grant->grantedBy?->name ?? '—']) }}</span></span>
                        @if (auth()->user()->managesAccess())
                            <form method="POST" action="{{ route('people.access.revoke', [$person, $grant]) }}">@csrf @method('DELETE')
                                <button class="text-stone-500 hover:text-black link">{{ __('Remove') }}</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
            @if (auth()->user()->managesAccess())
                <form method="POST" action="{{ route('people.access.grant', $person) }}" class="mt-4 grid gap-2 md:grid-cols-[1fr_1fr_auto]">
                    @csrf
                    <select name="user_id" class="input" required>
                        <option value="">{{ __('Add a person…') }}</option>
                        @foreach ($grantable as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} · {{ $u->entity }} · {{ $u->job_title }}</option>
                        @endforeach
                    </select>
                    <input name="reason" class="input" maxlength="100" placeholder="{{ __('Why, e.g. AU account lead') }}">
                    <button class="btn-primary justify-center">{{ __('Give access') }}</button>
                </form>
            @endif
        </div>
    @endif
</x-layouts.app>
