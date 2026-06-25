<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Contractor
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('admin.contractors.show', $contractor) }}" class="text-sm underline">
                Back to contractor
            </a>

            <h1 class="text-2xl font-bold mt-4">
                Edit contractor
            </h1>

            <p class="text-sm text-gray-600 mt-1">
                Name and email are locked because they are linked to the login account.
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

        <div class="border border-gray-300 bg-gray-50 p-4 mb-6">
            <div class="text-sm">
                <div><strong>Name:</strong> {{ $contractor->name }}</div>
                <div><strong>Email:</strong> {{ $contractor->email }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.contractors.update', $contractor) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="border border-gray-300 bg-white p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold">
                        Contact details
                    </h2>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold mb-2">
                        Phone number
                    </label>

                    <input
                        id="phone"
                        name="phone"
                        type="text"
                        value="{{ old('phone', $contractor->phone) }}"
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
                        value="{{ old('company_name', $contractor->company_name) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                    >
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold">
                        Invoice details
                    </h2>

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
                    >{{ old('address', $contractor->address) }}</textarea>
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
                            value="{{ old('day_rate', $contractor->day_rate) }}"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            required
                        >
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold mb-2">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >
                        <option value="active" @selected(old('status', $contractor->status) === 'active')>
                            Active
                        </option>

                        <option value="inactive" @selected(old('status', $contractor->status) === 'inactive')>
                            Inactive
                        </option>
                    </select>
                </div>
            </section>

            <div class="flex items-center gap-4 pt-4">
                <button type="submit"
                        class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Save contractor
                </button>

                <a href="{{ route('admin.contractors.show', $contractor) }}" class="text-sm underline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>