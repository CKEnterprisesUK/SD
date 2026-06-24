<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit User Access
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="border border-gray-300 bg-white p-6 mb-6">
            <h1 class="text-xl font-bold">{{ $user->name }}</h1>
            <p class="text-sm text-gray-600">{{ $user->email }}</p>
            <p class="text-sm text-gray-600 mt-2">
                Name and email are locked from this screen.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                    <p class="font-semibold mb-2">There is a problem with the form.</p>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-semibold mb-2" for="role">Role</label>
                <select id="role" name="role" class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                    <option value="contractor" @selected(old('role', $user->role) === 'contractor')>Contractor</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2" for="status">Status</label>
                <select id="status" name="status" class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                </select>
            </div>

            <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                Save access
            </button>
        </form>
    </div>
</x-app-layout>