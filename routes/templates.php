<?php

use App\Exports\AOQTemplateExport;
use App\Models\PurchaseRequestItem;
use App\Models\RFQ;
use App\Models\RFQItem;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::get('/templates', function (): Factory|View {
    return view('templates', [
        'templates' => [
            ['id' => 1, 'title' => 'APP Template', 'file' => 'standard-template/app-template.xlsx', 'type' => 'XLSX'],
            ['id' => 2, 'title' => 'PPMP Template', 'file' => 'standard-template/ppmp-template.xlsx', 'type' => 'XLSX'],
            ['id' => 3, 'title' => 'Project Proposal Template', 'file' => '3. project-proposal-template.docx', 'type' => 'DOCX'],
            ['id' => 4, 'title' => 'Project Brief Template', 'file' => '4. project-brief-template.docx', 'type' => 'DOCX'],
            ['id' => 5, 'title' => 'Work Program Template', 'file' => '5. work-program-template.docx', 'type' => 'DOCX'],
            ['id' => 6, 'title' => 'Emanating Template', 'file' => '6. emanating-template.xlsx', 'type' => 'XLSX'],
            ['id' => 7, 'title' => 'AOQ Quotation Matrix Template', 'file' => 'aoq-quotation-matrix-template.xlsx', 'type' => 'XLSX'],
        ],
    ]);
})->name('templates.index');

Route::get('/templates/{template}', function (int $template): BinaryFileResponse {
    $templates = [
        1 => 'standard-template/app-template.xlsx',
        2 => 'standard-template/ppmp-template.xlsx',
        3 => '3. project-proposal-template.docx',
        4 => '4. project-brief-template.docx',
        5 => '5. work-program-template.docx',
        6 => '6. emanating-template.xlsx',
    ];

    abort_unless(array_key_exists($template, $templates) || $template === 7, 404);

    if ($template === 7) {
        $rfq = RFQ::with([
            'items.purchaseRequestItem',
            'suppliers.supplier',
        ])->first();

        $supplierCount = 3;
        $supplierNames = ['Supplier 1', 'Supplier 2', 'Supplier 3'];

        if ($rfq) {
            $base = $rfq->suppliers->pluck('supplier.name')->filter()->values()->all();
            foreach ($base as $i => $name) {
                $supplierNames[$i] = $name;
            }

            $supplierCount = max(count($supplierNames), 1);
        }

        // If no RFQ exists, create a demo RFQ with sample items
        if (! $rfq) {
            $rfq = new RFQ;
            $sampleItem = new RFQItem;
            $sampleItem->item_name = '[Item Name]';
            $sampleItem->quantity = 1;
            $sampleItem->unit = 'pcs';
            $samplePrItem = new PurchaseRequestItem;
            $samplePrItem->unit_cost = 0;
            $sampleItem->setRelation('purchaseRequestItem', $samplePrItem);
            $rfq->setRelation('items', collect([$sampleItem]));
        }

        $export = new AOQTemplateExport($rfq, $supplierCount, $supplierNames);

        return Excel::download($export, 'aoq-quotation-matrix-template.xlsx');
    }

    $filePath = base_path('documents/'.$templates[$template]);
    abort_unless(is_file($filePath), 404);

    return response()->download($filePath, basename($templates[$template]));
})->whereNumber('template')->name('templates.download');
