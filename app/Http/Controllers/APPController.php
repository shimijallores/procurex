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
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('APPs/Index', [
            'apps' => $lengthAwarePaginator,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('APPs/Create', [
            'offices' => Office::all(['id', 'name']),
        ]);
    }

    public function store(StoreAPPRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Create APP
            $app = APP::create([
                'office_id' => $validated['office_id'],
                'fiscal_year' => $validated['fiscal_year'],
            ]);

            // Handle CSV import if file is uploaded
            if ($request->hasFile('csv_file') && $request->file('csv_file')->isValid()) {
                // Store the uploaded file
                $filePath = $request->file('csv_file')->store('apps', 'public');
                $app->update(['uploaded_file' => $filePath]);

                Excel::import(new APPImport($app), $request->file('csv_file'));
            }

            DB::commit();

            return redirect()->route('apps.index')
                ->with('success', 'Annual Procurement Plan created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create APP: ' . $e->getMessage()]);
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

    public function update(UpdateAPPRequest $request, APP $app): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $app->update([
                'office_id' => $validated['office_id'],
                'fiscal_year' => $validated['fiscal_year'],
            ]);

            DB::commit();

            return redirect()->route('apps.index')
                ->with('success', 'Annual Procurement Plan updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update APP: ' . $e->getMessage()]);
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
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete APP: ' . $e->getMessage()]);
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
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to import CSV: ' . $e->getMessage()]);
        }
    }

    /**
     * Download the uploaded APP file
     */
    public function download(APP $app): BinaryFileResponse|RedirectResponse
    {
        if (!$app->uploaded_file || !Storage::disk('public')->exists($app->uploaded_file)) {
            return back()->withErrors(['error' => 'No file available for download.']);
        }

        $filePath = Storage::disk('public')->path($app->uploaded_file);
        $fileName = basename($app->uploaded_file);

        return response()->download($filePath, $fileName);
    }
}
