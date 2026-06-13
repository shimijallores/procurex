<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = Account::query()
            ->when($request->search, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('code', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('main_category', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('subcategory', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->when($request->main_category, function ($query, string $mainCategory): void {
                $query->where('main_category', $mainCategory);
            })
            ->when($request->subcategory, function ($query, string $subcategory): void {
                $query->where('subcategory', $subcategory);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $mainCategories = Account::query()
            ->distinct()
            ->pluck('main_category')
            ->sort()
            ->values();

        $subcategories = Account::query()
            ->whereNotNull('subcategory')
            ->when($request->main_category, function ($query, string $mainCategory): void {
                $query->where('main_category', $mainCategory);
            })
            ->distinct()
            ->pluck('subcategory')
            ->sort()
            ->values();

        return Inertia::render('Accounts/Index', [
            'accounts' => $lengthAwarePaginator,
            'mainCategories' => $mainCategories,
            'subcategories' => $subcategories,
            'filters' => [
                'search' => $request->search,
                'main_category' => $request->main_category,
                'subcategory' => $request->subcategory,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounts/Create');
    }

    public function store(StoreAccountRequest $storeAccountRequest): RedirectResponse
    {
        Account::create($storeAccountRequest->validated());

        return redirect()->route('accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function show(Account $account): Response
    {
        return Inertia::render('Accounts/Show', [
            'account' => $account,
        ]);
    }

    public function edit(Account $account): Response
    {
        return Inertia::render('Accounts/Edit', [
            'account' => $account,
        ]);
    }

    public function update(UpdateAccountRequest $updateAccountRequest, Account $account): RedirectResponse
    {
        $account->update($updateAccountRequest->validated());

        return redirect()->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
