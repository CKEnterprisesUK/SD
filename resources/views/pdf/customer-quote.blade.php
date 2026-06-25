@php
    $settings = $portalSettings ?? $settings ?? \App\Models\PortalSetting::current();

    $frontPagePath = $settings->quote_pack_front_page_path ?? null;
    $backPagePath = $settings->quote_pack_back_page_path ?? null;

    $frontPageFullPath = $frontPagePath ? public_path($frontPagePath) : null;
    $backPageFullPath = $backPagePath ? public_path($backPagePath) : null;

    $hasFrontPage = $frontPageFullPath && file_exists($frontPageFullPath);
    $hasBackPage = $backPageFullPath && file_exists($backPageFullPath);

    $selectedPhotos = $quote->files
        ->filter(fn ($file) => (bool) ($file->include_in_quote_pack ?? false) && str_starts_with((string) $file->mime_type, 'image/'))
        ->sortBy(fn ($file) => (int) ($file->quote_pack_sort_order ?? 0))
        ->values();

    $lineItems = $quote->lineItems ?? collect();
@endphp

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quote {{ $quote->quote_number }}</title>

    <style>
        @page {
            margin: 30px 36px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
        }

        h1, h2, h3 {
            margin: 0;
            color: #111827;
        }

        h1 {
            font-size: 28px;
            line-height: 1.2;
        }

        h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        h3 {
            font-size: 13px;
            margin-bottom: 6px;
        }

        p {
            margin: 0 0 8px;
        }

        .page-break {
            page-break-after: always;
        }

        .cover-page {
            page-break-after: always;
        }

        .full-page-image {
            width: 100%;
            height: 100%;
            text-align: center;
        }

        .full-page-image img {
            width: 100%;
            max-height: 1040px;
            object-fit: contain;
        }

        .fallback-cover {
            border: 2px solid #111827;
            padding: 42px;
            min-height: 900px;
            position: relative;
        }

        .fallback-cover .bottom {
            position: absolute;
            bottom: 42px;
            left: 42px;
            right: 42px;
        }

        .muted {
            color: #4b5563;
        }

        .small {
            font-size: 10px;
        }

        .section {
            margin-bottom: 22px;
        }

        .box {
            border: 1px solid #d1d5db;
            padding: 14px;
            margin-bottom: 14px;
        }

        .box-light {
            background: #f9fafb;
        }

        .two-col {
            width: 100%;
            border-collapse: collapse;
        }

        .two-col td {
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }

        .quote-meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .quote-meta td {
            border: 1px solid #d1d5db;
            padding: 10px;
            vertical-align: top;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }

        .items td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        .items .num {
            text-align: right;
            white-space: nowrap;
        }

        .totals {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .totals td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        .totals .label {
            background: #f9fafb;
        }

        .totals .total {
            font-weight: bold;
            font-size: 14px;
        }

        .photo-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .photo-grid td {
            width: 50%;
            padding: 7px;
            vertical-align: top;
        }

        .photo-card {
            border: 1px solid #d1d5db;
            padding: 8px;
            min-height: 220px;
        }

        .photo-card img {
            width: 100%;
            max-height: 210px;
            object-fit: cover;
            display: block;
        }

        .photo-caption {
            margin-top: 6px;
            font-size: 10px;
            color: #374151;
        }

        ul {
            margin-top: 6px;
            padding-left: 18px;
        }

        li {
            margin-bottom: 4px;
        }

        .footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>

<body>
    @if ($hasFrontPage)
        <div class="cover-page full-page-image">
            <img src="{{ $frontPageFullPath }}" alt="Quote pack front page">
        </div>
    @else
        <div class="cover-page fallback-cover">
            <div>
                <p class="small muted">Customer quote pack</p>
                <h1>{{ $quote->title ?: 'Quotation' }}</h1>

                <table class="quote-meta">
                    <tr>
                        <td>
                            <strong>Quote number</strong><br>
                            {{ $quote->quote_number }}
                        </td>
                        <td>
                            <strong>Prepared for</strong><br>
                            {{ $quote->customer?->display_name ?: 'Customer' }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Site address</strong><br>
                            {!! nl2br(e($quote->site_address ?: $quote->customer?->address ?: 'To be confirmed')) !!}
                        </td>
                        <td>
                            <strong>Total including VAT</strong><br>
                            £{{ $quote->total }}
                        </td>
                    </tr>
                </table>
            </div>

            <div class="bottom">
                <p class="muted">
                    {{ $settings->company_name ?? config('app.name') }}
                </p>
            </div>
        </div>
    @endif

    <div class="footer">
        Quote {{ $quote->quote_number }} · {{ $settings->company_name ?? config('app.name') }}
    </div>

    <section class="section">
        <h1>{{ $quote->title ?: 'Quotation' }}</h1>

        <table class="quote-meta">
            <tr>
                <td>
                    <strong>Prepared for</strong><br>
                    {{ $quote->customer?->display_name ?: 'Customer' }}
                </td>
                <td>
                    <strong>Quote number</strong><br>
                    {{ $quote->quote_number }}
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Site address</strong><br>
                    {!! nl2br(e($quote->site_address ?: $quote->customer?->address ?: 'To be confirmed')) !!}
                </td>
                <td>
                    <strong>Quote total</strong><br>
                    £{{ $quote->total }} including VAT
                </td>
            </tr>
        </table>
    </section>

    @if ($quote->final_customer_message)
        <section class="section box box-light">
            {!! nl2br(e($quote->final_customer_message)) !!}
        </section>
    @endif

    <section class="section">
        <h2>Customer requirements</h2>

        @if ($quote->final_site_visit_summary)
            <div class="box">
                <h3>Site visit summary</h3>
                {!! nl2br(e($quote->final_site_visit_summary)) !!}
            </div>
        @endif

        @if ($quote->final_existing_setup || $quote->final_measurements_summary)
            <table class="two-col">
                <tr>
                    <td>
                        <div class="box">
                            <h3>Current setup</h3>
                            {!! nl2br(e($quote->final_existing_setup ?: 'To be confirmed.')) !!}
                        </div>
                    </td>

                    <td>
                        <div class="box">
                            <h3>Measurements</h3>
                            {!! nl2br(e($quote->final_measurements_summary ?: 'To be confirmed.')) !!}
                        </div>
                    </td>
                </tr>
            </table>
        @endif

        @if ($quote->final_customer_requirements)
            <div class="box">
                <h3>Customer requirements</h3>
                {!! nl2br(e($quote->final_customer_requirements)) !!}
            </div>
        @endif

        @if ($quote->final_preferences_assumptions)
            <div class="box">
                <h3>Customer preferences and assumptions</h3>
                {!! nl2br(e($quote->final_preferences_assumptions)) !!}
            </div>
        @endif
    </section>

    @if ($quote->final_scope)
        <section class="section">
            <h2>Proposed scope of works</h2>
            <div class="box">
                {!! nl2br(e($quote->final_scope)) !!}
            </div>
        </section>
    @endif

    @if ($quote->final_timeline || $quote->final_assumptions || $quote->final_exclusions)
        <section class="section">
            <h2>Project notes</h2>

            <table class="two-col">
                <tr>
                    <td>
                        <div class="box">
                            <h3>Timeline</h3>
                            {!! nl2br(e($quote->final_timeline ?: 'To be confirmed.')) !!}
                        </div>
                    </td>

                    <td>
                        <div class="box">
                            <h3>Assumptions</h3>
                            {!! nl2br(e($quote->final_assumptions ?: 'Standard assumptions apply unless otherwise stated.')) !!}
                        </div>
                    </td>
                </tr>
            </table>

            @if ($quote->final_exclusions)
                <div class="box">
                    <h3>Exclusions</h3>
                    {!! nl2br(e($quote->final_exclusions)) !!}
                </div>
            @endif
        </section>
    @endif

    <div class="page-break"></div>

    <section class="section">
        <h2>Quote breakdown</h2>

        <table class="items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="num">Qty</th>
                    <th>Unit</th>
                    <th class="num">Unit amount</th>
                    <th class="num">Total</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($lineItems as $lineItem)
                    <tr>
                        <td>
                            {{ $lineItem->description }}

                            @if ($lineItem->is_optional)
                                <br><span class="small muted">Optional item</span>
                            @endif
                        </td>
                        <td class="num">{{ $lineItem->quantity }}</td>
                        <td>{{ $lineItem->unit }}</td>
                        <td class="num">£{{ $lineItem->unit_amount }}</td>
                        <td class="num">£{{ $lineItem->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No line items have been added.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="label">Subtotal</td>
                <td class="num">£{{ $quote->subtotal }}</td>
            </tr>
            <tr>
                <td class="label">VAT</td>
                <td class="num">£{{ $quote->vat }}</td>
            </tr>
            <tr>
                <td class="label total">Total</td>
                <td class="num total">£{{ $quote->total }}</td>
            </tr>
        </table>
    </section>

    @if ($selectedPhotos->isNotEmpty())
        <div class="page-break"></div>

        <section class="section">
            <h2>Survey photos</h2>

            @foreach ($selectedPhotos->chunk(2) as $photoRow)
                <table class="photo-grid">
                    <tr>
                        @foreach ($photoRow as $photo)
                            @php
                                $photoPath = public_path($photo->path);
                            @endphp

                            <td>
                                <div class="photo-card">
                                    @if ($photo->path && file_exists($photoPath))
                                        <img src="{{ $photoPath }}" alt="{{ $photo->original_name }}">
                                    @endif

                                    <div class="photo-caption">
                                        {{ $photo->quote_pack_caption_display ?: $photo->original_name }}
                                    </div>
                                </div>
                            </td>
                        @endforeach

                        @if ($photoRow->count() === 1)
                            <td></td>
                        @endif
                    </tr>
                </table>
            @endforeach
        </section>
    @endif

    @if ($quote->final_terms)
        <div class="page-break"></div>

        <section class="section">
            <h2>Terms and next steps</h2>

            <div class="box">
                {!! nl2br(e($quote->final_terms)) !!}
            </div>
        </section>
    @endif

    @if ($hasBackPage)
        <div class="page-break"></div>

        <div class="full-page-image">
            <img src="{{ $backPageFullPath }}" alt="Quote pack back page">
        </div>
    @endif
</body>
</html>