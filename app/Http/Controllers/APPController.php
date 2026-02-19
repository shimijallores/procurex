<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAPPRequest;
use App\Http\Requests\UpdateAPPRequest;
use App\Imports\APPImport;
use App\Models\APP;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class APPController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = APP::with('office')
            ->when($request->search, function ($query, string $search): void {
                $query->where('fiscal_year', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('office', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search));
                    });
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

        // Get unique offices and fiscal years for filters
        $offices = APP::distinct()
            ->pluck('office_id')
            ->mapWithKeys(function ($officeId) {
                $office = Office::find($officeId);
                return [$officeId => $office?->name];
            })
            ->filter()
            ->sort();

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear))
            ->mapWithKeys(fn ($year) => [$year => $year])
            ->reverse();

        return Inertia::render('APPs/Index', [
            'apps' => $lengthAwarePaginator,
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
        return Inertia::render('APPs/Create', [
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function store(StoreAPPRequest $storeAPPRequest): RedirectResponse
    {
        $validated = $storeAPPRequest->validated();

        DB::beginTransaction();
        try {
            // Delete existing APP with same office_id + fiscal_year
            APP::where('office_id', $validated['office_id'])
                ->where('fiscal_year', $validated['fiscal_year'])
                ->delete();

            // Create APP
            $app = APP::create([
                'office_id' => $validated['office_id'],
                'fiscal_year' => $validated['fiscal_year'],
            ]);

            // Handle CSV import if file is uploaded
            if ($storeAPPRequest->hasFile('csv_file') && $storeAPPRequest->file('csv_file')->isValid()) {
                // Store the uploaded file
                $filePath = $storeAPPRequest->file('csv_file')->store('apps', 'public');
                $app->update(['uploaded_file' => $filePath]);

                Excel::import(new APPImport($app), $storeAPPRequest->file('csv_file'));
            }

            DB::commit();

            return redirect()->route('apps.index')
                ->with('success', 'Annual Procurement Plan created successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to create APP: '.$exception->getMessage()]);
        }
    }

    public function show(APP $app): Response
    {
        $app->load(['office', 'APPCategories.APPItems']);

        return Inertia::render('APPs/Show', [
            'app' => $app,
        ]);
    }

    public function edit(APP $app): Response
    {
        $app->load(['office', 'APPCategories.APPItems']);

        return Inertia::render('APPs/Edit', [
            'app' => $app,
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function update(UpdateAPPRequest $updateAPPRequest, APP $app): RedirectResponse
    {
        $validated = $updateAPPRequest->validated();

        DB::beginTransaction();
        try {
            $app->update([
                'office_id' => $validated['office_id'],
                'fiscal_year' => $validated['fiscal_year'],
            ]);

            DB::commit();

            return redirect()->route('apps.index')
                ->with('success', 'Annual Procurement Plan updated successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to update APP: '.$exception->getMessage()]);
        }
    }

    public function destroy(APP $app): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $app->delete();
            DB::commit();

            return redirect()->route('apps.index')
                ->with('success', 'Annual Procurement Plan deleted successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to delete APP: '.$exception->getMessage()]);
        }
    }

    /**
     * Handle CSV import for existing APP
     */
    public function import(Request $request, APP $app): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        DB::beginTransaction();
        try {
            // Delete existing categories and items
            $app->APPCategories()->delete();

            // Handle file upload
            if ($request->hasFile('csv_file') && $request->file('csv_file')->isValid()) {
                // Delete old file if exists
                if ($app->uploaded_file) {
                    Storage::disk('public')->delete($app->uploaded_file);
                }

                // Store new file
                $filePath = $request->file('csv_file')->store('apps', 'public');
                $app->update(['uploaded_file' => $filePath]);
            }

            // Import from CSV/Excel
            Excel::import(new APPImport($app), $request->file('csv_file'));

            DB::commit();

            return redirect()->route('apps.show', $app)
                ->with('success', 'CSV data imported successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to import CSV: '.$exception->getMessage()]);
        }
    }

    /**
     * Download the uploaded APP file
     */
    public function download(APP $app): BinaryFileResponse|RedirectResponse
    {
        if (! $app->uploaded_file || ! Storage::disk('public')->exists($app->uploaded_file)) {
            return back()->withErrors(['error' => 'No file available for download.']);
        }

        $filePath = Storage::disk('public')->path($app->uploaded_file);
        $fileName = basename($app->uploaded_file);

        return response()->download($filePath, $fileName);
    }
}
