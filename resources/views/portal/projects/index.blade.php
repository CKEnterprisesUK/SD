<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Projects
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-8">
            <h1 class="text-3xl font-bold">Projects</h1>
            <p class="text-gray-600 mt-1">Projects you have access to.</p>
        </div>

        <div class="border border-gray-300 bg-white p-6">
            @forelse ($projects ?? [] as $project)
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 last:border-b-0 py-3">
                    <span class="font-semibold">{{ $project->name }}</span>

                    @if (Route::has('projects.library'))
                        <a href="{{ route('projects.library', $project) }}" class="text-sm underline">
                            Open library
                        </a>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-600">
                    You do not have access to any projects yet.
                </p>
            @endforelse
        </div>
    </div>
</x-app-layout>
