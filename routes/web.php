<?php

use App\Http\Controllers\APPController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CanvasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EarmarkController;
use App\Http\Controllers\EmanatingController;
use App\Http\Controllers\FundController;
use App\Http\Controllers\MasterListCategoryController;
use App\Http\Controllers\MasterListItemController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PPMPController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\RFQController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): \Illuminate\Http\RedirectResponse {
    return redirect(route('login'));
});

// Authentication
Route::get('/login', [SessionController::class, 'index'])->name('login')->middleware('guest');
Route::post('/login', [SessionController::class, 'login'])->name('login.login')->middleware('guest');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/logout', [SessionController::class, 'logout'])->name('logout');

    // Dashboard - accessible to all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Define all resources once, authorization will be handled in controllers/policies
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class)->middleware('role:Superadmin');
    Route::resource('offices', OfficeController::class)->middleware('role:Superadmin');
    Route::resource('calendars', CalendarController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('role:Superadmin,BAC Reso Admin,Budgeting Admin,office');
    Route::post('calendars/check-date', [CalendarController::class, 'checkDate'])->middleware('auth')->name('calendars.check-date');
    Route::resource('funds', FundController::class)->middleware('role:Superadmin,office');
    Route::resource('apps', APPController::class)->middleware('role:Superadmin,BAC Reso Admin,office');
    Route::post('apps/{app}/import', [APPController::class, 'import'])->middleware('role:Superadmin,BAC Reso Admin,office')->name('apps.import');
    Route::get('apps/{app}/download', [APPController::class, 'download'])->middleware('role:Superadmin,BAC Reso Admin,office')->name('apps.download');
    Route::resource('ppmps', PPMPController::class)->middleware('role:Superadmin,Budgeting Admin,office');
    Route::post('ppmps/{ppmp}/import', [PPMPController::class, 'import'])->middleware('role:Superadmin,Budgeting Admin,office')->name('ppmps.import');
    Route::get('ppmps/{ppmp}/download-csv', [PPMPController::class, 'downloadCsv'])->middleware('role:Superadmin,Budgeting Admin,office')->name('ppmps.download-csv');
    Route::post('ppmps/{ppmp}/approve', [PPMPController::class, 'approve'])->middleware('role:Superadmin,Budgeting Admin,office')->name('ppmps.approve');
    Route::post('ppmps/{ppmp}/reject', [PPMPController::class, 'reject'])->middleware('role:Superadmin,Budgeting Admin,office')->name('ppmps.reject');
    Route::resource('emanatings', EmanatingController::class)->middleware('role:Superadmin,Budgeting Admin,office');
    Route::post('emanatings/{emanating}/import', [EmanatingController::class, 'import'])->middleware('role:Superadmin,Budgeting Admin,office')->name('emanatings.import');
    Route::get('emanatings/{emanating}/download-csv', [EmanatingController::class, 'downloadCsv'])->middleware('role:Superadmin,Budgeting Admin,office')->name('emanatings.download-csv');
    Route::post('emanatings/{emanating}/approve', [EmanatingController::class, 'approve'])->middleware('role:Superadmin,Budgeting Admin,office')->name('emanatings.approve');
    Route::post('emanatings/{emanating}/reject', [EmanatingController::class, 'reject'])->middleware('role:Superadmin,Budgeting Admin,office')->name('emanatings.reject');

    // Canvassing module
    $canvassingRoles = 'role:Superadmin,Canvassing Admin';
    Route::resource('suppliers', SupplierController::class)->middleware($canvassingRoles);
    Route::resource('master-list-categories', MasterListCategoryController::class)->except(['show'])->middleware($canvassingRoles);
    Route::resource('master-list-items', MasterListItemController::class)->except(['show'])->middleware($canvassingRoles);
    Route::post('master-list-items/{master_list_item}/toggle-phase-out', [MasterListItemController::class, 'togglePhaseOut'])->middleware($canvassingRoles)->name('master-list-items.toggle-phase-out');
    Route::resource('canvasses', CanvasController::class)->except(['edit', 'update'])->middleware($canvassingRoles);
    Route::post('canvasses/{canvas}/items/{canvas_item}/selections', [CanvasController::class, 'saveItemSelections'])->middleware($canvassingRoles)->name('canvasses.items.selections');
    Route::post('canvasses/{canvas}/complete', [CanvasController::class, 'complete'])->middleware($canvassingRoles)->name('canvasses.complete');
    Route::post('canvasses/{canvas}/return', [CanvasController::class, 'return'])->middleware($canvassingRoles)->name('canvasses.return');

    // Purchase Request module
    $prRoles = 'role:Superadmin,PR Admin';
    Route::resource('purchase-requests', PurchaseRequestController::class)->middleware($prRoles);
    Route::post('purchase-requests/{purchase_request}/approve', [PurchaseRequestController::class, 'approve'])->middleware($prRoles)->name('purchase-requests.approve');
    Route::post('purchase-requests/{purchase_request}/return', [PurchaseRequestController::class, 'returnToOffice'])->middleware($prRoles)->name('purchase-requests.return');
    Route::get('purchase-requests/{purchase_request}/pdf', [PurchaseRequestController::class, 'printPdf'])->middleware($prRoles)->name('purchase-requests.pdf');

    // Budgeting module
    $budgetingRoles = 'role:Superadmin,Budgeting Admin';
    Route::resource('earmarks', EarmarkController::class)->middleware($budgetingRoles);
    Route::get('earmarks/{earmark}/pdf', [EarmarkController::class, 'printPdf'])->middleware($budgetingRoles)->name('earmarks.pdf');
    Route::post('purchase-requests/{purchase_request}/budget-return', [EarmarkController::class, 'budgetReturn'])->middleware($budgetingRoles)->name('purchase-requests.budget-return');

    // RFQ module
    $rfqRoles = 'role:Superadmin,Quotation Admin';
    Route::resource('rfqs', RFQController::class)->only(['index', 'create', 'store', 'show', 'destroy'])->middleware($rfqRoles);
    Route::post('rfqs/{rfq}/suppliers/{rfq_supplier}/submit', [RFQController::class, 'submitSupplier'])->middleware($rfqRoles)->name('rfqs.suppliers.submit');
    Route::get('rfqs/{rfq}/pdf', [RFQController::class, 'printPdf'])->middleware($rfqRoles)->name('rfqs.pdf');
});
