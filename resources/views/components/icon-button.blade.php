@props([
    'label',            // required accessible name, e.g. "Download"
    'as' => 'a',        // 'a' or 'button'
    'href' => null,
])

@php
    $tag = $as === 'button' ? 'button' : 'a';
    // ~44px touch target with a square black/white/gray look and a visible focus ring.
    $base = 'inline-flex items-center justify-center min-w-[44px] min-h-[44px] rounded-none '
          . 'text-gray-500 hover:text-gray-900 '
          . 'focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2';
@endphp

@if ($tag === 'button')
    <button
        type="button"
        aria-label="{{ $label }}"
        {{ $attributes->merge(['class' => $base]) }}
    >
        {{ $slot }} {{-- expected: an SVG with aria-hidden="true" --}}
    </button>
@else
    <a
        href="{{ $href }}"
        aria-label="{{ $label }}"
        {{ $attributes->merge(['class' => $base]) }}
    >
        {{ $slot }} {{-- expected: an SVG with aria-hidden="true" --}}
    </a>
@endif
