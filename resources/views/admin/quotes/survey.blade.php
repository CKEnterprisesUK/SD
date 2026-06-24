<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Survey mode — {{ $quote->quote_number }}
            </h2>

            <a href="{{ route('admin.quotes.show', $quote) }}"
               class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Back to quote
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-6 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                <p class="font-semibold mb-2">There is a problem with the form.</p>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="border border-gray-300 bg-white p-5 mb-6">
            <h1 class="text-2xl font-bold">{{ $quote->title }}</h1>
            <p class="text-sm text-gray-600 mt-2">
                {{ $quote->customer?->display_name }} — {{ $quote->site_address ?: 'No site address added' }}
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <section class="border border-gray-300 bg-white p-5">
                <h2 class="text-lg font-semibold mb-4">Add survey note</h2>

                <form method="POST" action="{{ route('admin.quotes.notes.store', $quote) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold mb-2">Type</label>
                        <select name="type" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                            <option value="requirement">Requirement</option>
                            <option value="measurement">Measurement</option>
                            <option value="consideration">Consideration</option>
                            <option value="risk">Risk</option>
                            <option value="material">Material</option>
                            <option value="equipment">Equipment</option>
                            <option value="customer_comment">Customer comment</option>
                            <option value="general">General</option>
                            <option value="internal">Internal</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Room / area</label>
                        <input name="room_or_area"
                               type="text"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none"
                               placeholder="e.g. Kitchen, rear garden, footings">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Note</label>
                        <textarea name="body"
                                  rows="7"
                                  class="w-full border border-gray-400 px-4 py-3 rounded-none text-base"
                                  placeholder="Add what you see while walking round..."
                                  required></textarea>
                    </div>

                    <button type="submit" class="w-full px-5 py-4 bg-black text-white text-sm font-semibold">
                        Add note
                    </button>
                </form>
            </section>

            <section class="border border-gray-300 bg-white p-5">
                <h2 class="text-lg font-semibold mb-4">Add photo / file</h2>

                <form method="POST"
                      action="{{ route('admin.quotes.files.store', $quote) }}"
                      enctype="multipart/form-data"
                      class="space-y-4">
                    @csrf

                    <input type="hidden" name="type" value="photo">

                    <div>
                        <label class="block text-sm font-semibold mb-2">Photo</label>
                        <input name="file"
                               type="file"
                               accept="image/*"
                               capture="environment"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none"
                               required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Room / area</label>
                        <input name="room_or_area"
                               type="text"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none"
                               placeholder="e.g. Kitchen, hallway, rear elevation">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Caption</label>
                        <textarea name="caption"
                                  rows="4"
                                  class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                  placeholder="What does this photo show?"></textarea>
                    </div>

                    <button type="submit" class="w-full px-5 py-4 bg-black text-white text-sm font-semibold">
                        Upload photo
                    </button>
                </form>
            </section>
        </div>

        <section class="border border-gray-300 bg-white p-5 mb-6">
            <h2 class="text-lg font-semibold mb-4">Survey notes</h2>

            <div class="space-y-4">
                @forelse ($quote->notes as $note)
                    <article class="border border-gray-300 bg-gray-50 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    {{ ucfirst(str_replace('_', ' ', $note->type)) }}
                                    @if ($note->room_or_area)
                                        — {{ $note->room_or_area }}
                                    @endif
                                </div>

                                <p class="text-sm whitespace-pre-line mt-2">{{ $note->body }}</p>

                                <p class="text-xs text-gray-500 mt-3">
                                    {{ $note->creator?->name ?: 'Unknown user' }}
                                    ·
                                    {{ $note->created_at ? $note->created_at->format('d M Y H:i') : '' }}
                                </p>
                            </div>

                            <form method="POST" action="{{ route('admin.quotes.notes.destroy', [$quote, $note]) }}">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="text-xs underline text-red-700"
                                        onclick="return confirm('Delete this note?')">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-gray-600">No survey notes yet.</p>
                @endforelse
            </div>
        </section>

        <section class="border border-gray-300 bg-white p-5">
            <h2 class="text-lg font-semibold mb-4">Photos and files</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($quote->files as $file)
                    <article class="border border-gray-300 bg-gray-50 p-3">
                        @if ($file->is_image)
                            <a href="{{ $file->url }}" target="_blank">
                                <img src="{{ $file->url }}"
                                     alt="{{ $file->caption ?: $file->original_name }}"
                                     class="w-full h-48 object-cover border border-gray-300">
                            </a>
                        @else
                            <a href="{{ $file->url }}" target="_blank" class="underline">
                                {{ $file->original_name ?: 'View file' }}
                            </a>
                        @endif

                        @if ($file->room_or_area)
                            <p class="text-xs font-semibold text-gray-600 mt-3">
                                {{ $file->room_or_area }}
                            </p>
                        @endif

                        @if ($file->caption)
                            <p class="text-sm mt-1">{{ $file->caption }}</p>
                        @endif

                        <form method="POST" action="{{ route('admin.quotes.files.destroy', [$quote, $file]) }}" class="mt-3">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="text-xs underline text-red-700"
                                    onclick="return confirm('Delete this file?')">
                                Delete
                            </button>
                        </form>
                    </article>
                @empty
                    <p class="text-sm text-gray-600">No photos or files uploaded yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>