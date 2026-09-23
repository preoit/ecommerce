<?php

namespace App\Http\Middleware;

use App\Support\PermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $permission = PermissionRegistry::requiredFor($request);
        if ($permission === null || ! Schema::hasTable('roles') || $request->user()?->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this feature.');
    }
}
