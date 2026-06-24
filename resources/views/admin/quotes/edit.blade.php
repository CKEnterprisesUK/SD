<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Quote
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('admin.quotes.show', $quote) }}" class="text-sm underline">
                Back to quote
            </a>

            <h1 class="text-3xl font-bold mt-4">Edit quote</h1>
            <p class="text-gray-600 mt-2">
                Update the quote details and editable customer pack sections.
            </p>
        </div>

        @include('admin.quotes._form', [
            'quote' => $quote,
            'customers' => $customers,
            'users' => $users,
            'selectedCustomer' => null,
            'action' => route('admin.quotes.update', $quote),
            'method' => 'PUT',
        ])
    </div>
</x-app-layout>