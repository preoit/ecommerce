<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_admin) {
            return redirect()->route('account.dashboard');
        }

        abort_if(! ($request->user()->is_active ?? true), 403, 'This administrator account is inactive.');

        return $next($request);
    }
}
