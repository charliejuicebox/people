<x-layouts.app :title="__('Dashboard')">
    <div class="flex items-end justify-between mb-6">
        <div>
            <h1 class="text-4xl md:text-5xl font-medium">{{ __('Hi :name', ['name' => $user->firstName()]) }}</h1>
            <p class="muted mt-2">{{ $quarter->label() }} · {{ trans_choice(':count day left in the quarter|:count days left in the quarter', max($daysLeft, 0)) }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('feedback.index') }}" class="btn-secondary">{{ __('Give feedback') }}</a>
            <a href="{{ route('praise.index') }}" class="btn-primary">{{ __('Give praise') }}</a>
        </div>
    </div>

    @if ($toAcknowledge->isNotEmpty())
        <div class="card-dark p-5 mb-6">
            @foreach ($toAcknowledge as $review)
                <div class="flex items-center justify-between">
                    <p class="text-sm"><strong>{{ __('Your :quarter review is ready.', ['quarter' => $review->period()->label()]) }}</strong> {{ __('Read it, add your comments and acknowledge it.') }}</p>
                    <a href="{{ route('reviews.show', $review) }}" class="btn bg-white text-black hover:bg-olive-100">{{ __('Open review') }}</a>
                </div>
            @endforeach
        </div>
    @endif

    @if ($rows->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-4 mb-6">
            @foreach ([
                ['label' => __('Acknowledged'), 'value' => $summary['acknowledged'], 'class' => 'text-black'],
                ['label' => __('Waiting for acknowledgement'), 'value' => $summary['shared'], 'class' => 'text-olive-700'],
                ['label' => __('Draft'), 'value' => $summary['draft'], 'class' => 'text-stone-600'],
                ['label' => __('Not started'), 'value' => $summary['not_started'], 'class' => 'text-stone-400'],
            ] as $stat)
                <div class="card p-5">
                    <p class="muted">{{ $stat['label'] }}</p>
                    <p class="text-5xl font-medium tracking-tight mt-3 {{ $stat['class'] }}">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </div>

        @if ($user->seesEveryone())
            <div class="grid gap-6 md:grid-cols-2 mb-6">
                @foreach ([__('By department') => $byDepartment, __('By entity') => $byEntity] as $heading => $groups)
                    <div class="card p-5">
                        <h2 class="font-medium mb-4">{{ $heading }}</h2>
                        <div class="space-y-3">
                            @foreach ($groups as $name => $s)
                                <div>
                                    <div class="flex justify-between text-sm mb-1"><span>{{ $name }}</span><span class="muted">{{ $s['acknowledged'] }}/{{ $s['total'] }} · {{ $s['percent'] }}%</span></div>
                                    <x-progress :summary="$s" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="card overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-stone-200 flex justify-between items-center">
                <h2 class="font-medium">{{ $user->seesEveryone() ? __(':quarter reviews, whole group', ['quarter' => $quarter->label()]) : __(':quarter reviews, my team', ['quarter' => $quarter->label()]) }}</h2>
                <x-progress :summary="$summary" class="max-w-40" />
            </div>
            <table class="w-full text-sm">
                <thead class="bg-stone-50 text-left text-stone-500">
                    <tr>
                        <th class="px-5 py-2 font-medium">{{ __('Person') }}</th>
                        <th class="px-5 py-2 font-medium">{{ __('Department') }}</th>
                        <th class="px-5 py-2 font-medium">{{ __('Team lead') }}</th>
                        <th class="px-5 py-2 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('people.show', $row['person']) }}" class="font-medium hover:underline">{{ $row['person']->name }}</a> <span class="badge bg-stone-100 text-stone-600">{{ $row['person']->entity }}</span></td>
                            <td class="px-5 py-3 text-stone-600">{{ $row['person']->department?->name }}</td>
                            <td class="px-5 py-3 text-stone-600">{{ $row['person']->team_lead_id === $user->id ? __('You') : $row['person']->teamLead?->name }}
                                @if (! $user->seesEveryone() && $row['person']->team_lead_id !== $user->id)<span class="badge bg-olive-100 text-olive-900 ml-1">{{ __('Shared with you') }}</span>@endif</td>
                            <td class="px-5 py-3"><x-status-badge :review="$row['review']" /></td>
                            <td class="px-5 py-3 text-right">
                                @if ($row['review'])
                                    <a href="{{ route('reviews.show', $row['review']) }}" class="link">{{ __('Open') }}</a>
                                @elseif ($user->can('create', [\App\Models\QuarterlyReview::class, $row['person']]))
                                    <form method="POST" action="{{ route('reviews.start', $row['person']) }}">@csrf<button class="link">{{ __('Start review') }}</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div class="card p-5">
            <h2 class="font-medium mb-3">{{ __('My reviews') }}</h2>
            @forelse ($myReviews as $review)
                <a href="{{ route('reviews.show', $review) }}" class="flex justify-between py-2 border-b border-stone-100 last:border-0 text-sm hover:underline">
                    <span>{{ $review->period()->label() }}</span><x-status-badge :review="$review" />
                </a>
            @empty
                <p class="muted">{{ __('No reviews shared with you yet.') }}</p>
            @endforelse
        </div>
        <div class="card p-5">
            <h2 class="font-medium mb-3">{{ __('Latest praise') }}</h2>
            @forelse ($recentPraise as $p)
                <div class="py-2 border-b border-stone-100 last:border-0 text-sm">
                    <p><strong>{{ $p->recipient->name }}</strong> <span class="muted">{{ __('from') }} {{ $p->author?->name }}</span> <x-value-badge :value="$p->value_tag" /></p>
                    <p class="text-stone-600 mt-1">{{ str($p->message)->limit(140) }}</p>
                </div>
            @empty
                <p class="muted">{{ __('No praise yet. Be the first!') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
