@props([
    'heading',          // required heading, e.g. "This folder is empty"
    'message' => null,  // optional supporting sentence
])

<div {{ $attributes->merge(['class' => 'px-4 py-16 text-center']) }}>
    <div class="mx-auto text-gray-300" aria-hidden="true">
        @if (isset($icon))
            {{ $icon }} {{-- optional icon slot (expected: a decorative SVG) --}}
        @else
            {{-- default document/folder glyph, matching the existing portal empty states --}}
            <svg width="48" height="48" class="mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
            </svg>
        @endif
    </div>

    <p class="mt-3 text-sm font-medium text-gray-700">{{ $heading }}</p>

    @if ($message)
        <p class="mt-1 text-sm text-gray-500">{{ $message }}</p>
    @endif
</div>
