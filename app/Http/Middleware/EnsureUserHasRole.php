<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Enums\Role;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            throw new HttpException(403);
        }

        $role = $user->role;

        if ($role instanceof Role) {
            $roleValue = $role->value;
            $isAdmin = $role === Role::ADMIN;
        } else {
            $roleValue = is_string($role) ? strtolower($role) : $role;
            $isAdmin = $roleValue === Role::ADMIN->value;
        }

        $allowedRoles = array_map(
            static fn ($allowedRole) => is_string($allowedRole)
                ? strtolower($allowedRole)
                : $allowedRole,
            $roles,
        );

        if (!$isAdmin && !in_array($roleValue, $allowedRoles, true)) {
            throw new HttpException(403);
        }

        return $next($request);
    }
}
