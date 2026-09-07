{{--
    Top-level folder reorder partial.

    Expects:
      - $project (App\Models\Project)

    Submits an ordered list of folder ids (folder_ids[]) to
    admin.projects.folders.reorder. The controller assigns sort_order from the
    submitted order, so the up/down controls simply move rows and the hidden
    folder_ids[] inputs are read top-to-bottom.
--}}
@php
    $topLevelFolders = $project->relationLoaded('topLevelFolders')
        ? $project->topLevelFolders
        : $project->topLevelFolders()->orderBy('sort_order')->get();
@endphp

@if (Route::has('admin.projects.folders.reorder') && $topLevelFolders->isNotEmpty())
    <section class="border border-gray-300 bg-white">
        <div class="p-4 border-b border-gray-300">
            <h2 class="text-lg font-semibold">
                Reorder folders
            </h2>

            <p class="text-sm text-gray-600 mt-1">
                Move folders up or down, then save. Folders are ordered top-to-bottom.
            </p>
        </div>

        <form method="POST"
              action="{{ route('admin.projects.folders.reorder', $project) }}"
              class="p-4 space-y-3"
              x-data>
            @csrf
            @method('PUT')

            <div data-folder-list class="space-y-2">
                @foreach ($topLevelFolders as $topLevel)
                    <div data-folder-row class="flex items-center gap-3 border border-gray-300 px-4 py-3">
                        <div class="flex flex-col gap-1">
                            <button type="button"
                                    title="Move up"
                                    class="px-2 py-0.5 border border-gray-400 text-xs rounded-none"
                                    @click="const row = $el.closest('[data-folder-row]'); const prev = row.previousElementSibling; if (prev) prev.before(row);">
                                &uarr;
                            </button>
                            <button type="button"
                                    title="Move down"
                                    class="px-2 py-0.5 border border-gray-400 text-xs rounded-none"
                                    @click="const row = $el.closest('[data-folder-row]'); const next = row.nextElementSibling; if (next) next.after(row);">
                                &darr;
                            </button>
                        </div>

                        <span class="text-sm text-gray-900">{{ $topLevel->name }}</span>

                        {{-- The controller reads folder_ids in submitted (DOM) order. --}}
                        <input type="hidden" name="folder_ids[]" value="{{ $topLevel->id }}">
                    </div>
                @endforeach
            </div>

            <div>
                <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Save order
                </button>
            </div>
        </form>
    </section>
@endif
