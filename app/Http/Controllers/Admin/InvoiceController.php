<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContractorInvoiceReadyForPayment;
use App\Mail\ContractorInvoiceReturned;
use App\Models\Contractor;
use App\Models\ContractorInvoice;
use App\Models\PortalSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function index(Request $request)
{
    abort_unless(auth()->user()->isAdmin(), 403);

    $validated = $request->validate([
        'contractor_id' => ['nullable', 'integer', 'exists:contractors,id'],
        'status' => ['nullable', 'string', 'max:50'],
        'invoice_date_from' => ['nullable', 'date'],
        'invoice_date_to' => ['nullable', 'date'],
        'week_commencing_from' => ['nullable', 'date'],
        'week_commencing_to' => ['nullable', 'date'],
    ]);

    /*
    |--------------------------------------------------------------------------
    | Base query
    |--------------------------------------------------------------------------
    |
    | This applies contractor/date filters. The summary cards use the same base
    | filters, but intentionally ignore the selected status filter so you can
    | still see the full state breakdown for the selected contractor/date range.
    |
    */

    $baseQuery = ContractorInvoice::query()
        ->when($validated['contractor_id'] ?? null, fn ($query, $contractorId) => $query->where('contractor_id', $contractorId))
        ->when($validated['invoice_date_from'] ?? null, fn ($query, $date) => $query->whereDate('invoice_date', '>=', $date))
        ->when($validated['invoice_date_to'] ?? null, fn ($query, $date) => $query->whereDate('invoice_date', '<=', $date))
        ->when($validated['week_commencing_from'] ?? null, fn ($query, $date) => $query->whereDate('week_commencing', '>=', $date))
        ->when($validated['week_commencing_to'] ?? null, fn ($query, $date) => $query->whereDate('week_commencing', '<=', $date));

    $summaryFor = function (array $statuses) use ($baseQuery) {
        $query = clone $baseQuery;

        return [
            'count' => (clone $query)->whereIn('status', $statuses)->count(),
            'total_pence' => (int) (clone $query)->whereIn('status', $statuses)->sum('total_pence'),
        ];
    };

    $summary = [
        'awaiting_review' => $summaryFor(['submitted', 'resubmitted', 'under_review']),
        'returned' => $summaryFor(['returned']),
        'ready_or_paid' => $summaryFor(['ready_for_payment', 'paid']),
        'cancelled_or_replaced' => $summaryFor(['cancelled', 'replaced']),
        'all' => [
            'count' => (clone $baseQuery)->count(),
            'total_pence' => (int) (clone $baseQuery)->sum('total_pence'),
        ],
    ];

    $invoices = (clone $baseQuery)
        ->with('contractor')
        ->when($validated['status'] ?? null, function ($query, $status) {
            return match ($status) {
                'awaiting_review' => $query->whereIn('status', ['submitted', 'resubmitted', 'under_review']),
                'ready_or_paid' => $query->whereIn('status', ['ready_for_payment', 'paid']),
                'cancelled_or_replaced' => $query->whereIn('status', ['cancelled', 'replaced']),
                default => $query->where('status', $status),
            };
        })
        ->latest('invoice_date')
        ->latest('id')
        ->paginate(15)
        ->withQueryString();

    $contractors = Contractor::orderBy('name')->get();

    return view('admin.invoices.index', [
        'invoices' => $invoices,
        'contractors' => $contractors,
        'filters' => $validated,
        'summary' => $summary,
    ]);
}

    public function show(ContractorInvoice $invoice)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $invoice->load('contractor', 'user', 'lineItems');

        return view('admin.invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    public function download(ContractorInvoice $invoice)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $invoice->load('contractor', 'user', 'lineItems');

        $pdf = Pdf::loadView('pdf.contractor-invoice', [
            'invoice' => $invoice,
            'contractor' => $invoice->contractor,
            'settings' => PortalSetting::current(),
        ])->setPaper('a4');

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    public function sendForPayment(ContractorInvoice $invoice)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        abort_unless(in_array($invoice->status, [
            'submitted',
            'resubmitted',
            'under_review',
        ]), 403);

        $settings = PortalSetting::current();

        $invoice->update([
            'status' => 'ready_for_payment',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'ready_for_payment_at' => now(),
            'review_comment' => null,
        ]);

        $invoice->load('contractor', 'user', 'lineItems');

        try {
            if ($invoice->supplier_email) {
                Mail::to($invoice->supplier_email)
                    ->send(new ContractorInvoiceReadyForPayment(
                        invoice: $invoice,
                        settings: $settings,
                        recipientType: 'contractor',
                    ));
            }

            if ($settings->accounts_email) {
                Mail::to($settings->accounts_email)
                    ->send(new ContractorInvoiceReadyForPayment(
                        invoice: $invoice,
                        settings: $settings,
                        recipientType: 'accounts',
                    ));
            }

            $invoice->update([
                'emailed_at' => now(),
            ]);

            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'Invoice marked as ready for payment and email sent.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'Invoice marked as ready for payment, but the email could not be sent.');
        }
    }

    public function returnToContractor(Request $request, ContractorInvoice $invoice)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        abort_unless(in_array($invoice->status, [
            'submitted',
            'resubmitted',
            'under_review',
        ]), 403);

        $validated = $request->validate([
            'review_comment' => ['required', 'string', 'max:2000'],
        ]);

        $settings = PortalSetting::current();

        $invoice->update([
            'status' => 'returned',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'returned_at' => now(),
            'review_comment' => $validated['review_comment'],
        ]);

        $invoice->load('contractor', 'user', 'lineItems');

        try {
            if ($invoice->supplier_email) {
                Mail::to($invoice->supplier_email)
                    ->send(new ContractorInvoiceReturned(
                        invoice: $invoice,
                        settings: $settings,
                    ));
            }

            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'Invoice returned to contractor and email sent.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'Invoice returned to contractor, but the email could not be sent.');
        }
    }
}