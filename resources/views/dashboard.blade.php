<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </div>
    </x-slot>

    @php
        $user = auth()->user();

        $nameParts = preg_split('/\s+/', trim($user->name ?? 'there'));
        $firstName = $nameParts[0] ?? 'there';

        $hour = (int) now()->format('H');

        $greeting = match (true) {
            $hour < 12 => 'Morning',
            $hour < 18 => 'Afternoon',
            default => 'Evening',
        };

        /*
         * Replace this URL with your own image later.
         * Good size: wide landscape image, 1600px+ wide.
         */
        $heroImageUrl = 'https://greenst.co.uk/wp-content/uploads/2026/04/green-room-22.jpeg';

        $settingsRoute = null;

        if (Route::has('admin.settings.index')) {
            $settingsRoute = route('admin.settings.index');
        } elseif (Route::has('admin.settings.edit')) {
            $settingsRoute = route('admin.settings.edit');
        }
    @endphp

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <section
                class="relative border border-gray-300 bg-black overflow-hidden mb-6 sm:mb-8"
                style="height: 190px;"
            >
                <img
                    src="{{ $heroImageUrl }}"
                    alt="SiteDesk dashboard"
                    style="width: 100%; height: 100%; object-fit: cover; display: block;"
                >

                <div
                    class="absolute inset-0"
                    style="background: rgba(0, 0, 0, 0.58);"
                ></div>

                <div class="absolute inset-0 flex items-center">
                    <div class="px-5 sm:px-8">
                        <p class="text-sm font-semibold" style="color: rgba(255,255,255,0.82);">
                            SiteDesk
                        </p>

                        <h1 class="text-3xl sm:text-4xl font-bold mt-1" style="color: #ffffff;">
                            {{ $greeting }}, {{ $firstName }}
                        </h1>

                        <p class="text-sm sm:text-base mt-2 max-w-2xl" style="color: rgba(255,255,255,0.92);">
                            Manage contractors, invoices, customers, quotes and settings from one place.
                        </p>
                    </div>
                </div>
            </section>

            <div class="mb-6 sm:mb-8">
                <h2 class="text-3xl font-bold text-gray-900">
                    Tools
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @if ($user->isAdmin())
                    @if (Route::has('admin.contractors.index'))
                        <a href="{{ route('admin.contractors.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                                <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.162-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21C6.447 21 4.401 20.44 2.625 19.456v-.114A6.375 6.375 0 019 12.967c1.033 0 2.006.246 2.864.683M12 7.5a3 3 0 11-6 0 3 3 0 016 0zm6 1.5a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                            </div>

                            <div class="p-6 flex flex-col flex-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    Contractors
                                </h2>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Manage contractor records, invoice addresses, day rates, login access and invoice history.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open contractors
                                </span>
                            </div>
                        </a>
                    @endif

                    @if (Route::has('admin.invoices.index'))
                        <a href="{{ route('admin.invoices.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                                <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H6.75A2.25 2.25 0 004.5 4.5v15A2.25 2.25 0 006.75 21.75h10.5A2.25 2.25 0 0019.5 19.5v-5.25z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M14.25 2.25L19.5 7.5M8.25 13.5h7.5M8.25 16.5h7.5M8.25 10.5h3" />
                                </svg>
                            </div>

                            <div class="p-6 flex flex-col flex-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    Contractor invoices
                                </h2>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    View, filter, review and download contractor-submitted invoices.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open invoices
                                </span>
                            </div>
                        </a>
                    @endif

                    @if (Route::has('admin.customers.index'))
                        <a href="{{ route('admin.customers.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                                <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            </div>

                            <div class="p-6 flex flex-col flex-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    Customers
                                </h2>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Manage customer records, contacts, site addresses and quote history.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open customers
                                </span>
                            </div>
                        </a>
                    @endif

                    @if (Route::has('admin.quotes.index'))
                        <a href="{{ route('admin.quotes.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                                <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19.5 14.25v-2.625A3.375 3.375 0 0016.125 8.25h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H6.75A2.25 2.25 0 004.5 4.5v15A2.25 2.25 0 006.75 21.75h10.5A2.25 2.25 0 0019.5 19.5v-5.25z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M14.25 2.25L19.5 7.5M8.25 12h7.5M8.25 15h7.5M8.25 18h4.5" />
                                </svg>
                            </div>

                            <div class="p-6 flex flex-col flex-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    Quotes
                                </h2>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Create quotes, complete surveys, build pricing and generate customer quote packs.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open quotes
                                </span>
                            </div>
                        </a>
                    @endif

                    @if (Route::has('admin.projects.index'))
                        <a href="{{ route('admin.projects.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                                <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                </svg>
                            </div>

                            <div class="p-6 flex flex-col flex-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    Projects
                                </h2>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Manage customer projects, document libraries, folders and permissions.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open projects
                                </span>
                            </div>
                        </a>
                    @endif

                    @if (Route::has('admin.users.index'))
                        <a href="{{ route('admin.users.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                                <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.941 3.199l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0A5.971 5.971 0 006 18.719m6-6.469a3 3 0 100-6 3 3 0 000 6z" />
                                </svg>
                            </div>

                            <div class="p-6 flex flex-col flex-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    Users
                                </h2>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Manage admin and contractor access to the portal.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open users
                                </span>
                            </div>
                        </a>
                    @endif

                    @if ($settingsRoute)
                        <a href="{{ $settingsRoute }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                                <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 6h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m3 6h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5" />
                                </svg>
                            </div>

                            <div class="p-6 flex flex-col flex-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    Settings
                                </h2>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Manage portal settings, AI settings, pricing guidance and quote pack pages.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open settings
                                </span>
                            </div>
                        </a>
                    @endif

                @endif

                @if ($user->isContractor())
                    @if (Route::has('contractor.invoices.index'))
                        <a href="{{ route('contractor.invoices.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                                <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H6.75A2.25 2.25 0 004.5 4.5v15A2.25 2.25 0 006.75 21.75h10.5A2.25 2.25 0 0019.5 19.5v-5.25z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M14.25 2.25L19.5 7.5M8.25 13.5h7.5M8.25 16.5h7.5M8.25 10.5h3" />
                                </svg>
                            </div>

                            <div class="p-6 flex flex-col flex-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    My invoices
                                </h2>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Submit contractor invoices and view your invoice history.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open my invoices
                                </span>
                            </div>
                        </a>
                    @endif

                    @if (Route::has('contractor.projects.index') && $user->hasAssignedProjects())
                        <a href="{{ route('contractor.projects.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                                <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.5"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                </svg>
                            </div>

                            <div class="p-6 flex flex-col flex-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    My projects
                                </h2>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    View the projects you are assigned to, their site addresses and document libraries.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open my projects
                                </span>
                            </div>
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}"
                       class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                        <div class="h-36 bg-gray-100 border-b border-gray-300 flex items-center justify-center">
                            <svg class="h-16 w-16 text-gray-700 group-hover:text-gray-900 transition"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>

                        <div class="p-6 flex flex-col flex-1">
                            <h2 class="text-xl font-bold text-gray-900">
                                Profile
                            </h2>

                            <p class="text-sm text-gray-600 mt-3 flex-1">
                                Update your profile details and contractor invoice address.
                            </p>

                            <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                Open profile
                            </span>
                        </div>
                    </a>
                @endif
            </div>


        </div>
    </div>
</x-app-layout>