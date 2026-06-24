<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Quote
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('admin.quotes.index') }}" class="text-sm underline">
                Back to quotes
            </a>

            <h1 class="text-3xl font-bold mt-4">Create quote</h1>
            <p class="text-gray-600 mt-2">
                Create a quote record against a customer. Site notes and line items are added after creation.
            </p>
        </div>

        @include('admin.quotes._form', [
            'quote' => null,
            'customers' => $customers,
            'users' => $users,
            'selectedCustomer' => $selectedCustomer ?? null,
            'action' => route('admin.quotes.store'),
            'method' => 'POST',
        ])
    </div>
</x-app-layout>