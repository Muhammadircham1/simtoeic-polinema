<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PreventBackHistory
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Skip adding headers for file downloads (BinaryFileResponse)
        if ($response instanceof BinaryFileResponse) {
            return $response;
        }

        return $response->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');
    }
}
