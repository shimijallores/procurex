<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = Role::with('office')
            ->withCount('users')
            ->when($request->search, function ($query, string $search): void {
                $query->where('name', 'like', sprintf('%%%s%%', $search));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Role/Index', [
            'roles' => $lengthAwarePaginator,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Role/Create', [
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'office_id' => ['required', 'exists:offices,id'],
        ]);

        Role::create($validated);

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(Role $role): Response
    {
        $role->load('users', 'office');
        $role->loadCount('users');

        return Inertia::render('Role/Show', [
            'role' => $role,
        ]);
    }

    public function edit(Role $role): RedirectResponse|Response
    {
        // Prevent editing system roles
        if ($role->isSystemRole()) {
            return redirect()->route('roles.index')
                ->with('error', 'System roles cannot be edited.');
        }

        return Inertia::render('Role/Edit', [
            'role' => $role,
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        // Prevent editing system roles
        if ($role->isSystemRole()) {
            return redirect()->route('roles.index')
                ->with('error', 'System roles cannot be edited.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'office_id' => ['required', 'exists:offices,id'],
        ]);

        $role->update($validated);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        // Prevent deleting system roles
        if ($role->isSystemRole()) {
            return redirect()->route('roles.index')
                ->with('error', 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
