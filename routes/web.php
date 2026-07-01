<?php

use App\Enums\RoleType;
use App\Http\Controllers\AcceptanceInspectionController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AOQController;
use App\Http\Controllers\APPController;
use App\Http\Controllers\BACResolutionController;
use App\Http\Controllers\BatchAoqRequestController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CanvasController;
use App\Http\Controllers\COAInspectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmanatingController;
use App\Http\Controllers\FundController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\MasterListCategoryController;
use App\Http\Controllers\MasterListItemController;
use App\Http\Controllers\NOAController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\POTransmittalController;
use App\Http\Controllers\PPMPController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectCodeController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\PurchaseRequestMatrixController;
use App\Http\Controllers\RFQController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SvpMatrixController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/templates.php';

Route::get('/', function (): \Illuminate\Http\RedirectResponse {
    return redirect(route('login'));
});

// Authentication
Route::get('/login', [SessionController::class, 'index'])
    ->name('login')
    ->middleware('guest');
Route::post('/login', [SessionController::class, 'login'])
    ->name('login.login')
    ->middleware('guest');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/logout', [SessionController::class, 'logout'])->name('logout');

    // Global search
    Route::get('/search', GlobalSearchController::class)->name('search');

    // Dashboard - accessible to all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name(
        'dashboard.index',
    );

    // Profile & Settings - accessible to all authenticated users
    Route::get('/profile', [ProfileController::class, 'index'])->name(
        'profile.index',
    );
    Route::get('/settings', [ProfileController::class, 'settings'])->name(
        'settings.index',
    );
    Route::post('/compliance/acknowledge', [ProfileController::class, 'acknowledgeCompliance'])->name(
        'compliance.acknowledge',
    );
    // Define all resources once, authorization will be handled in controllers/policies
    Route::resource('users', UserController::class)->middleware(
        'role:'.RoleType::SUPERADMIN->value,
    );
    Route::resource('roles', RoleController::class)->middleware(
        'role:'.RoleType::SUPERADMIN->value,
    );
    Route::resource('offices', OfficeController::class)->middleware(
        'role:'.RoleType::SUPERADMIN->value,
    );
    Route::resource('project-codes', ProjectCodeController::class)->middleware(
        'role:'.RoleType::SUPERADMIN->value,
    );
    Route::resource('accounts', AccountController::class)->middleware(
        'role:'.RoleType::SUPERADMIN->value,
    );
    Route::resource('calendars', CalendarController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware(
            'role:'.
                implode(',', [
                    RoleType::SUPERADMIN->value,
                    RoleType::CHECKING_ADMIN->value,
                    RoleType::RESOLUTION_ADMIN->value,
                    RoleType::NOA_ADMIN->value,
                    RoleType::PO_ADMIN->value,
                    RoleType::INSPECTION_ADMIN->value,
                    'office',
                ]),
        );
    Route::post('calendars/check-date', [
        CalendarController::class,
        'checkDate',
    ])
        ->middleware('auth')
        ->name('calendars.check-date');
    $officeRelatedRoles =
        'role:'.implode(',', RoleType::officeSubmissionRoles());
    Route::resource('funds', FundController::class)->middleware(
        $officeRelatedRoles,
    );
    Route::resource('apps', APPController::class)->middleware(
        $officeRelatedRoles,
    );
    Route::post('apps/{app}/import', [APPController::class, 'import'])
        ->middleware($officeRelatedRoles)
        ->name('apps.import');
    Route::get('apps/{app}/download', [APPController::class, 'download'])
        ->middleware($officeRelatedRoles)
        ->name('apps.download');
    Route::resource('ppmps', PPMPController::class)->middleware(
        $officeRelatedRoles,
    );
    Route::post('ppmps/{ppmp}/import', [PPMPController::class, 'import'])
        ->middleware($officeRelatedRoles)
        ->name('ppmps.import');
    Route::get('ppmps/{ppmp}/download-xlsx', [
        PPMPController::class,
        'downloadXlsx',
    ])
        ->middleware($officeRelatedRoles)
        ->name('ppmps.download-xlsx');
    Route::resource('emanatings', EmanatingController::class)->middleware(
        $officeRelatedRoles,
    );
    Route::post('emanatings/{emanating}/import', [
        EmanatingController::class,
        'import',
    ])
        ->middleware($officeRelatedRoles)
        ->name('emanatings.import');
    Route::get('emanatings/{emanating}/download-xlsx', [
        EmanatingController::class,
        'downloadXlsx',
    ])
        ->middleware($officeRelatedRoles)
        ->name('emanatings.download-xlsx');
    Route::post('emanatings/{emanating}/approve', [
        EmanatingController::class,
        'approve',
    ])
        ->middleware($officeRelatedRoles)
        ->name('emanatings.approve');
    Route::post('emanatings/{emanating}/reject', [
        EmanatingController::class,
        'reject',
    ])
        ->middleware($officeRelatedRoles)
        ->name('emanatings.reject');

    // Canvassing module
    $canvassingRoles =
        'role:'.
        implode(',', [
            RoleType::SUPERADMIN->value,
            RoleType::CANVASSING_ADMIN->value,
        ]);
    Route::resource('suppliers', SupplierController::class)->middleware(
        $canvassingRoles,
    );
    Route::resource(
        'master-list-categories',
        MasterListCategoryController::class,
    )
        ->except(['show'])
        ->middleware($canvassingRoles);
    Route::resource('master-list-items', MasterListItemController::class)
        ->except(['show'])
        ->middleware($canvassingRoles);
    Route::get('master-list-items/print/pdf', [
        MasterListItemController::class,
        'printPdf',
    ])
        ->middleware($canvassingRoles)
        ->name('master-list-items.print.pdf');
    Route::post('master-list-items/{master_list_item}/toggle-phase-out', [
        MasterListItemController::class,
        'togglePhaseOut',
    ])
        ->middleware($canvassingRoles)
        ->name('master-list-items.toggle-phase-out');
    Route::resource('canvasses', CanvasController::class)
        ->except(['edit', 'update'])
        ->middleware($canvassingRoles);
    Route::post('canvasses/{canvas}/items/{canvas_item}/selections', [
        CanvasController::class,
        'saveItemSelections',
    ])
        ->middleware($canvassingRoles)
        ->name('canvasses.items.selections');
    Route::post('canvasses/{canvas}/complete', [
        CanvasController::class,
        'complete',
    ])
        ->middleware($canvassingRoles)
        ->name('canvasses.complete');
    Route::post('canvasses/{canvas}/delete', [
        CanvasController::class,
        'delete',
    ])
        ->middleware($canvassingRoles)
        ->name('canvasses.delete');

    // Purchase Request module
    $prRoles =
        'role:'.
        implode(',', [RoleType::SUPERADMIN->value, RoleType::PR_ADMIN->value]);
    Route::resource(
        'purchase-requests',
        PurchaseRequestController::class,
    )->middleware($prRoles);

    // Import PR Excel files (bulk import) - creates PRs from uploaded files
    Route::post('purchase-requests/import', [PurchaseRequestController::class, 'import'])
        ->name('purchase-requests.import')
        ->middleware($prRoles);
    Route::get('purchase-request-matrix', [
        PurchaseRequestMatrixController::class,
        'index',
    ])
        ->middleware($prRoles)
        ->name('purchase-request-matrix.index');
    Route::get('purchase-request-matrix/export/xlsx', [
        PurchaseRequestMatrixController::class,
        'exportXlsx',
    ])
        ->middleware($prRoles)
        ->name('purchase-request-matrix.export-xlsx');
    Route::get('purchase-request-matrix/{purchase_request_item}', [
        PurchaseRequestMatrixController::class,
        'show',
    ])
        ->middleware($prRoles)
        ->name('purchase-request-matrix.show');
    Route::get('purchase-request-matrix/{purchase_request_item}/edit', [
        PurchaseRequestMatrixController::class,
        'edit',
    ])
        ->middleware($prRoles)
        ->name('purchase-request-matrix.edit');
    Route::put('purchase-request-matrix/{purchase_request_item}', [
        PurchaseRequestMatrixController::class,
        'update',
    ])
        ->middleware($prRoles)
        ->name('purchase-request-matrix.update');
    $svpMatrixRoles =
        'role:'.
        implode(',', [
            RoleType::SUPERADMIN->value,
            RoleType::PO_ADMIN->value,
            RoleType::INSPECTION_ADMIN->value,
            RoleType::RFQ_ADMIN->value,
            RoleType::ABSTRACT_ADMIN->value,
            RoleType::RESOLUTION_ADMIN->value,
            RoleType::NOA_ADMIN->value,
        ]);
    Route::get('svp-matrix', [SvpMatrixController::class, 'index'])
        ->middleware($svpMatrixRoles)
        ->name('svp-matrix.index');
    Route::get('svp-matrix/export/xlsx', [
        SvpMatrixController::class,
        'exportXlsx',
    ])
        ->middleware($svpMatrixRoles)
        ->name('svp-matrix.export-xlsx');
    Route::get('svp-matrix/{svp_matrix}', [SvpMatrixController::class, 'show'])
        ->middleware($svpMatrixRoles)
        ->name('svp-matrix.show');
    Route::get('svp-matrix/{svp_matrix}/edit', [
        SvpMatrixController::class,
        'edit',
    ])
        ->middleware($svpMatrixRoles)
        ->name('svp-matrix.edit');
    Route::put('svp-matrix/{svp_matrix}', [
        SvpMatrixController::class,
        'update',
    ])
        ->middleware($svpMatrixRoles)
        ->name('svp-matrix.update');
    Route::post('purchase-requests/suggest-pr-no', [
        PurchaseRequestController::class,
        'suggestPrNo',
    ])
        ->middleware($prRoles)
        ->name('purchase-requests.suggest-pr-no');
    Route::get('purchase-requests/{purchase_request}/imported-edit', [
        PurchaseRequestController::class,
        'editImported',
    ])
        ->name('purchase-requests.edit-imported')
        ->middleware($prRoles);
    Route::put('purchase-requests/{purchase_request}/imported-update', [
        PurchaseRequestController::class,
        'updateImported',
    ])
        ->name('purchase-requests.update-imported')
        ->middleware($prRoles);
    Route::post('purchase-requests/{purchase_request}/approve', [
        PurchaseRequestController::class,
        'approve',
    ])
        ->middleware($prRoles)
        ->name('purchase-requests.approve');
    Route::post('purchase-requests/{purchase_request}/return', [
        PurchaseRequestController::class,
        'returnToOffice',
    ])
        ->middleware($prRoles)
        ->name('purchase-requests.return');
    Route::get('purchase-requests/{purchase_request}/pdf', [
        PurchaseRequestController::class,
        'printPdf',
    ])
        ->middleware($prRoles)
        ->name('purchase-requests.pdf');

    // RFQ module
    $rfqRoles =
        'role:'.
        implode(',', [RoleType::SUPERADMIN->value, RoleType::RFQ_ADMIN->value]);
    $rfqAndAoqRoles = [
        'role:'.implode(',', [
            RoleType::SUPERADMIN->value,
            RoleType::RFQ_ADMIN->value,
            RoleType::ABSTRACT_ADMIN->value,
        ]),
    ];
    Route::get('rfqs/suggest-prs', [RFQController::class, 'suggestPrs'])
        ->middleware($rfqRoles)
        ->name('rfqs.suggest-prs');
    Route::get('rfqs/recent-svps', [RFQController::class, 'recentSvps'])
        ->middleware($rfqAndAoqRoles)
        ->name('rfqs.recent-svps');
    Route::resource('rfqs', RFQController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->middleware($rfqRoles);
    Route::get('rfqs/suggest-date', [RFQController::class, 'suggestRfqDate'])
        ->middleware($rfqRoles)
        ->name('rfqs.suggest-date');
    Route::get('rfqs/{rfq}/pdf', [RFQController::class, 'printPdf'])
        ->middleware($rfqRoles)
        ->name('rfqs.pdf');
    Route::post('rfqs/download-pdfs', [RFQController::class, 'downloadPdfs'])
        ->middleware($rfqRoles)
        ->name('rfqs.download-pdfs');

    // AOQ module
    $aoqRoles =
        'role:'.
        implode(',', [
            RoleType::SUPERADMIN->value,
            RoleType::ABSTRACT_ADMIN->value,
        ]);
    Route::get('aoqs/find-rfq-by-svp', [AOQController::class, 'findRfqBySvp'])
        ->middleware($aoqRoles)
        ->name('aoqs.find-rfq-by-svp');
    Route::get('aoqs/active-earmark', [AOQController::class, 'checkActiveEarmark'])
        ->middleware($aoqRoles)
        ->name('aoqs.active-earmark');
    Route::resource('aoqs', AOQController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->middleware($aoqRoles);
    Route::get('aoqs/{aoq}/pdf', [AOQController::class, 'printPdf'])
        ->middleware($aoqRoles)
        ->name('aoqs.pdf');
    Route::post('aoqs/download-pdfs', [AOQController::class, 'downloadPdfs'])
        ->middleware($aoqRoles)
        ->name('aoqs.download-pdfs');
    Route::get('aoqs/{aoq}/template', [AOQController::class, 'downloadTemplate'])
        ->middleware($aoqRoles)
        ->name('aoqs.template');
    Route::post('aoqs/import-matrix', [AOQController::class, 'importMatrix'])
        ->middleware($aoqRoles)
        ->name('aoqs.import-matrix');
    Route::get('aoqs/suggest-batch', [BatchController::class, 'suggestBatch'])
        ->middleware($aoqRoles)
        ->name('aoqs.suggest-batch');
    Route::post('aoqs/store-batch', [BatchController::class, 'storeBatch'])
        ->middleware($aoqRoles)
        ->name('aoqs.store-batch');
    Route::post('aoqs/find-or-create-batch', [AOQController::class, 'findOrCreateBatch'])
        ->middleware($aoqRoles)
        ->name('aoqs.find-or-create-batch');
    // Batch module
    $batchRoles =
        'role:'.
        implode(',', [
            RoleType::SUPERADMIN->value,
            RoleType::ABSTRACT_ADMIN->value,
        ]);
    Route::get('batches/recent-batches', [BatchController::class, 'recentBatches'])
        ->middleware($batchRoles)
        ->name('batches.recent-batches');
    Route::put('batches/{batch}/update-dates', [BatchController::class, 'updateDates'])
        ->middleware($batchRoles)
        ->name('batches.update-dates');
    Route::resource('batches', BatchController::class)
        ->except(['update'])
        ->middleware($batchRoles);
    Route::put('batches/{batch}', [BatchController::class, 'update'])
        ->middleware($batchRoles)
        ->name('batches.update');
    Route::get('batches/available-aoqs', [BatchController::class, 'availableAoqs'])
        ->middleware($batchRoles)
        ->name('batches.available-aoqs');

    // Batch AOQ Requests module
    $batchAoqRequestRoles =
        'role:'.
        implode(',', [
            RoleType::SUPERADMIN->value,
            RoleType::ABSTRACT_ADMIN->value,
        ]);
    Route::get('batch-aoq-requests/my-requests', [BatchAoqRequestController::class, 'myRequests'])
        ->middleware('auth')
        ->name('batch-aoq-requests.my-requests');
    Route::get('batch-aoq-requests/locked-batches', [BatchAoqRequestController::class, 'lockedBatches'])
        ->middleware('auth')
        ->name('batch-aoq-requests.locked-batches');
    Route::post('batch-aoq-requests', [BatchAoqRequestController::class, 'store'])
        ->middleware('auth')
        ->name('batch-aoq-requests.store');
    Route::get('batch-aoq-requests', [BatchAoqRequestController::class, 'index'])
        ->middleware('role:'.RoleType::SUPERADMIN->value)
        ->name('batch-aoq-requests.index');
    Route::post('batch-aoq-requests/{batch_aoq_request}/approve', [BatchAoqRequestController::class, 'approve'])
        ->middleware('role:'.RoleType::SUPERADMIN->value)
        ->name('batch-aoq-requests.approve');
    Route::post('batch-aoq-requests/{batch_aoq_request}/reject', [BatchAoqRequestController::class, 'reject'])
        ->middleware('role:'.RoleType::SUPERADMIN->value)
        ->name('batch-aoq-requests.reject');

    // BAC Resolution module
    $bacResolutionRoles =
        'role:'.
        implode(',', [
            RoleType::SUPERADMIN->value,
            RoleType::RESOLUTION_ADMIN->value,
        ]);
    Route::resource('bac-resolutions', BACResolutionController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'show', 'destroy'])
        ->middleware($bacResolutionRoles);
    Route::get('bac-resolutions/batch/{batch}/aoqs', [
        BACResolutionController::class,
        'fetchBatchAoqs',
    ])
        ->middleware($bacResolutionRoles)
        ->name('bac-resolutions.batch-aoqs');
    Route::get('bac-resolutions/{bac_resolution}/pdf', [
        BACResolutionController::class,
        'printPdf',
    ])
        ->middleware($bacResolutionRoles)
        ->name('bac-resolutions.pdf');
    Route::post('bac-resolutions/download-pdfs', [
        BACResolutionController::class,
        'downloadPdfs',
    ])
        ->middleware($bacResolutionRoles)
        ->name('bac-resolutions.download-pdfs');
    Route::post('bac-resolutions/{bac_resolution}/finalize', [
        BACResolutionController::class,
        'finalize',
    ])
        ->middleware($bacResolutionRoles)
        ->name('bac-resolutions.finalize');
    Route::post('bac-resolutions/{bac_resolution}/regenerate', [
        BACResolutionController::class,
        'regenerate',
    ])
        ->middleware($bacResolutionRoles)
        ->name('bac-resolutions.regenerate');

    // Notice of Award module
    $noaRoles =
        'role:'.
        implode(',', [RoleType::SUPERADMIN->value, RoleType::NOA_ADMIN->value]);

    Route::get('noas/recent-noas', [NOAController::class, 'recentNoas'])
        ->middleware($noaRoles)
        ->name('noas.recent-noas');
    Route::get('noas/find-batch-by-noa', [NOAController::class, 'findBatchByNoa'])
        ->middleware($noaRoles)
        ->name('noas.find-batch-by-noa');
    Route::get('noas/find-batch-by-svp', [NOAController::class, 'findBatchBySvp'])
        ->middleware($noaRoles)
        ->name('noas.find-batch-by-svp');
    Route::get('noas/batch-aoqs/{batch}', [NOAController::class, 'batchAoqs'])
        ->middleware($noaRoles)
        ->name('noas.batch-aoqs');

    Route::resource('noas', NOAController::class)
        ->except(['edit', 'update'])
        ->middleware($noaRoles);
    Route::get('noas/{noa}/edit', [NOAController::class, 'edit'])
        ->middleware($noaRoles)
        ->name('noas.edit');
    Route::put('noas/{noa}', [NOAController::class, 'update'])
        ->middleware($noaRoles)
        ->name('noas.update');
    Route::get('noas/{noa}/pdf', [NOAController::class, 'printPdf'])
        ->middleware($noaRoles)
        ->name('noas.pdf');
    Route::post('noas/download-pdfs', [NOAController::class, 'downloadPdfs'])
        ->middleware($noaRoles)
        ->name('noas.download-pdfs');
    Route::get('batches/{batch}/print-noas', [NOAController::class, 'printBatch'])
        ->middleware($noaRoles)
        ->name('noas.print-batch');

    // Purchase Order module
    $poRoles =
        'role:'.
        implode(',', [RoleType::SUPERADMIN->value, RoleType::PO_ADMIN->value]);
    Route::get('purchase-orders/recent-pos', [PurchaseOrderController::class, 'recentPos'])
        ->middleware($poRoles)
        ->name('purchase-orders.recent-pos');
    Route::post('purchase-orders/suggest-po-no', [
        PurchaseOrderController::class,
        'suggestPoNo',
    ])
        ->middleware($poRoles)
        ->name('purchase-orders.suggest-po-no');
    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->except(['edit', 'update'])
        ->middleware($poRoles);
    Route::get('purchase-orders/{purchase_order}/edit', [PurchaseOrderController::class, 'edit'])
        ->middleware($poRoles)
        ->name('purchase-orders.edit');
    Route::put('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'update'])
        ->middleware($poRoles)
        ->name('purchase-orders.update');
    Route::get('purchase-orders/{purchase_order}/pdf', [
        PurchaseOrderController::class,
        'printPdf',
    ])
        ->middleware($poRoles)
        ->name('purchase-orders.pdf');
    Route::post('purchase-orders/download-pdfs', [
        PurchaseOrderController::class,
        'downloadPdfs',
    ])
        ->middleware($poRoles)
        ->name('purchase-orders.download-pdfs');
    Route::get('batches/{batch}/print-pos', [PurchaseOrderController::class, 'printBatch'])
        ->middleware($poRoles)
        ->name('purchase-orders.print-batch');

    // PO Transmittal module
    $inspectionRoles =
        'role:'.
        implode(',', [RoleType::SUPERADMIN->value, RoleType::PO_ADMIN->value]);
    Route::resource('po-transmittals', POTransmittalController::class)
        ->only(['index', 'create', 'store', 'show', 'update', 'destroy'])
        ->middleware($inspectionRoles);
    Route::get('po-transmittals/{po_transmittal}/pdf', [
        POTransmittalController::class,
        'printPdf',
    ])
        ->middleware($inspectionRoles)
        ->name('po-transmittals.pdf');
    Route::post('po-transmittals/download-pdfs', [
        POTransmittalController::class,
        'downloadPdfs',
    ])
        ->middleware($inspectionRoles)
        ->name('po-transmittals.download-pdfs');
    Route::get('po-transmittals/batch/{batch}/purchase-orders', [
        POTransmittalController::class,
        'batchPurchaseOrders',
    ])
        ->middleware($inspectionRoles)
        ->name('po-transmittals.batch-purchase-orders');
    Route::get('po-transmittals/batch/{batch}/print', [
        POTransmittalController::class,
        'printBatchPdf',
    ])
        ->middleware($inspectionRoles)
        ->name('po-transmittals.print-batch');

    // Acceptance & Inspection module
    $acceptanceInspectionRoles =
        'role:'.
        implode(',', [
            RoleType::SUPERADMIN->value,
            RoleType::INSPECTION_ADMIN->value,
            RoleType::PO_ADMIN->value,
        ]);
    Route::resource(
        'acceptance-inspections',
        AcceptanceInspectionController::class,
    )
        ->only(['index', 'create', 'store', 'show', 'update', 'destroy'])
        ->middleware($acceptanceInspectionRoles);
    Route::get('acceptance-inspections/{acceptance_inspection}/pdf', [
        AcceptanceInspectionController::class,
        'printPdf',
    ])
        ->middleware($acceptanceInspectionRoles)
        ->name('acceptance-inspections.pdf');

    // COA Inspection module
    $coaInspectionRoles =
        'role:'.
        implode(',', [
            RoleType::SUPERADMIN->value,
            RoleType::INSPECTION_ADMIN->value,
            RoleType::PO_ADMIN->value,
        ]);
    Route::resource('coa-inspections', COAInspectionController::class)
        ->parameters(['coa-inspections' => 'coa_inspection'])
        ->only(['index', 'create', 'store', 'show', 'update', 'destroy'])
        ->middleware($coaInspectionRoles);
    Route::get('coa-inspections/{coa_inspection}/pdf', [
        COAInspectionController::class,
        'printPdf',
    ])
        ->middleware($coaInspectionRoles)
        ->name('coa-inspections.pdf');
});
