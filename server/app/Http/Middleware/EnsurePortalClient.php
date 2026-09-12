<?php

namespace App\Http\Middleware;

use App\Guard\AuthGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = AuthGuard::requireUser($request->user());

        if (!$user->client) {
            return response()->json([
                'message' => 'Only family accounts can use the portal.',
            ], 403);
        }

        $request->attributes->set('client', $user->client);

        return $next($request);
    }
}
