@props(['review' => null])
@php
    [$label, $class] = match ($review?->status) {
        'acknowledged' => [__('Acknowledged'), 'bg-black text-white'],
        'shared' => [__('Waiting for acknowledgement'), 'bg-olive-100 text-olive-900'],
        'draft' => [__('Draft'), 'bg-white text-stone-700 ring-1 ring-inset ring-stone-300'],
        default => [__('Not started'), 'bg-stone-100 text-stone-600'],
    };
@endphp
<span {{ $attributes->merge(['class' => "badge $class"]) }}>{{ $label }}</span>
