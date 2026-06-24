<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>

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

        .brand-row {
            width: 100%;
        }

        .brand-title {
            font-size: 24px;
            font-weight: bold;
        }

        .brand-subtitle {
            font-size: 11px;
            color: #4b5563;
            margin-top: 4px;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            text-align: right;
        }

        .muted {
            color: #4b5563;
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
        }

        .detail-table th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .summary-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
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

        .notice {
            border: 1px solid #92400e;
            background: #fffbeb;
            padding: 10px;
            color: #78350f;
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

        .page-break-safe {
            page-break-inside: avoid;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .mt-6 {
            margin-top: 18px;
        }

        .whitespace-pre-line {
            white-space: pre-line;
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="brand-row">
            <tr>
                <td>
                    
                    
                </td>
                <td class="text-right">
                    <div class="invoice-title">Contractor Invoice</div>
                    <div class="muted">{{ $invoice->invoice_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section page-break-safe">
        <table class="two-column">
            <tr>
                <td style="padding-right: 10px;">
                    <div class="box">
                        <div class="section-title">Supplier</div>
                        <p class="font-bold">{{ $invoice->supplier_name }}</p>

                        @if ($invoice->supplier_email)
                            <p>{{ $invoice->supplier_email }}</p>
                        @endif

                        @if ($invoice->supplier_phone)
                            <p>{{ $invoice->supplier_phone }}</p>
                        @endif

                        @if ($invoice->supplier_address)
                            <p class="whitespace-pre-line mt-6">{{ $invoice->supplier_address }}</p>
                        @endif
                    </div>
                </td>

                <td style="padding-left: 10px;">
                    <div class="box">
                        <div class="section-title">Customer</div>
                        <p class="font-bold">{{ $invoice->customer_name ?: '—' }}</p>

                        @if ($invoice->customer_address)
                            <p class="whitespace-pre-line mt-6">{{ $invoice->customer_address }}</p>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section page-break-safe">
        <div class="section-title">Invoice details</div>

        <table class="detail-table">
            <tr>
                <th>Invoice number</th>
                <td>{{ $invoice->invoice_number }}</td>
                <th>Invoice date</th>
                <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
            </tr>
            <tr>
                <th>Week commencing</th>
                <td>{{ $invoice->week_commencing->format('d M Y') }}</td>
                <th>Status</th>
                <td>{{ ucfirst($invoice->status) }}</td>
            </tr>
            <tr>
                <th>Submitted at</th>
                <td>{{ $invoice->submitted_at->format('d M Y H:i') }}</td>
                <th>Submitted by</th>
                <td>{{ $invoice->supplier_name }}</td>
            </tr>
        </table>
    </div>

    <div class="section page-break-safe">
        <div class="section-title">Work submitted</div>

        <table class="detail-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Days claimed</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monday</td>
                    <td>{{ number_format((float) $invoice->monday_days, 2) }}</td>
                </tr>
                <tr>
                    <td>Tuesday</td>
                    <td>{{ number_format((float) $invoice->tuesday_days, 2) }}</td>
                </tr>
                <tr>
                    <td>Wednesday</td>
                    <td>{{ number_format((float) $invoice->wednesday_days, 2) }}</td>
                </tr>
                <tr>
                    <td>Thursday</td>
                    <td>{{ number_format((float) $invoice->thursday_days, 2) }}</td>
                </tr>
                <tr>
                    <td>Friday</td>
                    <td>{{ number_format((float) $invoice->friday_days, 2) }}</td>
                </tr>
                <tr>
                    <td>Saturday</td>
                    <td>{{ number_format((float) $invoice->saturday_days, 2) }}</td>
                </tr>
                <tr>
                    <td>Sunday</td>
                    <td>{{ number_format((float) $invoice->sunday_days, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section page-break-safe">
        <div class="section-title">Charges</div>

        <table class="detail-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Days</th>
                    <th>Day rate</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Contractor work submitted for week commencing
                        {{ $invoice->week_commencing->format('d M Y') }}

                        @if ($invoice->day_rate_overridden)
                            <br>
                            <span class="muted">
                                Day rate manually overridden from default rate of £{{ $invoice->default_day_rate }}.
                            </span>
                        @endif
                    </td>
                    <td>{{ $invoice->days_worked }}</td>
                    <td>£{{ $invoice->actual_day_rate }}</td>
                    <td>£{{ $invoice->subtotal }}</td>
                </tr>
            </tbody>
        </table>

        <table class="summary-table mt-6">
            <tr>
                <th>Subtotal</th>
                <td>£{{ $invoice->subtotal }}</td>
            </tr>
            <tr>
                <th>VAT</th>
                <td>£{{ $invoice->vat }}</td>
            </tr>
            <tr class="total-row">
                <th>Total due</th>
                <td>£{{ $invoice->total }}</td>
            </tr>
        </table>
    </div>

    <div class="section page-break-safe">
        <div class="notice">
            This is not a VAT invoice. VAT has not been charged.
        </div>
    </div>

    @if ($invoice->contractor_notes)
        <div class="section page-break-safe">
            <div class="section-title">Contractor notes</div>
            <p class="whitespace-pre-line">{{ $invoice->contractor_notes }}</p>
        </div>
    @endif

    <div class="section page-break-safe">
        <div class="section-title">Submission confirmation</div>
        <p>{{ $invoice->contractor_confirmation_text }}</p>

        @if ($invoice->submitted_ip)
            <p class="muted mt-6">
                Submission IP: {{ $invoice->submitted_ip }}
            </p>
        @endif
    </div>

    <div class="footer">
        This invoice was submitted by {{ $invoice->supplier_name }} to {{ $invoice->customer_name ?: 'the building firm' }}
        using SiteDesk — A CK Enterprises Product. This is not a VAT invoice. VAT has not been charged.
    </div>
</body>
</html>