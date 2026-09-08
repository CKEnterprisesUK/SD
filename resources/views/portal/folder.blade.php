<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $folder->name }}
        </h2>
    </x-slot>

    @php
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

        // Whether a document can be previewed inline (images + PDFs). Mirrors
        // DocumentServeController::isInlineViewable — HTML/SVG never inline.
        $isViewable = function ($mime) {
            $mime = (string) $mime;
            if ($mime === 'application/pdf') {
                return true;
            }
            return str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml';
        };

        $totalItems = $subfolders->count() + $documents->count();

        $libraryRoute = Route::has('projects.library') ? route('projects.library', $project) : null;
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
            @if ($libraryRoute)
                <a href="{{ $libraryRoute }}" class="hover:text-gray-900 hover:underline">
                    {{ $project->name }}
                </a>
            @else
                <span class="text-gray-900">{{ $project->name }}</span>
            @endif

            <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-semibold text-gray-900">{{ $folder->name }}</span>
        </nav>

        {{-- Explorer panel --}}
        <div class="border border-gray-300 bg-white">
            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    @php
                        $upHref = ($folder->parent && Route::has('projects.folders.show'))
                            ? route('projects.folders.show', [$project, $folder->parent])
                            : $libraryRoute;
                    @endphp

                    @if ($upHref)
                        <a href="{{ $upHref }}"
                           title="{{ $folder->parent ? 'Up one level' : 'Back to library' }}"
                           class="inline-flex items-center justify-center w-9 h-9 border border-gray-300 text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
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

                @can('upload', [App\Models\ProjectDocument::class, $folder])
                    @if (Route::has('admin.projects.documents.store'))
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    onclick="document.getElementById('file-input').click()"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-black text-white text-sm font-medium hover:bg-gray-800">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 20h16" />
                                </svg>
                                Upload
                            </button>
                        </div>
                    @endif
                @endcan
            </div>

            {{-- Hidden upload form: file picker auto-submits --}}
            @can('upload', [App\Models\ProjectDocument::class, $folder])
                @if (Route::has('admin.projects.documents.store'))
                    <form id="upload-form" method="POST"
                          action="{{ route('admin.projects.documents.store', [$project, $folder]) }}"
                          enctype="multipart/form-data" class="hidden">
                        @csrf
                        <input id="file-input" name="file" type="file"
                               onchange="if (this.files.length) document.getElementById('upload-form').submit();">
                    </form>
                @endif
            @endcan

            {{-- Column header --}}
            <div class="hidden sm:flex items-center gap-3 px-4 py-2 border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <span class="flex-1">Name</span>
                <span class="w-32">Type</span>
                <span class="w-20 text-right">Size</span>
                <span class="w-28 text-right">Modified</span>
                <span class="w-16 text-right">Actions</span>
            </div>

            {{-- Rows: folders first, then files --}}
            <div class="divide-y divide-gray-100">
                @foreach ($subfolders as $subfolder)
                    <div class="group flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50/60">
                        <a href="{{ Route::has('projects.folders.show') ? route('projects.folders.show', [$project, $subfolder]) : '#' }}"
                           class="flex flex-1 items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                            </svg>
                            <span class="font-medium text-gray-900 truncate group-hover:text-blue-700">{{ $subfolder->name }}</span>
                        </a>

                        <span class="hidden sm:block w-32 text-sm text-gray-500">Folder</span>
                        <span class="hidden sm:block w-20 text-right text-sm text-gray-400">—</span>
                        <span class="hidden sm:block w-28 text-right text-sm text-gray-500">{{ optional($subfolder->updated_at)->format('d M Y') ?? '—' }}</span>
                        <span class="hidden sm:block w-16"></span>
                    </div>
                @endforeach

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

                        <div class="w-16 flex justify-end items-center gap-2">
                            @if (Route::has('documents.serve') && $isViewable($document->mime_type))
                                <a href="{{ route('documents.serve', $document) }}" target="_blank" rel="noopener" title="View in browser"
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
                        <p class="mt-1 text-sm text-gray-500">Nothing has been added here yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
