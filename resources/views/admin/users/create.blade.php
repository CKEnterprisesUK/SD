<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add User
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

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
                <label class="block text-sm font-semibold mb-2" for="name">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" class="w-full border border-gray-400 px-4 py-3 rounded-none">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full border border-gray-400 px-4 py-3 rounded-none">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2" for="role">Role</label>
                <select id="role" name="role" class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    <option value="admin">Admin</option>
                    <option value="contractor">Contractor</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2" for="status">Status</label>
                <select id="status" name="status" class="w-full border border-gray-400 px-4 py-3 rounded-none">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2" for="password">Password</label>
                <input id="password" name="password" type="password" class="w-full border border-gray-400 px-4 py-3 rounded-none">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="w-full border border-gray-400 px-4 py-3 rounded-none">
            </div>

            <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                Save user
            </button>
        </form>
    </div>
</x-app-layout>