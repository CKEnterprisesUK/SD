<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Projects
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-8">
            <h1 class="text-3xl font-bold">Your projects</h1>
            <p class="text-gray-600 mt-1">Open a project to view its documents.</p>
        </div>

        <div class="border border-gray-300 bg-white">
            @forelse ($projects as $project)
                <div class="border-b border-gray-200 last:border-b-0 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <a href="{{ route('projects.library', $project['id']) }}"
                               class="font-semibold underline">
                                {{ $project['name'] }}
                            </a>

                            @if (! empty($project['reference']))
                                <p class="text-xs text-gray-500 mt-1">Ref: {{ $project['reference'] }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-xs uppercase tracking-wide text-gray-600 border border-gray-300 px-2 py-1">
                                {{ $project['state'] }}
                            </span>

                            <a href="{{ route('projects.library', $project['id']) }}"
                               class="inline-flex px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                View
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6">
                    <p class="text-sm text-gray-600">You don't have any projects yet.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $projects->links() }}
        </div>
    </div>
</x-app-layout>
