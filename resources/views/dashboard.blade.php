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
         * These are placeholder photo URLs.
         * You can replace any of these with your own uploaded image URLs later.
         */
        $cardImages = [
            'contractors' => 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=900&q=80',
            'invoices' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=900&q=80',
            'customers' => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=900&q=80',
            'quotes' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=900&q=80',
            'users' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=900&q=80',
            'settings' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80',
            'jobs' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=900&q=80',
            'profile' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80',
        ];

        $settingsRoute = null;

        if (Route::has('admin.settings.index')) {
            $settingsRoute = route('admin.settings.index');
        } elseif (Route::has('admin.settings.edit')) {
            $settingsRoute = route('admin.settings.edit');
        }
    @endphp

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-gray-500">
                    {{ $greeting }}, {{ $firstName }}
                </p>

                <h1 class="text-3xl font-bold text-gray-900 mt-1">
                    Tools
                </h1>

                <p class="text-gray-600 mt-2 max-w-2xl">
                    SiteDesk gives you access to the tools available for your account.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @if ($user->isAdmin())
                    @if (Route::has('admin.contractors.index'))
                        <a href="{{ route('admin.contractors.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-[200px] bg-gray-100 border-b border-gray-300 overflow-hidden">
                                <img
                                    src="{{ $cardImages['contractors'] }}"
                                    alt="Contractors"
                                    class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300"
                                >
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
                            <div class="h-[200px] bg-gray-100 border-b border-gray-300 overflow-hidden">
                                <img
                                    src="{{ $cardImages['invoices'] }}"
                                    alt="Contractor invoices"
                                    class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300"
                                >
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
                            <div class="h-[200px] bg-gray-100 border-b border-gray-300 overflow-hidden">
                                <img
                                    src="{{ $cardImages['customers'] }}"
                                    alt="Customers"
                                    class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300"
                                >
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
                            <div class="h-[200px] bg-gray-100 border-b border-gray-300 overflow-hidden">
                                <img
                                    src="{{ $cardImages['quotes'] }}"
                                    alt="Quotes"
                                    class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300"
                                >
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

                    @if (Route::has('admin.users.index'))
                        <a href="{{ route('admin.users.index') }}"
                           class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                            <div class="h-[200px] bg-gray-100 border-b border-gray-300 overflow-hidden">
                                <img
                                    src="{{ $cardImages['users'] }}"
                                    alt="Users"
                                    class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300"
                                >
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
                            <div class="h-[200px] bg-gray-100 border-b border-gray-300 overflow-hidden">
                                <img
                                    src="{{ $cardImages['settings'] }}"
                                    alt="Settings"
                                    class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300"
                                >
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

                    <div class="flex flex-col border border-gray-300 bg-white opacity-75 min-h-[320px]">
                        <div class="h-[200px] bg-gray-100 border-b border-gray-300 overflow-hidden grayscale">
                            <img
                                src="{{ $cardImages['jobs'] }}"
                                alt="Jobs"
                                class="w-full h-full object-cover"
                            >
                        </div>

                        <div class="p-6 flex flex-col flex-1">
                            <div class="flex items-center justify-between gap-4">
                                <h2 class="text-xl font-bold text-gray-700">
                                    Jobs
                                </h2>

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
                            <div class="h-[200px] bg-gray-100 border-b border-gray-300 overflow-hidden">
                                <img
                                    src="{{ $cardImages['invoices'] }}"
                                    alt="My invoices"
                                    class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300"
                                >
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

                    <a href="{{ route('profile.edit') }}"
                       class="group flex flex-col border border-gray-300 bg-white hover:border-gray-900 focus:border-gray-900 focus:outline-none transition min-h-[320px]">
                        <div class="h-[200px] bg-gray-100 border-b border-gray-300 overflow-hidden">
                            <img
                                src="{{ $cardImages['profile'] }}"
                                alt="Profile"
                                class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300"
                            >
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