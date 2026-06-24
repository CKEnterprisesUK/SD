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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-sm text-gray-600 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            {{ $portalSettings->company_name ?: $portalSettings->portal_name }}
        </div>

        <div>
            {{ $portalSettings->pdf_footer ?: 'Powered by SiteDesk — A CK Enterprises Product' }}
        </div>
    </div>
</footer>
        </div>
    </body>
</html>
