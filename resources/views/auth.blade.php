@extends('layout')
@section('title', ($register ? 'Create account' : 'Sign in').' · AJ Vibe')
@section('main-class', 'fashion-main auth-main')
@section('content')
<div class="auth-grid fashion-container" @if(!$register) data-login-back-guard @endif><section class="auth-editorial" aria-label="The new collection"><img src="{{ asset('images/dashboard/bieber.jpg') }}" alt="Men's fashion collection"><div class="auth-editorial-copy"><p class="fashion-kicker">Everyday essentials / New collection</p><h2>Your style.<br>Your next chapter.</h2><p>A collection made for your everyday.</p><a class="fashion-outline-button" href="{{ route('catalog.index') }}">Explore the collection ↗</a></div></section>
<section class="auth-form-panel" aria-labelledby="auth-title"><nav class="auth-page-tabs" aria-label="Account access"><a href="{{ route('login') }}" @if(!$register) aria-current="page" @endif>Sign in</a><a href="{{ route('register') }}" @if($register) aria-current="page" @endif>Create account</a></nav><span class="fashion-kicker">AJ Vibe / Your personal space</span><h1 id="auth-title">{{ $register ? 'Your next chapter.' : 'Welcome back.' }}</h1><p class="auth-description">{{ $register ? 'Create your customer account and make everyday style your own.' : 'Your favorites, your orders, your style. All in one place.' }}</p>
<form method="post" action="{{ $register ? route('register') : route('login') }}">@csrf

@if($register)
<label class="form-label" for="name">Full name</label><input @class(['form-control mb-3', 'is-invalid' => $errors->has('name')]) id="name" name="name" value="{{ old('name') }}" placeholder="Enter your full name" maxlength="255" required autocomplete="name">@error('name')<p class="text-danger small">{{ $message }}</p>@enderror
@endif
<label class="form-label" for="email">Email address</label><input @class(['form-control mb-3', 'is-invalid' => $errors->has('email')]) id="email" type="email" name="email" value="{{ old('email') }}" @if($register) placeholder="you@example.com" @endif maxlength="255" required autocomplete="email">@error('email')<p class="text-danger small">{{ $message }}</p>@enderror
<label class="form-label" for="password">Password</label><input class="form-control mb-3" id="password" type="password" name="password" required autocomplete="{{ $register ? 'new-password' : 'current-password' }}">
@if($register)
<p class="small text-muted">At least 12 characters, including upper and lowercase letters and numbers.</p><label class="form-label" for="password_confirmation">Confirm password</label><input class="form-control mb-3" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
@endif
<button class="fashion-button w-100 my-3 justify-content-center" type="submit">{{ $register ? 'Create account' : 'Sign in' }} <span aria-hidden="true">→</span></button>
@if(!$register)<p class="text-center mb-3"><a class="small" href="{{ route('password.request') }}">Forgot password?</a></p>@endif
</form><p class="auth-switch">{{ $register ? 'Already have an account?' : 'New here?' }} <a href="{{ $register ? route('login') : route('register') }}">{{ $register ? 'Sign in' : 'Create an account' }}</a></p><div class="auth-bottom-note"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><span>Your personal AJ Vibe account.</span></div></section></div>
@endsection
