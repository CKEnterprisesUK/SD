@props([
    'size' => 'md',                                  // sm | md | lg
    'href' => 'https://ckenterprises.co.uk/sitedesk', // set to null to render without a link
])

@php
    $sizes = [
        'sm' => ['site' => '18px', 'company' => '16px', 'by' => '10px'],
        'md' => ['site' => '24px', 'company' => '21px', 'by' => '11px'],
        'lg' => ['site' => '34px', 'company' => '30px', 'by' => '13px'],
    ];

    $s = $sizes[$size] ?? $sizes['md'];

    $tag = $href ? 'a' : 'span';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" target="_blank" rel="noopener noreferrer" @endif
    {{ $attributes->merge(['class' => 'inline-flex items-baseline no-underline']) }}
    style="font-family: 'Cabin Sketch', 'Comic Sans MS', cursive, sans-serif; line-height: 1; white-space: nowrap; text-decoration: none;"
>
    <span style="font-weight: 700; font-size: {{ $s['site'] }}; color: #dc2626;">SiteDesk</span>
    <span style="font-family: Arial, Helvetica, sans-serif; font-weight: 700; font-size: {{ $s['by'] }}; letter-spacing: 0.08em; color: #9ca3af; margin: 0 5px;">BY</span>
    <span style="font-weight: 700; font-size: {{ $s['company'] }}; color: #374151;">CK Enterprises</span>
</{{ $tag }}>
