<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $quote->quote_number }}</title>

    <style>
        @page {
            margin: 32px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.45;
        }

        h1, h2, h3, p {
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }

        .header-table {
            width: 100%;
        }

        .brand-title {
            font-size: 20px;
            font-weight: bold;
        }

        .brand-subtitle {
            font-size: 10px;
            color: #4b5563;
            margin-top: 4px;
        }

        .quote-title {
            font-size: 30px;
            font-weight: bold;
            text-align: right;
        }

        .quote-number {
            font-size: 12px;
            color: #4b5563;
            text-align: right;
            margin-top: 4px;
        }

        .section {
            margin-bottom: 22px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .two-column {
            width: 100%;
        }

        .two-column td {
            vertical-align: top;
            width: 50%;
        }

        .box {
            border: 1px solid #d1d5db;
            padding: 12px;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        .detail-table th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .line-table {
            width: 100%;
            border-collapse: collapse;
        }

        .line-table th,
        .line-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        .line-table th {
            background: #f3f4f6;
            text-align: left;
        }

        .line-table .number {
            text-align: right;
            white-space: nowrap;
        }

        .summary-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        .summary-table th {
            text-align: left;
            background: #f3f4f6;
        }

        .summary-table td {
            text-align: right;
        }

        .total-row th,
        .total-row td {
            font-size: 14px;
            font-weight: bold;
            background: #e5e7eb;
        }

        .muted {
            color: #4b5563;
        }

        .whitespace-pre-line {
            white-space: pre-line;
        }

        .page-break-safe {
            page-break-inside: avoid;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -10px;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
            font-size: 10px;
            color: #4b5563;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-4 {
            margin-top: 14px;
        }
    </style>
</head>

<body>
    @php
        $companyName = $settings?->company_name
            ?: $settings?->portal_name
            ?: config('app.name', 'SiteDesk');

        $customerContact = $customer?->primaryContact ?: $customer?->contacts?->first();
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-title">
                        {{ $companyName }}
                    </div>

                    @if ($settings?->company_address)
                        <div class="brand-subtitle whitespace-pre-line">
                            {{ $settings->company_address }}
                        </div>
                    @endif
                </td>

                <td>
                    <div class="quote-title">Quote</div>
                    <div class="quote-number">{{ $quote->quote_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section page-break-safe">
        <h1 style="font-size: 24px; margin-bottom: 8px;">
            {{ $quote->title }}
        </h1>

        @if ($quote->summary)
            <p class="muted whitespace-pre-line">
                {{ $quote->summary }}
            </p>
        @endif
    </div>

    <div class="section page-break-safe">
        <table class="two-column">
            <tr>
                <td style="padding-right: 10px;">
                    <div class="box">
                        <div class="section-title">Prepared for</div>

                        <p class="font-bold">{{ $customer?->display_name ?: 'Customer' }}</p>

                        @if ($customerContact?->name)
                            <p class="mt-2">{{ $customerContact->name }}</p>
                        @endif

                        @if ($customerContact?->email)
                            <p>{{ $customerContact->email }}</p>
                        @endif

                        @if ($customerContact?->phone)
                            <p>{{ $customerContact->phone }}</p>
                        @endif

                        @if ($customer?->address)
                            <p class="whitespace-pre-line mt-4">{{ $customer->address }}</p>
                        @endif
                    </div>
                </td>

                <td style="padding-left: 10px;">
                    <div class="box">
                        <div class="section-title">Quote details</div>

                        <table class="detail-table">
                            <tr>
                                <th>Quote number</th>
                                <td>{{ $quote->quote_number }}</td>
                            </tr>

                            <tr>
                                <th>Date</th>
                                <td>{{ now()->format('d M Y') }}</td>
                            </tr>

                            <tr>
                                <th>Valid until</th>
                                <td>{{ $quote->valid_until ? $quote->valid_until->format('d M Y') : 'To be confirmed' }}</td>
                            </tr>

                            <tr>
                                <th>Site address</th>
                                <td class="whitespace-pre-line">{{ $quote->site_address ?: $customer?->address ?: 'To be confirmed' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if ($quote->final_customer_message)
        <div class="section page-break-safe">
            <div class="section-title">Introduction</div>
            <p class="whitespace-pre-line">{{ $quote->final_customer_message }}</p>
        </div>
    @endif

    @if ($quote->final_scope)
        <div class="section page-break-safe">
            <div class="section-title">Scope of works</div>
            <p class="whitespace-pre-line">{{ $quote->final_scope }}</p>
        </div>
    @endif

    <div class="section page-break-safe">
        <div class="section-title">Quote breakdown</div>

        <table class="line-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="number">Quantity</th>
                    <th class="number">Unit</th>
                    <th class="number">Unit price</th>
                    <th class="number">Total</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($quote->lineItems as $lineItem)
                    <tr>
                        <td>
                            {{ $lineItem->description }}

                            @if ($lineItem->is_optional)
                                <br>
                                <span class="muted">Optional item</span>
                            @endif
                        </td>

                        <td class="number">{{ $lineItem->quantity }}</td>
                        <td class="number">{{ $lineItem->unit }}</td>
                        <td class="number">£{{ $lineItem->unit_amount }}</td>
                        <td class="number">£{{ $lineItem->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No line items have been added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <th>Subtotal</th>
                <td>£{{ $quote->subtotal }}</td>
            </tr>

            <tr>
                <th>VAT</th>
                <td>£{{ $quote->vat }}</td>
            </tr>

            <tr class="total-row">
                <th>Total</th>
                <td>£{{ $quote->total }}</td>
            </tr>
        </table>
    </div>

    @if ($quote->final_timeline)
        <div class="section page-break-safe">
            <div class="section-title">Estimated timeline</div>
            <p class="whitespace-pre-line">{{ $quote->final_timeline }}</p>
        </div>
    @endif

    @if ($quote->final_assumptions)
        <div class="section page-break-safe">
            <div class="section-title">Assumptions</div>
            <p class="whitespace-pre-line">{{ $quote->final_assumptions }}</p>
        </div>
    @endif

    @if ($quote->final_exclusions)
        <div class="section page-break-safe">
            <div class="section-title">Exclusions</div>
            <p class="whitespace-pre-line">{{ $quote->final_exclusions }}</p>
        </div>
    @endif

    @if ($quote->final_terms)
        <div class="section page-break-safe">
            <div class="section-title">Terms</div>
            <p class="whitespace-pre-line">{{ $quote->final_terms }}</p>
        </div>
    @endif

    <div class="section page-break-safe">
        <div class="section-title">Next steps</div>

        <p>
            Please review this quote and contact us if you would like to discuss the proposed works,
            make any amendments, or confirm that you would like to proceed.
        </p>
    </div>

    <div class="footer">
        Quote {{ $quote->quote_number }} generated using SiteDesk — A CK Enterprises UK Product.
    </div>
</body>
</html>