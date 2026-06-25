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
         * Replace this with your own image URL later.
         * Recommended size: landscape image, ideally 1600px wide or more.
         */
        $heroImageUrl = 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=1600&q=80';

        $user = auth()->user();

        $nameParts = preg_split('/\s+/', trim($user->name ?? 'there'));
        $firstName = $nameParts[0] ?? 'there';

        $hour = (int) now()->format('H');

        $greeting = match (true) {
            $hour < 12 => 'Morning',
            $hour < 18 => 'Afternoon',
            default => 'Evening',
        };

        $settingsRoute = null;

        if (Route::has('admin.settings.index')) {
            $settingsRoute = route('admin.settings.index');
        } elseif (Route::has('admin.settings.edit')) {
            $settingsRoute = route('admin.settings.edit');
        }
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <section class="relative border border-gray-300 bg-black overflow-hidden">
                <img
                    src="{{ $heroImageUrl }}"
                    alt="SiteDesk dashboard"
                    class="w-full h-[200px] object-cover opacity-75"
                >

                <div class="absolute inset-0 bg-black/35"></div>

                <div class="absolute inset-0 flex items-center">
                    <div class="px-6 sm:px-8">
                        <p class="text-sm font-semibold text-white/80">
                            SiteDesk
                        </p>

                        <h1 class="text-3xl sm:text-4xl font-bold text-white mt-2">
                            {{ $greeting }}, {{ $firstName }}
                        </h1>

                        <p class="text-sm sm:text-base text-white/90 mt-3 max-w-2xl">
                            Manage contractors, invoices, customers, quotes and settings from one place.
                        </p>
                    </div>
                </div>
            </section>

            <div>
                <h2 class="text-3xl font-bold text-gray-900">
                    Tools
                </h2>

                <p class="text-gray-600 mt-2 max-w-2xl">
                    SiteDesk gives you access to the tools available for your account.
                </p>
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
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-gray-900">
                                        Contractors
                                    </h3>

                                    <span class="text-sm text-gray-500">
                                        Active
                                    </span>
                                </div>

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
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-gray-900">
                                        Contractor invoices
                                    </h3>

                                    <span class="text-sm text-gray-500">
                                        Active
                                    </span>
                                </div>

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
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-gray-900">
                                        Customers
                                    </h3>

                                    <span class="text-sm text-gray-500">
                                        Active
                                    </span>
                                </div>

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
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-gray-900">
                                        Quotes
                                    </h3>

                                    <span class="text-sm text-gray-500">
                                        Active
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Create quotes, complete surveys, build pricing and generate customer quote packs.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open quotes
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
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-gray-900">
                                        Users
                                    </h3>

                                    <span class="text-sm text-gray-500">
                                        Active
                                    </span>
                                </div>

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
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-gray-900">
                                        Settings
                                    </h3>

                                    <span class="text-sm text-gray-500">
                                        Active
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Manage portal settings, AI settings, pricing guidance and quote pack pages.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open settings
                                </span>
                            </div>
                        </a>
                    @endif

                    <div class="flex flex-col border border-gray-300 bg-white opacity-70 min-h-[320px]">
                        <div class="h-36 bg-gray-50 border-b border-gray-300 flex items-center justify-center">
                            <svg class="h-16 w-16 text-gray-400"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3.75 21h16.5M4.5 3h15l-.75 18H5.25L4.5 3zM9 7.5h6M8.25 12h7.5M9 16.5h6" />
                            </svg>
                        </div>

                        <div class="p-6 flex flex-col flex-1">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-xl font-bold text-gray-700">
                                    Jobs
                                </h3>

                                <span class="text-sm text-gray-500">
                                    Coming soon
                                </span>
                            </div>

                            <p class="text-sm text-gray-600 mt-3 flex-1">
                                Track jobs, sites, progress and contractor activity.
                            </p>

                            <span class="mt-6 inline-flex w-fit px-4 py-2 border border-gray-400 text-gray-600 text-sm font-semibold rounded-none">
                                Not available yet
                            </span>
                        </div>
                    </div>
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
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-gray-900">
                                        My invoices
                                    </h3>

                                    <span class="text-sm text-gray-500">
                                        Active
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mt-3 flex-1">
                                    Submit contractor invoices and view your invoice history.
                                </p>

                                <span class="mt-6 inline-flex w-fit px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                                    Open my invoices
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
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-xl font-bold text-gray-900">
                                    Profile
                                </h3>

                                <span class="text-sm text-gray-500">
                                    Active
                                </span>
                            </div>

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