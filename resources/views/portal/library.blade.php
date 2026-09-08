<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Document Library
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        {{-- Breadcrumb / back --}}
        <nav class="text-sm text-gray-600 mb-4 flex flex-wrap items-center gap-1">
            @if (Route::has('customer.projects.index'))
                <a href="{{ route('customer.projects.index') }}" class="hover:text-gray-900 hover:underline">
                    My Projects
                </a>
                <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            @elseif (Route::has('projects.index'))
                <a href="{{ route('projects.index') }}" class="hover:text-gray-900 hover:underline">
                    Projects
                </a>
                <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            @endif
            <span class="font-semibold text-gray-900">{{ $project->name }}</span>
        </nav>

        <div class="mb-6">
            <h1 class="text-3xl font-bold">{{ $project->name }}</h1>
            <p class="text-gray-600 mt-1">Browse the folders you have access to.</p>
        </div>

        {{-- Explorer panel --}}
        <div class="border border-gray-300 bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Folders</h2>
                    <p class="text-xs text-gray-500">
                        {{ $folders->count() }} {{ Str::plural('folder', $folders->count()) }}
                    </p>
                </div>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($folders as $folder)
                    <div class="group flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50/60">
                        <a href="{{ Route::has('projects.folders.show') ? route('projects.folders.show', [$project, $folder]) : '#' }}"
                           class="flex flex-1 items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                            </svg>
                            <span class="font-medium text-gray-900 truncate group-hover:text-blue-700">{{ $folder->name }}</span>
                        </a>

                        @if (Route::has('projects.folders.show'))
                            <a href="{{ route('projects.folders.show', [$project, $folder]) }}"
                               class="text-sm text-gray-500 hover:text-gray-900 hover:underline">
                                Open
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-16 text-center">
                        <svg width="48" height="48" class="mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                        </svg>
                        <p class="mt-3 text-sm font-medium text-gray-700">No folders available</p>
                        <p class="mt-1 text-sm text-gray-500">There are no folders available to you in this project.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
