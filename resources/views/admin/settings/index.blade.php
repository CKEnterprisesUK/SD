<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Settings
                </h2>

                <p class="text-sm text-gray-600 mt-1">
                    Central settings area for SiteDesk.
                </p>
            </div>
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

                <section class="border border-gray-300 bg-white">
                    <div class="p-6 border-b border-gray-300">
                        <h1 class="text-2xl font-bold">
                            Settings overview
                        </h1>

                        <p class="text-sm text-gray-600 mt-2">
                            Choose a settings section to manage portal details, AI behaviour, pricing guidance and quote-pack pages.
                        </p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        @if (Route::has('admin.settings.edit'))
                            <a href="{{ route('admin.settings.edit') }}"
                               class="block border border-gray-300 bg-white p-5 hover:border-black">
                                <h2 class="text-lg font-bold">
                                    Portal settings
                                </h2>

                                <p class="text-sm text-gray-600 mt-2">
                                    Company details, branding and general portal configuration.
                                </p>

                                <span class="inline-flex mt-5 px-4 py-2 bg-black text-white text-sm font-semibold">
                                    Open
                                </span>
                            </a>
                        @endif

                        @if (Route::has('admin.settings.ai.edit'))
                            <a href="{{ route('admin.settings.ai.edit') }}"
                               class="block border border-gray-300 bg-white p-5 hover:border-black">
                                <h2 class="text-lg font-bold">
                                    General AI settings
                                </h2>

                                <p class="text-sm text-gray-600 mt-2">
                                    Enable AI, choose model names and set the default AI tone.
                                </p>

                                <span class="inline-flex mt-5 px-4 py-2 bg-black text-white text-sm font-semibold">
                                    Open
                                </span>
                            </a>
                        @endif

                        @if (Route::has('admin.pricing-settings.edit'))
                            <a href="{{ route('admin.pricing-settings.edit') }}"
                               class="block border border-gray-300 bg-white p-5 hover:border-black">
                                <h2 class="text-lg font-bold">
                                    AI pricing settings
                                </h2>

                                <p class="text-sm text-gray-600 mt-2">
                                    Pricing policy, estimate guidance and job templates.
                                </p>

                                <span class="inline-flex mt-5 px-4 py-2 bg-black text-white text-sm font-semibold">
                                    Open
                                </span>
                            </a>
                        @endif

                        @if (Route::has('admin.settings.folder-template.edit'))
                            <a href="{{ route('admin.settings.folder-template.edit') }}"
                               class="block border border-gray-300 bg-white p-5 hover:border-black">
                                <h2 class="text-lg font-bold">
                                    Folder template
                                </h2>

                                <p class="text-sm text-gray-600 mt-2">
                                    Master folder structure and per-role permissions applied to new project libraries.
                                </p>

                                <span class="inline-flex mt-5 px-4 py-2 bg-black text-white text-sm font-semibold">
                                    Open
                                </span>
                            </a>
                        @endif

                        @if (Route::has('admin.settings.quote-pack.edit'))
                            <a href="{{ route('admin.settings.quote-pack.edit') }}"
                               class="block border border-gray-300 bg-white p-5 hover:border-black">
                                <h2 class="text-lg font-bold">
                                    Quote pack
                                </h2>

                                <p class="text-sm text-gray-600 mt-2">
                                    Upload the front and back pages used in customer quote packs.
                                </p>

                                <span class="inline-flex mt-5 px-4 py-2 bg-black text-white text-sm font-semibold">
                                    Open
                                </span>
                            </a>
                        @endif
                        @if (Route::has('admin.settings.users.index'))
    <a href="{{ route('admin.settings.users.index') }}"
       class="block border border-gray-300 bg-white p-6 hover:border-black">
        <h2 class="text-lg font-bold text-gray-900">
            Users
        </h2>

        <p class="text-sm text-gray-600 mt-2">
            Add admin users and send password reset emails.
        </p>
    </a>
@endif

                        @if (Route::has('admin.users.index'))
                            <a href="{{ route('admin.users.index') }}"
                               class="block border border-gray-300 bg-white p-5 hover:border-black">
                                <h2 class="text-lg font-bold">
                                    Users
                                </h2>

                                <p class="text-sm text-gray-600 mt-2">
                                    Manage admin and contractor access.
                                </p>

                                <span class="inline-flex mt-5 px-4 py-2 bg-black text-white text-sm font-semibold">
                                    Open
                                </span>
                            </a>
                        @endif
                    </div>
                </section>
            </main>
        </div>
    </div>
</x-app-layout>