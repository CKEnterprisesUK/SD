<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                          @if (auth()->user()->isContractor())
    <a href="{{ route('contractor.invoices.index') }}"
       class="block border border-gray-300 bg-white p-6 hover:border-gray-900">
        <h2 class="text-xl font-bold">My Invoices</h2>
        <p class="text-sm text-gray-600 mt-2">
            Submit weekly contractor invoices and view your invoice history.
        </p>
    </a>
@endif
    </div>
                </div>
            </div>
        </div>
  
</x-app-layout>
