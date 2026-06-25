<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Users
                </h2>

                <p class="text-sm text-gray-600 mt-1">
                    Manage admin access to SiteDesk.
                </p>
            </div>

            <a href="{{ route('admin.settings.index') }}"
               class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                Back to settings
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                <p class="font-semibold mb-2">There is a problem with the form.</p>

                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <aside class="lg:col-span-1">
                @includeIf('admin.settings.partials.sidebar')
            </aside>

            <main class="lg:col-span-3 space-y-6">
                <section class="border border-gray-300 bg-white">
                    <div class="p-6 border-b border-gray-300">
                        <h1 class="text-2xl font-bold text-gray-900">
                            Admin users
                        </h1>

                        <p class="text-sm text-gray-600 mt-2">
                            Add internal admin users and send password reset emails. Public self-registration is disabled.
                        </p>
                    </div>

                    <div class="p-6">
                        <form method="POST"
                              action="{{ route('admin.settings.users.store') }}"
                              class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @csrf

                            <div>
                                <label for="name" class="block text-sm font-semibold mb-2">
                                    Name <span class="text-red-700">*</span>
                                </label>

                                <input id="name"
                                       name="name"
                                       type="text"
                                       value="{{ old('name') }}"
                                       class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                       required>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-semibold mb-2">
                                    Email <span class="text-red-700">*</span>
                                </label>

                                <input id="email"
                                       name="email"
                                       type="email"
                                       value="{{ old('email') }}"
                                       class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                       required>
                            </div>

                            <div class="md:col-span-2">
                                <label class="inline-flex items-start gap-3">
                                    <input type="checkbox"
                                           name="send_password_reset"
                                           value="1"
                                           class="mt-1"
                                           checked>

                                    <span>
                                        <span class="block text-sm font-semibold">
                                            Send password reset email
                                        </span>

                                        <span class="block text-xs text-gray-600 mt-1">
                                            The new admin will receive Laravel’s standard password reset email and set their own password.
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <div class="md:col-span-2">
                                <button type="submit"
                                        class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                                    Add admin user
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="border border-gray-300 bg-white">
                    <div class="p-6 border-b border-gray-300">
                        <h2 class="text-lg font-bold text-gray-900">
                            Existing admin users
                        </h2>

                        <p class="text-sm text-gray-600 mt-1">
                            Email addresses are not edited here. Send a password reset if an admin needs access.
                        </p>
                    </div>

                    <div class="p-6">
                        <div class="overflow-x-auto border border-gray-300">
                            <table class="min-w-full divide-y divide-gray-300 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                            Name
                                        </th>

                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                            Email
                                        </th>

                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                            Status
                                        </th>

                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                            Created
                                        </th>

                                        <th class="px-4 py-3 text-right font-semibold text-gray-700">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($users as $user)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold">
                                                {{ $user->name }}
                                            </td>

                                            <td class="px-4 py-3">
                                                {{ $user->email }}
                                            </td>

                                            <td class="px-4 py-3">
                                                @php
                                                    $status = $user->status ?? 'active';

                                                    $statusClass = match ($status) {
                                                        'active' => 'border-green-700 bg-green-50 text-green-900',
                                                        'inactive' => 'border-gray-500 bg-gray-50 text-gray-800',
                                                        default => 'border-gray-400 bg-white text-gray-700',
                                                    };
                                                @endphp

                                                <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $statusClass }}">
                                                    {{ ucfirst($status) }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3 whitespace-nowrap">
                                                {{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                <form method="POST"
                                                      action="{{ route('admin.settings.users.send-password-reset', $user) }}">
                                                    @csrf

                                                    <button type="submit"
                                                            class="underline text-sm font-semibold">
                                                        Send password reset
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-600">
                                                No admin users have been added yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</x-app-layout>