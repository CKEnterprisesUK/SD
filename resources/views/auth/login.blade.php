<x-guest-layout>
    @php
        $portalSettings = \App\Models\PortalSetting::current();
        $appName = $portalSettings->portal_name ?: config('app.name', 'SiteDesk');
    @endphp

    <div class="min-h-screen bg-white">
        <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
            {{-- Left image panel - desktop only --}}
            <div
                class="hidden lg:block bg-gray-900 bg-cover bg-center bg-no-repeat"
                style="background-image: linear-gradient(rgba(17, 24, 39, 0.25), rgba(17, 24, 39, 0.25)), url('https://greenst.co.uk/wp-content/uploads/2026/04/project-patios-10.png');"
            >
                <div class="h-full w-full flex items-end p-10">
                    <div class="text-white">
                        <div class="text-sm uppercase tracking-widest opacity-80">
                            SiteDesk
                        </div>
                        <div class="mt-2 text-3xl font-bold">
                            {{ $appName }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Login panel --}}
            <div class="flex min-h-screen items-center justify-center px-6 py-12 sm:px-10 lg:px-16">
                <div class="w-full max-w-md">
                    <div class="mb-10">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 flex items-center justify-center border-2 border-gray-900 font-bold text-sm">
                                SD
                            </div>

                            <div>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ $appName }}
                                </div>

                                <div class="text-sm text-gray-600">
                                    SiteDesk
                                </div>
                            </div>
                        </div>
                    </div>

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

                    <div class="mt-10 border-t border-gray-300 pt-6 space-y-4">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-gray-400">Powered by</span>
                            <x-brand-logo size="sm" />
                        </div>

                        <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-500">
                            <a href="{{ route('legal.privacy') }}" class="hover:text-gray-900">Privacy Notice</a>
                            <a href="{{ route('legal.terms') }}" class="hover:text-gray-900">Terms of Service</a>
                            <a href="{{ route('legal.cookies') }}" class="hover:text-gray-900">Cookies Policy</a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>