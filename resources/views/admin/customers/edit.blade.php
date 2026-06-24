<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Customer
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('admin.customers.show', $customer) }}" class="text-sm underline">
                Back to customer
            </a>

            <h1 class="text-3xl font-bold mt-4">Edit customer</h1>
            <p class="text-gray-600 mt-2">
                Update the customer record and contact details.
            </p>
        </div>

        @include('admin.customers._form', [
            'customer' => $customer,
            'action' => route('admin.customers.update', $customer),
            'method' => 'PUT',
        ])
    </div>
</x-app-layout>