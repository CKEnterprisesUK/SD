<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Admin\UserController;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.users.index', [
            'users' => User::latest()->paginate(10),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,contractor'],
            'status' => ['required', 'in:active,inactive'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'role' => ['required', 'in:admin,contractor'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $user->update($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
            
            Route::get('/users', [UserController::class, 'index'])
    ->name('users.index');

Route::get('/users/create', [UserController::class, 'create'])
    ->name('users.create');

Route::post('/users', [UserController::class, 'store'])
    ->name('users.store');

Route::get('/users/{user}/edit', [UserController::class, 'edit'])
    ->name('users.edit');

Route::put('/users/{user}', [UserController::class, 'update'])
    ->name('users.update');
    }
    
}