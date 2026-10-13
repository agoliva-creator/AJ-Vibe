<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActive
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user() && (! $request->user()->active || $request->user()->trashed()), 403, 'This account is disabled.');

        return $next($request);
    }
}
