<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PortalSetting;
use App\Models\PricingRateItem;
use Barryvdh\DomPDF\Facade\Pdf;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        $quotes = Quote::query()
            ->with(['customer', 'assignedUser'])
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('quote_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['customer_id'] ?? null, fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::orderBy('company_name')
            ->orderBy('name')
            ->get();

        return view('admin.quotes.index', [
            'quotes' => $quotes,
            'customers' => $customers,
            'filters' => $validated,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $customers = Customer::orderBy('company_name')
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        $selectedCustomer = null;

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->integer('customer_id'));
        }

        return view('admin.quotes.create', [
            'customers' => $customers,
            'users' => $users,
            'selectedCustomer' => $selectedCustomer,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,survey_in_progress,survey_completed,ai_compiled,sent,accepted,declined,expired,cancelled'],
            'site_address' => ['nullable', 'string', 'max:5000'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'valid_until' => ['nullable', 'date'],
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        $quote = Quote::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => auth()->id(),
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'quote_number' => $this->nextQuoteNumber(),
            'title' => $validated['title'],
            'status' => $validated['status'],
            'site_address' => $validated['site_address'] ?: $customer->address,
            'summary' => $validated['summary'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
        ]);

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('status', 'Quote created successfully.');
    }

    public function show(Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $quote->load([
            'customer.contacts',
            'creator',
            'assignedUser',
            'notes.creator',
            'lineItems',
            'followUps.assignedUser',
        ]);

        return view('admin.quotes.show', [
            'quote' => $quote,
        ]);
    }

    public function edit(Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $customers = Customer::orderBy('company_name')
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        return view('admin.quotes.edit', [
            'quote' => $quote,
            'customers' => $customers,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,survey_in_progress,survey_completed,ai_compiled,sent,accepted,declined,expired,cancelled'],
            'site_address' => ['nullable', 'string', 'max:5000'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'final_customer_message' => ['nullable', 'string'],
            'final_scope' => ['nullable', 'string'],
            'final_assumptions' => ['nullable', 'string'],
            'final_exclusions' => ['nullable', 'string'],
            'final_timeline' => ['nullable', 'string'],
            'final_terms' => ['nullable', 'string'],
            'valid_until' => ['nullable', 'date'],
        ]);

        $quote->update([
            'customer_id' => $validated['customer_id'],
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'title' => $validated['title'],
            'status' => $validated['status'],
            'site_address' => $validated['site_address'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'final_customer_message' => $validated['final_customer_message'] ?? null,
            'final_scope' => $validated['final_scope'] ?? null,
            'final_assumptions' => $validated['final_assumptions'] ?? null,
            'final_exclusions' => $validated['final_exclusions'] ?? null,
            'final_timeline' => $validated['final_timeline'] ?? null,
            'final_terms' => $validated['final_terms'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
        ]);

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('status', 'Quote updated successfully.');
    }

    public function markSurveyInProgress(Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $quote->update([
            'status' => 'survey_in_progress',
        ]);

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('status', 'Survey marked as in progress.');
    }

    public function markSurveyCompleted(Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $quote->update([
            'status' => 'survey_completed',
        ]);

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('status', 'Survey marked as completed.');
    }

    public function survey(Quote $quote)
{
    abort_unless(auth()->user()->isAdmin(), 403);

    $quote->load([
        'customer.contacts',
        'assignedUser',
        'notes.creator',
        'files.uploadedBy',
    ]);

    return view('admin.quotes.survey', [
        'quote' => $quote,
    ]);
}

public function pricing(Quote $quote)
{
    abort_unless(auth()->user()->isAdmin(), 403);

    $quote->load([
        'customer',
        'lineItems',
        'aiDrafts.items.rateItem',
    ]);

    $latestDraft = $quote->aiDrafts->first();
    $rateItems = PricingRateItem::where('is_active', true)
        ->orderBy('category')
        ->orderBy('name')
        ->get();

    return view('admin.quotes.pricing', [
        'quote' => $quote,
        'latestDraft' => $latestDraft,
        'rateItems' => $rateItems,
    ]);
}

public function pack(Quote $quote)
{
    abort_unless(auth()->user()->isAdmin(), 403);

    $quote->load([
        'customer.contacts',
        'lineItems',
        'notes',
        'files',
    ]);

    return view('admin.quotes.pack', [
        'quote' => $quote,
    ]);
}

public function updatePack(Request $request, Quote $quote)
{
    abort_unless(auth()->user()->isAdmin(), 403);

    $validated = $request->validate([
        'final_customer_message' => ['nullable', 'string'],
        'final_scope' => ['nullable', 'string'],
        'final_assumptions' => ['nullable', 'string'],
        'final_exclusions' => ['nullable', 'string'],
        'final_timeline' => ['nullable', 'string'],
        'final_terms' => ['nullable', 'string'],
    ]);

    $quote->update($validated);

    return redirect()
        ->route('admin.quotes.pack', $quote)
        ->with('status', 'Customer pack updated successfully.');
}

public function download(Quote $quote)
{
    abort_unless(auth()->user()->isAdmin(), 403);

    $quote->load([
        'customer.contacts',
        'lineItems',
        'files',
    ]);

    $portalSettings = \App\Models\PortalSetting::current();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.customer-quote', [
        'quote' => $quote,
        'portalSettings' => $portalSettings,
    ])->setPaper('a4');

    return $pdf->download($quote->quote_number . '-customer-quote.pdf');
}

    private function nextQuoteNumber(): string
    {
        $prefix = 'Q-' . now()->format('Y');

        $latest = Quote::where('quote_number', 'like', $prefix . '-%')
            ->latest('id')
            ->first();

        if (! $latest) {
            return $prefix . '-0001';
        }

        $lastNumber = (int) str_replace($prefix . '-', '', $latest->quote_number);

        return $prefix . '-' . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}