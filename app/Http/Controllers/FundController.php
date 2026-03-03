<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreFundRequest;
use App\Http\Requests\UpdateFundRequest;
use App\Models\Fund;
use App\Models\Office;
use App\Models\PPMP;
use App\Models\Project;
use App\Models\ProjectBrief;
use App\Models\ProjectProposal;
use App\Models\WorkProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FundController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = Fund::with('office')
            ->when($request->search, function ($query, string $search): void {
                $query->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('code', 'like', sprintf('%%%s%%', $search));
            })
            ->when($request->office_id, function ($query, string $officeId): void {
                $query->where('office_id', $officeId);
            })
            ->when($request->fiscal_year, function ($query, string $fiscalYear): void {
                $query->where('fiscal_year', $fiscalYear);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Get unique offices for filter
        $offices = Fund::distinct()
            ->pluck('office_id')
            ->mapWithKeys(function ($officeId) {
                $office = Office::find($officeId);
                return [$officeId => $office?->name];
            })
            ->filter()
            ->sort();

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('Funds/Index', [
            'funds' => $lengthAwarePaginator,
            'offices' => $offices,
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'search' => $request->search,
                'office_id' => $request->office_id,
                'fiscal_year' => $request->fiscal_year,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Funds/Create', [
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function store(StoreFundRequest $storeFundRequest): RedirectResponse
    {
        $validated = $storeFundRequest->validated();

        $fund = Fund::create([
            'office_id' => $validated['office_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'fiscal_year' => $validated['fiscal_year'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        // If type is project, create project and upload files
        if ($validated['type'] === 'project') {
            $project = Project::create([
                'fund_id' => $fund->id,
                'name' => $validated['project_name'] ?? $fund->name,
                'remarks' => $fund->remarks,
            ]);

            $this->storeProjectDocuments($storeFundRequest, $project);
        }

        return redirect()->route('funds.index')
            ->with('success', 'Fund created successfully.');
    }

    public function show(Fund $fund): Response
    {
        $fund->load([
            'office',
            'ppmp',
            'emanatings',
            'project.workProgram',
            'project.projectBrief',
            'project.projectProposal',
        ]);

        $ppmpReference = PPMP::query()
            ->where('office_id', $fund->office_id)
            ->where('fiscal_year', $fund->fiscal_year)
            ->whereNotNull('csv_path')
            ->latest()
            ->first(['id', 'fiscal_year', 'csv_path']);

        return Inertia::render('Funds/Show', [
            'fund' => $fund,
            'ppmpReference' => $ppmpReference,
        ]);
    }

    public function edit(Fund $fund): Response
    {
        $fund->load([
            'office',
            'ppmp',
            'emanatings',
            'project.workProgram',
            'project.projectBrief',
            'project.projectProposal',
        ]);

        return Inertia::render('Funds/Edit', [
            'fund' => $fund,
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function update(UpdateFundRequest $updateFundRequest, Fund $fund): RedirectResponse
    {
        $validated = $updateFundRequest->validated();

        $fund->update([
            'office_id' => $validated['office_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'fiscal_year' => $validated['fiscal_year'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        // Handle project type
        if ($validated['type'] === 'project') {
            if (! $fund->project) {
                $project = Project::create([
                    'fund_id' => $fund->id,
                    'name' => $validated['project_name'] ?? $fund->name,
                    'remarks' => $fund->remarks,
                ]);
            } else {
                $project = $fund->project;
                $project->update([
                    'name' => $validated['project_name'] ?? $fund->name,
                    'remarks' => $fund->remarks,
                ]);
            }

            $this->storeProjectDocuments($updateFundRequest, $project);
        } elseif ($fund->project) {
            $this->deleteProjectDocumentFiles($fund->project);
            $fund->project->delete();
        }

        return redirect()->route('funds.index')
            ->with('success', 'Fund updated successfully.');
    }

    public function destroy(Fund $fund): RedirectResponse
    {
        if ($fund->project) {
            $this->deleteProjectDocumentFiles($fund->project);
        }

        $fund->delete();

        return redirect()->route('funds.index')
            ->with('success', 'Fund deleted successfully.');
    }

    private function storeProjectDocuments(Request $request, Project $project): void
    {
        if ($request->hasFile('work_program') && $request->file('work_program')?->isValid()) {
            $this->replaceProjectDocument(
                $project,
                'workProgram',
                $request->file('work_program'),
                'projects/work-programs',
                WorkProgram::class
            );
        }

        if ($request->hasFile('project_brief') && $request->file('project_brief')?->isValid()) {
            $this->replaceProjectDocument(
                $project,
                'projectBrief',
                $request->file('project_brief'),
                'projects/project-briefs',
                ProjectBrief::class
            );
        }

        if ($request->hasFile('project_proposal') && $request->file('project_proposal')?->isValid()) {
            $this->replaceProjectDocument(
                $project,
                'projectProposal',
                $request->file('project_proposal'),
                'projects/project-proposals',
                ProjectProposal::class
            );
        }
    }

    private function replaceProjectDocument(Project $project, string $relation, UploadedFile $file, string $directory, string $modelClass): void
    {
        $existingDocument = $project->{$relation};

        if ($existingDocument) {
            Storage::disk('public')->delete($existingDocument->file_url);
            $existingDocument->delete();
        }

        $filePath = $file->store($directory, 'public');

        $modelClass::create([
            'project_id' => $project->id,
            'file_url' => $filePath,
        ]);
    }

    private function deleteProjectDocumentFiles(Project $project): void
    {
        $project->loadMissing(['workProgram', 'projectBrief', 'projectProposal']);

        if ($project->workProgram) {
            Storage::disk('public')->delete($project->workProgram->file_url);
        }

        if ($project->projectBrief) {
            Storage::disk('public')->delete($project->projectBrief->file_url);
        }

        if ($project->projectProposal) {
            Storage::disk('public')->delete($project->projectProposal->file_url);
        }
    }
}
