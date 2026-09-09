<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        return $request->user() && ! $request->user()->is_admin
            ? $next($request)
            : redirect()->route('dashboard');
    }
}