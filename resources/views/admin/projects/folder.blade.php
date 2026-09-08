<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $folder->name }}
        </h2>
    </x-slot>

    @php
        $isWritable = ! $project->isComplete();
    @endphp

    <div class="max-w-5xl mx-auto py-8 px-4">
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

        {{-- Breadcrumb trail --}}
        <nav class="text-sm text-gray-600 mb-6 flex flex-wrap items-center gap-1">
            <a href="{{ route('admin.projects.show', $project) }}" class="underline">
                {{ $project->name }}
            </a>

            @foreach ($breadcrumbs as $crumb)
                <span class="text-gray-400">/</span>

                @if ($loop->last)
                    <span class="font-semibold text-gray-900">{{ $crumb->name }}</span>
                @else
                    <a href="{{ route('admin.projects.folders.browse', [$project, $crumb]) }}" class="underline">
                        {{ $crumb->name }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="flex items-start justify-between gap-6 mb-6">
            <div>
                <h1 class="text-3xl font-bold">{{ $folder->name }}</h1>
                <p class="text-gray-600 mt-1">
                    {{ $subfolders->count() }} {{ Str::plural('folder', $subfolders->count()) }},
                    {{ $documents->count() }} {{ Str::plural('document', $documents->count()) }}
                </p>
            </div>

            @if ($folder->parent && Route::has('admin.projects.folders.browse'))
                <a href="{{ route('admin.projects.folders.browse', [$project, $folder->parent]) }}"
                   class="px-4 py-2 border border-gray-900 text-sm font-semibold rounded-none">
                    Up one level
                </a>
            @else
                <a href="{{ route('admin.projects.show', $project) }}"
                   class="px-4 py-2 border border-gray-900 text-sm font-semibold rounded-none">
                    Back to project
                </a>
            @endif
        </div>

        {{-- Folders --}}
        <section class="border border-gray-300 bg-white p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold">Folders</h2>

                @if ($isWritable && Route::has('admin.projects.folders.store'))
                    <button type="button"
                            onclick="document.getElementById('new-subfolder').classList.toggle('hidden')"
                            class="inline-flex px-4 py-2 border border-gray-900 text-sm font-semibold rounded-none">
                        New folder
                    </button>
                @endif
            </div>

            @if ($isWritable && Route::has('admin.projects.folders.store'))
                <form id="new-subfolder" method="POST"
                      action="{{ route('admin.projects.folders.store', $project) }}"
                      class="hidden mb-4 flex flex-col sm:flex-row gap-3">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $folder->id }}">
                    <input type="text" name="name" required placeholder="Folder name"
                           class="flex-1 border border-gray-400 px-3 py-2 rounded-none text-sm">
                    <button type="submit"
                            class="px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                        Create folder
                    </button>
                </form>
            @endif

            <div class="divide-y divide-gray-200 border-t border-gray-200">
                @forelse ($subfolders as $subfolder)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                            </svg>

                            @if (Route::has('admin.projects.folders.browse'))
                                <a href="{{ route('admin.projects.folders.browse', [$project, $subfolder]) }}"
                                   class="font-semibold underline">
                                    {{ $subfolder->name }}
                                </a>
                            @else
                                <span class="font-semibold">{{ $subfolder->name }}</span>
                            @endif
                        </div>

                        @if ($isWritable && Route::has('admin.projects.folders.destroy'))
                            <form method="POST"
                                  action="{{ route('admin.projects.folders.destroy', [$project, $subfolder]) }}"
                                  onsubmit="return confirm('Delete this folder and everything inside it?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs underline text-red-800">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600 py-3">No subfolders.</p>
                @endforelse
            </div>
        </section>

        {{-- Documents --}}
        <section class="border border-gray-300 bg-white p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Documents</h2>

            <div class="divide-y divide-gray-200 border-t border-gray-200">
                @forelse ($documents as $document)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">{{ $document->original_name }}</span>
                        </div>

                        <div class="flex items-center gap-4">
                            @if (Route::has('documents.serve'))
                                <a href="{{ route('documents.serve', $document) }}" class="text-sm underline">
                                    Download
                                </a>
                            @endif

                            @if ($isWritable && Route::has('admin.projects.documents.destroy'))
                                <form method="POST"
                                      action="{{ route('admin.projects.documents.destroy', [$project, $document]) }}"
                                      onsubmit="return confirm('Delete this document?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs underline text-red-800">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600 py-3">No documents in this folder.</p>
                @endforelse
            </div>
        </section>

        {{-- Upload --}}
        @if ($isWritable && Route::has('admin.projects.documents.store'))
            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Upload a document</h2>

                <form method="POST"
                      action="{{ route('admin.projects.documents.store', [$project, $folder]) }}"
                      enctype="multipart/form-data"
                      class="flex flex-col sm:flex-row gap-3">
                    @csrf

                    <input id="file" name="file" type="file"
                           class="flex-1 border border-gray-400 px-3 py-2 rounded-none text-sm" required>

                    <button type="submit"
                            class="px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                        Upload
                    </button>
                </form>
            </section>
        @endif
    </div>
</x-app-layout>
