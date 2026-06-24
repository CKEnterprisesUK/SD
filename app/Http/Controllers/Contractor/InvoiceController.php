<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use App\Mail\ContractorInvoiceSubmitted;
use App\Models\ContractorInvoice;
use App\Models\PortalSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

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

        $returnedInvoicesCount = ContractorInvoice::where('contractor_id', $contractor->id)
            ->where('status', 'returned')
            ->count();

        return view('contractor.invoices.index', [
            'contractor' => $contractor,
            'invoices' => $invoices,
            'returnedInvoicesCount' => $returnedInvoicesCount,
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
                    ->where(function ($query) use ($contractor) {
                        return $query
                            ->where('contractor_id', $contractor->id)
                            ->whereNotIn('status', ['cancelled', 'replaced']);
                    }),
            ],

            'day_rate' => ['required', 'numeric', 'min:0'],

            'monday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'tuesday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'wednesday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'thursday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'friday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'saturday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'sunday_days' => ['required', 'numeric', 'min:0', 'max:1'],

            'line_items' => ['nullable', 'array'],
            'line_items.*.type' => ['nullable', 'in:materials,expense,plant_hire,other'],
            'line_items.*.description' => ['nullable', 'string', 'max:255'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_amount' => ['nullable', 'numeric', 'min:0'],

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

        if ($workedDays <= 0) {
            return back()
                ->withErrors([
                    'days_worked' => 'You must select at least one day or part-day worked.',
                ])
                ->withInput();
        }

        $lineItemResult = $this->prepareLineItems($validated['line_items'] ?? []);

        $labourSubtotalPence = (int) round($workedDays * $actualRatePence);
        $subtotalPence = $labourSubtotalPence + $lineItemResult['total_pence'];
        $vatPence = 0;
        $totalPence = $subtotalPence + $vatPence;

        $customerName = $settings->company_name ?: $settings->portal_name ?: 'the building firm';

        $confirmationText = "I confirm this invoice is accurate and is being submitted by me to {$customerName} for payment.";

        $invoice = DB::transaction(function () use (
            $contractor,
            $validated,
            $settings,
            $defaultRatePence,
            $actualRatePence,
            $mondayDays,
            $tuesdayDays,
            $wednesdayDays,
            $thursdayDays,
            $fridayDays,
            $saturdayDays,
            $sundayDays,
            $workedDays,
            $subtotalPence,
            $vatPence,
            $totalPence,
            $confirmationText,
            $lineItemResult,
            $request,
            $customerName
        ) {
            $invoice = ContractorInvoice::create([
                'contractor_id' => $contractor->id,
                'user_id' => auth()->id(),

                'invoice_number' => $this->nextInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'week_commencing' => $validated['week_commencing'],

                'supplier_name' => $contractor->company_name ?: $contractor->name,
                'supplier_email' => $contractor->email,
                'supplier_phone' => $contractor->phone,
                'supplier_address' => $contractor->address ?? null,

                'customer_name' => $customerName,
                'customer_address' => $settings->company_address,

                'default_day_rate_pence' => $defaultRatePence,
                'actual_day_rate_pence' => $actualRatePence,
                'day_rate_overridden' => $actualRatePence !== $defaultRatePence,

                'monday_days' => $mondayDays,
                'tuesday_days' => $tuesdayDays,
                'wednesday_days' => $wednesdayDays,
                'thursday_days' => $thursdayDays,
                'friday_days' => $fridayDays,
                'saturday_days' => $saturdayDays,
                'sunday_days' => $sundayDays,

                'worked_monday' => $mondayDays > 0,
                'worked_tuesday' => $tuesdayDays > 0,
                'worked_wednesday' => $wednesdayDays > 0,
                'worked_thursday' => $thursdayDays > 0,
                'worked_friday' => $fridayDays > 0,
                'worked_saturday' => $saturdayDays > 0,
                'worked_sunday' => $sundayDays > 0,

                'days_worked' => $workedDays,

                'subtotal_pence' => $subtotalPence,
                'vat_pence' => $vatPence,
                'total_pence' => $totalPence,

                'contractor_notes' => $validated['contractor_notes'] ?? null,

                'contractor_confirmation_text' => $confirmationText,
                'submitted_at' => now(),
                'submitted_ip' => $request->ip(),

                'status' => 'submitted',
            ]);

            foreach ($lineItemResult['items'] as $lineItem) {
                $invoice->lineItems()->create($lineItem);
            }

            return $invoice->load('lineItems');
        });

        try {
            if ($settings->accounts_email) {
                Mail::to($settings->accounts_email)
                    ->send(new ContractorInvoiceSubmitted(
                        invoice: $invoice,
                        settings: $settings,
                        recipientType: 'accounts',
                    ));
            }

            if ($invoice->supplier_email) {
                Mail::to($invoice->supplier_email)
                    ->send(new ContractorInvoiceSubmitted(
                        invoice: $invoice,
                        settings: $settings,
                        recipientType: 'contractor',
                    ));
            }

            $invoice->update([
                'emailed_at' => now(),
            ]);

            return redirect()
                ->route('contractor.invoices.show', $invoice)
                ->with('status', 'Invoice submitted successfully and sent for review.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('contractor.invoices.show', $invoice)
                ->with('status', 'Invoice submitted, but the email notification could not be sent. Please contact the accounts team.');
        }
    }

    public function show(ContractorInvoice $invoice)
    {
        abort_unless(auth()->user()->isContractor(), 403);

        $contractor = auth()->user()->contractor;

        abort_unless($contractor, 403);
        abort_unless($invoice->contractor_id === $contractor->id, 403);

        $invoice->load('lineItems');

        return view('contractor.invoices.show', [
            'invoice' => $invoice,
            'contractor' => $contractor,
        ]);
    }

    public function edit(ContractorInvoice $invoice)
    {
        abort_unless(auth()->user()->isContractor(), 403);

        $contractor = auth()->user()->contractor;

        abort_unless($contractor, 403);
        abort_unless($invoice->contractor_id === $contractor->id, 403);
        abort_unless($invoice->status === 'returned', 403);

        $invoice->load('lineItems');

        return view('contractor.invoices.edit', [
            'invoice' => $invoice,
            'contractor' => $contractor,
            'settings' => PortalSetting::current(),
        ]);
    }

    public function update(Request $request, ContractorInvoice $invoice)
    {
        abort_unless(auth()->user()->isContractor(), 403);

        $contractor = auth()->user()->contractor;

        abort_unless($contractor, 403);
        abort_unless($invoice->contractor_id === $contractor->id, 403);
        abort_unless($invoice->status === 'returned', 403);

        $validated = $request->validate([
            'day_rate' => ['required', 'numeric', 'min:0'],

            'monday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'tuesday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'wednesday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'thursday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'friday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'saturday_days' => ['required', 'numeric', 'min:0', 'max:1'],
            'sunday_days' => ['required', 'numeric', 'min:0', 'max:1'],

            'line_items' => ['nullable', 'array'],
            'line_items.*.type' => ['nullable', 'in:materials,expense,plant_hire,other'],
            'line_items.*.description' => ['nullable', 'string', 'max:255'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_amount' => ['nullable', 'numeric', 'min:0'],

            'contractor_notes' => ['nullable', 'string', 'max:2000'],
            'confirm_submission' => ['accepted'],
        ]);

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

        if ($workedDays <= 0) {
            return back()
                ->withErrors([
                    'days_worked' => 'You must select at least one day or part-day worked.',
                ])
                ->withInput();
        }

        $lineItemResult = $this->prepareLineItems($validated['line_items'] ?? []);

        $labourSubtotalPence = (int) round($workedDays * $actualRatePence);
        $subtotalPence = $labourSubtotalPence + $lineItemResult['total_pence'];
        $vatPence = 0;
        $totalPence = $subtotalPence + $vatPence;

        DB::transaction(function () use (
            $invoice,
            $validated,
            $defaultRatePence,
            $actualRatePence,
            $mondayDays,
            $tuesdayDays,
            $wednesdayDays,
            $thursdayDays,
            $fridayDays,
            $saturdayDays,
            $sundayDays,
            $workedDays,
            $subtotalPence,
            $vatPence,
            $totalPence,
            $lineItemResult,
            $request
        ) {
            $invoice->update([
                'actual_day_rate_pence' => $actualRatePence,
                'day_rate_overridden' => $actualRatePence !== $defaultRatePence,

                'monday_days' => $mondayDays,
                'tuesday_days' => $tuesdayDays,
                'wednesday_days' => $wednesdayDays,
                'thursday_days' => $thursdayDays,
                'friday_days' => $fridayDays,
                'saturday_days' => $saturdayDays,
                'sunday_days' => $sundayDays,

                'worked_monday' => $mondayDays > 0,
                'worked_tuesday' => $tuesdayDays > 0,
                'worked_wednesday' => $wednesdayDays > 0,
                'worked_thursday' => $thursdayDays > 0,
                'worked_friday' => $fridayDays > 0,
                'worked_saturday' => $saturdayDays > 0,
                'worked_sunday' => $sundayDays > 0,

                'days_worked' => $workedDays,

                'subtotal_pence' => $subtotalPence,
                'vat_pence' => $vatPence,
                'total_pence' => $totalPence,

                'contractor_notes' => $validated['contractor_notes'] ?? null,

                'status' => 'resubmitted',
                'review_comment' => null,
                'resubmitted_at' => now(),
                'submitted_at' => now(),
                'submitted_ip' => $request->ip(),
                'submission_version' => ($invoice->submission_version ?? 1) + 1,
            ]);

            $invoice->lineItems()->delete();

            foreach ($lineItemResult['items'] as $lineItem) {
                $invoice->lineItems()->create($lineItem);
            }
        });

        return redirect()
            ->route('contractor.invoices.show', $invoice)
            ->with('status', 'Invoice resubmitted successfully and sent back for review.');
    }

    public function download(ContractorInvoice $invoice)
    {
        abort_unless(auth()->user()->isContractor(), 403);

        $contractor = auth()->user()->contractor;

        abort_unless($contractor, 403);
        abort_unless($invoice->contractor_id === $contractor->id, 403);

        $settings = PortalSetting::current();

        $invoice->load('lineItems');

        $pdf = Pdf::loadView('pdf.contractor-invoice', [
            'invoice' => $invoice,
            'contractor' => $contractor,
            'settings' => $settings,
        ])->setPaper('a4');

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    private function prepareLineItems(array $lineItems): array
    {
        $items = [];
        $totalPence = 0;

        foreach ($lineItems as $index => $item) {
            if (empty($item['description'])) {
                continue;
            }

            $quantity = (float) ($item['quantity'] ?? 1);
            $unitAmountPence = (int) round(((float) ($item['unit_amount'] ?? 0)) * 100);
            $lineTotalPence = (int) round($quantity * $unitAmountPence);

            if ($quantity <= 0 || $unitAmountPence <= 0) {
                continue;
            }

            $totalPence += $lineTotalPence;

            $items[] = [
                'type' => $item['type'] ?? 'other',
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_amount_pence' => $unitAmountPence,
                'total_pence' => $lineTotalPence,
                'sort_order' => $index,
            ];
        }

        return [
            'items' => $items,
            'total_pence' => $totalPence,
        ];
    }

    private function nextInvoiceNumber(): string
    {
        $nextNumber = (ContractorInvoice::max('id') ?? 0) + 1;

        return 'CI-' . now()->format('Y') . '-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }
}