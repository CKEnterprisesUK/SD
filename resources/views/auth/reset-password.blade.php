<x-guest-layout>
    <x-auth-card
        heading="Choose a new password"
        subheading="Enter a new password for your account below."
    >
        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-input-label for="email" value="Email address" />

                <x-text-input
                    id="email"
                    class="block mt-2 w-full rounded-none border-gray-400 px-4 py-3"
                    type="email"
                    name="email"
                    :value="old('email', $request->email)"
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
                    autocomplete="new-password"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirm password" />

                <x-text-input
                    id="password_confirmation"
                    class="block mt-2 w-full rounded-none border-gray-400 px-4 py-3"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full px-5 py-3 bg-black text-white text-sm font-semibold hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900"
                >
                    Reset password
                </button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
