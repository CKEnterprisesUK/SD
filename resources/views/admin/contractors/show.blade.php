<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Contractor Details
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-start justify-between gap-6 mb-8">
            <div>
                <a href="{{ route('admin.contractors.index') }}" class="text-sm underline">
                    Back to contractors
                </a>

                <h1 class="text-3xl font-bold mt-4">
                    {{ $contractor->name }}
                </h1>

                <p class="text-gray-600 mt-1">
                    {{ $contractor->email }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.contractors.edit', $contractor) }}"
                   class="px-4 py-2 border border-gray-900 text-sm font-semibold">
                    Edit contractor
                </a>

                <form method="POST" action="{{ route('admin.contractors.send-invite', $contractor) }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-black text-white text-sm font-semibold">
                        Send invite/reset
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Contractor profile</h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-semibold text-gray-700">Company</dt>
                        <dd>{{ $contractor->company_name ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Phone</dt>
                        <dd>{{ $contractor->phone ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Day rate</dt>
                        <dd>
                            <span title="£{{ $contractor->day_rate }}" class="cursor-help tracking-widest">
                                ***
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Status</dt>
                        <dd>{{ ucfirst($contractor->status) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Login account</h2>

                @if ($contractor->user)
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-semibold text-gray-700">User role</dt>
                            <dd>{{ ucfirst($contractor->user->role) }}</dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700">Account status</dt>
                            <dd>{{ ucfirst($contractor->user->status) }}</dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700">Last login</dt>
                            <dd>
                                {{ $contractor->user->last_login_at ? $contractor->user->last_login_at->format('d M Y H:i') : 'Not recorded yet' }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-gray-600">
                        No login account is linked yet.
                    </p>
                @endif
            </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
           <section class="border border-gray-300 bg-white p-6">
    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-semibold">Invoices</h2>
            <p class="text-sm text-gray-600 mt-1">
                Contractor-submitted invoice history.
            </p>
        </div>

        <a href="{{ route('admin.invoices.index', ['contractor_id' => $contractor->id]) }}"
           class="text-sm underline">
            View all
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-50 text-left">
                    <th class="px-3 py-2 font-semibold">Invoice</th>
                    <th class="px-3 py-2 font-semibold">Week</th>
                    <th class="px-3 py-2 font-semibold">Days</th>
                    <th class="px-3 py-2 font-semibold">Total</th>
                    <th class="px-3 py-2 font-semibold">Status</th>
                    <th class="px-3 py-2 font-semibold">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($invoices as $invoice)
                    <tr class="border-b border-gray-200">
                        <td class="px-3 py-2 font-semibold">
                            {{ $invoice->invoice_number }}
                        </td>

                        <td class="px-3 py-2">
                            {{ $invoice->week_commencing->format('d M Y') }}
                        </td>

                        <td class="px-3 py-2">
                            {{ $invoice->days_worked }}
                        </td>

                        <td class="px-3 py-2">
                            £{{ $invoice->total }}
                        </td>

                        <td class="px-3 py-2">
                            {{ ucfirst($invoice->status) }}
                        </td>

                        <td class="px-3 py-2">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="underline">
                                    View
                                </a>

                                <a href="{{ route('admin.invoices.download', $invoice) }}" class="underline">
                                    Download
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-600">
                            No invoices have been submitted by this contractor yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
</section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Timesheets</h2>
                <p class="text-sm text-gray-600">
                    Submitted timesheets will appear here once the timesheet module is added.
                </p>
            </section>
        </div>

        <section class="border border-gray-300 bg-white p-6 mt-6">
            <h2 class="text-lg font-semibold mb-4">Activity log</h2>

            <div class="space-y-4">
                @forelse ($contractor->activityLogs as $log)
                    <div class="border-l-4 border-gray-300 pl-4">
                        <div class="text-sm font-semibold">
                            {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                        </div>

                        <div class="text-sm text-gray-600">
                            {{ $log->description }}
                        </div>

                        <div class="text-xs text-gray-500 mt-1">
                            {{ $log->created_at->format('d M Y H:i') }}
                            @if ($log->user)
                                by {{ $log->user->name }}
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">
                        No activity has been recorded yet.
                    </p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>