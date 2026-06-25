<aside class="border border-gray-300 bg-white">
    <div class="p-5 border-b border-gray-300">
        <h2 class="text-lg font-bold">
            Settings
        </h2>

        <p class="text-sm text-gray-600 mt-1">
            Manage SiteDesk configuration.
        </p>
    </div>

    <nav class="p-3 space-y-1 text-sm">
        @if (Route::has('admin.settings.index'))
            <a href="{{ route('admin.settings.index') }}"
               class="block px-4 py-3 border rounded-none {{ request()->routeIs('admin.settings.index') ? 'border-black bg-black text-white' : 'border-transparent hover:border-gray-300 text-gray-900' }}">
                Settings overview
            </a>
        @endif

        @if (Route::has('admin.settings.edit'))
            <a href="{{ route('admin.settings.edit') }}"
               class="block px-4 py-3 border rounded-none {{ request()->routeIs('admin.settings.edit') ? 'border-black bg-black text-white' : 'border-transparent hover:border-gray-300 text-gray-900' }}">
                Portal settings
            </a>
        @endif

        @if (Route::has('admin.settings.ai.edit'))
            <a href="{{ route('admin.settings.ai.edit') }}"
               class="block px-4 py-3 border rounded-none {{ request()->routeIs('admin.settings.ai.*') ? 'border-black bg-black text-white' : 'border-transparent hover:border-gray-300 text-gray-900' }}">
                General AI settings
            </a>
        @endif

        @if (Route::has('admin.pricing-settings.edit'))
            <a href="{{ route('admin.pricing-settings.edit') }}"
               class="block px-4 py-3 border rounded-none {{ request()->routeIs('admin.pricing-settings.*') ? 'border-black bg-black text-white' : 'border-transparent hover:border-gray-300 text-gray-900' }}">
                AI pricing settings
            </a>
        @endif

        @if (Route::has('admin.settings.quote-pack.edit'))
            <a href="{{ route('admin.settings.quote-pack.edit') }}"
               class="block px-4 py-3 border rounded-none {{ request()->routeIs('admin.settings.quote-pack.*') ? 'border-black bg-black text-white' : 'border-transparent hover:border-gray-300 text-gray-900' }}">
                Quote pack
            </a>
        @endif

        @if (Route::has('admin.users.index'))
            <a href="{{ route('admin.users.index') }}"
               class="block px-4 py-3 border rounded-none {{ request()->routeIs('admin.users.*') ? 'border-black bg-black text-white' : 'border-transparent hover:border-gray-300 text-gray-900' }}">
                Users
            </a>
        @endif
    </nav>
</aside>