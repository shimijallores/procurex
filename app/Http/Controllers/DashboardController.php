<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Models\APP;
use App\Models\AOQ;
use App\Models\BACResolution;
use App\Models\Canvas;
use App\Models\Earmark;
use App\Models\Emanating;
use App\Models\MasterListItem;
use App\Models\NOA;
use App\Models\POTransmittal;
use App\Models\PPMP;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RFQ;
use App\Models\RFQSupplier;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()?->loadMissing(['role', 'office']);
        $roleName = $user?->role?->name;

        $payload = [
            'roleName' => $roleName,
            'scopeLabel' => RoleType::isSystemRole((string) $roleName)
                ? 'System-wide'
                : ($user?->office?->name ?? 'Office'),
            'metrics' => [],
            'pipeline' => [],
            'recentActivities' => [],
            'quickLinks' => [],
        ];

        if ($roleName === RoleType::SUPERADMIN->value) {
            $payload['metrics'] = [
                $this->metric('Total Users', User::count(), 'lucide:users', 'All authenticated accounts'),
                $this->metric('Purchase Requests', PurchaseRequest::count(), 'lucide:file-plus-2', 'End-to-end request volume'),
                $this->metric('Purchase Orders', PurchaseOrder::count(), 'lucide:file-signature', 'Awarded and generated POs'),
                $this->metric('PO Transmittals', POTransmittal::count(), 'lucide:files', 'COA and OPG transmittals'),
            ];

            $payload['pipeline'] = $this->systemPipeline();
            $payload['recentActivities'] = $this->superadminRecent();
            $payload['quickLinks'] = [
                $this->quickLink('Purchase Requests', route('purchase-requests.index'), 'lucide:file-plus-2'),
                $this->quickLink('Request for Quotation', route('rfqs.index'), 'lucide:file-text'),
                $this->quickLink('Purchase Orders', route('purchase-orders.index'), 'lucide:file-signature'),
                $this->quickLink('PO Transmittals', route('po-transmittals.index'), 'lucide:files'),
            ];
        } elseif ($roleName === RoleType::BAC_RESO_ADMIN->value) {
            $payload['metrics'] = [
                $this->metric('BAC Resolutions', BACResolution::count(), 'lucide:files', 'Total drafted and finalized'),
                $this->metric('Finalized', BACResolution::whereNotNull('finalized_at')->count(), 'lucide:check-circle', 'Ready for NOA generation'),
                $this->metric('Pending Finalize', BACResolution::whereNull('finalized_at')->count(), 'lucide:clock', 'Requires final review'),
                $this->metric('NOAs Generated', NOA::count(), 'lucide:file-badge', 'Issued notices of award'),
            ];

            $payload['pipeline'] = [
                $this->stage('AOQ Ready', AOQ::whereDoesntHave('bacResolution')->count()),
                $this->stage('BAC Drafted', BACResolution::whereNull('finalized_at')->count()),
                $this->stage('BAC Finalized', BACResolution::whereNotNull('finalized_at')->count()),
                $this->stage('NOA Issued', NOA::count()),
            ];

            $payload['recentActivities'] = BACResolution::with('aoq.rfq')
                ->latest('resolution_date')
                ->limit(6)
                ->get()
                ->map(fn(BACResolution $item) => [
                    'title' => $item->resolution_no ?: ('BAC #' . $item->id),
                    'subtitle' => $item->project_name ?: 'No project name',
                    'meta' => $item->isFinalized() ? 'Finalized' : 'Draft',
                    'date' => optional($item->resolution_date)->format('M d, Y') ?: '—',
                    'link' => route('bac-resolutions.show', $item),
                ])->values();

            $payload['quickLinks'] = [
                $this->quickLink('BAC Resolutions', route('bac-resolutions.index'), 'lucide:files'),
                $this->quickLink('Notices of Award', route('noas.index'), 'lucide:file-badge'),
                $this->quickLink('AOQ List', route('aoqs.index'), 'lucide:file-spreadsheet'),
            ];
        } elseif ($roleName === RoleType::BUDGETING_ADMIN->value) {
            $payload['metrics'] = [
                $this->metric('PPMPs', PPMP::count(), 'lucide:clipboard-list', 'Annual and addendum plans'),
                $this->metric('Emanatings', Emanating::count(), 'lucide:clipboard-minus', 'Submitted emanating requests'),
                $this->metric('Pending Budget Review', PurchaseRequest::where('status', 'for_budget_review')->count(), 'lucide:clock', 'Awaiting budget action'),
                $this->metric('Earmarks Issued', Earmark::count(), 'lucide:stamp', 'Budget certification records'),
            ];

            $payload['pipeline'] = [
                $this->stage('PR for Budget', PurchaseRequest::where('status', 'for_budget_review')->count()),
                $this->stage('PR Approved', PurchaseRequest::where('status', 'approved')->count()),
                $this->stage('PR Returned', PurchaseRequest::where('status', 'returned')->count()),
                $this->stage('Earmarks', Earmark::count()),
            ];

            $payload['recentActivities'] = Earmark::with('purchaseRequest.office')
                ->latest('earmark_date')
                ->limit(6)
                ->get()
                ->map(fn(Earmark $item) => [
                    'title' => $item->earmark_no ?: ('Earmark #' . $item->id),
                    'subtitle' => $item->purchaseRequest?->office?->name ?: 'No office',
                    'meta' => 'Certified Amount: ₱' . number_format((float) $item->certified_amount, 2),
                    'date' => optional($item->earmark_date)->format('M d, Y') ?: '—',
                    'link' => route('earmarks.show', $item),
                ])->values();

            $payload['quickLinks'] = [
                $this->quickLink('PPMPs', route('ppmps.index'), 'lucide:clipboard-list'),
                $this->quickLink('Emanatings', route('emanatings.index'), 'lucide:clipboard-minus'),
                $this->quickLink('Budgeting / Earmarks', route('earmarks.index'), 'lucide:stamp'),
            ];
        } elseif ($roleName === RoleType::CANVASSING_ADMIN->value) {
            $payload['metrics'] = [
                $this->metric('Canvasses', Canvas::count(), 'lucide:shopping-cart', 'All canvassing records'),
                $this->metric('Pending Canvasses', Canvas::where('status', 'pending')->count(), 'lucide:clock', 'Still being canvassed'),
                $this->metric('Completed Canvasses', Canvas::where('status', 'completed')->count(), 'lucide:check-circle', 'Ready for PR flow'),
                $this->metric('Master List Items', MasterListItem::count(), 'lucide:list-checks', 'Catalogued items'),
            ];

            $payload['pipeline'] = [
                $this->stage('Pending', Canvas::where('status', 'pending')->count()),
                $this->stage('Completed', Canvas::where('status', 'completed')->count()),
            ];

            $payload['recentActivities'] = Canvas::with('emanating.project')
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn(Canvas $item) => [
                    'title' => 'Canvas #' . $item->id,
                    'subtitle' => $item->emanating?->project?->name ?: 'No project',
                    'meta' => ucfirst((string) $item->status),
                    'date' => optional($item->created_at)->format('M d, Y') ?: '—',
                    'link' => route('canvasses.show', $item),
                ])->values();

            $payload['quickLinks'] = [
                $this->quickLink('Canvassing', route('canvasses.index'), 'lucide:shopping-cart'),
                $this->quickLink('Suppliers', route('suppliers.index'), 'lucide:truck'),
                $this->quickLink('Master List', route('master-list-items.index'), 'lucide:list-checks'),
            ];
        } elseif ($roleName === RoleType::PR_ADMIN->value) {
            $payload['metrics'] = [
                $this->metric('Purchase Requests', PurchaseRequest::count(), 'lucide:file-plus-2', 'All submitted PRs'),
                $this->metric('Draft', PurchaseRequest::where('status', 'draft')->count(), 'lucide:edit-3', 'Still editable'),
                $this->metric('For Budget Review', PurchaseRequest::where('status', 'for_budget_review')->count(), 'lucide:clock', 'Awaiting budget action'),
                $this->metric('Approved', PurchaseRequest::where('status', 'approved')->count(), 'lucide:check-circle', 'Ready for RFQ'),
            ];

            $payload['pipeline'] = [
                $this->stage('Draft', PurchaseRequest::where('status', 'draft')->count()),
                $this->stage('For Budget Review', PurchaseRequest::where('status', 'for_budget_review')->count()),
                $this->stage('Approved', PurchaseRequest::where('status', 'approved')->count()),
                $this->stage('Returned', PurchaseRequest::where('status', 'returned')->count()),
            ];

            $payload['recentActivities'] = PurchaseRequest::with('office')
                ->latest('pr_date')
                ->limit(6)
                ->get()
                ->map(fn(PurchaseRequest $item) => [
                    'title' => $item->pr_no ?: ('PR #' . $item->id),
                    'subtitle' => $item->office?->name ?: 'No office',
                    'meta' => ucfirst(str_replace('_', ' ', (string) $item->status)),
                    'date' => optional($item->pr_date)->format('M d, Y') ?: '—',
                    'link' => route('purchase-requests.show', $item),
                ])->values();

            $payload['quickLinks'] = [
                $this->quickLink('Purchase Requests', route('purchase-requests.index'), 'lucide:file-plus-2'),
                $this->quickLink('Create PR', route('purchase-requests.create'), 'lucide:plus-circle'),
            ];
        } elseif ($roleName === RoleType::QUOTATION_ADMIN->value) {
            $payload['metrics'] = [
                $this->metric('RFQs', RFQ::count(), 'lucide:file-text', 'Quotation requests created'),
                $this->metric('Due This Week', RFQ::whereBetween('submission_deadline', [now()->toDateString(), now()->addWeek()->toDateString()])->count(), 'lucide:calendar-days', 'Submission deadlines approaching'),
                $this->metric('AOQs', AOQ::count(), 'lucide:file-spreadsheet', 'Abstract of quotation records'),
                $this->metric('Late Supplier Submissions', RFQSupplier::where('is_late', true)->count(), 'lucide:alert-triangle', 'Marked late supplier quotes'),
            ];

            $payload['pipeline'] = [
                $this->stage('RFQ Created', RFQ::count()),
                $this->stage('AOQ Generated', AOQ::count()),
                $this->stage('BAC Pending', AOQ::whereDoesntHave('bacResolution')->count()),
            ];

            $payload['recentActivities'] = RFQ::with('purchaseRequest.office')
                ->latest('rfq_date')
                ->limit(6)
                ->get()
                ->map(fn(RFQ $item) => [
                    'title' => $item->svp_no ?: ('RFQ #' . $item->id),
                    'subtitle' => $item->project_name ?: 'No project name',
                    'meta' => $item->purchaseRequest?->office?->name ?: 'No office',
                    'date' => optional($item->rfq_date)->format('M d, Y') ?: '—',
                    'link' => route('rfqs.show', $item),
                ])->values();

            $payload['quickLinks'] = [
                $this->quickLink('RFQs', route('rfqs.index'), 'lucide:file-text'),
                $this->quickLink('AOQs', route('aoqs.index'), 'lucide:file-spreadsheet'),
            ];
        } elseif ($roleName === RoleType::DOCUMENT_ADMIN->value) {
            $payload['metrics'] = [
                $this->metric('Notices of Award', NOA::count(), 'lucide:file-badge', 'Issued NOA records'),
                $this->metric('Purchase Orders', PurchaseOrder::count(), 'lucide:file-signature', 'Generated POs'),
                $this->metric('PO Transmittals', POTransmittal::count(), 'lucide:files', 'COA and OPG transmittals'),
                $this->metric('PO Without Transmittal', PurchaseOrder::whereDoesntHave('poTransmittals')->count(), 'lucide:alert-circle', 'Needs transmittal generation'),
            ];

            $payload['pipeline'] = [
                $this->stage('NOA Issued', NOA::count()),
                $this->stage('PO Generated', PurchaseOrder::count()),
                $this->stage('PO Transmitted', POTransmittal::count()),
            ];

            $payload['recentActivities'] = POTransmittal::with('purchaseOrder')
                ->latest('transmittal_date')
                ->limit(6)
                ->get()
                ->map(fn(POTransmittal $item) => [
                    'title' => strtoupper((string) $item->type) . ' - ' . ($item->transmittal_no ?: ('#' . $item->id)),
                    'subtitle' => $item->purchaseOrder?->po_no ?: 'No PO',
                    'meta' => $item->signatory_name ?: 'No signatory',
                    'date' => optional($item->transmittal_date)->format('M d, Y') ?: '—',
                    'link' => route('po-transmittals.show', $item),
                ])->values();

            $payload['quickLinks'] = [
                $this->quickLink('Notice of Award', route('noas.index'), 'lucide:file-badge'),
                $this->quickLink('Purchase Order', route('purchase-orders.index'), 'lucide:file-signature'),
                $this->quickLink('PO Transmittal', route('po-transmittals.index'), 'lucide:files'),
            ];
        } else {
            $officeId = $user?->office_id;

            $emanatingQuery = Emanating::whereHas('project.fund', fn($fund) => $fund->where('office_id', $officeId));
            $prQuery = PurchaseRequest::where('office_id', $officeId);

            $payload['metrics'] = [
                $this->metric('APPs', APP::where('office_id', $officeId)->count(), 'lucide:layout-grid', 'Annual procurement plans'),
                $this->metric('PPMPs', PPMP::where('office_id', $officeId)->count(), 'lucide:clipboard-list', 'Project procurement plans'),
                $this->metric('Emanatings', (clone $emanatingQuery)->count(), 'lucide:clipboard-minus', 'Submitted emanating requests'),
                $this->metric('Purchase Requests', (clone $prQuery)->count(), 'lucide:file-plus-2', 'Office PR records'),
            ];

            $payload['pipeline'] = [
                $this->stage('PR Draft', (clone $prQuery)->where('status', 'draft')->count()),
                $this->stage('PR Budget Review', (clone $prQuery)->where('status', 'for_budget_review')->count()),
                $this->stage('PR Approved', (clone $prQuery)->where('status', 'approved')->count()),
                $this->stage('PR Returned', (clone $prQuery)->where('status', 'returned')->count()),
            ];

            $payload['recentActivities'] = PurchaseRequest::with('office')
                ->where('office_id', $officeId)
                ->latest('pr_date')
                ->limit(6)
                ->get()
                ->map(fn(PurchaseRequest $item) => [
                    'title' => $item->pr_no ?: ('PR #' . $item->id),
                    'subtitle' => $item->purpose ?: 'No purpose provided',
                    'meta' => ucfirst(str_replace('_', ' ', (string) $item->status)),
                    'date' => optional($item->pr_date)->format('M d, Y') ?: '—',
                    'link' => route('purchase-requests.show', $item),
                ])->values();

            $payload['quickLinks'] = [
                $this->quickLink('APPs', route('apps.index'), 'lucide:layout-grid'),
                $this->quickLink('PPMPs', route('ppmps.index'), 'lucide:clipboard-list'),
                $this->quickLink('Emanatings', route('emanatings.index'), 'lucide:clipboard-minus'),
                $this->quickLink('Purchase Requests', route('purchase-requests.index'), 'lucide:file-plus-2'),
            ];
        }

        return Inertia::render('Dashboard', $payload);
    }

    private function metric(string $title, int|float $value, string $icon, string $description): array
    {
        return [
            'title' => $title,
            'value' => $value,
            'icon' => $icon,
            'description' => $description,
        ];
    }

    private function stage(string $label, int $value): array
    {
        return [
            'label' => $label,
            'value' => $value,
        ];
    }

    private function quickLink(string $label, string $href, string $icon): array
    {
        return [
            'label' => $label,
            'href' => $href,
            'icon' => $icon,
        ];
    }

    private function systemPipeline(): array
    {
        return [
            $this->stage('PR', PurchaseRequest::count()),
            $this->stage('RFQ', RFQ::count()),
            $this->stage('AOQ', AOQ::count()),
            $this->stage('BAC', BACResolution::count()),
            $this->stage('NOA', NOA::count()),
            $this->stage('PO', PurchaseOrder::count()),
            $this->stage('PO Transmittal', POTransmittal::count()),
        ];
    }

    private function superadminRecent()
    {
        return PurchaseOrder::with('noa.bacResolution')
            ->latest('po_date')
            ->limit(6)
            ->get()
            ->map(fn(PurchaseOrder $item) => [
                'title' => $item->po_no,
                'subtitle' => $item->noa?->bacResolution?->project_name ?: 'No project name',
                'meta' => 'PO Amount: ₱' . number_format((float) $item->total_amount, 2),
                'date' => optional($item->po_date)->format('M d, Y') ?: '—',
                'link' => route('purchase-orders.show', $item),
            ])->values();
    }
}
