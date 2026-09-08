<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $folder->name }}
        </h2>
    </x-slot>

    @php
        $isWritable = ! $project->isComplete();

        $formatBytes = function ($bytes) {
            if ($bytes === null) {
                return '—';
            }
            $bytes = (int) $bytes;
            if ($bytes < 1024) {
                return $bytes . ' B';
            }
            $units = ['KB', 'MB', 'GB', 'TB'];
            $i = -1;
            do {
                $bytes /= 1024;
                $i++;
            } while ($bytes >= 1024 && $i < count($units) - 1);

            return round($bytes, $bytes < 10 ? 1 : 0) . ' ' . $units[$i];
        };

        $fileKind = function ($mime, $name) {
            $ext = strtoupper(pathinfo($name, PATHINFO_EXTENSION) ?: '');
            if ($ext !== '') {
                return $ext . ' file';
            }
            return $mime ?: 'File';
        };

        // Whether a document can be previewed inline (images + PDFs).
        $isViewable = function ($mime) {
            $mime = (string) $mime;
            if ($mime === 'application/pdf') {
                return true;
            }
            return str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml';
        };

        // Colour-coded permission badge for a resolved access level.
        // Full class strings are written as literals (not concatenated) so
        // Tailwind's JIT scanner can see and compile them.
        $permissionBadge = function (?string $level) {
            return match ($level) {
                'read-write' => '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 border-green-300">Read / write</span>',
                'read-only' => '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 border-amber-300">Read only</span>',
                'no-access' => '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 border-gray-300">No access</span>',
                default => '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium bg-gray-50 text-gray-400 border-gray-200">—</span>',
            };
        };

        // The current folder's resolved access level applies to the documents
        // inside it (documents inherit their folder's permission).
        $folderLevel = $folderPermissions[$folder->id] ?? null;

        $totalItems = $subfolders->count() + $documents->count();
    @endphp

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

        {{-- Breadcrumb trail --}}
        <nav class="text-sm text-gray-600 mb-4 flex flex-wrap items-center gap-1">
            <a href="{{ route('admin.projects.show', $project) }}" class="hover:text-gray-900 hover:underline">
                {{ $project->name }}
            </a>

            @foreach ($breadcrumbs as $crumb)
                <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>

                @if ($loop->last)
                    <span class="font-semibold text-gray-900">{{ $crumb->name }}</span>
                @else
                    <a href="{{ route('admin.projects.folders.browse', [$project, $crumb]) }}" class="hover:text-gray-900 hover:underline">
                        {{ $crumb->name }}
                    </a>
                @endif
            @endforeach
        </nav>

        {{-- Explorer panel --}}
        <div class="border border-gray-300 bg-white">
            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    @if ($folder->parent && Route::has('admin.projects.folders.browse'))
                        <a href="{{ route('admin.projects.folders.browse', [$project, $folder->parent]) }}"
                           title="Up one level"
                           class="inline-flex items-center justify-center w-9 h-9 border border-gray-300 text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('admin.projects.show', $project) }}"
                           title="Back to project"
                           class="inline-flex items-center justify-center w-9 h-9 border border-gray-300 text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18" />
                            </svg>
                        </a>
                    @endif

                    <div class="min-w-0">
                        <h1 class="text-lg font-semibold text-gray-900 truncate">{{ $folder->name }}</h1>
                        <p class="text-xs text-gray-500">
                            {{ $subfolders->count() }} {{ Str::plural('folder', $subfolders->count()) }} ·
                            {{ $documents->count() }} {{ Str::plural('file', $documents->count()) }}
                        </p>
                    </div>
                </div>

                @if ($isWritable)
                    <div class="flex items-center gap-2">
                        @if (Route::has('admin.projects.folders.store'))
                            <button type="button"
                                    onclick="document.getElementById('new-subfolder').classList.toggle('hidden')"
                                    class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 11v4M10 13h4" />
                                </svg>
                                New folder
                            </button>
                        @endif

                        @if (Route::has('admin.projects.documents.store'))
                            <button type="button"
                                    onclick="document.getElementById('file-input').click()"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-black text-white text-sm font-medium hover:bg-gray-800">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 20h16" />
                                </svg>
                                Upload
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Inline new-folder form --}}
            @if ($isWritable && Route::has('admin.projects.folders.store'))
                <form id="new-subfolder" method="POST"
                      action="{{ route('admin.projects.folders.store', $project) }}"
                      class="hidden border-b border-gray-200 bg-gray-50 px-4 py-3 flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $folder->id }}">
                    <input type="text" name="name" required placeholder="Folder name" autofocus
                           class="flex-1 border border-gray-300 px-3 py-2 rounded-none text-sm">
                    <button type="submit"
                            class="px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                        Create
                    </button>
                    <button type="button"
                            onclick="document.getElementById('new-subfolder').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 rounded-none hover:bg-gray-100">
                        Cancel
                    </button>
                </form>
            @endif

            {{-- Hidden upload form: file picker auto-submits --}}
            @if ($isWritable && Route::has('admin.projects.documents.store'))
                <form id="upload-form" method="POST"
                      action="{{ route('admin.projects.documents.store', [$project, $folder]) }}"
                      enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input id="file-input" name="file" type="file"
                           onchange="if (this.files.length) document.getElementById('upload-form').submit();">
                </form>
            @endif

            {{-- Column header --}}
            <div class="hidden sm:flex items-center gap-3 px-4 py-2 border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <span class="flex-1">Name</span>
                <span class="w-32">Type</span>
                <span class="w-20 text-right">Size</span>
                <span class="w-28 text-right">Modified</span>
                <span class="w-28">Access</span>
                <span class="w-16 text-right">Actions</span>
            </div>

            {{-- Rows: folders first, then files --}}
            <div class="divide-y divide-gray-100">
                @forelse ($subfolders as $subfolder)
                    <div class="group flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50/60">
                        <a href="{{ Route::has('admin.projects.folders.browse') ? route('admin.projects.folders.browse', [$project, $subfolder]) : '#' }}"
                           class="flex flex-1 items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                            </svg>
                            <span class="font-medium text-gray-900 truncate group-hover:text-blue-700">{{ $subfolder->name }}</span>
                        </a>

                        <span class="hidden sm:block w-32 text-sm text-gray-500">Folder</span>
                        <span class="hidden sm:block w-20 text-right text-sm text-gray-400">—</span>
                        <span class="hidden sm:block w-28 text-right text-sm text-gray-500">{{ optional($subfolder->updated_at)->format('d M Y') ?? '—' }}</span>
                        <span class="hidden sm:block w-28">{!! $permissionBadge($folderPermissions[$subfolder->id] ?? null) !!}</span>

                        <div class="w-16 flex justify-end">
                            @if ($isWritable && Route::has('admin.projects.folders.destroy'))
                                <form method="POST"
                                      action="{{ route('admin.projects.folders.destroy', [$project, $subfolder]) }}"
                                      onsubmit="return confirm('Delete this folder and everything inside it?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete folder"
                                            class="text-gray-400 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                @endforelse

                @foreach ($documents as $document)
                    <div class="group flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50/60">
                        <div class="flex flex-1 items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 3h6l4 4v13a1 1 0 01-1 1H8a1 1 0 01-1-1V4a1 1 0 011-1z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v4h4" />
                            </svg>
                            @if (Route::has('documents.serve'))
                                <a href="{{ route('documents.serve', $document) }}"
                                   @if ($isViewable($document->mime_type)) target="_blank" rel="noopener" @endif
                                   class="text-gray-900 truncate hover:text-blue-700 hover:underline">{{ $document->original_name }}</a>
                            @else
                                <span class="text-gray-900 truncate">{{ $document->original_name }}</span>
                            @endif
                        </div>

                        <span class="hidden sm:block w-32 text-sm text-gray-500 truncate">{{ $fileKind($document->mime_type, $document->original_name) }}</span>
                        <span class="hidden sm:block w-20 text-right text-sm text-gray-500">{{ $formatBytes($document->size_bytes) }}</span>
                        <span class="hidden sm:block w-28 text-right text-sm text-gray-500">{{ optional($document->created_at)->format('d M Y') ?? '—' }}</span>
                        <span class="hidden sm:block w-28">{!! $permissionBadge($folderLevel) !!}</span>

                        <div class="w-16 flex justify-end items-center gap-2">
                            @if (Route::has('documents.serve') && $isViewable($document->mime_type))
                                <a href="{{ route('documents.serve', $document) }}" target="_blank" rel="noopener" title="View"
                                   class="text-gray-400 hover:text-gray-900 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S5.5 5.5 12 5.5 21.5 12 21.5 12 18.5 18.5 12 18.5 2.5 12 2.5 12z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </a>
                            @endif

                            @if (Route::has('documents.serve'))
                                <a href="{{ route('documents.serve', ['document' => $document, 'download' => 1]) }}" title="Download"
                                   class="text-gray-400 hover:text-gray-900 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16" />
                                    </svg>
                                </a>
                            @endif

                            @if ($isWritable && Route::has('admin.projects.documents.destroy'))
                                <form method="POST"
                                      action="{{ route('admin.projects.documents.destroy', [$project, $document]) }}"
                                      onsubmit="return confirm('Delete this document?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete document"
                                            class="text-gray-400 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Empty state --}}
                @if ($totalItems === 0)
                    <div class="px-4 py-16 text-center">
                        <svg width="48" height="48" class="mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                        </svg>
                        <p class="mt-3 text-sm font-medium text-gray-700">This folder is empty</p>
                        <p class="mt-1 text-sm text-gray-500">
                            @if ($isWritable)
                                Create a folder or upload a file to get started.
                            @else
                                Nothing has been added here yet.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>

        @if ($isWritable && Route::has('admin.projects.documents.store'))
            <p class="mt-3 text-xs text-gray-400">Tip: use the Upload button above to add files to this folder.</p>
        @endif
    </div>
</x-app-layout>
