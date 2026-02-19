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
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with(['role', 'office']);

        // Filter based on current user's role
        $currentUser = $request->user();

        if ($currentUser && $currentUser->role) {
            $currentUserRoleName = $currentUser->role->name;

            // Superadmin can see all users
            if (RoleType::isSystemRole($currentUserRoleName)) {
                if ($currentUserRoleName === RoleType::SUPERADMIN->value) {
                    // Superadmin sees all users — no filter
                } else {
                    // BAC Reso Admin, Budgeting Admin, Canvassing Admin see only users with their own role
                    $query->whereHas('role', function ($q) use ($currentUserRoleName) {
                        $q->where('name', $currentUserRoleName);
                    });
                }
            } else {
                // Office role users see only their office's users
                $query->where('office_id', $currentUser->role->office_id);
            }
        }

        $lengthAwarePaginator = $query
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

    public function create(): Response | RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->role && !RoleType::isSystemRole($user->role->name)) {
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
        $user = auth()->user();
        if ($user && $user->role && !RoleType::isSystemRole($user->role->name)) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to create users.');
        }

        $validated = $storeUserRequest->validated();
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): Response
    {
        $user->load(['role', 'office']);

        return Inertia::render('User/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): Response | RedirectResponse
    {
        $authUser = auth()->user();
        if ($authUser && $authUser->role && !RoleType::isSystemRole($authUser->role->name)) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to edit users.');
        }

        return Inertia::render('User/Edit', [
            'user' => $user,
            'roles' => Role::all(['id', 'name', 'is_system_role', 'office_id']),
            'offices' => Office::all(['id', 'name']),
            'systemRoles' => RoleType::systemRoles(),
        ]);
    }

    public function update(UpdateUserRequest $updateUserRequest, User $user): RedirectResponse
    {
        $authUser = auth()->user();
        if ($authUser && $authUser->role && !RoleType::isSystemRole($authUser->role->name)) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to edit users.');
        }

        $validated = $updateUserRequest->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $authUser = auth()->user();
        if ($authUser && $authUser->role && !RoleType::isSystemRole($authUser->role->name)) {
            return redirect()->route('users.index')
                ->with('error', 'You do not have permission to delete users.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
