<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Project
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('admin.projects.show', $project) }}" class="text-sm underline">
                Back to project
            </a>

            <h1 class="text-2xl font-bold mt-4">Edit project</h1>
            <p class="text-sm text-gray-600 mt-1">{{ $project->name }}</p>
        </div>

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

        <form method="POST" action="{{ route('admin.projects.update', $project) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="border border-gray-300 bg-white p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold">Project details</h2>
                </div>

                <div>
                    <label for="name" class="block text-sm font-semibold mb-2">
                        Project name
                    </label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $project->name) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >
                </div>

                <div>
                    <label for="customer_id" class="block text-sm font-semibold mb-2">
                        Customer
                    </label>
                    <select
                        id="customer_id"
                        name="customer_id"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >
                        <option value="">Select a customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id', $project->customer_id) === (string) $customer->id)>
                                {{ $customer->company_name ?: $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reference" class="block text-sm font-semibold mb-2">
                        Reference
                    </label>
                    <input
                        id="reference"
                        name="reference"
                        type="text"
                        value="{{ old('reference', $project->reference) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                    >
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold mb-2">
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                    >{{ old('description', $project->description) }}</textarea>
                </div>
            </section>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Save changes
                </button>
                <a href="{{ route('admin.projects.show', $project) }}" class="text-sm underline">
                    Cancel
                </a>
            </div>
        </form>

        {{-- State change --}}
        <section class="border border-gray-300 bg-white p-6 mt-6">
            <h2 class="text-lg font-semibold mb-4">Project state</h2>

            <form method="POST" action="{{ route('admin.projects.state.update', $project) }}"
                  class="flex flex-wrap items-end gap-4">
                @csrf
                @method('PUT')

                <div class="min-w-[200px]">
                    <label for="state" class="block text-sm font-semibold mb-2">State</label>
                    <select id="state" name="state" class="w-full border border-gray-400 px-4 py-3 rounded-none">
                        @foreach (\App\Models\Project::STATES as $state)
                            <option value="{{ $state }}" @selected(old('state', $project->state) === $state)>
                                {{ $state }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="px-4 py-3 border border-gray-900 text-sm font-semibold rounded-none">
                    Update state
                </button>
            </form>
        </section>
    </div>
</x-app-layout>
