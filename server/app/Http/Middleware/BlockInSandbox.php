<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInSandbox
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.sandbox')) {
            return response()->json([
                'message' => 'Subscriptions, renewals and new branches are managed on your live AMUMA, not in the demo.',
            ], 403);
        }

        return $next($request);
    }
}
