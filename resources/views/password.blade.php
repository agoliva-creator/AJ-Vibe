@extends('layout')
@section('title', ($reset ? 'Reset password' : 'Forgot password').' · AJ Vibe')
@section('content')
<section class="card p-4 p-md-5 mx-auto" style="max-width:560px"><p class="fashion-kicker">AJ Vibe / Account recovery</p><h1 class="h3 mb-3">{{ $reset ? 'Choose a new password.' : 'Forgot your password?' }}</h1><p class="text-muted small">{{ $reset ? 'Use at least 12 characters with uppercase, lowercase, and numbers.' : 'Enter your registered email and we will send a password reset link.' }}</p>
<form method="post" action="{{ $reset ? route('password.update') : route('password.email') }}">@csrf
@if($reset)<input type="hidden" name="token" value="{{ $token }}">@endif
<label class="form-label" for="email">Email address</label><input class="form-control mb-3" id="email" name="email" type="email" value="{{ old('email',request('email')) }}" required autocomplete="email">
@if($reset)
<label class="form-label" for="password">New password</label><input class="form-control mb-3" id="password" name="password" type="password" required autocomplete="new-password">
<label class="form-label" for="password_confirmation">Confirm new password</label><input class="form-control mb-3" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
@endif
<button class="btn btn-primary w-100 mt-2">{{ $reset ? 'Reset password' : 'Send reset link' }}</button></form><a class="btn btn-outline-secondary w-100 mt-3 d-inline-flex align-items-center justify-content-center gap-2" href="{{ route('login') }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12H4m6-6-6 6 6 6"/></svg><span>Back to sign in</span></a></section>
@endsection
