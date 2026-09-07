<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Document Library
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-8">
            @if (Route::has('projects.index'))
                <a href="{{ route('projects.index') }}" class="text-sm underline">
                    Back to projects
                </a>
            @endif

            <h1 class="text-3xl font-bold mt-4">{{ $project->name }}</h1>
            <p class="text-gray-600 mt-1">Browse the folders you have access to.</p>
        </div>

        <div class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Folders</h2>

            @forelse ($folders as $folder)
                <div class="border-b border-gray-200 last:border-b-0 py-3">
                    @if (Route::has('projects.folders.show'))
                        <a href="{{ route('projects.folders.show', [$project, $folder]) }}"
                           class="font-semibold underline">
                            {{ $folder->name }}
                        </a>
                    @else
                        <span class="font-semibold">{{ $folder->name }}</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-600">
                    There are no folders available to you in this project.
                </p>
            @endforelse
        </div>
    </div>
</x-app-layout>
