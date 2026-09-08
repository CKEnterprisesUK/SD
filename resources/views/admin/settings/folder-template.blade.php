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

        $savedByName = $folders->keyBy('name');
    @endphp

    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6">
            @include('admin.settings.partials.sidebar')

            <main class="space-y-6">
                @if (session('status'))
                    <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-semibold mb-1">There is a problem.</p>
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Master folder template</h1>
                        <p class="text-sm text-gray-600 mt-1 max-w-2xl">
                            These folders are created in every new project. Files you add to a folder are
                            shared into every project's copy — stored once, never re-uploaded.
                        </p>
                    </div>
                </div>

                <form method="POST"
                      action="{{ route('admin.settings.folder-template.update') }}"
                      x-data="folderTemplateEditor(@js($seed))"
                      class="space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Sticky action bar --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-gray-500">
                            <span class="font-semibold text-gray-700" x-text="folders.length"></span>
                            top-level <span x-text="folders.length === 1 ? 'folder' : 'folders'"></span>
                        </p>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="addFolder()"
                                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 11v4M10 13h4" /></svg>
                                New folder
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black">
                                Save template
                            </button>
                        </div>
                    </div>

                    {{-- One card per top-level folder --}}
                    <template x-for="(folder, index) in folders" :key="index">
                        <section class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                            {{-- Folder header --}}
                            <div class="flex items-center gap-3 px-4 py-3 bg-gray-50/80 border-b border-gray-200">
                                <svg class="w-5 h-5 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                </svg>
                                <input type="text"
                                       :name="`folders[${index}][name]`"
                                       x-model="folder.name"
                                       maxlength="255"
                                       placeholder="Folder name"
                                       class="flex-1 min-w-0 border-0 border-b border-transparent hover:border-gray-300 focus:border-gray-500 focus:ring-0 bg-transparent px-0 py-1 text-base font-semibold text-gray-900">

                                <div class="flex items-center gap-1 shrink-0">
                                    <button type="button" @click="move(index, -1)" :disabled="index === 0"
                                            class="rounded p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-700 disabled:opacity-25 disabled:hover:bg-transparent" title="Move up">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                                    </button>
                                    <button type="button" @click="move(index, 1)" :disabled="index === folders.length - 1"
                                            class="rounded p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-700 disabled:opacity-25 disabled:hover:bg-transparent" title="Move down">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                    <button type="button" @click="removeFolder(index)"
                                            class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600" title="Remove folder">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" /></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Permissions strip --}}
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3 border-b border-gray-100">
                                @foreach (['admin' => 'Admin', 'contractor' => 'Contractor', 'customer' => 'Customer'] as $role => $roleLabel)
                                    <label class="flex items-center gap-2 text-sm">
                                        <span class="text-gray-500 w-20">{{ $roleLabel }}</span>
                                        <select :name="`folders[${index}][permissions][{{ $role }}]`"
                                                x-model="folder.permissions.{{ $role }}"
                                                class="rounded-md border-gray-300 py-1.5 pl-2.5 pr-8 text-sm shadow-sm focus:border-gray-500 focus:ring-0">
                                            @foreach ($levelLabels as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endforeach
                            </div>

                            {{-- Contents: subfolders + shared files --}}
                            <div>
                                {{-- Subfolders --}}
                                <template x-for="(sub, subIndex) in folder.subfolders" :key="subIndex">
                                    <div class="group flex items-center gap-3 px-4 py-2 hover:bg-gray-50 border-b border-gray-50">
                                        <input type="hidden" :name="`folders[${index}][subfolders][]`" :value="sub">
                                        <span class="w-4"></span>
                                        <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                        </svg>
                                        <span class="flex-1 text-sm text-gray-800 truncate" x-text="sub"></span>
                                        <span class="text-xs text-gray-400 hidden sm:block">Subfolder</span>
                                        <button type="button" @click="removeSub(index, subIndex)"
                                                class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600" title="Remove subfolder">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" /></svg>
                                        </button>
                                    </div>
                                </template>

                                {{-- Shared files for this saved folder --}}
                                @foreach ($savedByName as $savedName => $savedFolder)
                                    @php $docs = $sharedDocuments->get($savedName, collect()); @endphp
                                    <template x-if="folder.name === @js($savedName)">
                                        <div>
                                            @foreach ($docs as $doc)
                                                <div class="group flex items-center gap-3 px-4 py-2 hover:bg-gray-50 border-b border-gray-50">
                                                    <span class="w-4"></span>
                                                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 3h6l4 4v13a1 1 0 01-1 1H8a1 1 0 01-1-1V4a1 1 0 011-1z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v4h4" />
                                                    </svg>
                                                    <span class="flex-1 text-sm text-gray-900 truncate">{{ $doc->original_name }}</span>

                                                    @if ($doc->is_locked)
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500" title="Cannot be deleted from within a project">
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                            Locked in projects
                                                        </span>
                                                    @else
                                                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-600">Shared</span>
                                                    @endif

                                                    {{-- Always-visible remove: settings can delete ANY file, even locked. --}}
                                                    <button type="submit"
                                                            form="shared-destroy-{{ $doc->id }}"
                                                            onclick="return confirm('Remove “{{ addslashes($doc->original_name) }}” from the template and all projects?');"
                                                            class="inline-flex items-center gap-1 rounded-md border border-gray-200 px-2 py-1 text-xs font-medium text-gray-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700"
                                                            title="Remove file">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" /></svg>
                                                        Remove
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </template>
                                @endforeach

                                {{-- Empty hint when a folder has no contents --}}
                                <template x-if="folder.subfolders.length === 0 && !hasFiles(folder.name)">
                                    <p class="px-4 py-3 text-xs text-gray-400">No subfolders or files yet.</p>
                                </template>
                            </div>

                            {{-- Footer: add subfolder + upload (upload only once folder is saved) --}}
                            <div class="flex flex-wrap items-center gap-3 px-4 py-3 bg-gray-50/60 border-t border-gray-100">
                                <div class="flex items-center gap-2">
                                    <input type="text"
                                           x-model="folder._newSub"
                                           @keydown.enter.prevent="addSub(index)"
                                           placeholder="Add subfolder"
                                           class="rounded-md border-gray-300 py-1.5 px-2.5 text-sm shadow-sm focus:border-gray-500 focus:ring-0 w-44">
                                    <button type="button" @click="addSub(index)"
                                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        Add
                                    </button>
                                </div>

                                <span class="hidden sm:block h-5 w-px bg-gray-200"></span>

                                {{-- Upload control per saved folder --}}
                                @foreach ($savedByName as $savedName => $savedFolder)
                                    <template x-if="folder.name === @js($savedName)">
                                        <div class="flex items-center gap-2">
                                            <label class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                                                <input type="checkbox" form="shared-upload-{{ $savedFolder->id }}" name="locked" value="1" checked
                                                       class="rounded border-gray-300">
                                                Lock in projects
                                            </label>
                                            <input type="file" form="shared-upload-{{ $savedFolder->id }}" name="file" required
                                                   class="text-sm text-gray-600 file:mr-2 file:rounded-md file:border file:border-gray-300 file:bg-white file:px-2.5 file:py-1 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-50">
                                            <button type="submit" form="shared-upload-{{ $savedFolder->id }}"
                                                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-800 bg-white px-3 py-1.5 text-sm font-semibold text-gray-800 hover:bg-gray-900 hover:text-white">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 20h16" /></svg>
                                                Upload file
                                            </button>
                                        </div>
                                    </template>
                                @endforeach

                                <template x-if="!isSaved(folder.name)">
                                    <span class="text-xs text-gray-400">Save the template to upload files to this folder.</span>
                                </template>
                            </div>
                        </section>
                    </template>

                    {{-- Empty state --}}
                    <div x-show="folders.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-16 text-center">
                        <svg width="48" height="48" class="mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" /></svg>
                        <p class="mt-3 text-sm font-medium text-gray-700">No template folders yet</p>
                        <p class="mt-1 text-sm text-gray-500">Use “New folder” to start building the template.</p>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-black">
                            Save template
                        </button>
                        <span class="text-xs text-gray-400">Names, order and subfolders save with this button. File uploads and removals save instantly.</span>
                    </div>
                </form>

                {{-- Standalone forms for shared-file upload/delete (posted to their own routes). --}}
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
                foldersWithFiles: @js($sharedDocuments->filter(fn ($d) => $d->isNotEmpty())->keys()->values()->all()),
                isSaved(name) {
                    return this.savedNames.indexOf(name) !== -1;
                },
                hasFiles(name) {
                    return this.foldersWithFiles.indexOf(name) !== -1;
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
