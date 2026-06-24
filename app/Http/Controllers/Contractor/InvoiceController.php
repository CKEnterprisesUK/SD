<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use App\Models\ContractorInvoice;
use App\Models\PortalSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isContractor(), 403);

        $contractor = auth()->user()->contractor;

        abort_unless($contractor, 403);

        $invoices = ContractorInvoice::where('contractor_id', $contractor->id)
            ->latest('week_commencing')
            ->paginate(10);

        return view('contractor.invoices.index', [
            'contractor' => $contractor,
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->isContractor(), 403);

        $contractor = auth()->user()->contractor;

        abort_unless($contractor, 403);

        return view('contractor.invoices.create', [
            'contractor' => $contractor,
            'settings' => PortalSetting::current(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isContractor(), 403);

        $contractor = auth()->user()->contractor;

        abort_unless($contractor, 403);

        $validated = $request->validate([
            'week_commencing' => [
                'required',
                'date',
                Rule::unique('contractor_invoices', 'week_commencing')
                    ->where('contractor_id', $contractor->id)
                    ->whereNotIn('status', ['cancelled']),
            ],

            'day_rate' => ['required', 'numeric', 'min:0'],

            'monday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'tuesday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'wednesday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'thursday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'friday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'saturday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'sunday_days' => ['required', 'numeric', 'min:0', 'max:1'],

            'contractor_notes' => ['nullable', 'string', 'max:2000'],
            'confirm_submission' => ['accepted'],
        ]);

        $settings = PortalSetting::current();

        $defaultRatePence = $contractor->day_rate_pence;
        $actualRatePence = (int) round((float) $validated['day_rate'] * 100);

        $mondayDays = (float) $validated['monday_days'];
        $tuesdayDays = (float) $validated['tuesday_days'];
        $wednesdayDays = (float) $validated['wednesday_days'];
        $thursdayDays = (float) $validated['thursday_days'];
        $fridayDays = (float) $validated['friday_days'];
        $saturdayDays = (float) $validated['saturday_days'];
        $sundayDays = (float) $validated['sunday_days'];

        $workedDays = collect([
            $mondayDays,
            $tuesdayDays,
            $wednesdayDays,
            $thursdayDays,
            $fridayDays,
            $saturdayDays,
            $sundayDays,
        ])->sum();

        abort_if($workedDays <= 0, 422, 'You must select at least one day or part-day worked.');

        $subtotalPence = (int) round($workedDays * $actualRatePence);
        $vatPence = 0;
        $totalPence = $subtotalPence + $vatPence;

        $customerName = $settings->company_name ?: $settings->portal_name ?: 'the building firm';

        $confirmationText = "I confirm this invoice is accurate and is being submitted by me to {$customerName} for payment.";

        $invoice = ContractorInvoice::create([
            'contractor_id' => $contractor->id,
            'user_id' => auth()->id(),

            'invoice_number' => $this->nextInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'week_commencing' => $validated['week_commencing'],

            /*
            |--------------------------------------------------------------------------
            | Supplier snapshot
            |--------------------------------------------------------------------------
            |
            | These values are copied onto the invoice at submission time. This means
            | historic invoices stay accurate even if the contractor profile changes.
            |
            */

            'supplier_name' => $contractor->company_name ?: $contractor->name,
            'supplier_email' => $contractor->email,
            'supplier_phone' => $contractor->phone,
            'supplier_address' => $contractor->address ?? null,

            /*
            |--------------------------------------------------------------------------
            | Customer snapshot
            |--------------------------------------------------------------------------
            |
            | These values come from the client portal settings at submission time.
            |
            */

            'customer_name' => $customerName,
            'customer_address' => $settings->company_address,

            /*
            |--------------------------------------------------------------------------
            | Rate snapshot
            |--------------------------------------------------------------------------
            */

            'default_day_rate_pence' => $defaultRatePence,
            'actual_day_rate_pence' => $actualRatePence,
            'day_rate_overridden' => $actualRatePence !== $defaultRatePence,

            /*
            |--------------------------------------------------------------------------
            | Part-days
            |--------------------------------------------------------------------------
            */

            'monday_days' => $mondayDays,
            'tuesday_days' => $tuesdayDays,
            'wednesday_days' => $wednesdayDays,
            'thursday_days' => $thursdayDays,
            'friday_days' => $fridayDays,
            'saturday_days' => $saturdayDays,
            'sunday_days' => $sundayDays,

            /*
            |--------------------------------------------------------------------------
            | Backwards-compatible boolean day fields
            |--------------------------------------------------------------------------
            |
            | These can be removed later after the app is fully using part-day fields.
            |
            */

            'worked_monday' => $mondayDays > 0,
            'worked_tuesday' => $tuesdayDays > 0,
            'worked_wednesday' => $wednesdayDays > 0,
            'worked_thursday' => $thursdayDays > 0,
            'worked_friday' => $fridayDays > 0,
            'worked_saturday' => $saturdayDays > 0,
            'worked_sunday' => $sundayDays > 0,

            'days_worked' => $workedDays,

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            |
            | VAT is deliberately zero because this module is for non-VAT contractor
            | invoices. The PDF should state: "This is not a VAT invoice. VAT has
            | not been charged."
            |
            */

            'subtotal_pence' => $subtotalPence,
            'vat_pence' => $vatPence,
            'total_pence' => $totalPence,

            'contractor_notes' => $validated['contractor_notes'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Submission proof
            |--------------------------------------------------------------------------
            */

            'contractor_confirmation_text' => $confirmationText,
            'submitted_at' => now(),
            'submitted_ip' => $request->ip(),

            'status' => 'submitted',
        ]);

        return redirect()
            ->route('contractor.invoices.show', $invoice)
            ->with('status', 'Invoice submitted successfully.');
    }

    public function show(ContractorInvoice $invoice)
    {
        abort_unless(auth()->user()->isContractor(), 403);

        $contractor = auth()->user()->contractor;

        abort_unless($contractor, 403);

        /*
        |--------------------------------------------------------------------------
        | Contractor isolation
        |--------------------------------------------------------------------------
        |
        | Contractors can only view invoices linked to their own contractor record.
        |
        */

        abort_unless($invoice->contractor_id === $contractor->id, 403);

        return view('contractor.invoices.show', [
            'invoice' => $invoice,
            'contractor' => $contractor,
        ]);
    }
    public function download(ContractorInvoice $invoice)
{
    abort_unless(auth()->user()->isContractor(), 403);

    $contractor = auth()->user()->contractor;

    abort_unless($contractor, 403);
    abort_unless($invoice->contractor_id === $contractor->id, 403);

    $settings = PortalSetting::current();

    $pdf = Pdf::loadView('pdf.contractor-invoice', [
        'invoice' => $invoice,
        'contractor' => $contractor,
        'settings' => $settings,
    ])->setPaper('a4');

    return $pdf->download($invoice->invoice_number . '.pdf');
}

    private function nextInvoiceNumber(): string
    {
        $nextNumber = (ContractorInvoice::max('id') ?? 0) + 1;

        return 'CI-' . now()->format('Y') . '-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }
}