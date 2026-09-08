<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Folder template
            </h2>

            <p class="text-sm text-gray-600 mt-1">
                Set up the folders every new project starts with.
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

        // Map stored folders by name so saved folders can show a shared-document panel.
        $savedByName = $folders->keyBy('name');
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

                {{-- Intro / help --}}
                <section class="border border-gray-300 bg-white">
                    <div class="p-6">
                        <h1 class="text-2xl font-bold">Master folder template</h1>
                        <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                            These folders and their permissions are copied into every new project, in the
                            order shown. Drag order is set by the arrows on each folder. You can attach
                            <span class="font-semibold">shared documents</span> (like an insurance
                            certificate) to a folder — the file is stored once and shown in every project's
                            copy of that folder, without being uploaded again and again.
                        </p>
                    </div>
                </section>

                {{-- Folder editor (Alpine-driven, degrades to the pre-rendered rows) --}}
                <form method="POST"
                      action="{{ route('admin.settings.folder-template.update') }}"
                      x-data="folderTemplateEditor(@js($seed))"
                      class="space-y-4">
                    @csrf
                    @method('PUT')

                    <template x-for="(folder, index) in folders" :key="index">
                        <section class="border border-gray-300 bg-white">
                            {{-- Card header --}}
                            <div class="p-4 border-b border-gray-300 flex items-center justify-between gap-3 bg-gray-50">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="inline-flex items-center justify-center w-7 h-7 bg-black text-white text-xs font-bold shrink-0"
                                          x-text="index + 1"></span>
                                    <input type="text"
                                           :name="`folders[${index}][name]`"
                                           x-model="folder.name"
                                           maxlength="255"
                                           placeholder="Folder name"
                                           class="w-full sm:w-80 border border-gray-400 px-3 py-2 rounded-none text-sm font-semibold">
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <button type="button"
                                            @click="move(index, -1)"
                                            :disabled="index === 0"
                                            class="w-8 h-8 border border-gray-400 text-sm disabled:opacity-30 hover:bg-gray-100"
                                            title="Move up">&uarr;</button>
                                    <button type="button"
                                            @click="move(index, 1)"
                                            :disabled="index === folders.length - 1"
                                            class="w-8 h-8 border border-gray-400 text-sm disabled:opacity-30 hover:bg-gray-100"
                                            title="Move down">&darr;</button>
                                    <button type="button"
                                            @click="removeFolder(index)"
                                            class="w-8 h-8 border border-red-400 text-red-700 text-sm hover:bg-red-50"
                                            title="Remove folder">&times;</button>
                                </div>
                            </div>

                            <div class="p-4 space-y-5">
                                {{-- Subfolders as chips --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Subfolders</label>

                                    <div class="flex flex-wrap gap-2 mb-2" x-show="folder.subfolders.length">
                                        <template x-for="(sub, subIndex) in folder.subfolders" :key="subIndex">
                                            <span class="inline-flex items-center gap-2 border border-gray-400 bg-gray-50 pl-3 pr-2 py-1 text-sm">
                                                <input type="hidden"
                                                       :name="`folders[${index}][subfolders][]`"
                                                       :value="sub">
                                                <span x-text="sub"></span>
                                                <button type="button"
                                                        @click="removeSub(index, subIndex)"
                                                        class="text-gray-500 hover:text-red-700"
                                                        title="Remove subfolder">&times;</button>
                                            </span>
                                        </template>
                                    </div>

                                    <div class="flex gap-2">
                                        <input type="text"
                                               x-model="folder._newSub"
                                               @keydown.enter.prevent="addSub(index)"
                                               placeholder="Add a subfolder and press Enter"
                                               class="flex-1 border border-gray-400 px-3 py-2 rounded-none text-sm">
                                        <button type="button"
                                                @click="addSub(index)"
                                                class="px-4 py-2 border border-gray-800 text-sm font-semibold hover:bg-gray-100">
                                            Add
                                        </button>
                                    </div>
                                </div>

                                {{-- Permissions --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Who can access this folder</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        @foreach (['admin' => 'Admin', 'contractor' => 'Contractor', 'customer' => 'Customer'] as $role => $roleLabel)
                                            <div>
                                                <span class="block text-xs text-gray-600 mb-1">{{ $roleLabel }}</span>
                                                <select :name="`folders[${index}][permissions][{{ $role }}]`"
                                                        x-model="folder.permissions.{{ $role }}"
                                                        class="block w-full border border-gray-400 px-3 py-2 rounded-none text-sm">
                                                    @foreach ($levelLabels as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Shared documents: only for folders already saved (need a template id) --}}
                                <div x-show="folder.name" class="border-t border-gray-200 pt-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-sm font-semibold">Shared documents</label>
                                    </div>

                                    @foreach ($savedByName as $savedName => $savedFolder)
                                        <div x-show="folder.name === @js($savedName)" class="space-y-2">
                                            @php $docs = $sharedDocuments->get($savedName, collect()); @endphp

                                            @if ($docs->isNotEmpty())
                                                <ul class="border border-gray-300 divide-y divide-gray-200">
                                                    @foreach ($docs as $doc)
                                                        <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                                            <span class="min-w-0 truncate">
                                                                {{ $doc->original_name }}
                                                                @if ($doc->is_locked)
                                                                    <span class="ml-2 inline-block text-xs bg-gray-800 text-white px-2 py-0.5">Locked</span>
                                                                @endif
                                                            </span>
                                                            <form method="POST"
                                                                  action="{{ route('admin.settings.folder-template.shared-documents.destroy', $doc) }}"
                                                                  onsubmit="return confirm('Remove this shared document from all projects?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-700 hover:underline text-xs">Remove</button>
                                                            </form>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-xs text-gray-500">No shared documents attached to this folder yet.</p>
                                            @endif

                                            {{-- Upload form (separate from the main template form) --}}
                                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                                <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                                    <input type="checkbox" form="shared-upload-{{ $savedFolder->id }}" name="locked" value="1" checked>
                                                    Lock (cannot be deleted from projects)
                                                </label>
                                                <input type="file" form="shared-upload-{{ $savedFolder->id }}" name="file" required
                                                       class="text-xs">
                                                <button type="submit" form="shared-upload-{{ $savedFolder->id }}"
                                                        class="px-3 py-2 border border-gray-800 text-xs font-semibold hover:bg-gray-100">
                                                    Upload shared document
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach

                                    <p x-show="!isSaved(folder.name)" class="text-xs text-gray-500">
                                        Save the template first to attach shared documents to this folder.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </template>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button"
                                @click="addFolder()"
                                class="px-4 py-3 border border-gray-800 text-sm font-semibold hover:bg-gray-100">
                            + Add folder
                        </button>

                        <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                            Save folder template
                        </button>
                    </div>
                </form>

                {{-- Standalone upload forms, referenced by the buttons above via form="" so
                     the multipart upload posts to its own route, not the template form. --}}
                @foreach ($savedByName as $savedName => $savedFolder)
                    <form id="shared-upload-{{ $savedFolder->id }}"
                          method="POST"
                          enctype="multipart/form-data"
                          action="{{ route('admin.settings.folder-template.shared-documents.store', $savedFolder) }}"
                          class="hidden">
                        @csrf
                    </form>
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
