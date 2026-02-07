<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreFundRequest;
use App\Http\Requests\UpdateFundRequest;
use App\Models\Fund;
use App\Models\Office;
use App\Models\Project;
use App\Models\ProjectBrief;
use App\Models\ProjectProposal;
use App\Models\WorkProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Funds/Index', [
            'funds' => $lengthAwarePaginator,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Funds/Create', [
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function store(StoreFundRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
            // Create project record
            $project = Project::create([
                'fund_id' => $fund->id,
                'remarks' => $fund->remarks,
            ]);

            // Upload and create work program
            if ($request->hasFile('work_program') && $request->file('work_program')->isValid()) {
                $workProgramPath = $request->file('work_program')->store('projects/work-programs', 'public');
                WorkProgram::create([
                    'project_id' => $project->id,
                    'file_url' => $workProgramPath,
                ]);
            }

            // Upload and create project brief
            if ($request->hasFile('project_brief') && $request->file('project_brief')->isValid()) {
                $projectBriefPath = $request->file('project_brief')->store('projects/project-briefs', 'public');
                ProjectBrief::create([
                    'project_id' => $project->id,
                    'file_url' => $projectBriefPath,
                ]);
            }

            // Upload and create project proposal
            if ($request->hasFile('project_proposal') && $request->file('project_proposal')->isValid()) {
                $projectProposalPath = $request->file('project_proposal')->store('projects/project-proposals', 'public');
                ProjectProposal::create([
                    'project_id' => $project->id,
                    'file_url' => $projectProposalPath,
                ]);
            }
        }

        return redirect()->route('funds.index')
            ->with('success', 'Fund created successfully.');
    }

    public function show(Fund $fund): Response
    {
        $fund->load(['office', 'project.workProgram', 'project.projectBrief', 'project.projectProposal']);

        return Inertia::render('Funds/Show', [
            'fund' => $fund,
        ]);
    }

    public function edit(Fund $fund): Response
    {
        $fund->load(['office', 'project.workProgram', 'project.projectBrief', 'project.projectProposal']);

        return Inertia::render('Funds/Edit', [
            'fund' => $fund,
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function update(UpdateFundRequest $request, Fund $fund): RedirectResponse
    {
        $validated = $request->validated();

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
            // Create project if it doesn't exist
            if (!$fund->project) {
                $project = Project::create([
                    'fund_id' => $fund->id,
                    'remarks' => $fund->remarks,
                ]);
            } else {
                $project = $fund->project;
            }

            // Handle work program upload
            if ($request->hasFile('work_program') && $request->file('work_program')->isValid()) {
                // Delete old file if exists
                if ($project->workProgram) {
                    Storage::disk('public')->delete($project->workProgram->file_url);
                    $project->workProgram->delete();
                }

                // Upload and create new work program
                $workProgramPath = $request->file('work_program')->store('projects/work-programs', 'public');
                WorkProgram::create([
                    'project_id' => $project->id,
                    'file_url' => $workProgramPath,
                ]);
            }

            // Handle project brief upload
            if ($request->hasFile('project_brief') && $request->file('project_brief')->isValid()) {
                // Delete old file if exists
                if ($project->projectBrief) {
                    Storage::disk('public')->delete($project->projectBrief->file_url);
                    $project->projectBrief->delete();
                }

                // Upload and create new project brief
                $projectBriefPath = $request->file('project_brief')->store('projects/project-briefs', 'public');
                ProjectBrief::create([
                    'project_id' => $project->id,
                    'file_url' => $projectBriefPath,
                ]);
            }

            // Handle project proposal upload
            if ($request->hasFile('project_proposal') && $request->file('project_proposal')->isValid()) {
                // Delete old file if exists
                if ($project->projectProposal) {
                    Storage::disk('public')->delete($project->projectProposal->file_url);
                    $project->projectProposal->delete();
                }

                // Upload and create new project proposal
                $projectProposalPath = $request->file('project_proposal')->store('projects/project-proposals', 'public');
                ProjectProposal::create([
                    'project_id' => $project->id,
                    'file_url' => $projectProposalPath,
                ]);
            }
        } else {
            // If type changed from project to general, delete the project and related files
            if ($fund->project) {
                if ($fund->project->workProgram) {
                    Storage::disk('public')->delete($fund->project->workProgram->file_url);
                }
                if ($fund->project->projectBrief) {
                    Storage::disk('public')->delete($fund->project->projectBrief->file_url);
                }
                if ($fund->project->projectProposal) {
                    Storage::disk('public')->delete($fund->project->projectProposal->file_url);
                }
                $fund->project->delete();
            }
        }

        return redirect()->route('funds.index')
            ->with('success', 'Fund updated successfully.');
    }

    public function destroy(Fund $fund): RedirectResponse
    {
        // Delete project files if exists
        if ($fund->project) {
            if ($fund->project->workProgram) {
                Storage::disk('public')->delete($fund->project->workProgram->file_url);
            }
            if ($fund->project->projectBrief) {
                Storage::disk('public')->delete($fund->project->projectBrief->file_url);
            }
            if ($fund->project->projectProposal) {
                Storage::disk('public')->delete($fund->project->projectProposal->file_url);
            }
        }

        $fund->delete();

        return redirect()->route('funds.index')
            ->with('success', 'Fund deleted successfully.');
    }
}
