<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Contractor
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('admin.contractors.index') }}"
               class="text-sm underline">
                Back to contractors
            </a>

            <h1 class="text-2xl font-bold mt-4">
                Add contractor
            </h1>

            <p class="text-sm text-gray-600 mt-1">
                Create a contractor record, set their day rate and add the invoice address used on future invoices.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                <p class="font-semibold mb-2">
                    There is a problem with the form.
                </p>

                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.contractors.store') }}" class="space-y-6">
            @csrf

            <section class="border border-gray-300 bg-white p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold">
                        Login and contact details
                    </h2>

                    <p class="text-sm text-gray-600 mt-1">
                        These details create the contractor record and login account.
                    </p>
                </div>

                <div>
                    <label for="name" class="block text-sm font-semibold mb-2">
                        Contractor name
                    </label>

                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold mb-2">
                        Email address
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold mb-2">
                        Phone number
                    </label>

                    <input
                        id="phone"
                        name="phone"
                        type="text"
                        value="{{ old('phone') }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                    >
                </div>

                <div>
                    <label for="company_name" class="block text-sm font-semibold mb-2">
                        Company name
                    </label>

                    <input
                        id="company_name"
                        name="company_name"
                        type="text"
                        value="{{ old('company_name') }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                    >
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold">
                        Invoice details
                    </h2>

                    <p class="text-sm text-gray-600 mt-1">
                        This address will be copied onto future contractor invoices when they are submitted.
                    </p>
                </div>

                <div>
                    <label for="address" class="block text-sm font-semibold mb-2">
                        Contractor invoice address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        rows="5"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        placeholder="Enter the address the contractor wants shown on invoices"
                    >{{ old('address') }}</textarea>
                </div>

                <div>
                    <label for="day_rate" class="block text-sm font-semibold mb-2">
                        Day rate
                    </label>

                    <div class="flex">
                        <span class="inline-flex items-center border border-r-0 border-gray-400 px-4 bg-gray-50">
                            £
                        </span>

                        <input
                            id="day_rate"
                            name="day_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            value="{{ old('day_rate') }}"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            required
                        >
                    </div>
                </div>
            </section>

            <div class="border border-gray-300 bg-gray-50 p-4">
                <label class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        name="send_invite"
                        value="1"
                        class="mt-1"
                        checked
                    >

                    <span>
                        <span class="block text-sm font-semibold">
                            Send invite email
                        </span>

                        <span class="block text-sm text-gray-600">
                            The contractor will receive a link to set their password and log in.
                        </span>
                    </span>
                </label>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button type="submit"
                        class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Save contractor
                </button>

                <a href="{{ route('admin.contractors.index') }}"
                   class="text-sm underline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>