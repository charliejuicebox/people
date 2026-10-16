<x-layouts.app :title="$person->name">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ $person->name }}</h1>
            <p class="muted">{{ $person->job_title }} · {{ $person->department?->name }} · {{ $person->entityName() }}</p>
            <p class="muted">{{ __('Team lead') }}: {{ $person->teamLead?->name ?? '—' }}
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
                <a href="{{ route('reviews.show', $review) }}" class="flex items-center justify-between py-2 border-b border-stone-100 last:border-0 text-sm hover:text-juice-700">
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
</x-layouts.app>
