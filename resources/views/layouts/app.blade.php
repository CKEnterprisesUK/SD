<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CK Enterprises UK') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cabin+Sketch:wght@400;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
            @php
    $portalSettings = \App\Models\PortalSetting::current();
@endphp

<footer class="border-t border-gray-300 bg-white mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-sm text-gray-600 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
            <span>{{ $portalSettings->company_name ?: $portalSettings->portal_name }}</span>

            <nav class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <a href="{{ route('legal.privacy') }}" class="hover:text-gray-900">Privacy Notice</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-gray-900">Terms of Service</a>
                <a href="{{ route('legal.cookies') }}" class="hover:text-gray-900">Cookies Policy</a>
            </nav>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-gray-400">Powered by</span>
            <x-brand-logo size="sm" />
        </div>
    </div>
</footer>
        </div>
    </body>
</html>
