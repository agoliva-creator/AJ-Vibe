<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->validated() + ['active' => true])) {
            throw ValidationException::withMessages(['email' => 'The credentials do not match an active account.']);
        }
        $request->session()->regenerate();
        if ($request->user()->role === 'customer') {
            $this->cart->mergeGuest($request);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            Mail::to($user->email)->queue((new WelcomeMail($user->name))->afterCommit());
            $user->sendEmailVerificationNotification();

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();
        $this->cart->mergeGuest($request);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
