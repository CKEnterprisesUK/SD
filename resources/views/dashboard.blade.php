<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </div>
    </x-slot>

    @php
        /*
         * Replace this with your own photo URL later if you want.
         * Recommended size: roughly 1200x400 or wider landscape.
         */
        $heroImageUrl = 'https://greenst.co.uk/wp-content/uploads/2026/04/green-room-22.jpeg';

        $settingsRoute = null;

        if (Route::has('admin.settings.index')) {
            $settingsRoute = route('admin.settings.index');
        } elseif (Route::has('admin.settings.edit')) {
            $settingsRoute = route('admin.settings.edit');
        }
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="border border-gray-300 bg-white overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px]">
                    <div class="p-6 lg:p-8">
                        <p class="text-sm font-semibold text-gray-500">
                            SiteDesk
                        </p>

                        <h1 class="text-3xl font-bold text-gray-900 mt-2">
                            Your workspace
                        </h1>

                        <p class="text-gray-600 mt-3 max-w-2xl">
                            Manage contractors, customers, quotes, invoices and settings from one place.
                        </p>

                        <div class="flex flex-wrap gap-3 mt-6">
                            @if (auth()->user()->isAdmin() && Route::has('admin.quotes.index'))
                                <a href="{{ route('admin.quotes.index') }}"
                                   class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                                    Open quotes
                                </a>
                            @endif

                            @if (auth()->user()->isAdmin() && Route::has('admin.customers.index'))
                                <a href="{{ route('admin.customers.index') }}"
                                   class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                                    Customers
                                </a>
                            @endif

                            @if (auth()->user()->isContractor() && Route::has('contractor.invoices.index'))
                                <a href="{{ route('contractor.invoices.index') }}"
                                   class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                                    My invoices
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="hidden lg:block border-l border-gray-300 bg-gray-100">
                        <img
                            src="{{ $heroImageUrl }}"
                            alt="SiteDesk workspace"
                            class="w-full h-full min-h-[220px] object-cover"
                        >
                    </div>
                </div>
            </section>

            <section>
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-gray-900">
                        Tools
                    </h2>

                    <p class="text-sm text-gray-600 mt-1">
                        Choose a tool to continue.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    @if (auth()->user()->isAdmin())
                        @if (Route::has('admin.contractors.index'))
                            <a href="{{ route('admin.contractors.index') }}"
                               class="group block border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            Contractors
                                        </h3>

                                        <p class="text-sm text-gray-600 mt-2">
                                            Manage contractor records, day rates, invoice addresses and login access.
                                        </p>
                                    </div>

                                    <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                        Open
                                    </span>
                                </div>
                            </a>
                        @endif

                        @if (Route::has('admin.invoices.index'))
                            <a href="{{ route('admin.invoices.index') }}"
                               class="group block border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            Contractor invoices
                                        </h3>

                                        <p class="text-sm text-gray-600 mt-2">
                                            View, filter, review and download contractor-submitted invoices.
                                        </p>
                                    </div>

                                    <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                        Open
                                    </span>
                                </div>
                            </a>
                        @endif

                        @if (Route::has('admin.customers.index'))
                            <a href="{{ route('admin.customers.index') }}"
                               class="group block border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            Customers
                                        </h3>

                                        <p class="text-sm text-gray-600 mt-2">
                                            Manage customer records, contacts, site addresses and quote history.
                                        </p>
                                    </div>

                                    <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                        Open
                                    </span>
                                </div>
                            </a>
                        @endif

                        @if (Route::has('admin.quotes.index'))
                            <a href="{{ route('admin.quotes.index') }}"
                               class="group block border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            Quotes
                                        </h3>

                                        <p class="text-sm text-gray-600 mt-2">
                                            Create quotes, complete surveys, build pricing and generate customer quote packs.
                                        </p>
                                    </div>

                                    <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                        Open
                                    </span>
                                </div>
                            </a>
                        @endif

                        @if (Route::has('admin.users.index'))
                            <a href="{{ route('admin.users.index') }}"
                               class="group block border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            Users
                                        </h3>

                                        <p class="text-sm text-gray-600 mt-2">
                                            Manage admin and contractor access to the portal.
                                        </p>
                                    </div>

                                    <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                        Open
                                    </span>
                                </div>
                            </a>
                        @endif

                        @if ($settingsRoute)
                            <a href="{{ $settingsRoute }}"
                               class="group block border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            Settings
                                        </h3>

                                        <p class="text-sm text-gray-600 mt-2">
                                            Manage portal settings, AI settings, pricing guidance and quote pack pages.
                                        </p>
                                    </div>

                                    <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                        Open
                                    </span>
                                </div>
                            </a>
                        @endif

                        <div class="block border border-gray-300 bg-gray-50 p-5 opacity-70">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-700">
                                        Jobs
                                    </h3>

                                    <p class="text-sm text-gray-600 mt-2">
                                        Track jobs, sites, progress and contractor activity.
                                    </p>
                                </div>

                                <span class="text-sm font-semibold text-gray-500">
                                    Soon
                                </span>
                            </div>
                        </div>
                    @endif

                    @if (auth()->user()->isContractor())
                        @if (Route::has('contractor.invoices.index'))
                            <a href="{{ route('contractor.invoices.index') }}"
                               class="group block border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            My invoices
                                        </h3>

                                        <p class="text-sm text-gray-600 mt-2">
                                            Submit contractor invoices and view your invoice history.
                                        </p>
                                    </div>

                                    <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                        Open
                                    </span>
                                </div>
                            </a>
                        @endif

                        <a href="{{ route('profile.edit') }}"
                           class="group block border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">
                                        Profile
                                    </h3>

                                    <p class="text-sm text-gray-600 mt-2">
                                        Update your profile details and contractor invoice address.
                                    </p>
                                </div>

                                <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                    Open
                                </span>
                            </div>
                        </a>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>