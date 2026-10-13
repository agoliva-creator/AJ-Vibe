<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiTokenController extends Controller
{
    public function store(LoginRequest $request): mixed
    {
        $user = User::where('email', $request->validated('email'))->where('active', true)->first();
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }
        $token = $user->createToken('commerce-api', ['commerce'], now()->addDays(7));

        return response()->json(['token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $token->accessToken->expires_at], 201);
    }

    public function destroy(Request $request): mixed
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }
}
