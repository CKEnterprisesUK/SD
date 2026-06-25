<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Quote pack settings
            </h2>

            <p class="text-sm text-gray-600 mt-1">
                Upload the reusable front and back pages for customer quote packs.
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
                            Quote pack pages
                        </h1>

                        <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                            Upload the front and back pages used on every customer quote pack. Use JPG or PNG files exported from your design software.
                        </p>
                    </div>

                    <form method="POST"
                          action="{{ route('admin.settings.quote-pack.update') }}"
                          enctype="multipart/form-data"
                          class="p-6 space-y-8">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                            <section class="border border-gray-300 bg-white">
                                <div class="p-4 border-b border-gray-300">
                                    <h2 class="text-lg font-semibold">
                                        Front page
                                    </h2>
                                </div>

                                <div class="p-4 space-y-4">
                                    @if ($settings->quote_pack_front_page_path)
                                        <div class="border border-gray-300 bg-gray-50 p-3">
                                            <img
                                                src="{{ asset($settings->quote_pack_front_page_path) }}"
                                                alt="Current quote pack front page"
                                                class="w-full max-h-[520px] object-contain bg-white"
                                            >
                                        </div>

                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="remove_front_page" value="1">
                                            Remove current front page
                                        </label>
                                    @else
                                        <div class="border border-gray-300 bg-gray-50 p-8 text-center text-sm text-gray-600">
                                            No front page uploaded yet.
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">
                                            Upload new front page
                                        </label>

                                        <input
                                            type="file"
                                            name="front_page"
                                            accept="image/png,image/jpeg"
                                            class="block w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                                        >
                                    </div>
                                </div>
                            </section>

                            <section class="border border-gray-300 bg-white">
                                <div class="p-4 border-b border-gray-300">
                                    <h2 class="text-lg font-semibold">
                                        Back page
                                    </h2>
                                </div>

                                <div class="p-4 space-y-4">
                                    @if ($settings->quote_pack_back_page_path)
                                        <div class="border border-gray-300 bg-gray-50 p-3">
                                            <img
                                                src="{{ asset($settings->quote_pack_back_page_path) }}"
                                                alt="Current quote pack back page"
                                                class="w-full max-h-[520px] object-contain bg-white"
                                            >
                                        </div>

                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="remove_back_page" value="1">
                                            Remove current back page
                                        </label>
                                    @else
                                        <div class="border border-gray-300 bg-gray-50 p-8 text-center text-sm text-gray-600">
                                            No back page uploaded yet.
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">
                                            Upload new back page
                                        </label>

                                        <input
                                            type="file"
                                            name="back_page"
                                            accept="image/png,image/jpeg"
                                            class="block w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                                        >
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                                Save quote pack pages
                            </button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>
</x-app-layout>