@php
    $portalSettings = \App\Models\PortalSetting::current();
    $user = Auth::user();
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-300">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between min-h-16">
            <div class="flex">
                <!-- Logo / Portal Brand -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        @if ($portalSettings->logo_path)
    <div style="height:48px; width:160px; overflow:hidden; display:flex; align-items:center;">
        <img
            src="{{ asset($portalSettings->logo_path) }}"
            alt="{{ $portalSettings->portal_name }} logo"
            style="max-height:40px; max-width:144px; width:auto; height:auto; object-fit:contain;"
        >
    </div>
@else
                            <div class="h-11 w-11 flex items-center justify-center border-2 border-gray-900 font-bold text-sm">
                                SD
                            </div>
                        @endif

                        
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if ($user?->isAdmin())
                        <x-nav-link :href="route('admin.contractors.index')" :active="request()->routeIs('admin.contractors.*')">
                            Contractors
                        </x-nav-link>

                        @if (Route::has('admin.users.index'))
                            <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                                Users
                            </x-nav-link>
                        @endif

                        <x-nav-link :href="route('admin.invoices.index')" :active="request()->routeIs('admin.invoices.*')">
                            Invoices
                        </x-nav-link>

                        @if (Route::has('admin.settings.edit'))
                            <x-nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')">
                                Settings
                            </x-nav-link>
                        @endif
                    @endif

                    @if ($user?->isContractor())
                       <x-nav-link :href="route('contractor.invoices.index')" :active="request()->routeIs('contractor.invoices.*')">
                            My Invoices
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-none text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition ease-in-out duration-150">
                            <div>
                                <div class="font-semibold text-gray-900">
                                    {{ $user->name }}
                                </div>
                                <div class="text-xs text-gray-500 text-left">
                                    {{ ucfirst($user->role ?? 'user') }}
                                </div>
                            </div>

                            <div class="ms-2">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @if ($user?->isAdmin())
                            <x-dropdown-link :href="route('admin.contractors.index')">
                                Contractors
                            </x-dropdown-link>

                            @if (Route::has('admin.users.index'))
                                <x-dropdown-link :href="route('admin.users.index')">
                                    Users
                                </x-dropdown-link>
                            @endif

                            @if (Route::has('admin.settings.edit'))
                                <x-dropdown-link :href="route('admin.settings.edit')">
                                    Settings
                                </x-dropdown-link>
                            @endif
                        @endif

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                            >
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button
                    @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 border border-gray-300 text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-900 transition duration-150 ease-in-out"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path
                            :class="{'hidden': open, 'inline-flex': ! open }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                        <path
                            :class="{'hidden': ! open, 'inline-flex': open }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-300">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if ($user?->isAdmin())
                <x-responsive-nav-link :href="route('admin.contractors.index')" :active="request()->routeIs('admin.contractors.*')">
                    Contractors
                </x-responsive-nav-link>

                @if (Route::has('admin.users.index'))
                    <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                        Users
                    </x-responsive-nav-link>
                @endif

                @if (Route::has('admin.settings.edit'))
                    <x-responsive-nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')">
                        Settings
                    </x-responsive-nav-link>
                @endif
            @endif

            @if ($user?->isContractor())
                <x-responsive-nav-link href="#" :active="false">
                    My Timesheets
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('contractor.invoices.index')" :active="request()->routeIs('contractor.invoices.*')">
                    My Invoices
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-300">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">
                    {{ $user->name }}
                </div>
                <div class="font-medium text-sm text-gray-500">
                    {{ $user->email }}
                </div>
                <div class="font-medium text-xs text-gray-500 mt-1">
                    {{ ucfirst($user->role ?? 'user') }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link
                        :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                    >
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>