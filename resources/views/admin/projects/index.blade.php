<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Projects
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold">Projects</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Manage customer projects and their document libraries.
                </p>
            </div>

            <a href="{{ route('admin.projects.create') }}"
               class="inline-flex items-center px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                New project
            </a>
        </div>

        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" action="{{ route('admin.projects.index') }}" class="mb-6 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-semibold mb-2">Search</label>
                <input
                    id="search"
                    name="search"
                    type="text"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Project name, reference or customer"
                    class="w-full border border-gray-400 px-4 py-2 rounded-none"
                >
            </div>

            <div class="min-w-[160px]">
                <label for="state" class="block text-sm font-semibold mb-2">State</label>
                <select id="state" name="state" class="w-full border border-gray-400 px-4 py-2 rounded-none">
                    <option value="">All states</option>
                    @foreach ($states as $state)
                        <option value="{{ $state }}" @selected(($filters['state'] ?? '') === $state)>
                            {{ $state }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 border border-gray-900 text-sm font-semibold rounded-none">
                    Filter
                </button>

                @if (!empty($filters['search']) || !empty($filters['state']))
                    <a href="{{ route('admin.projects.index') }}" class="text-sm underline">Clear</a>
                @endif
            </div>
        </form>

        <div class="border border-gray-300 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-50 text-left">
                        <th class="px-4 py-3 font-semibold">Project</th>
                        <th class="px-4 py-3 font-semibold">Reference</th>
                        <th class="px-4 py-3 font-semibold">Customer</th>
                        <th class="px-4 py-3 font-semibold">State</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($projects as $project)
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3 font-semibold">{{ $project->name }}</td>
                            <td class="px-4 py-3">{{ $project->reference ?: '—' }}</td>
                            <td class="px-4 py-3">
                                {{ optional($project->customer)->company_name ?: optional($project->customer)->name ?: '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block border border-gray-400 px-2 py-1 text-xs font-semibold">
                                    {{ $project->state }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.projects.show', $project) }}" class="underline">
                                        View
                                    </a>
                                    <a href="{{ route('admin.projects.edit', $project) }}" class="underline">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-600">
                                No projects have been created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $projects->links() }}
        </div>
    </div>
</x-app-layout>
