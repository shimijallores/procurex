<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficeController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = Office::with('users')
            ->withCount('users')
            ->when($request->search, function ($query, string $search): void {
                $query->where('name', 'like', sprintf('%%%s%%', $search));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Office/Index', [
            'offices' => $lengthAwarePaginator,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Office/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:offices,name'],
        ]);

        Office::create($validated);

        return redirect()->route('offices.index')
            ->with('success', 'Office created successfully.');
    }

    public function show(Office $office): Response
    {
        $office->load('users');
        $office->loadCount('users');

        return Inertia::render('Office/Show', [
            'office' => $office,
        ]);
    }

    public function edit(Office $office): Response
    {
        return Inertia::render('Office/Edit', [
            'office' => $office,
        ]);
    }

    public function update(Request $request, Office $office): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:offices,name,'.$office->id],
        ]);

        $office->update($validated);

        return redirect()->route('offices.index')
            ->with('success', 'Office updated successfully.');
    }

    public function destroy(Office $office): RedirectResponse
    {
        $office->delete();

        return redirect()->route('offices.index')
            ->with('success', 'Office deleted successfully.');
    }
}
