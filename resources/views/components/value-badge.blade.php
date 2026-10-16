@props(['value'])
@php
    $class = match ($value) {
        'make_it_matter' => 'bg-juice-100 text-juice-700',
        'work_as_one' => 'bg-sky-100 text-sky-800',
        'embrace_change' => 'bg-emerald-100 text-emerald-800',
        default => 'bg-stone-100 text-stone-600',
    };
@endphp
@if ($value)
    <span {{ $attributes->merge(['class' => "badge $class"]) }}>{{ \App\Support\Values::label($value) }}</span>
@endif
