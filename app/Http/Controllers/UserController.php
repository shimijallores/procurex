<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $builder = User::with(['roles', 'office']);

        $currentUser = $request->user()?->loadMissing('roles');

        if ($currentUser && ! $currentUser->hasRole(RoleType::SUPERADMIN->value)) {
            $builder->where('office_id', $currentUser->office_id);
        }

        $lengthAwarePaginator = $builder
            ->when($request->search, function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('User/Index', [
            'users' => $lengthAwarePaginator,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $user = Auth::user()?->loadMissing('roles');
        if ($user && ! $user->hasRole(RoleType::SUPERADMIN->value)) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to create users.');
        }

        return Inertia::render('User/Create', [
            'roles' => Role::all(['id', 'name', 'is_system_role', 'office_id']),
            'offices' => Office::all(['id', 'name']),
            'systemRoles' => RoleType::systemRoles(),
        ]);
    }

    public function store(StoreUserRequest $storeUserRequest): RedirectResponse
    {
        $user = Auth::user()?->loadMissing('roles');
        if ($user && ! $user->hasRole(RoleType::SUPERADMIN->value)) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to create users.');
        }

        $validated = $storeUserRequest->validated();
        $roleIds = $validated['role_ids'];
        unset($validated['role_ids']);
        $validated['password'] = Hash::make($validated['password']);

        $createdUser = User::create($validated);
        $createdUser->roles()->sync($roleIds);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): Response
    {
        $user->load(['roles', 'office']);

        return Inertia::render('User/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): Response|RedirectResponse
    {
        $authUser = Auth::user()?->loadMissing('roles');
        if ($authUser && ! $authUser->hasRole(RoleType::SUPERADMIN->value)) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to edit users.');
        }

        $user->load('roles');

        return Inertia::render('User/Edit', [
            'user' => $user,
            'roles' => Role::all(['id', 'name', 'is_system_role', 'office_id']),
            'offices' => Office::all(['id', 'name']),
            'systemRoles' => RoleType::systemRoles(),
        ]);
    }

    public function update(UpdateUserRequest $updateUserRequest, User $user): RedirectResponse
    {
        $authUser = Auth::user()?->loadMissing('roles');
        if ($authUser && ! $authUser->hasRole(RoleType::SUPERADMIN->value)) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to edit users.');
        }

        $validated = $updateUserRequest->validated();
        $roleIds = $validated['role_ids'];
        unset($validated['role_ids']);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->roles()->sync($roleIds);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $authUser = Auth::user()?->loadMissing('roles');
        if ($authUser && ! $authUser->hasRole(RoleType::SUPERADMIN->value)) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to delete users.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
