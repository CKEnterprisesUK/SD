<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Profile Information
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Your profile details are managed by an administrator.
        </p>
    </header>

    <div class="mt-6 space-y-4">
        <div>
            <x-input-label value="Name" />
            <div class="mt-1 border border-gray-300 bg-gray-50 px-4 py-3">
                {{ auth()->user()->name }}
            </div>
        </div>

        <div>
            <x-input-label value="Email" />
            <div class="mt-1 border border-gray-300 bg-gray-50 px-4 py-3">
                {{ auth()->user()->email }}
            </div>
        </div>

        <div>
            <x-input-label value="Role" />
            <div class="mt-1 border border-gray-300 bg-gray-50 px-4 py-3">
                {{ ucfirst(auth()->user()->role) }}
            </div>
        </div>
    </div>
</section>