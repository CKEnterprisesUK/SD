<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Project Details
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-start justify-between gap-6 mb-8">
            <div>
                <a href="{{ route('admin.projects.index') }}" class="text-sm underline">
                    Back to projects
                </a>

                <h1 class="text-3xl font-bold mt-4">{{ $project->name }}</h1>

                <p class="text-gray-600 mt-1">
                    {{ optional($project->customer)->company_name ?: optional($project->customer)->name ?: 'No customer linked' }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if (Route::has('admin.projects.audit-log.index'))
                    <a href="{{ route('admin.projects.audit-log.index', $project) }}"
                       class="px-4 py-2 border border-gray-900 text-sm font-semibold">
                        Audit log
                    </a>
                @endif

                <a href="{{ route('admin.projects.edit', $project) }}"
                   class="px-4 py-2 bg-black text-white text-sm font-semibold">
                    Edit project
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Project profile</h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-semibold text-gray-700">Customer</dt>
                        <dd>{{ optional($project->customer)->company_name ?: optional($project->customer)->name ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Reference</dt>
                        <dd>{{ $project->reference ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">State</dt>
                        <dd>
                            <span class="inline-block border border-gray-400 px-2 py-1 text-xs font-semibold">
                                {{ $project->state }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Created</dt>
                        <dd>{{ $project->created_at ? $project->created_at->format('d M Y H:i') : '—' }}</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="font-semibold text-gray-700">Description</dt>
                        <dd class="whitespace-pre-line">{{ $project->description ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Contractor assignment</h2>

                @if ($project->contractors->isEmpty())
                    <p class="text-sm text-gray-600">No contractors are assigned yet.</p>
                @else
                    <ul class="space-y-3 text-sm">
                        @foreach ($project->contractors as $contractor)
                            <li class="flex items-center justify-between gap-3 border-b border-gray-200 pb-2">
                                <span>
                                    <span class="font-semibold">{{ $contractor->name }}</span>
                                    @if ($contractor->company_name)
                                        <span class="block text-xs text-gray-600">{{ $contractor->company_name }}</span>
                                    @endif
                                </span>

                                @if (Route::has('admin.projects.contractors.destroy'))
                                    <form method="POST"
                                          action="{{ route('admin.projects.contractors.destroy', [$project, $contractor]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs underline text-red-800">
                                            Remove
                                        </button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (Route::has('admin.projects.contractors.store'))
                    @php
                        $assignedContractorIds = $project->contractors->pluck('id')->all();
                        $availableContractors = \App\Models\Contractor::whereNotIn('id', $assignedContractorIds)
                            ->orderBy('name')
                            ->get();
                    @endphp

                    <form method="POST" action="{{ route('admin.projects.contractors.store', $project) }}"
                          class="mt-6 space-y-3">
                        @csrf

                        <label for="contractor_id" class="block text-sm font-semibold">Assign contractor</label>
                        <select id="contractor_id" name="contractor_id"
                                class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm" required>
                            <option value="">Select a contractor</option>
                            @foreach ($availableContractors as $contractor)
                                <option value="{{ $contractor->id }}">
                                    {{ $contractor->name }}{{ $contractor->company_name ? ' — ' . $contractor->company_name : '' }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit"
                                class="w-full px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                            Assign
                        </button>
                    </form>
                @endif
            </section>
        </div>

        <section class="border border-gray-300 bg-white p-6 mt-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-semibold">Document library</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Click a folder to open it. Subfolders and documents open one level at a time.
                    </p>
                </div>

                @unless ($project->isComplete())
                    @if (Route::has('admin.projects.folders.store'))
                        <button type="button"
                                onclick="document.getElementById('new-top-folder').classList.toggle('hidden')"
                                class="inline-flex px-4 py-2 border border-gray-900 text-sm font-semibold rounded-none">
                            New folder
                        </button>
                    @endif
                @endunless
            </div>

            @unless ($project->isComplete())
                @if (Route::has('admin.projects.folders.store'))
                    <form id="new-top-folder" method="POST"
                          action="{{ route('admin.projects.folders.store', $project) }}"
                          class="hidden mb-4 flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input type="text" name="name" required placeholder="Folder name"
                               class="flex-1 border border-gray-400 px-3 py-2 rounded-none text-sm">
                        <button type="submit"
                                class="px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                            Create folder
                        </button>
                    </form>
                @endif
            @endunless

            <div class="divide-y divide-gray-200 border-t border-gray-200">
                @forelse ($project->topLevelFolders as $folder)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                            </svg>

                            @if (Route::has('admin.projects.folders.browse'))
                                <a href="{{ route('admin.projects.folders.browse', [$project, $folder]) }}"
                                   class="font-semibold underline">
                                    {{ $folder->name }}
                                </a>
                            @else
                                <span class="font-semibold">{{ $folder->name }}</span>
                            @endif
                        </div>

                        @if (Route::has('admin.projects.folders.browse'))
                            <a href="{{ route('admin.projects.folders.browse', [$project, $folder]) }}"
                               class="text-sm underline text-gray-600">
                                Open
                            </a>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600 py-3">
                        This project has no folders yet.
                    </p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
