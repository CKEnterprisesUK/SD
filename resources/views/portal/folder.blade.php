<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $folder->name }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4">
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

        <div class="mb-8">
            @if (Route::has('projects.library'))
                <a href="{{ route('projects.library', $project) }}" class="text-sm underline">
                    Back to library
                </a>
            @endif

            <h1 class="text-3xl font-bold mt-4">{{ $folder->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $project->name }}</p>
        </div>

        <div class="border border-gray-300 bg-white p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Folders</h2>

            @forelse ($subfolders as $subfolder)
                <div class="border-b border-gray-200 last:border-b-0 py-3">
                    @if (Route::has('projects.folders.show'))
                        <a href="{{ route('projects.folders.show', [$project, $subfolder]) }}"
                           class="font-semibold underline">
                            {{ $subfolder->name }}
                        </a>
                    @else
                        <span class="font-semibold">{{ $subfolder->name }}</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-600">No subfolders.</p>
            @endforelse
        </div>

        <div class="border border-gray-300 bg-white p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Documents</h2>

            @forelse ($documents as $document)
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 last:border-b-0 py-3">
                    <span class="text-sm">{{ $document->original_name }}</span>

                    @if (Route::has('documents.serve'))
                        <a href="{{ route('documents.serve', $document) }}" class="text-sm underline">
                            Download
                        </a>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-600">No documents in this folder.</p>
            @endforelse
        </div>

        @can('upload', [App\Models\ProjectDocument::class, $folder])
            @if (Route::has('admin.projects.documents.store'))
                <div class="border border-gray-300 bg-white p-6">
                    <h2 class="text-lg font-semibold mb-4">Upload a document</h2>

                    <form method="POST"
                          action="{{ route('admin.projects.documents.store', [$project, $folder]) }}"
                          enctype="multipart/form-data"
                          class="space-y-3">
                        @csrf

                        <div>
                            <label for="file" class="block text-sm font-semibold mb-2">File</label>
                            <input id="file" name="file" type="file"
                                   class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm" required>
                        </div>

                        <button type="submit"
                                class="px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                            Upload
                        </button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</x-app-layout>
