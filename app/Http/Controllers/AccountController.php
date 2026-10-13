<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function update(ProfileRequest $request, ImageService $images): mixed
    {
        $user = $request->user();
        $old = $user->avatar_path;
        $path = $request->hasFile('avatar') ? $images->store($request->file('avatar'), 'avatars') : null;
        $data = $request->safe()->only(['name', 'email']);
        $emailChanged = isset($data['email']) && $data['email'] !== $user->email;
        if ($emailChanged) {
            $data['email_verified_at'] = null;
        }
        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
            $data['remember_token'] = null;
        }
        if ($path) {
            $data['avatar_path'] = $path;
        }
        try {
            DB::transaction(function () use ($user, $data, $emailChanged, $request): void {
                $user->forceFill($data)->save();
                if ($emailChanged || $request->filled('password')) {
                    $user->tokens()->delete();
                }
                if ($request->filled('password') && config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
                }
            });
        } catch (\Throwable $exception) {
            $images->delete($path);
            throw $exception;
        }
        if ($path) {
            $images->delete($old);
        }
        if ($emailChanged || $request->filled('password')) {
            $request->session()->regenerate();
        }
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('success', 'Profile updated.');
    }
}
