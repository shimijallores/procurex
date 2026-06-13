<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectCodeRequest;
use App\Http\Requests\UpdateProjectCodeRequest;
use App\Models\Office;
use App\Models\ProjectCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectCodeController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = ProjectCode::with('office')
            ->when($request->search, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('code', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('office', function ($officeQuery) use ($search): void {
                            $officeQuery->where('name', 'like', sprintf('%%%s%%', $search))
                                ->orWhere('code', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('ProjectCodes/Index', [
            'projectCodes' => $lengthAwarePaginator,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ProjectCodes/Create', [
            'offices' => Office::query()->orderBy('code')->get(['id', 'name', 'code', 'acronym']),
        ]);
    }

    public function store(StoreProjectCodeRequest $storeProjectCodeRequest): RedirectResponse
    {
        ProjectCode::create($storeProjectCodeRequest->validated());

        return redirect()->route('project-codes.index')
            ->with('success', 'Project code created successfully.');
    }

    public function show(ProjectCode $projectCode): Response
    {
        return Inertia::render('ProjectCodes/Show', [
            'projectCode' => $projectCode->load('office'),
        ]);
    }

    public function edit(ProjectCode $projectCode): Response
    {
        return Inertia::render('ProjectCodes/Edit', [
            'projectCode' => $projectCode,
            'offices' => Office::query()->orderBy('code')->get(['id', 'name', 'code', 'acronym']),
        ]);
    }

    public function update(UpdateProjectCodeRequest $updateProjectCodeRequest, ProjectCode $projectCode): RedirectResponse
    {
        $projectCode->update($updateProjectCodeRequest->validated());

        return redirect()->route('project-codes.index')
            ->with('success', 'Project code updated successfully.');
    }

    public function destroy(ProjectCode $projectCode): RedirectResponse
    {
        $projectCode->delete();

        return redirect()->route('project-codes.index')
            ->with('success', 'Project code deleted successfully.');
    }
}
