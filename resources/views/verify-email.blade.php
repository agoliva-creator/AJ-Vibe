@extends('layout')
@section('title', 'Verify email · AJ Vibe')
@section('content')
<section class="card p-4 p-md-5 mx-auto" style="max-width:600px"><p class="fashion-kicker">AJ Vibe / Account verification</p><h1 class="h3 mb-3">Verify your email.</h1><p class="text-muted">Open the verification link sent to <strong>{{ auth()->user()->email }}</strong>. Email verification is optional. You can shop and place orders while waiting.</p><form method="post" action="{{ route('verification.send') }}">@csrf<button class="btn btn-primary w-100">Resend verification email</button></form><a class="btn btn-outline-secondary w-100 mt-3" href="{{ route('account') }}">Check or change email address</a><a class="small mt-4" href="{{ route('catalog.index') }}">Continue shopping →</a></section>
@endsection
