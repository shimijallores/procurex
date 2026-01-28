<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        $lengthAwarePaginator = User::with(['role', 'office'])
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

    public function create(): Response
    {
        return Inertia::render('User/Create', [
            'roles' => Role::all(['id', 'name']),
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $storeUserRequest): RedirectResponse
    {
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

    public function edit(User $user): Response
    {
        return Inertia::render('User/Edit', [
            'user' => $user,
            'roles' => Role::all(['id', 'name']),
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function update(UpdateUserRequest $updateUserRequest, User $user): RedirectResponse
    {
        $validated = $updateUserRequest->validated();

        if (! empty($validated['password'])) {
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
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
