@php
    $depth = $depth ?? 0;
@endphp

<div class="@if ($depth > 0) ml-5 border-l border-gray-200 pl-4 @endif mt-2">
    <div class="flex items-center gap-2 text-sm">
        <span class="font-semibold">
            {{ $folder->name }}
        </span>
        @if ($folder->is_top_level)
            <span class="text-xs text-gray-500">(top level)</span>
        @endif
    </div>

    @if ($folder->relationLoaded('documents') ? $folder->documents->isNotEmpty() : $folder->documents()->exists())
        <ul class="ml-4 mt-1 space-y-1 text-sm text-gray-700">
            @foreach ($folder->documents as $document)
                <li class="flex items-center gap-2">
                    <span class="text-gray-400">•</span>
                    <span>{{ $document->original_name }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @foreach ($folder->children as $child)
        @include('admin.projects.partials.folder-node', ['folder' => $child, 'depth' => $depth + 1])
    @endforeach
</div>
