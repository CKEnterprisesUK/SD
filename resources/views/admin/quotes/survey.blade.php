<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Survey mode
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $quote->quote_number }} — {{ $quote->title }}
                </p>
            </div>

            <a href="{{ route('admin.quotes.show', $quote) }}"
               class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Back to quote
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-6 px-4">
        @if (session('status'))
            <div class="mb-4 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                <p class="font-semibold mb-2">There is a problem with the form.</p>

                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="border border-gray-300 bg-white p-4 mb-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ $quote->customer?->display_name }}
                    </h1>

                    <p class="text-sm text-gray-600 mt-1">
                        {{ $quote->site_address ?: 'No site address added' }}
                    </p>
                </div>

                <div class="shrink-0">
                    <span class="inline-flex px-2 py-1 border border-blue-700 bg-blue-50 text-blue-900 text-xs font-semibold">
                        {{ ucfirst(str_replace('_', ' ', $quote->status)) }}
                    </span>
                </div>
            </div>
        </section>

        <section class="border border-gray-300 bg-white mb-4">
            <div class="border-b border-gray-300 px-4 py-3 bg-gray-50">
                <h2 class="text-sm font-semibold">Add to survey feed</h2>
            </div>

            <form method="POST"
                  action="{{ route('admin.quotes.notes.store', $quote) }}"
                  class="p-4 space-y-3">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="type" class="block text-xs font-semibold mb-1">
                            Type
                        </label>

                        <select id="type"
                                name="type"
                                class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                required>
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
                        <label for="room_or_area" class="block text-xs font-semibold mb-1">
                            Room / area
                        </label>

                        <input id="room_or_area"
                               name="room_or_area"
                               type="text"
                               class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                               placeholder="Kitchen, hallway, footings, rear garden">
                    </div>
                </div>

                <div>
                    <label for="body" class="block text-xs font-semibold mb-1">
                        Message
                    </label>

                    <textarea id="body"
                              name="body"
                              rows="3"
                              class="w-full border border-gray-400 px-3 py-3 rounded-none text-base"
                              placeholder="Type a quick survey note..."
                              required></textarea>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button type="submit"
                            class="px-5 py-3 bg-black text-white text-sm font-semibold">
                        Send note
                    </button>

                    <button type="button"
                            onclick="document.getElementById('camera-upload').click()"
                            class="inline-flex items-center gap-2 px-4 py-3 border border-gray-900 text-sm font-semibold">
                        <span style="font-size: 18px; line-height: 1;">📷</span>
                        Add photo
                    </button>
                </div>
            </form>

            <form id="photo-upload-form"
                  method="POST"
                  action="{{ route('admin.quotes.files.store', $quote) }}"
                  enctype="multipart/form-data"
                  class="hidden">
                @csrf

                <input type="hidden" name="type" value="photo">
                <input type="hidden" name="room_or_area" id="photo-room-or-area">
                <input type="hidden" name="caption" id="photo-caption">

                <input id="camera-upload"
                       name="file"
                       type="file"
                       accept="image/*"
                       capture="environment"
                       onchange="preparePhotoUpload(this)">
            </form>
        </section>

        <section class="border border-gray-300 bg-white">
            <div class="border-b border-gray-300 px-4 py-3 bg-gray-50 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold">Survey feed</h2>

                <span class="text-xs text-gray-600">
                    {{ $quote->notes->count() }} notes · {{ $quote->files->count() }} files
                </span>
            </div>

            <div class="p-4 space-y-4">
                @php
                    $feedItems = collect()
                        ->merge($quote->notes->map(function ($note) {
                            return [
                                'kind' => 'note',
                                'created_at' => $note->created_at,
                                'item' => $note,
                            ];
                        }))
                        ->merge($quote->files->map(function ($file) {
                            return [
                                'kind' => 'file',
                                'created_at' => $file->created_at,
                                'item' => $file,
                            ];
                        }))
                        ->sortByDesc('created_at')
                        ->values();

                    $typeClasses = [
                        'requirement' => 'border-blue-700 bg-blue-50 text-blue-900',
                        'measurement' => 'border-purple-700 bg-purple-50 text-purple-900',
                        'consideration' => 'border-yellow-700 bg-yellow-50 text-yellow-900',
                        'risk' => 'border-red-700 bg-red-50 text-red-900',
                        'material' => 'border-green-700 bg-green-50 text-green-900',
                        'equipment' => 'border-gray-700 bg-gray-50 text-gray-900',
                        'customer_comment' => 'border-indigo-700 bg-indigo-50 text-indigo-900',
                        'general' => 'border-gray-500 bg-white text-gray-800',
                        'internal' => 'border-black bg-gray-100 text-black',
                    ];
                @endphp

                @forelse ($feedItems as $feedItem)
                    @if ($feedItem['kind'] === 'note')
                        @php
                            $note = $feedItem['item'];
                            $badgeClass = $typeClasses[$note->type] ?? 'border-gray-500 bg-white text-gray-800';
                        @endphp

                        <article class="flex gap-3">
                            <div class="shrink-0 w-9 h-9 border border-gray-400 bg-gray-100 flex items-center justify-center text-sm font-bold">
                                {{ strtoupper(substr($note->creator?->name ?: 'U', 0, 1)) }}
                            </div>

                            <div class="flex-1">
                                <div class="border border-gray-300 bg-gray-50 p-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $badgeClass }}">
                                                {{ ucfirst(str_replace('_', ' ', $note->type)) }}
                                            </span>

                                            @if ($note->room_or_area)
                                                <span class="text-xs font-semibold text-gray-600">
                                                    {{ $note->room_or_area }}
                                                </span>
                                            @endif
                                        </div>

                                        <span class="text-xs text-gray-500">
                                            {{ $note->created_at ? $note->created_at->format('d M Y H:i') : '' }}
                                        </span>
                                    </div>

                                    <p class="text-sm whitespace-pre-line">{{ $note->body }}</p>

                                    <div class="flex items-center justify-between gap-3 mt-3">
                                        <p class="text-xs text-gray-500">
                                            Added by {{ $note->creator?->name ?: 'Unknown user' }}
                                        </p>

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
                                </div>
                            </div>
                        </article>
                    @else
                        @php
                            $file = $feedItem['item'];
                        @endphp

                        <article class="flex gap-3">
                            <div class="shrink-0 w-9 h-9 border border-gray-400 bg-gray-100 flex items-center justify-center text-sm">
                                📷
                            </div>

                            <div class="flex-1">
                                <div class="border border-gray-300 bg-white p-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                        <div>
                                            <span class="inline-flex px-2 py-1 border border-gray-500 bg-gray-50 text-gray-800 text-xs font-semibold">
                                                Photo
                                            </span>

                                            @if ($file->room_or_area)
                                                <span class="ml-2 text-xs font-semibold text-gray-600">
                                                    {{ $file->room_or_area }}
                                                </span>
                                            @endif
                                        </div>

                                        <span class="text-xs text-gray-500">
                                            {{ $file->created_at ? $file->created_at->format('d M Y H:i') : '' }}
                                        </span>
                                    </div>

                                    @if ($file->is_image)
                                        <a href="{{ $file->url }}" target="_blank">
                                            <img src="{{ $file->url }}"
                                                 alt="{{ $file->caption ?: $file->original_name }}"
                                                 class="w-full max-h-[420px] object-cover border border-gray-300">
                                        </a>
                                    @else
                                        <a href="{{ $file->url }}" target="_blank" class="underline">
                                            {{ $file->original_name ?: 'View file' }}
                                        </a>
                                    @endif

                                    @if ($file->caption)
                                        <p class="text-sm mt-3 whitespace-pre-line">{{ $file->caption }}</p>
                                    @endif

                                    <div class="flex items-center justify-between gap-3 mt-3">
                                        <p class="text-xs text-gray-500">
                                            Uploaded by {{ $file->uploadedBy?->name ?: 'Unknown user' }}
                                        </p>

                                        <form method="POST" action="{{ route('admin.quotes.files.destroy', [$quote, $file]) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="text-xs underline text-red-700"
                                                    onclick="return confirm('Delete this file?')">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endif
                @empty
                    <div class="border border-gray-300 bg-gray-50 p-6 text-center">
                        <p class="text-sm text-gray-600">
                            No survey notes or photos have been added yet.
                        </p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <script>
        function preparePhotoUpload(input) {
            if (!input.files || !input.files.length) {
                return;
            }

            const roomInput = document.getElementById('room_or_area');
            const bodyInput = document.getElementById('body');

            document.getElementById('photo-room-or-area').value = roomInput ? roomInput.value : '';
            document.getElementById('photo-caption').value = bodyInput ? bodyInput.value : '';

            document.getElementById('photo-upload-form').submit();
        }
    </script>
</x-app-layout>