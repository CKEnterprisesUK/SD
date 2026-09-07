{{--
    Per-role permission matrix partial (top-level folders only).

    Expects:
      - $project (App\Models\Project)
      - $folder  (App\Models\ProjectFolder) the top-level folder whose
        permissions are being edited.

    Subfolders inherit their permissions from the top-level ancestor, so this
    form is only rendered for top-level folders.
--}}
@php
    $levels = ['read-write' => 'Read &amp; write', 'read-only' => 'Read only', 'no-access' => 'No access'];
    $currentLevels = [];
    if (isset($folder)) {
        $permissions = $folder->relationLoaded('permissions')
            ? $folder->permissions
            : $folder->permissions()->get();
        foreach ($permissions as $permission) {
            $currentLevels[$permission->role] = $permission->level;
        }
    }
    $defaults = ['admin' => 'read-write', 'contractor' => 'no-access', 'customer' => 'no-access'];
@endphp

@if (isset($folder) && $folder->is_top_level && Route::has('admin.projects.folders.permissions.update'))
    <section class="border border-gray-300 bg-white">
        <div class="p-4 border-b border-gray-300">
            <h2 class="text-lg font-semibold">
                Folder permissions
            </h2>

            <p class="text-sm text-gray-600 mt-1">
                Set the access level for each role. Subfolders inherit these permissions.
            </p>
        </div>

        <form method="POST"
              action="{{ route('admin.projects.folders.permissions.update', [$project, $folder]) }}"
              class="p-4 space-y-4">
            @csrf
            @method('PUT')

            @foreach (['admin' => 'Admin', 'contractor' => 'Contractor', 'customer' => 'Customer'] as $role => $roleLabel)
                @php
                    $selected = old("permissions.$role", $currentLevels[$role] ?? $defaults[$role]);
                @endphp
                <div class="grid grid-cols-[120px_1fr] items-center gap-3">
                    <label for="perm_{{ $folder->id }}_{{ $role }}" class="text-sm font-semibold">
                        {{ $roleLabel }}
                    </label>

                    <select id="perm_{{ $folder->id }}_{{ $role }}"
                            name="permissions[{{ $role }}]"
                            class="block w-full border border-gray-400 px-4 py-2 rounded-none text-sm">
                        @foreach ($levels as $value => $label)
                            <option value="{{ $value }}" @selected($selected === $value)>
                                {!! $label !!}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <div>
                <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Save permissions
                </button>
            </div>
        </form>
    </section>
@endif
