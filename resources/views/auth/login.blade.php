<x-guest-layout>
    <div
        class="min-h-screen bg-gray-900 bg-cover bg-center bg-no-repeat"
        style="background-image: linear-gradient(rgba(17, 24, 39, 0.72), rgba(17, 24, 39, 0.72)), url('https://greenst.co.uk/wp-content/uploads/2026/04/project-patios-12.png');"
    >
        <div class="min-h-screen flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="bg-white border border-gray-300 shadow-xl">
                    <div class="p-8 border-b border-gray-200">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-12 w-12 flex items-center justify-center border-2 border-gray-900 font-bold text-sm">
                                SD
                            </div>

                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">
                                    SiteDesk
                                </h1>
                                <p class="text-sm text-gray-600">
                                    Contractor invoice portal
                                </p>
                            </div>
                        </div>

                        <h2 class="text-xl font-bold text-gray-900">
                            Sign in to your account
                        </h2>

                        <p class="text-sm text-gray-600 mt-2">
                            Access your SiteDesk modules, invoices and contractor records.
                        </p>
                    </div>

                    <div class="p-8">
                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}">
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

                            <div class="mt-5">
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

                            <div class="flex items-center justify-between gap-4 mt-5">
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

                            <div class="mt-8">
                                <button
                                    type="submit"
                                    class="w-full px-5 py-3 bg-black text-white text-sm font-semibold hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900"
                                >
                                    Sign in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-6 text-center text-sm text-white/80">
                    Generated using SiteDesk — A CK Enterprises Product
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>