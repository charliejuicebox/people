@props(['summary'])
<div class="flex h-2 w-full overflow-hidden rounded-full bg-stone-100" title="{{ $summary['acknowledged'] }}/{{ $summary['total'] }}">
    @if ($summary['total'])
        <div class="bg-green-500" style="width: {{ $summary['acknowledged'] / $summary['total'] * 100 }}%"></div>
        <div class="bg-blue-400" style="width: {{ $summary['shared'] / $summary['total'] * 100 }}%"></div>
        <div class="bg-amber-300" style="width: {{ $summary['draft'] / $summary['total'] * 100 }}%"></div>
    @endif
</div>
