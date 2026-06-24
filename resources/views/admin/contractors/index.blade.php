<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Contractors
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold">Contractors</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Manage contractor records and day rates.
                </p>
            </div>

            <a href="{{ route('admin.contractors.create') }}"
               class="inline-flex items-center px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                Add contractor
            </a>
        </div>

        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="border border-gray-300 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-50 text-left">
                        <th class="px-4 py-3 font-semibold">Name</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">Company</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                         <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($contractors as $contractor)
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3">{{ $contractor->name }}</td>
                            <td class="px-4 py-3">{{ $contractor->email }}</td>
                            <td class="px-4 py-3">{{ $contractor->company_name ?: '—' }}</td>
                            
                            <td class="px-4 py-3">{{ ucfirst($contractor->status) }}</td>
                            <td class="px-4 py-3">
    <a href="{{ route('admin.contractors.show', $contractor) }}" class="underline">
        View
    </a>
</td>
                        
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-600">
                                No contractors have been added yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $contractors->links() }}
        </div>
    </div>
</x-app-layout>