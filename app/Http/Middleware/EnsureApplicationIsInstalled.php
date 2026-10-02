<?php

namespace App\Http\Middleware;

use App\Support\Installer\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationIsInstalled
{
    public function __construct(private readonly InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        $installerRequest = $request->is('install', 'install/*');
        $installed = $this->state->isInstalled();

        if ($installerRequest) {
            abort_if($installed, 404);

            return $next($request);
        }

        if (! $installed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Application installation is required.',
                    'install_url' => url('/install'),
                ], 503);
            }

            return redirect()->route('installer.index');
        }

        return $next($request);
    }
}
