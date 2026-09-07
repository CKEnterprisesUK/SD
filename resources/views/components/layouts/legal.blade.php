@php
    $portalSettings = \App\Models\PortalSetting::current();
    $companyName = $portalSettings->company_name ?: ($portalSettings->portal_name ?: 'CK Enterprises');
    $effectiveDate = $effectiveDate ?? 'September 2026';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ ($pageTitle ?? 'Legal') }} — {{ $portalSettings->portal_name ?: 'SiteDesk' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin+Sketch:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100 text-gray-900">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-gray-300">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <a href="{{ url('/') }}" class="inline-flex">
                    <x-brand-logo size="md" />
                </a>
            </div>
        </header>

        <main class="flex-1">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="bg-white border border-gray-300 shadow-sm p-6 sm:p-10">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                        {{ $pageTitle ?? 'Legal' }}
                    </h1>

                    <p class="mt-2 text-sm text-gray-500">
                        Last updated: {{ $effectiveDate }}
                    </p>

                    <div class="mt-8 space-y-6 text-sm leading-relaxed text-gray-700
                                [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-gray-900 [&_h2]:mt-8
                                [&_h2]:mb-2 [&_p]:mb-3 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:space-y-1
                                [&_a]:text-red-600 [&_a]:underline">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </main>

        <footer class="border-t border-gray-300 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">Powered by</span>
                    <x-brand-logo size="sm" />
                </div>

                <nav class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <a href="{{ route('legal.privacy') }}" class="hover:text-gray-900">Privacy Notice</a>
                    <a href="{{ route('legal.terms') }}" class="hover:text-gray-900">Terms of Service</a>
                    <a href="{{ route('legal.cookies') }}" class="hover:text-gray-900">Cookies Policy</a>
                </nav>
            </div>
        </footer>
    </div>
</body>
</html>
