@props([
    'heading' => null,
    'subheading' => null,
])

@php
    $portalSettings = \App\Models\PortalSetting::current();
    $appName = $portalSettings->portal_name ?: config('app.name', 'SiteDesk');
    $pageHeading = $heading ?: $appName;
@endphp

<div class="min-h-screen bg-white">
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
        {{-- Left image panel - desktop only --}}
        <div
            class="hidden lg:block bg-gray-900 bg-cover bg-center bg-no-repeat"
            style="background-image: linear-gradient(rgba(17, 24, 39, 0.25), rgba(17, 24, 39, 0.25)), url('https://greenst.co.uk/wp-content/uploads/2026/04/project-patios-10.png');"
        >
        </div>

        {{-- Form panel --}}
        <div class="flex min-h-screen items-center justify-center px-6 py-12 sm:px-10 lg:px-16">
            <div class="w-full max-w-md">
                {{-- Company logo --}}
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

                {{-- Heading --}}
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ $pageHeading }}
                    </h1>

                    @if ($subheading)
                        <p class="mt-2 text-sm text-gray-600">
                            {{ $subheading }}
                        </p>
                    @endif
                </div>

                {{-- Page content --}}
                {{ $slot }}

                {{-- Footer --}}
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
