@props(['review' => null])
@php
    [$label, $class] = match ($review?->status) {
        'acknowledged' => [__('Acknowledged'), 'bg-green-100 text-green-800'],
        'shared' => [__('Waiting for acknowledgement'), 'bg-blue-100 text-blue-800'],
        'draft' => [__('Draft'), 'bg-amber-100 text-amber-800'],
        default => [__('Not started'), 'bg-stone-100 text-stone-600'],
    };
@endphp
<span {{ $attributes->merge(['class' => "badge $class"]) }}>{{ $label }}</span>
