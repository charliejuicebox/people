@props(['summary'])
<div {{ $attributes->merge(['class' => 'flex h-1.5 w-full overflow-hidden rounded-full bg-stone-100']) }} title="{{ $summary['acknowledged'] }}/{{ $summary['total'] }}">
    @if ($summary['total'])
        <div class="bg-black" style="width: {{ $summary['acknowledged'] / $summary['total'] * 100 }}%"></div>
        <div class="bg-olive-500" style="width: {{ $summary['shared'] / $summary['total'] * 100 }}%"></div>
        <div class="bg-stone-300" style="width: {{ $summary['draft'] / $summary['total'] * 100 }}%"></div>
    @endif
</div>
