<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Folder template
            </h2>

            <p class="text-sm text-gray-600 mt-1">
                The folder library every new project starts with.
            </p>
        </div>
    </x-slot>

    @php
        $levelLabels = ['read-write' => 'Read & write', 'read-only' => 'Read only', 'no-access' => 'No access'];

        // Prefill from validated old input on error, otherwise from the stored template.
        $rows = old('folders');
        if ($rows === null) {
            $rows = $folders->map(function ($folder) {
                return [
                    'name' => $folder->name,
                    'subfolders' => $folder->subfolders ?? [],
                    'permissions' => [
                        'admin' => $folder->permissions['admin'] ?? 'read-write',
                        'contractor' => $folder->permissions['contractor'] ?? 'no-access',
                        'customer' => $folder->permissions['customer'] ?? 'no-access',
                    ],
                ];
            })->values()->all();
        }

        // Alpine seed: normalise every row into a consistent shape.
        $seed = collect($rows)->map(function ($row) {
            return [
                'name' => $row['name'] ?? '',
                'subfolders' => array_values($row['subfolders'] ?? []),
                'permissions' => [
                    'admin' => $row['permissions']['admin'] ?? 'read-write',
                    'contractor' => $row['permissions']['contractor'] ?? 'no-access',
                    'customer' => $row['permissions']['customer'] ?? 'no-access',
                ],
            ];
        })->values()->all();

        // Map stored folders by name so saved folders can show shared documents.
        $savedByName = $folders->keyBy('name');

        // Colour-coded permission badge, mirroring the projects explorer.
        $permissionBadge = function (?string $level) {
            return match ($level) {
                'read-write' => '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 border-green-300">Read / write</span>',
                'read-only' => '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 border-amber-300">Read only</span>',
                'no-access' => '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 border-gray-300">No access</span>',
                default => '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium bg-gray-50 text-gray-400 border-gray-200">—</span>',
            };
        };
    @endphp

    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6">
            @include('admin.settings.partials.sidebar')

            <main class="space-y-6">
                @if (session('status'))
                    <div class="border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                        <p class="font-semibold mb-2">There is a problem.</p>
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <h1 class="text-2xl font-bold">Master folder template</h1>
                    <p class="text-sm text-gray-600 mt-1 max-w-3xl">
                        This is the folder library copied into every new project. Files added to a
                        folder here are shared into every project's copy of that folder — stored once,
                        never re-uploaded.
                    </p>
                </div>

                {{-- Explorer-style template editor. Structure still saves to the folder_templates
                     JSON table via this form; shared-document uploads post to their own routes. --}}
                <form method="POST"
                      action="{{ route('admin.settings.folder-template.update') }}"
                      x-data="folderTemplateEditor(@js($seed))"
                      class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="border border-gray-300 bg-white">
                        {{-- Toolbar --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold text-gray-900">Template library</h2>
                                <p class="text-xs text-gray-500">
                                    <span x-text="folders.length"></span>
                                    top-level <span x-text="folders.length === 1 ? 'folder' : 'folders'"></span>
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                        @click="addFolder()"
                                        class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 11v4M10 13h4" />
                                    </svg>
                                    New folder
                                </button>

                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-black text-white text-sm font-semibold hover:bg-gray-800">
                                    Save template
                                </button>
                            </div>
                        </div>

                        {{-- Column header --}}
                        <div class="hidden sm:flex items-center gap-3 px-4 py-2 border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <span class="flex-1">Name</span>
                            <span class="w-28">Type</span>
                            <span class="w-72">Access (admin · contractor · customer)</span>
                            <span class="w-20 text-right">Actions</span>
                        </div>

                        {{-- Rows --}}
                        <div class="divide-y divide-gray-100">
                            <template x-for="(folder, index) in folders" :key="index">
                                <div>
                                    {{-- Top-level folder row --}}
                                    <div class="group flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50/60">
                                        <div class="flex flex-1 items-center gap-3 min-w-0">
                                            <svg class="w-5 h-5 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                            </svg>
                                            <input type="text"
                                                   :name="`folders[${index}][name]`"
                                                   x-model="folder.name"
                                                   maxlength="255"
                                                   placeholder="Folder name"
                                                   class="w-full sm:w-72 border border-transparent hover:border-gray-300 focus:border-gray-400 bg-transparent px-2 py-1 rounded-none text-sm font-semibold text-gray-900">
                                        </div>

                                        <span class="hidden sm:block w-28 text-sm text-gray-500">Folder</span>

                                        {{-- Inline permission selects --}}
                                        <div class="hidden sm:flex w-72 items-center gap-1">
                                            @foreach (['admin', 'contractor', 'customer'] as $role)
                                                <select :name="`folders[${index}][permissions][{{ $role }}]`"
                                                        x-model="folder.permissions.{{ $role }}"
                                                        title="{{ ucfirst($role) }}"
                                                        class="flex-1 min-w-0 border border-gray-300 px-1.5 py-1 rounded-none text-xs">
                                                    @foreach ($levelLabels as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            @endforeach
                                        </div>

                                        <div class="w-20 flex justify-end items-center gap-1">
                                            <button type="button"
                                                    @click="move(index, -1)"
                                                    :disabled="index === 0"
                                                    class="text-gray-400 hover:text-gray-900 disabled:opacity-25"
                                                    title="Move up">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                                            </button>
                                            <button type="button"
                                                    @click="move(index, 1)"
                                                    :disabled="index === folders.length - 1"
                                                    class="text-gray-400 hover:text-gray-900 disabled:opacity-25"
                                                    title="Move down">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                            <button type="button"
                                                    @click="removeFolder(index)"
                                                    class="text-gray-400 hover:text-red-700"
                                                    title="Remove folder">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" /></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Subfolder rows (indented, explorer style) --}}
                                    <template x-for="(sub, subIndex) in folder.subfolders" :key="subIndex">
                                        <div class="group flex items-center gap-3 pl-12 pr-4 py-2 hover:bg-blue-50/60 border-t border-gray-50">
                                            <input type="hidden" :name="`folders[${index}][subfolders][]`" :value="sub">
                                            <div class="flex flex-1 items-center gap-3 min-w-0">
                                                <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                                </svg>
                                                <span class="text-sm text-gray-800 truncate" x-text="sub"></span>
                                            </div>
                                            <span class="hidden sm:block w-28 text-sm text-gray-400">Subfolder</span>
                                            <span class="hidden sm:block w-72 text-xs text-gray-400">Inherits parent access</span>
                                            <div class="w-20 flex justify-end">
                                                <button type="button"
                                                        @click="removeSub(index, subIndex)"
                                                        class="text-gray-400 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity"
                                                        title="Remove subfolder">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" /></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Shared-document rows (only for saved folders; keyed by name) --}}
                                    @foreach ($savedByName as $savedName => $savedFolder)
                                        @php $docs = $sharedDocuments->get($savedName, collect()); @endphp
                                        <template x-if="folder.name === @js($savedName)">
                                            <div>
                                                @foreach ($docs as $doc)
                                                    <div class="group flex items-center gap-3 pl-12 pr-4 py-2 hover:bg-blue-50/60 border-t border-gray-50">
                                                        <div class="flex flex-1 items-center gap-3 min-w-0">
                                                            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 3h6l4 4v13a1 1 0 01-1 1H8a1 1 0 01-1-1V4a1 1 0 011-1z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v4h4" />
                                                            </svg>
                                                            <span class="text-sm text-gray-900 truncate">{{ $doc->original_name }}</span>
                                                            @if ($doc->is_locked)
                                                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 shrink-0" title="Cannot be deleted from projects">
                                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                                    Locked
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <span class="hidden sm:block w-28 text-sm text-gray-500">Shared file</span>
                                                        <span class="hidden sm:block w-72 text-xs text-gray-400">Shown in every project</span>
                                                        <div class="w-20 flex justify-end">
                                                            <button type="submit"
                                                                    form="shared-destroy-{{ $doc->id }}"
                                                                    onclick="return confirm('Remove this shared document from all projects?');"
                                                                    class="text-gray-400 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity"
                                                                    title="Remove shared document">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" /></svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach

                                                {{-- Add-subfolder + upload row for this saved folder --}}
                                                <div class="flex flex-wrap items-center gap-2 pl-12 pr-4 py-2 border-t border-gray-50 bg-gray-50/60">
                                                    <div class="flex items-center gap-2">
                                                        <input type="text"
                                                               x-model="folder._newSub"
                                                               @keydown.enter.prevent="addSub(index)"
                                                               placeholder="New subfolder"
                                                               class="border border-gray-300 px-2 py-1 rounded-none text-xs w-44">
                                                        <button type="button"
                                                                @click="addSub(index)"
                                                                class="px-2 py-1 border border-gray-300 text-xs font-medium hover:bg-white">
                                                            Add subfolder
                                                        </button>
                                                    </div>

                                                    <span class="text-gray-300">|</span>

                                                    <label class="inline-flex items-center gap-1 text-xs text-gray-600">
                                                        <input type="checkbox" form="shared-upload-{{ $savedFolder->id }}" name="locked" value="1" checked>
                                                        Lock
                                                    </label>
                                                    <input type="file" form="shared-upload-{{ $savedFolder->id }}" name="file" required class="text-xs">
                                                    <button type="submit" form="shared-upload-{{ $savedFolder->id }}"
                                                            class="inline-flex items-center gap-1 px-2 py-1 border border-gray-800 text-xs font-semibold hover:bg-white">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 20h16" /></svg>
                                                        Upload shared file
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    @endforeach

                                    {{-- For unsaved / renamed folders: subfolder add + a save hint (no upload yet) --}}
                                    <template x-if="!isSaved(folder.name)">
                                        <div class="flex flex-wrap items-center gap-2 pl-12 pr-4 py-2 border-t border-gray-50 bg-gray-50/60">
                                            <div class="flex items-center gap-2">
                                                <input type="text"
                                                       x-model="folder._newSub"
                                                       @keydown.enter.prevent="addSub(index)"
                                                       placeholder="New subfolder"
                                                       class="border border-gray-300 px-2 py-1 rounded-none text-xs w-44">
                                                <button type="button"
                                                        @click="addSub(index)"
                                                        class="px-2 py-1 border border-gray-300 text-xs font-medium hover:bg-white">
                                                    Add subfolder
                                                </button>
                                            </div>
                                            <span class="text-xs text-gray-500">Save the template to attach shared files to this folder.</span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Empty state --}}
                            <div x-show="folders.length === 0" class="px-4 py-16 text-center">
                                <svg width="48" height="48" class="mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                                </svg>
                                <p class="mt-3 text-sm font-medium text-gray-700">No template folders yet</p>
                                <p class="mt-1 text-sm text-gray-500">Use “New folder” to start building the template.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                            Save template
                        </button>
                        <span class="text-xs text-gray-400">Renaming, reordering and subfolders are saved with this button. File uploads save instantly.</span>
                    </div>
                </form>

                {{-- Standalone forms for shared-document upload/delete, referenced by rows above via
                     form="" so their multipart / delete requests post to their own routes, not the
                     template form. --}}
                @foreach ($savedByName as $savedName => $savedFolder)
                    <form id="shared-upload-{{ $savedFolder->id }}"
                          method="POST"
                          enctype="multipart/form-data"
                          action="{{ route('admin.settings.folder-template.shared-documents.store', $savedFolder) }}"
                          class="hidden">
                        @csrf
                    </form>

                    @foreach ($sharedDocuments->get($savedName, collect()) as $doc)
                        <form id="shared-destroy-{{ $doc->id }}"
                              method="POST"
                              action="{{ route('admin.settings.folder-template.shared-documents.destroy', $doc) }}"
                              class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                @endforeach
            </main>
        </div>
    </div>

    <script>
        function folderTemplateEditor(seed) {
            return {
                folders: (seed || []).map(function (f) {
                    return {
                        name: f.name || '',
                        subfolders: (f.subfolders || []).slice(),
                        permissions: {
                            admin: (f.permissions && f.permissions.admin) || 'read-write',
                            contractor: (f.permissions && f.permissions.contractor) || 'no-access',
                            customer: (f.permissions && f.permissions.customer) || 'no-access',
                        },
                        _newSub: '',
                    };
                }),
                savedNames: @js($savedByName->keys()->values()->all()),
                isSaved(name) {
                    return this.savedNames.indexOf(name) !== -1;
                },
                addFolder() {
                    this.folders.push({
                        name: '',
                        subfolders: [],
                        permissions: { admin: 'read-write', contractor: 'no-access', customer: 'no-access' },
                        _newSub: '',
                    });
                },
                removeFolder(index) {
                    this.folders.splice(index, 1);
                },
                move(index, delta) {
                    const target = index + delta;
                    if (target < 0 || target >= this.folders.length) return;
                    const moved = this.folders.splice(index, 1)[0];
                    this.folders.splice(target, 0, moved);
                },
                addSub(index) {
                    const val = (this.folders[index]._newSub || '').trim();
                    if (!val) return;
                    this.folders[index].subfolders.push(val);
                    this.folders[index]._newSub = '';
                },
                removeSub(index, subIndex) {
                    this.folders[index].subfolders.splice(subIndex, 1);
                },
            };
        }
    </script>
</x-app-layout>
