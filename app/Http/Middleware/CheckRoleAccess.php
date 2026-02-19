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

        // Load role relationship if not already loaded
        if (! $user->relationLoaded('role')) {
            $user->load('role');
        }

        if (! $user->role) {
            return redirect()->route('login');
        }

        // Flatten comma-separated roles into array
        $roles = [];
        foreach ($allowedRoles as $allowedRole) {
            $roles = array_merge($roles, array_map('trim', explode(',', $allowedRole)));
        }

        $roleName = $user->role->name;

        // Special handling for 'office' role type
        if (in_array('office', $roles, true) && (isset($user->role->is_system_role) && $user->role->is_system_role === false && $user->role->office_id)) {
            return $next($request);
        }

        // Check if user's role is in the allowed roles
        if (in_array($roleName, $roles, true)) {
            return $next($request);
        }

        return redirect()->route('dashboard.index')
            ->with('error', 'You do not have access to this page.');
    }
}
