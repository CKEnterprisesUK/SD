<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\ContractorInvoice;
use App\Models\PortalSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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

        $invoices = ContractorInvoice::query()
            ->with('contractor')
            ->when($validated['contractor_id'] ?? null, fn ($query, $contractorId) => $query->where('contractor_id', $contractorId))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['invoice_date_from'] ?? null, fn ($query, $date) => $query->whereDate('invoice_date', '>=', $date))
            ->when($validated['invoice_date_to'] ?? null, fn ($query, $date) => $query->whereDate('invoice_date', '<=', $date))
            ->when($validated['week_commencing_from'] ?? null, fn ($query, $date) => $query->whereDate('week_commencing', '>=', $date))
            ->when($validated['week_commencing_to'] ?? null, fn ($query, $date) => $query->whereDate('week_commencing', '<=', $date))
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $contractors = Contractor::orderBy('name')->get();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'contractors' => $contractors,
            'filters' => $validated,
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

        $invoice->update([
            'status' => 'ready_for_payment',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'ready_for_payment_at' => now(),
            'review_comment' => null,
        ]);

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', 'Invoice marked as ready for payment.');
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

        $invoice->update([
            'status' => 'returned',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'returned_at' => now(),
            'review_comment' => $validated['review_comment'],
        ]);

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', 'Invoice returned to contractor.');
    }
}