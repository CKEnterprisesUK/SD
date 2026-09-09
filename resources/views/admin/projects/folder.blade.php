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

        // Short human label for a single level.
        $levelLabel = function (?string $level) {
            return match ($level) {
                'read-write' => 'Read / write',
                'read-only' => 'Read only',
                'no-access' => 'No access',
                default => '—',
            };
        };

        // Colour-coded "who has access" card summarising the per-role levels
        // (admin / contractor / customer). Access means the role's level is not
        // "no-access". Colour follows the combination of roles that can see the
        // folder:
        //   All (contractor + client + admin) .......... green
        //   Client & admin (no contractor) ............. amber
        //   Admin & contractor (no client) ............. amber
        //   Admin only ................................. red
        // Full Tailwind class strings are written as literals so the JIT
        // scanner can compile them.
        $accessCard = function (?array $access) use ($levelLabel) {
            $roles = $access['roles'] ?? null;

            if ($roles === null) {
                return '<span class="inline-flex items-center rounded-md border px-2 py-1 text-xs font-medium bg-gray-50 text-gray-400 border-gray-200">—</span>';
            }

            $contractorHas = ($roles['contractor'] ?? 'no-access') !== 'no-access';
            $customerHas = ($roles['customer'] ?? 'no-access') !== 'no-access';

            if ($contractorHas && $customerHas) {
                $label = 'All access';
                $sub = 'Contractor, Client &amp; Admin';
                $classes = 'bg-green-100 text-green-800 border-green-300';
            } elseif ($customerHas && ! $contractorHas) {
                $label = 'Client &amp; Admin';
                $sub = 'No contractor access';
                $classes = 'bg-amber-100 text-amber-800 border-amber-300';
            } elseif ($contractorHas && ! $customerHas) {
                $label = 'Admin &amp; Contractor';
                $sub = 'No client access';
                $classes = 'bg-amber-100 text-amber-800 border-amber-300';
            } else {
                $label = 'Admin only';
                $sub = 'Restricted';
                $classes = 'bg-red-100 text-red-800 border-red-300';
            }

            $tooltip = 'Admin: '.$levelLabel($roles['admin'] ?? 'no-access')
                .' | Contractor: '.$levelLabel($roles['contractor'] ?? 'no-access')
                .' | Client: '.$levelLabel($roles['customer'] ?? 'no-access');

            return '<span title="'.$tooltip.'" class="inline-flex flex-col items-start rounded-md border px-2 py-1 text-xs font-medium leading-tight '.$classes.'">'
                .'<span>'.$label.'</span>'
                .'<span class="text-[10px] font-normal opacity-80">'.$sub.'</span>'
                .'</span>';
        };

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
                <span class="w-44">Access</span>
                <span class="w-40 text-right">Actions</span>
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
                        @php $subAccess = $folderAccess[$subfolder->id] ?? null; @endphp
                        <span class="hidden sm:block w-44">
                            @if ($isWritable && ($subAccess['is_top_level'] ?? false) && Route::has('admin.projects.folders.permissions.update'))
                                <button type="button"
                                        title="Change who can access this folder"
                                        data-access-folder="{{ $subfolder->id }}"
                                        data-access-name="{{ $subfolder->name }}"
                                        data-access-admin="{{ $subAccess['roles']['admin'] ?? 'read-write' }}"
                                        data-access-contractor="{{ $subAccess['roles']['contractor'] ?? 'no-access' }}"
                                        data-access-customer="{{ $subAccess['roles']['customer'] ?? 'no-access' }}"
                                        class="text-left hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-blue-400 rounded-md">
                                    {!! $accessCard($subAccess) !!}
                                </button>
                            @else
                                {!! $accessCard($subAccess) !!}
                            @endif
                        </span>

                        <div class="w-40 flex justify-end items-center gap-2">
                            @if ($isWritable && Route::has('admin.projects.folders.update'))
                                <button type="button" title="Rename folder"
                                        data-rename-folder="{{ $subfolder->id }}"
                                        data-rename-name="{{ $subfolder->name }}"
                                        class="text-gray-400 hover:text-gray-900 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            @endif

                            @if ($isWritable && Route::has('admin.projects.folders.move'))
                                <button type="button" title="Move folder"
                                        data-move-folder="{{ $subfolder->id }}"
                                        data-move-name="{{ $subfolder->name }}"
                                        class="text-gray-400 hover:text-gray-900 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14h6m0 0l-2-2m2 2l-2 2" />
                                    </svg>
                                </button>
                            @endif

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
                        <span class="hidden sm:block w-44">{!! $accessCard($folderAccess[$folder->id] ?? null) !!}</span>

                        <div class="w-40 flex justify-end items-center gap-2">
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

                            @if ($isWritable && ! $document->isLocked() && Route::has('admin.projects.documents.update'))
                                <button type="button" title="Rename file"
                                        data-rename-document="{{ $document->id }}"
                                        data-rename-name="{{ $document->original_name }}"
                                        class="text-gray-400 hover:text-gray-900 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            @endif

                            @if ($isWritable && ! $document->isLocked() && Route::has('admin.projects.documents.move'))
                                <button type="button" title="Move file"
                                        data-move-document="{{ $document->id }}"
                                        data-move-name="{{ $document->original_name }}"
                                        class="text-gray-400 hover:text-gray-900 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14h6m0 0l-2-2m2 2l-2 2" />
                                    </svg>
                                </button>
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

    @if ($isWritable)
        {{-- Rename / Move dialogs. A single dialog of each kind is reused for
             every row; JS rewrites the form action and prefilled values when a
             row's rename/move button is clicked. --}}

        {{-- Rename dialog --}}
        <div id="rename-dialog"
             class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
             role="dialog" aria-modal="true" aria-labelledby="rename-title">
            <div class="w-full max-w-md bg-white border border-gray-300 shadow-lg">
                <form id="rename-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 id="rename-title" class="text-base font-semibold text-gray-900">Rename</h2>
                    </div>
                    <div class="px-5 py-4">
                        <label for="rename-input" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input id="rename-input" type="text" required maxlength="255"
                               class="w-full border border-gray-300 px-3 py-2 rounded-none text-sm">
                    </div>
                    <div class="px-5 py-4 border-t border-gray-200 flex justify-end gap-2">
                        <button type="button" data-close-dialog
                                class="px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 rounded-none hover:bg-gray-100">Cancel</button>
                        <button type="submit"
                                class="px-4 py-2 bg-black text-white text-sm font-semibold rounded-none hover:bg-gray-800">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Move dialog --}}
        <div id="move-dialog"
             class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
             role="dialog" aria-modal="true" aria-labelledby="move-title">
            <div class="w-full max-w-md bg-white border border-gray-300 shadow-lg">
                <form id="move-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 id="move-title" class="text-base font-semibold text-gray-900">Move</h2>
                        <p id="move-subtitle" class="text-xs text-gray-500 mt-0.5"></p>
                    </div>
                    <div class="px-5 py-4">
                        <label for="move-select" class="block text-sm font-medium text-gray-700 mb-1">Destination folder</label>
                        <select id="move-select" name="destination_folder_id"
                                class="w-full border border-gray-300 px-3 py-2 rounded-none text-sm bg-white">
                            {{-- Top-level option only shown for folders (documents must live in a folder) --}}
                            <option id="move-top-level-option" value="">Top level</option>
                            @foreach ($moveTargets as $target)
                                <option value="{{ $target['id'] }}">{{ $target['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="px-5 py-4 border-t border-gray-200 flex justify-end gap-2">
                        <button type="button" data-close-dialog
                                class="px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 rounded-none hover:bg-gray-100">Cancel</button>
                        <button type="submit"
                                class="px-4 py-2 bg-black text-white text-sm font-semibold rounded-none hover:bg-gray-800">Move here</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Access (permissions) dialog. Reused for any top-level folder row;
             JS rewrites the form action and prefilled levels when an access
             card is clicked. Posts to the permissions.update route. --}}
        @if (Route::has('admin.projects.folders.permissions.update'))
            @php
                $accessLevels = ['read-write' => 'Read / write', 'read-only' => 'Read only', 'no-access' => 'No access'];
            @endphp
            <div id="access-dialog"
                 class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                 role="dialog" aria-modal="true" aria-labelledby="access-title">
                <div class="w-full max-w-md bg-white border border-gray-300 shadow-lg">
                    <form id="access-form" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="px-5 py-4 border-b border-gray-200">
                            <h2 id="access-title" class="text-base font-semibold text-gray-900">Folder access</h2>
                            <p id="access-subtitle" class="text-xs text-gray-500 mt-0.5"></p>
                        </div>
                        <div class="px-5 py-4 space-y-4">
                            <p class="text-xs text-gray-500">Choose the level of access each role has to this folder and everything inside it.</p>
                            @foreach (['admin' => 'Admin', 'contractor' => 'Contractor', 'customer' => 'Client'] as $role => $roleLabel)
                                <div class="grid grid-cols-[110px_1fr] items-center gap-3">
                                    <label for="access-{{ $role }}" class="text-sm font-semibold text-gray-800">{{ $roleLabel }}</label>
                                    <select id="access-{{ $role }}"
                                            name="permissions[{{ $role }}]"
                                            class="block w-full border border-gray-300 px-3 py-2 rounded-none text-sm bg-white">
                                        @foreach ($accessLevels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                        <div class="px-5 py-4 border-t border-gray-200 flex justify-end gap-2">
                            <button type="button" data-close-dialog
                                    class="px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 rounded-none hover:bg-gray-100">Cancel</button>
                            <button type="submit"
                                    class="px-4 py-2 bg-black text-white text-sm font-semibold rounded-none hover:bg-gray-800">Save access</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <script>
            (function () {
                const currentFolderId = @json($folder->id);

                const renameDialog = document.getElementById('rename-dialog');
                const renameForm = document.getElementById('rename-form');
                const renameInput = document.getElementById('rename-input');
                const renameTitle = document.getElementById('rename-title');

                const moveDialog = document.getElementById('move-dialog');
                const moveForm = document.getElementById('move-form');
                const moveSelect = document.getElementById('move-select');
                const moveTitle = document.getElementById('move-title');
                const moveSubtitle = document.getElementById('move-subtitle');
                const moveTopLevelOption = document.getElementById('move-top-level-option');

                const accessDialog = document.getElementById('access-dialog');
                const accessForm = document.getElementById('access-form');
                const accessSubtitle = document.getElementById('access-subtitle');
                const accessAdmin = document.getElementById('access-admin');
                const accessContractor = document.getElementById('access-contractor');
                const accessCustomer = document.getElementById('access-customer');

                // URL templates with a __ID__ placeholder swapped per row.
                const routes = {
                    folderRename: @json($isWritable && Route::has('admin.projects.folders.update') ? route('admin.projects.folders.update', [$project, '__ID__']) : null),
                    folderMove: @json($isWritable && Route::has('admin.projects.folders.move') ? route('admin.projects.folders.move', [$project, '__ID__']) : null),
                    documentRename: @json($isWritable && Route::has('admin.projects.documents.update') ? route('admin.projects.documents.update', [$project, '__ID__']) : null),
                    documentMove: @json($isWritable && Route::has('admin.projects.documents.move') ? route('admin.projects.documents.move', [$project, '__ID__']) : null),
                    folderAccess: @json($isWritable && Route::has('admin.projects.folders.permissions.update') ? route('admin.projects.folders.permissions.update', [$project, '__ID__']) : null),
                };

                function openDialog(el) { el.classList.remove('hidden'); }
                function closeDialog(el) { el.classList.add('hidden'); }

                function fillRoute(template, id) {
                    return template ? template.replace('__ID__', id) : null;
                }

                // --- Rename wiring ---
                document.querySelectorAll('[data-rename-folder]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const id = btn.getAttribute('data-rename-folder');
                        renameForm.action = fillRoute(routes.folderRename, id);
                        renameForm.dataset.field = 'name';
                        renameInput.name = 'name';
                        renameInput.value = btn.getAttribute('data-rename-name') || '';
                        renameTitle.textContent = 'Rename folder';
                        openDialog(renameDialog);
                        renameInput.focus();
                        renameInput.select();
                    });
                });

                document.querySelectorAll('[data-rename-document]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const id = btn.getAttribute('data-rename-document');
                        renameForm.action = fillRoute(routes.documentRename, id);
                        renameInput.name = 'original_name';
                        renameInput.value = btn.getAttribute('data-rename-name') || '';
                        renameTitle.textContent = 'Rename file';
                        openDialog(renameDialog);
                        renameInput.focus();
                        renameInput.select();
                    });
                });

                // --- Move wiring ---
                function prepareMoveSelect(disableId, allowTopLevel) {
                    moveTopLevelOption.hidden = !allowTopLevel;
                    moveTopLevelOption.disabled = !allowTopLevel;
                    // Re-enable everything, then disable the current location and,
                    // for folders, the folder itself (its own subtree is handled
                    // server-side as a hard guard).
                    Array.prototype.forEach.call(moveSelect.options, function (opt) {
                        opt.disabled = false;
                    });
                    Array.prototype.forEach.call(moveSelect.options, function (opt) {
                        if (disableId !== null && opt.value === String(disableId)) {
                            opt.disabled = true;
                        }
                    });
                    // Default selection: first enabled option.
                    for (let i = 0; i < moveSelect.options.length; i++) {
                        const opt = moveSelect.options[i];
                        if (!opt.hidden && !opt.disabled) { moveSelect.selectedIndex = i; break; }
                    }
                }

                document.querySelectorAll('[data-move-folder]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const id = btn.getAttribute('data-move-folder');
                        moveForm.action = fillRoute(routes.folderMove, id);
                        moveTitle.textContent = 'Move folder';
                        moveSubtitle.textContent = btn.getAttribute('data-move-name') || '';
                        // Folders can go to top level; disable moving into itself.
                        prepareMoveSelect(id, true);
                        openDialog(moveDialog);
                    });
                });

                document.querySelectorAll('[data-move-document]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const id = btn.getAttribute('data-move-document');
                        moveForm.action = fillRoute(routes.documentMove, id);
                        moveTitle.textContent = 'Move file';
                        moveSubtitle.textContent = btn.getAttribute('data-move-name') || '';
                        // Documents must live in a folder: no top-level option.
                        // Disable the folder it's already in (the current folder).
                        prepareMoveSelect(currentFolderId, false);
                        openDialog(moveDialog);
                    });
                });

                // --- Access (permissions) wiring ---
                if (accessDialog && accessForm) {
                    document.querySelectorAll('[data-access-folder]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            const id = btn.getAttribute('data-access-folder');
                            accessForm.action = fillRoute(routes.folderAccess, id);
                            accessSubtitle.textContent = btn.getAttribute('data-access-name') || '';
                            accessAdmin.value = btn.getAttribute('data-access-admin') || 'read-write';
                            accessContractor.value = btn.getAttribute('data-access-contractor') || 'no-access';
                            accessCustomer.value = btn.getAttribute('data-access-customer') || 'no-access';
                            openDialog(accessDialog);
                        });
                    });
                }

                // --- Close handlers ---
                const allDialogs = [renameDialog, moveDialog];
                if (accessDialog) allDialogs.push(accessDialog);

                document.querySelectorAll('[data-close-dialog]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        allDialogs.forEach(closeDialog);
                    });
                });

                allDialogs.forEach(function (dialog) {
                    dialog.addEventListener('click', function (e) {
                        if (e.target === dialog) closeDialog(dialog);
                    });
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        allDialogs.forEach(closeDialog);
                    }
                });
            })();
        </script>
    @endif
</x-app-layout>
