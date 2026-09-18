<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // On success, send them to sign in with the confirmation message.
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        // An expired or already-used token is the most common failure here,
        // especially for invite/setup links that were opened too late. Rather
        // than leaving the customer on a dead form with a cryptic error, send
        // them to the "forgot password" page (with their email pre-filled) and
        // tell them to request a fresh link.
        if ($status == Password::INVALID_TOKEN) {
            return redirect()->route('password.request')
                ->withInput($request->only('email'))
                ->with('status', 'That password link has expired or already been used. Enter your email below and we will send you a fresh link to set your password.');
        }

        // Any other failure (e.g. unknown email) stays on the reset form.
        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
