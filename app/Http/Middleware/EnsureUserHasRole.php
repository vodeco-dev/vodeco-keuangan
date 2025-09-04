<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\Role;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (!$user || ($user->role !== Role::ADMIN && !in_array($user->role->value, $roles))) {
            abort(403);
        }

        return $next($request);
    }
}
