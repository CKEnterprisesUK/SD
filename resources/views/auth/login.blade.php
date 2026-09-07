<x-guest-layout>
    <x-auth-card subheading="Sign in to your account">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="email" value="Email address" />

                <x-text-input
                    id="email"
                    class="block mt-2 w-full rounded-none border-gray-400 px-4 py-3"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                />

                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" value="Password" />

                <x-text-input
                    id="password"
                    class="block mt-2 w-full rounded-none border-gray-400 px-4 py-3"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input
                        id="remember_me"
                        type="checkbox"
                        class="rounded-none border-gray-400 text-gray-900 shadow-sm focus:ring-gray-900"
                        name="remember"
                    >

                    <span class="ms-2 text-sm text-gray-600">
                        Remember me
                    </span>
                </label>

                @if (Route::has('password.request'))
                    <a
                        class="text-sm underline text-gray-700 hover:text-gray-900"
                        href="{{ route('password.request') }}"
                    >
                        Forgot password?
                    </a>
                @endif
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full px-5 py-3 bg-black text-white text-sm font-semibold hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900"
                >
                    Sign in
                </button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
