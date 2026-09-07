<x-guest-layout>
    <x-auth-card
        heading="Reset your password"
        subheading="Enter your email address and we'll send you a link to choose a new password."
    >
        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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
                <button
                    type="submit"
                    class="w-full px-5 py-3 bg-black text-white text-sm font-semibold hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900"
                >
                    Email password reset link
                </button>
            </div>

            <div class="text-sm">
                <a class="underline text-gray-700 hover:text-gray-900" href="{{ route('login') }}">
                    Back to sign in
                </a>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
