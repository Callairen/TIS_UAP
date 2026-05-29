<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data' => null,
                'errors' => null
            ], 401);
        }

        $user = auth('api')->user();

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'data' => null,
                'errors' => null
            ], 403);
        }

        return $next($request);
    }
}