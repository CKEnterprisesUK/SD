<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                General AI settings
            </h2>

            <p class="text-sm text-gray-600 mt-1">
                Configure how SiteDesk uses AI across estimates and customer packs.
            </p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6">
            @include('admin.settings.partials.sidebar')

            <main class="space-y-6">
                @if (session('status'))
                    <div class="border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                        <p class="font-semibold mb-2">There is a problem.</p>

                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="border border-gray-300 bg-white">
                    <div class="p-6 border-b border-gray-300">
                        <h1 class="text-2xl font-bold">
                            General AI settings
                        </h1>

                        <p class="text-sm text-gray-600 mt-2">
                            These settings control the default AI behaviour. If a model field is left blank, SiteDesk uses the value from your environment file.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.ai.update') }}" class="p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="border border-gray-300 bg-gray-50 p-4">
                            <label class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="ai_enabled"
                                    value="1"
                                    class="mt-1"
                                    @checked(old('ai_enabled', $settings->ai_enabled ?? true))
                                >

                                <span>
                                    <span class="block font-semibold text-sm">
                                        Enable AI tools
                                    </span>

                                    <span class="block text-sm text-gray-600 mt-1">
                                        Allows users to generate estimates and customer wording.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold mb-2">
                                    Estimate model
                                </label>

                                <input
                                    type="text"
                                    name="ai_estimate_model"
                                    value="{{ old('ai_estimate_model', $settings->ai_estimate_model ?? '') }}"
                                    placeholder="Example: gpt-4o"
                                    class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                >

                                <p class="text-xs text-gray-500 mt-2">
                                    Used by the AI estimate assistant.
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">
                                    Wording model
                                </label>

                                <input
                                    type="text"
                                    name="ai_wording_model"
                                    value="{{ old('ai_wording_model', $settings->ai_wording_model ?? '') }}"
                                    placeholder="Example: gpt-4o"
                                    class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                >

                                <p class="text-xs text-gray-500 mt-2">
                                    Used by the customer pack wording generator.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold mb-2">
                                    Default tone
                                </label>

                                <select
                                    name="ai_default_tone"
                                    class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                >
                                    @foreach ([
                                        'professional' => 'Professional',
                                        'friendly' => 'Friendly',
                                        'premium' => 'Premium',
                                        'plain_english' => 'Plain English',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('ai_default_tone', $settings->ai_default_tone ?? 'professional') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">
                                    Creativity
                                </label>

                                <input
                                    type="number"
                                    min="0"
                                    max="2"
                                    step="0.01"
                                    name="ai_temperature"
                                    value="{{ old('ai_temperature', $settings->ai_temperature ?? '0.20') }}"
                                    class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                    required
                                >

                                <p class="text-xs text-gray-500 mt-2">
                                    Lower is more consistent. Recommended: 0.20.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 border-t border-gray-300 pt-6">
                            <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                                Save AI settings
                            </button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>
</x-app-layout>