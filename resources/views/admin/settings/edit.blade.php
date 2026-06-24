<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Portal Settings
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                <p class="font-semibold mb-2">There is a problem with the form.</p>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ route('admin.settings.update') }}"
              enctype="multipart/form-data"
              class="space-y-8">
            @csrf
            @method('PUT')

            <section class="border border-gray-300 bg-white p-6">
                <h3 class="text-lg font-semibold mb-4">Branding</h3>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold mb-2" for="portal_name">
                            Portal name
                        </label>
                        <input id="portal_name"
                               name="portal_name"
                               type="text"
                               value="{{ old('portal_name', $settings->portal_name) }}"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" for="primary_colour">
                            Primary colour
                        </label>
                        <input id="primary_colour"
                               name="primary_colour"
                               type="text"
                               value="{{ old('primary_colour', $settings->primary_colour) }}"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none">
                        <p class="text-sm text-gray-600 mt-1">Example: #1d70b8</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" for="logo">
                            Logo
                        </label>

                        @if ($settings->logo_path)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $settings->logo_path) }}"
                                     alt="{{ $settings->portal_name }} logo"
                                     class="h-16 max-w-56 object-contain border border-gray-300 p-2 bg-white">
                            </div>
                        @endif

                        <input id="logo"
                               name="logo"
                               type="file"
                               accept="image/*"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    </div>
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h3 class="text-lg font-semibold mb-4">Company details</h3>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold mb-2" for="company_name">
                            Company name
                        </label>
                        <input id="company_name"
                               name="company_name"
                               type="text"
                               value="{{ old('company_name', $settings->company_name) }}"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" for="company_address">
                            Company address
                        </label>
                        <textarea id="company_address"
                                  name="company_address"
                                  rows="4"
                                  class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('company_address', $settings->company_address) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" for="company_number">
                            Company number
                        </label>
                        <input id="company_number"
                               name="company_number"
                               type="text"
                               value="{{ old('company_number', $settings->company_number) }}"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" for="vat_number">
                            VAT number
                        </label>
                        <input id="vat_number"
                               name="vat_number"
                               type="text"
                               value="{{ old('vat_number', $settings->vat_number) }}"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    </div>
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h3 class="text-lg font-semibold mb-4">Invoice settings</h3>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold mb-2" for="accounts_email">
                            Accounts email
                        </label>
                        <input id="accounts_email"
                               name="accounts_email"
                               type="email"
                               value="{{ old('accounts_email', $settings->accounts_email) }}"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" for="payment_terms_days">
                            Payment terms, in days
                        </label>
                        <input id="payment_terms_days"
                               name="payment_terms_days"
                               type="number"
                               min="0"
                               value="{{ old('payment_terms_days', $settings->payment_terms_days) }}"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" for="invoice_wording">
                            Invoice wording
                        </label>
                        <textarea id="invoice_wording"
                                  name="invoice_wording"
                                  rows="4"
                                  class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('invoice_wording', $settings->invoice_wording) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" for="pdf_footer">
                            PDF footer
                        </label>
                        <textarea id="pdf_footer"
                                  name="pdf_footer"
                                  rows="3"
                                  class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('pdf_footer', $settings->pdf_footer) }}</textarea>
                    </div>
                </div>
            </section>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Save settings
                </button>
            </div>
        </form>
    </div>
</x-app-layout>