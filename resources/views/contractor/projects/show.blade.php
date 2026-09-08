<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $project->name }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-8">
            <a href="{{ route('contractor.projects.index') }}" class="text-sm underline">
                Back to my projects
            </a>

            <h1 class="text-3xl font-bold mt-4">{{ $project->name }}</h1>

            <div class="flex items-center gap-3 mt-2">
                <span class="text-xs uppercase tracking-wide text-gray-600 border border-gray-300 px-2 py-1">
                    {{ $project->state }}
                </span>
                @if (! empty($project->reference))
                    <span class="text-xs text-gray-500">Ref: {{ $project->reference }}</span>
                @endif
            </div>
        </div>

        <div class="border border-gray-300 bg-white p-6 mb-6">
            <h2 class="text-lg font-semibold mb-2">Site address</h2>
            @if (! empty($address))
                <p class="text-sm text-gray-800 whitespace-pre-line">{{ $address }}</p>
            @else
                <p class="text-sm text-gray-500">No site address on file.</p>
            @endif
        </div>

        @if (Route::has('projects.library'))
            <div class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-2">Documents</h2>
                <p class="text-sm text-gray-600 mb-4">Browse the folders and files you have access to for this project.</p>
                <a href="{{ route('projects.library', $project) }}"
                   class="inline-block text-sm font-semibold underline">
                    Open document library
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
