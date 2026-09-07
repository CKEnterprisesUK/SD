<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Audit Log
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('admin.projects.show', $project) }}" class="text-sm underline">
                Back to project
            </a>

            <h1 class="text-3xl font-bold mt-4">Audit log</h1>
            <p class="text-gray-600 mt-1">{{ $project->name }}</p>
        </div>

        <div class="border border-gray-300 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-50 text-left">
                        <th class="px-4 py-3 font-semibold">Actor</th>
                        <th class="px-4 py-3 font-semibold">Action</th>
                        <th class="px-4 py-3 font-semibold">Target</th>
                        <th class="px-4 py-3 font-semibold">Timestamp</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $metadata = $log->metadata ?? [];
                            $target = optional($log->document)->original_name
                                ?? optional($log->folder)->name
                                ?? ($metadata['original_name'] ?? $metadata['name'] ?? '—');
                        @endphp
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3">{{ optional($log->user)->name ?: 'System' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block border border-gray-400 px-2 py-1 text-xs font-semibold">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $target }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $log->created_at ? $log->created_at->format('d M Y H:i') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-600">
                                No audit entries have been recorded for this project yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
