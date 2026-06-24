<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Customer
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('admin.customers.index') }}" class="text-sm underline">
                Back to customers
            </a>

            <h1 class="text-3xl font-bold mt-4">Add customer</h1>
            <p class="text-gray-600 mt-2">
                Create a customer record and add their contact details.
            </p>
        </div>

        @include('admin.customers._form', [
            'customer' => null,
            'action' => route('admin.customers.store'),
            'method' => 'POST',
        ])
    </div>
</x-app-layout>