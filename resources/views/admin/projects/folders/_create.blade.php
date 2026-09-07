{{--
    Folder create partial.

    Expects:
      - $project (App\Models\Project)
      - $folder  (App\Models\ProjectFolder|null) optional parent context; when
        provided the new folder is created as a subfolder of $folder.

    A top-level folder (no parent selected) exposes the per-role permission
    matrix; subfolders inherit their permissions from the top-level ancestor so
    the matrix is hidden when a parent is chosen.
--}}
@php
    $parentFolder = $folder ?? null;
    $topLevelFolders = $project->relationLoaded('topLevelFolders')
        ? $project->topLevelFolders
        : $project->topLevelFolders()->orderBy('sort_order')->get();
    $levels = ['read-write' => 'Read &amp; write', 'read-only' => 'Read only', 'no-access' => 'No access'];
@endphp

@if (Route::has('admin.projects.folders.store'))
    <section class="border border-gray-300 bg-white">
        <div class="p-4 border-b border-gray-300">
            <h2 class="text-lg font-semibold">
                Add folder
            </h2>

            <p class="text-sm text-gray-600 mt-1">
                Create a new top-level folder or a subfolder within an existing folder.
            </p>
        </div>

        <form method="POST"
              action="{{ route('admin.projects.folders.store', $project) }}"
              class="p-4 space-y-4"
              x-data="{ isTopLevel: {{ $parentFolder ? 'false' : 'true' }} }">
            @csrf

            <div>
                <label for="folder_name" class="block text-sm font-semibold mb-2">
                    Folder name
                </label>

                <input type="text"
                       id="folder_name"
                       name="name"
                       value="{{ old('name') }}"
                       required
                       maxlength="255"
                       class="block w-full border border-gray-400 px-4 py-3 rounded-none text-sm">
            </div>

            <div>
                <label for="folder_parent_id" class="block text-sm font-semibold mb-2">
                    Parent folder
                </label>

                @if ($parentFolder)
                    <input type="hidden" name="parent_id" value="{{ $parentFolder->id }}">
                    <p class="text-sm text-gray-700 border border-gray-300 bg-gray-50 px-4 py-3">
                        {{ $parentFolder->name }}
                    </p>
                @else
                    <select id="folder_parent_id"
                            name="parent_id"
                            class="block w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                            @change="isTopLevel = ($event.target.value === '')">
                        <option value="">— Top-level folder —</option>
                        @foreach ($topLevelFolders as $topLevel)
                            <option value="{{ $topLevel->id }}" @selected(old('parent_id') == $topLevel->id)>
                                {{ $topLevel->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Leave as top-level to set per-role permissions, or choose a parent to create a subfolder.
                    </p>
                @endif
            </div>

            @unless ($parentFolder)
                <fieldset class="border border-gray-300 p-4 space-y-4" x-show="isTopLevel" x-cloak>
                    <legend class="text-sm font-semibold px-2">
                        Permissions
                    </legend>

                    @foreach (['admin' => 'Admin', 'contractor' => 'Contractor', 'customer' => 'Customer'] as $role => $roleLabel)
                        <div class="grid grid-cols-[120px_1fr] items-center gap-3">
                            <label for="permissions_{{ $role }}" class="text-sm font-semibold">
                                {{ $roleLabel }}
                            </label>

                            <select id="permissions_{{ $role }}"
                                    name="permissions[{{ $role }}]"
                                    class="block w-full border border-gray-400 px-4 py-2 rounded-none text-sm">
                                @foreach ($levels as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected(old("permissions.$role", $role === 'admin' ? 'read-write' : 'no-access') === $value)>
                                        {!! $label !!}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </fieldset>
            @endunless

            <div>
                <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Create folder
                </button>
            </div>
        </form>
    </section>
@endif
