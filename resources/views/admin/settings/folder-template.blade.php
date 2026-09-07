<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Folder template
            </h2>

            <p class="text-sm text-gray-600 mt-1">
                Edit the master set of folders created for every new project library.
            </p>
        </div>
    </x-slot>

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

                <section class="border border-gray-300 bg-white">
                    <div class="p-6 border-b border-gray-300">
                        <h1 class="text-2xl font-bold">
                            Master folder template
                        </h1>

                        <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                            These folders, their subfolders and per-role permissions are copied into
                            every new project. Folders are created in the order shown. Put one
                            subfolder name per line.
                        </p>
                    </div>

                    @php
                        $levels = ['read-write' => 'Read & write', 'read-only' => 'Read only', 'no-access' => 'No access'];
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
                        // Provide a couple of extra blank rows as a JS-free "add folder" affordance.
                        $blankRows = 2;
                        $totalRows = count($rows) + $blankRows;
                    @endphp

                    <form method="POST"
                          action="{{ route('admin.settings.folder-template.update') }}"
                          class="p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        @for ($i = 0; $i < $totalRows; $i++)
                            @php
                                $row = $rows[$i] ?? ['name' => '', 'subfolders' => [], 'permissions' => ['admin' => 'read-write', 'contractor' => 'no-access', 'customer' => 'no-access']];
                                $subfoldersText = is_array($row['subfolders'] ?? null)
                                    ? implode("\n", $row['subfolders'])
                                    : '';
                            @endphp

                            <fieldset class="border border-gray-300 bg-white">
                                <div class="p-4 border-b border-gray-300 flex items-center justify-between">
                                    <legend class="text-sm font-semibold px-2">
                                        Folder {{ $i + 1 }}
                                    </legend>
                                    @if ($i >= count($rows))
                                        <span class="text-xs text-gray-500">Blank row — leave empty to skip</span>
                                    @endif
                                </div>

                                <div class="p-4 space-y-4">
                                    <div>
                                        <label for="folder_{{ $i }}_name" class="block text-sm font-semibold mb-2">
                                            Folder name
                                        </label>

                                        <input type="text"
                                               id="folder_{{ $i }}_name"
                                               name="folders[{{ $i }}][name]"
                                               value="{{ $row['name'] ?? '' }}"
                                               maxlength="255"
                                               class="block w-full border border-gray-400 px-4 py-3 rounded-none text-sm">
                                    </div>

                                    <div>
                                        <label for="folder_{{ $i }}_subfolders" class="block text-sm font-semibold mb-2">
                                            Subfolders
                                        </label>

                                        {{-- One subfolder per line. Rendered as repeated inputs on submit
                                             via a small parse step so field names match
                                             folders[i][subfolders][]. --}}
                                        <textarea id="folder_{{ $i }}_subfolders"
                                                  rows="4"
                                                  data-subfolders-source="{{ $i }}"
                                                  placeholder="One subfolder name per line"
                                                  class="block w-full border border-gray-400 px-4 py-3 rounded-none text-sm">{{ $subfoldersText }}</textarea>

                                        <div data-subfolders-target="{{ $i }}">
                                            @foreach (($row['subfolders'] ?? []) as $subfolder)
                                                <input type="hidden"
                                                       name="folders[{{ $i }}][subfolders][]"
                                                       value="{{ $subfolder }}">
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        @foreach (['admin' => 'Admin', 'contractor' => 'Contractor', 'customer' => 'Customer'] as $role => $roleLabel)
                                            <div>
                                                <label for="folder_{{ $i }}_perm_{{ $role }}" class="block text-sm font-semibold mb-2">
                                                    {{ $roleLabel }}
                                                </label>

                                                <select id="folder_{{ $i }}_perm_{{ $role }}"
                                                        name="folders[{{ $i }}][permissions][{{ $role }}]"
                                                        class="block w-full border border-gray-400 px-4 py-2 rounded-none text-sm">
                                                    @foreach ($levels as $value => $label)
                                                        <option value="{{ $value }}"
                                                            @selected(($row['permissions'][$role] ?? null) === $value)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </fieldset>
                        @endfor

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                                Save folder template
                            </button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>

    {{--
        Convert the per-folder subfolders textarea (one name per line) into the
        repeated hidden inputs the controller expects
        (folders[i][subfolders][]). Progressive enhancement: if JS is disabled,
        the pre-rendered hidden inputs preserve the existing subfolders.
    --}}
    <script>
        document.querySelectorAll('form[action*="folder-template"]').forEach(function (form) {
            form.addEventListener('submit', function () {
                form.querySelectorAll('textarea[data-subfolders-source]').forEach(function (textarea) {
                    var index = textarea.getAttribute('data-subfolders-source');
                    var target = form.querySelector('[data-subfolders-target="' + index + '"]');
                    if (!target) {
                        return;
                    }
                    target.innerHTML = '';
                    textarea.value.split('\n').map(function (line) {
                        return line.trim();
                    }).filter(function (line) {
                        return line.length > 0;
                    }).forEach(function (line) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'folders[' + index + '][subfolders][]';
                        input.value = line;
                        target.appendChild(input);
                    });
                });
            });
        });
    </script>
</x-app-layout>
