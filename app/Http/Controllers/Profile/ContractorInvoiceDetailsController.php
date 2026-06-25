<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use Illuminate\Http\Request;

class ContractorInvoiceDetailsController extends Controller
{
    public function update(Request $request)
    {
        abort_unless($request->user(), 403);
        abort_unless($request->user()->isContractor(), 403);

        $validated = $request->validate([
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        $contractor = $request->user()->contractor;

        if (! $contractor) {
            $contractor = Contractor::where('user_id', $request->user()->id)->first();
        }

        abort_unless($contractor, 403);

        $contractor->forceFill([
            'address' => $validated['address'] ?: null,
        ])->save();

        return redirect()
            ->route('profile.edit')
            ->with('status', 'contractor-invoice-details-updated');
    }
}