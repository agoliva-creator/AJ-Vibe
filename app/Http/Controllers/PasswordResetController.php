<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function send(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink(['email' => $request->validated('email'), 'active' => true]);

        return back()->with('success', 'If an active account matches this email, a reset link will be sent. Check your inbox and spam folder.');
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset($request->safe()->only(['email', 'password', 'password_confirmation', 'token']) + ['active' => true], function (User $user, string $password): void {
            DB::transaction(function () use ($user, $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $user->tokens()->delete();
                if (config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }
            });
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'This reset link is invalid or expired. Please request a new link.'])->withInput($request->only('email'));
        }

        return redirect()->route('login')->with('success', 'Password updated. Sign in with your new password.');
    }
}
