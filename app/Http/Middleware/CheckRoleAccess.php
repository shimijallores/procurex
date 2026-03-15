<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$allowedRoles
     */
    public function handle(Request $request, Closure $next, ...$allowedRoles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->relationLoaded('roles')) {
            $user->load('roles');
        }

        if ($user->roles->isEmpty()) {
            return redirect()->route('login');
        }

        // Flatten comma-separated roles into array
        $roles = [];
        foreach ($allowedRoles as $allowedRole) {
            $roles = array_merge($roles, array_map('trim', explode(',', $allowedRole)));
        }

        $roleNames = $user->roles->pluck('name')->all();

        if (in_array('office', $roles, true) && $user->roles->contains(fn($role) => $role->is_system_role === false && $role->office_id)) {
            return $next($request);
        }

        if (! empty(array_intersect($roleNames, $roles))) {
            return $next($request);
        }

        return redirect()->route('dashboard.index')
            ->with('error', 'You do not have access to this page.');
    }
}
