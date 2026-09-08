{{-- resources/views/components/document-icon.blade.php --}}
{{--
    File-type icon with generic fallback (NEW).

    Classifies a document into one of: pdf / image / doc / sheet / archive / generic
    using both the filename extension and the mime type, mirroring the intent of the
    existing $fileKind / $isViewable closures in portal/folder.blade.php.

    The icon is decorative: it is marked aria-hidden="true" and is never the sole
    conveyor of the file type (the type is also shown as text in the folder view).
    'generic' is the fallback glyph for unknown / empty / missing type.
--}}
@props(['name' => '', 'mime' => null])

@php
    $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION) ?: '');
    $mime = (string) $mime;

    $kind = match (true) {
        $mime === 'application/pdf' || $ext === 'pdf'
            => 'pdf',
        str_starts_with($mime, 'image/') || in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], true)
            => 'image',
        in_array($ext, ['doc', 'docx'], true) || str_contains($mime, 'word')
            => 'doc',
        in_array($ext, ['xls', 'xlsx', 'csv'], true) || str_contains($mime, 'sheet') || str_contains($mime, 'excel')
            => 'sheet',
        in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'], true) || str_contains($mime, 'zip') || str_contains($mime, 'compressed')
            => 'archive',
        default
            => 'generic',
    };

    $svgAttributes = $attributes->merge([
        'class' => 'w-5 h-5',
    ]);
@endphp

@switch($kind)
    @case('pdf')
        {{-- PDF document --}}
        <svg {{ $svgAttributes }} aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v5a1 1 0 0 0 1 1h5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h9l6 6v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 13h2.5a1.5 1.5 0 0 1 0 3H8v-3Zm0 0v5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 13v5h1.5m-1.5-2.5H16" />
        </svg>
        @break

    @case('image')
        {{-- Image file --}}
        <svg {{ $svgAttributes }} aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 10a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 16 5-4 4 3 3-2 6 5" />
        </svg>
        @break

    @case('doc')
        {{-- Word / text document --}}
        <svg {{ $svgAttributes }} aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v5a1 1 0 0 0 1 1h5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h9l6 6v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8M8 15h8M8 18h5" />
        </svg>
        @break

    @case('sheet')
        {{-- Spreadsheet --}}
        <svg {{ $svgAttributes }} aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v5a1 1 0 0 0 1 1h5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h9l6 6v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8M8 15h8M12 12v6M8 18h8" />
        </svg>
        @break

    @case('archive')
        {{-- Archive / compressed --}}
        <svg {{ $svgAttributes }} aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h14a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 3v2m2 1v2m-2 1v2m2 1v2" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 15h3l.5 3a2 2 0 0 1-4 0l.5-3Z" />
        </svg>
        @break

    @default
        {{-- generic: fallback document glyph for unknown / empty / missing type --}}
        <svg {{ $svgAttributes }} aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v5a1 1 0 0 0 1 1h5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h9l6 6v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" />
        </svg>
@endswitch
