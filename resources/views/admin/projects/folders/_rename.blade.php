{{--
    Folder rename partial.

    Expects:
      - $project (App\Models\Project)
      - $folder  (App\Models\ProjectFolder) the folder being renamed.
--}}
@if (isset($folder) && Route::has('admin.projects.folders.update'))
    <section class="border border-gray-300 bg-white">
        <div class="p-4 border-b border-gray-300">
            <h2 class="text-lg font-semibold">
                Rename folder
            </h2>
        </div>

        <form method="POST"
              action="{{ route('admin.projects.folders.update', [$project, $folder]) }}"
              class="p-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="rename_folder_{{ $folder->id }}" class="block text-sm font-semibold mb-2">
                    Folder name
                </label>

                <input type="text"
                       id="rename_folder_{{ $folder->id }}"
                       name="name"
                       value="{{ old('name', $folder->name) }}"
                       required
                       maxlength="255"
                       class="block w-full border border-gray-400 px-4 py-3 rounded-none text-sm">
            </div>

            <div>
                <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Save name
                </button>
            </div>
        </form>
    </section>
@endif
