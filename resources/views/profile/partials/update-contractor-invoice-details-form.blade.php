<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Contractor invoice details
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            This address will be shown on future contractor invoices you submit.
        </p>
    </header>

    <form
        method="POST"
        action="{{ route('profile.contractor-invoice-details.update') }}"
        class="mt-6 space-y-6"
    >
        @csrf
        @method('PATCH')

        <div>
            <x-input-label for="contractor_invoice_address" value="Invoice address" />

            <textarea
                id="contractor_invoice_address"
                name="address"
                rows="5"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-none shadow-sm"
                placeholder="Enter the address you want shown on your invoices"
            >{{ old('address', auth()->user()->contractor?->address) }}</textarea>

            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div class="border border-gray-300 bg-gray-50 p-4 text-sm text-gray-700">
            <p class="font-semibold text-gray-900">
                How this is used
            </p>

            <p class="mt-1">
                When you submit a new invoice, SiteDesk copies this address onto that invoice. Existing invoices keep the address they were submitted with.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>
                Save invoice address
            </x-primary-button>

            @if (session('status') === 'contractor-invoice-details-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-sm text-gray-600"
                >
                    Saved.
                </p>
            @endif
        </div>
    </form>
</section>