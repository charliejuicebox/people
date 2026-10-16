@props(['value'])
@php
    $class = match ($value) {
        'make_it_matter' => 'bg-stone-900 text-white',
        'work_as_one' => 'bg-olive-100 text-olive-900',
        'embrace_change' => 'bg-stone-100 text-stone-800 ring-1 ring-inset ring-stone-300',
        default => 'bg-stone-100 text-stone-600',
    };
@endphp
@if ($value)
    <span {{ $attributes->merge(['class' => "badge $class"]) }}>{{ \App\Support\Values::label($value) }}</span>
@endif
