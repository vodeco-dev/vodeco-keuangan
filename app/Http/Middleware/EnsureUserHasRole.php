<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (!$user || (!$user->is_admin && !in_array($user->role, $roles))) {
            abort(403);
        }

        return $next($request);
    }
}
