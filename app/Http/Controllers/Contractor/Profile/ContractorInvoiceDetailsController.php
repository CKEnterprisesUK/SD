<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContractorInvoiceDetailsController extends Controller
{
    public function update(Request $request)
    {
        abort_unless($request->user()?->isContractor(), 403);

        $contractor = $request->user()->contractor;

        abort_unless($contractor, 403);

        $validated = $request->validate([
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        $contractor->forceFill([
            'address' => $validated['address'] ?: null,
        ])->save();

        return back()->with('status', 'contractor-invoice-details-updated');
    }
}