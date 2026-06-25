<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $users = User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->get();

        return view('admin.settings.users', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'send_password_reset' => ['nullable', 'boolean'],
        ]);

        $user = new User();

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make(Str::random(48)),
        ])->save();

        if ($request->boolean('send_password_reset', true)) {
            $status = Password::sendResetLink([
                'email' => $user->email,
            ]);

            if ($status !== Password::RESET_LINK_SENT) {
                return redirect()
                    ->route('admin.settings.users.index')
                    ->with('status', 'Admin user created, but the password reset email could not be sent.');
            }
        }

        return redirect()
            ->route('admin.settings.users.index')
            ->with('status', 'Admin user created and password reset email sent.');
    }

    public function sendPasswordReset(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        abort_unless($user->role === 'admin', 403);

        if (! $user->email) {
            return redirect()
                ->route('admin.settings.users.index')
                ->with('status', 'This admin user does not have an email address.');
        }

        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()
                ->route('admin.settings.users.index')
                ->with('status', 'Password reset email sent to ' . $user->email . '.');
        }

        return redirect()
            ->route('admin.settings.users.index')
            ->with('status', 'The password reset email could not be sent.');
    }
}