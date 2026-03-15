<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMasterListItemRequest;
use App\Http\Requests\UpdateMasterListItemRequest;
use App\Models\MasterListCategory;
use App\Models\MasterListItem;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MasterListItemController extends Controller
{
    public function index(Request $request): Response
    {
        $lengthAwarePaginator = $this->filteredItemsQuery($request)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterListItems/Index', [
            'items' => $lengthAwarePaginator,
            'categories' => MasterListCategory::where('is_active', true)->get(['id', 'name']),
            'filters' => [
                'search' => $request->search,
                'category_id' => $request->category_id,
                'phased_out' => $request->phased_out,
            ],
        ]);
    }

    public function printPdf(Request $request): BinaryFileResponse|RedirectResponse
    {
        try {
            $this->ensurePhpWordInstalled();

            $items = $this->filteredItemsQuery($request)
                ->orderBy('master_list_category_id')
                ->orderBy('item_name')
                ->get();

            $phpWord = $this->buildMasterListDocument($items, $request);

            $settingsClass = 'PhpOffice\\PhpWord\\Settings';
            $settingsClass::setPdfRendererName($settingsClass::PDF_RENDERER_DOMPDF);
            $settingsClass::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

            $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('masterlist-pdf-', true) . '.pdf';
            $fileName = sprintf('masterlist-%s-%04d.pdf', now()->format('Ymd'), random_int(0, 9999));

            $ioFactoryClass = 'PhpOffice\\PhpWord\\IOFactory';
            $ioFactoryClass::createWriter($phpWord, 'PDF')->save($filePath);

            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="%s"', $fileName),
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            Log::error('Master List PDF export failed', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return redirect()->route('master-list-items.index')->with('error', 'Unable to generate PDF export at the moment.');
        }
    }

    public function create(Request $request): Response
    {
        return Inertia::render('MasterListItems/Create', [
            'categories' => MasterListCategory::where('is_active', true)->get(['id', 'name']),
            'suppliers' => Supplier::where('is_active', true)->get(['id', 'name']),
            'prefill' => [
                'item_name' => $request->query('item_name', ''),
                'unit' => $request->query('unit', ''),
                'search_key' => $request->query('search_key', ''),
            ],
        ]);
    }

    public function store(StoreMasterListItemRequest $storeMasterListItemRequest): RedirectResponse
    {
        MasterListItem::create($storeMasterListItemRequest->validated());

        return redirect()->route('master-list-items.index')
            ->with('success', 'Item added to master list successfully.');
    }

    public function edit(MasterListItem $masterListItem): Response
    {
        return Inertia::render('MasterListItems/Edit', [
            'item' => $masterListItem->load(['masterListCategory', 'supplier']),
            'categories' => MasterListCategory::where('is_active', true)->get(['id', 'name']),
            'suppliers' => Supplier::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(UpdateMasterListItemRequest $updateMasterListItemRequest, MasterListItem $masterListItem): RedirectResponse
    {
        $masterListItem->update($updateMasterListItemRequest->validated());

        return redirect()->route('master-list-items.index')
            ->with('success', 'Master list item updated successfully.');
    }

    public function destroy(MasterListItem $masterListItem): RedirectResponse
    {
        $masterListItem->delete();

        return redirect()->route('master-list-items.index')
            ->with('success', 'Master list item deleted successfully.');
    }

    public function togglePhaseOut(MasterListItem $masterListItem, Request $request): RedirectResponse
    {
        $request->validate([
            'phased_out_reason' => ['nullable', 'string'],
        ]);

        $masterListItem->update([
            'is_phased_out' => ! $masterListItem->is_phased_out,
            'phased_out_reason' => $masterListItem->is_phased_out ? null : $request->phased_out_reason,
        ]);

        $message = $masterListItem->is_phased_out
            ? 'Item marked as phased out.'
            : 'Item restored from phased out.';

        return redirect()->back()->with('success', $message);
    }

    private function filteredItemsQuery(Request $request): Builder
    {
        return MasterListItem::query()
            ->with(['masterListCategory', 'supplier'])
            ->when($request->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery->where('item_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('search_key', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('masterListCategory', function (Builder $categoryQuery) use ($search): void {
                            $categoryQuery->where('name', 'like', sprintf('%%%s%%', $search));
                        })
                        ->orWhereHas('supplier', function (Builder $supplierQuery) use ($search): void {
                            $supplierQuery->where('name', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->when($request->filled('category_id'), function (Builder $query) use ($request): void {
                $query->where('master_list_category_id', $request->category_id);
            })
            ->when($request->phased_out !== null && $request->phased_out !== '', function (Builder $query) use ($request): void {
                $query->where('is_phased_out', filter_var($request->phased_out, FILTER_VALIDATE_BOOLEAN));
            });
    }

    private function buildMasterListDocument($items, Request $request)
    {
        $phpWordClass = 'PhpOffice\\PhpWord\\PhpWord';
        $phpWord = new $phpWordClass();
        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
            'marginRight' => 900,
        ]);

        $sealPath = public_path('images/batangas-seal.png');
        $bagongPilipinasPath = public_path('images/bagong-pilipinas.png');

        $selectedCategoryName = 'All';
        if ($request->filled('category_id')) {
            $selectedCategoryName = (string) (MasterListCategory::query()
                ->where('id', (int) $request->category_id)
                ->value('name') ?? 'Selected Category');
        }

        $filterSummary = sprintf(
            'Filters - Search: %s | Category: %s | Status: %s',
            $request->search ?: 'All',
            $selectedCategoryName,
            $request->phased_out === '1' ? 'Phased Out' : ($request->phased_out === '0' ? 'Active Only' : 'All')
        );

        $headerTable = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 30,
        ]);
        $headerTable->addRow();

        $leftCell = $headerTable->addCell(1800, ['borderSize' => 0, 'valign' => 'center']);
        if (is_file($sealPath)) {
            $leftCell->addImage($sealPath, [
                'width' => 58,
                'height' => 58,
                'alignment' => 'center',
            ]);
        }

        $centerCell = $headerTable->addCell(7200, ['borderSize' => 0, 'valign' => 'center']);
        $centerCell->addText('Republic of the Philippines', ['bold' => true], ['alignment' => 'center']);
        $centerCell->addText('Provincial Government of Batangas', ['bold' => true], ['alignment' => 'center']);
        $centerCell->addText('Capitol Site, Kumintang Ibaba, Batangas City 4200', [], ['alignment' => 'center']);
        $centerCell->addText('Master List Items', ['bold' => true], ['alignment' => 'center']);

        $rightCell = $headerTable->addCell(1800, ['borderSize' => 0, 'valign' => 'center']);
        if (is_file($bagongPilipinasPath)) {
            $rightCell->addImage($bagongPilipinasPath, [
                'width' => 74,
                'height' => 58,
                'alignment' => 'center',
            ]);
        }

        $section->addTextBreak(1);
        $section->addText('Generated: ' . now()->format('F d, Y h:i A'));
        $section->addText($filterSummary);
        $section->addTextBreak(1);

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 60,
        ];
        $headerRowStyle = [
            'bgColor' => 'F5F5F5',
        ];

        $phpWord->addTableStyle('MasterListItemsTable', $tableStyle, $headerRowStyle);
        $table = $section->addTable('MasterListItemsTable');

        $table->addRow();
        $table->addCell(3000)->addText('Item', ['bold' => true]);
        $table->addCell(2100)->addText('Category', ['bold' => true]);
        $table->addCell(2100)->addText('Supplier', ['bold' => true]);
        $table->addCell(1000)->addText('Unit', ['bold' => true]);
        $table->addCell(1300)->addText('Default Price', ['bold' => true], ['alignment' => 'right']);
        $table->addCell(1100)->addText('Status', ['bold' => true]);

        if ($items->isEmpty()) {
            $table->addRow();
            $table->addCell(10600, ['gridSpan' => 6])->addText('No master list items found for the selected filters.', [], ['alignment' => 'center']);

            return $phpWord;
        }

        foreach ($items as $item) {
            $table->addRow();
            $table->addCell(3000)->addText((string) ($item->item_name ?? '-'));
            $table->addCell(2100)->addText((string) ($item->masterListCategory?->name ?? '-'));
            $table->addCell(2100)->addText((string) ($item->supplier?->name ?? '-'));
            $table->addCell(1000)->addText((string) ($item->unit ?? '-'));
            $table->addCell(1300)->addText(number_format((float) ($item->default_unit_price ?? 0), 2), [], ['alignment' => 'right']);
            $table->addCell(1100)->addText($item->is_phased_out ? 'Phased Out' : 'Active');
        }

        return $phpWord;
    }

    private function ensurePhpWordInstalled(): void
    {
        if (! class_exists('PhpOffice\\PhpWord\\PhpWord')) {
            abort(500, 'PhpWord is required for Master List print export. Please install phpoffice/phpword.');
        }
    }
}
