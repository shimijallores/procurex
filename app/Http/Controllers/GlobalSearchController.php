<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcceptanceInspection;
use App\Models\BACResolution;
use App\Models\NOA;
use App\Models\POTransmittal;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RFQ;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = collect();

        // Static module pages (index only)
        $modules = [
            'Dashboard' => 'dashboard.index',
            'Procurement Map' => 'procurement-map.index',
            'Users' => 'users.index',
            'Roles' => 'roles.index',
            'Offices' => 'offices.index',
            'Project Codes' => 'project-codes.index',
            'Accounts' => 'accounts.index',
            'APPs' => 'apps.index',
            'Funds' => 'funds.index',
            'PPMPs' => 'ppmps.index',
            'Emanatings' => 'emanatings.index',
            'Canvassing' => 'canvasses.index',
            'Suppliers' => 'suppliers.index',
            'Master List' => 'master-list-items.index',
            'Purchase Requests' => 'purchase-requests.index',
            'PR Matrix' => 'purchase-request-matrix.index',
            'SVP Matrix' => 'svp-matrix.index',
            'Request for Quotation' => 'rfqs.index',
            'Abstract of Quotation' => 'aoqs.index',
            'Notice of Award' => 'noas.index',
            'Purchase Order' => 'purchase-orders.index',
            'BAC Resolutions' => 'bac-resolutions.index',
            'PO Transmittal' => 'po-transmittals.index',
            'Acceptance & Inspection' => 'acceptance-inspections.index',
            'COA Inspection' => 'coa-inspections.index',
            'Calendar' => 'calendars.index',
        ];

        $lowerQuery = strtolower($query);

        foreach ($modules as $name => $routeName) {
            if (str_contains(strtolower($name), $lowerQuery)) {
                $results->push([
                    'type' => 'Module',
                    'label' => $name,
                    'url' => route($routeName),
                ]);
            }
        }

        $searches = [
            [
                'type' => 'PR',
                'model' => PurchaseRequest::class,
                'route' => 'purchase-requests.show',
                'labelKey' => 'pr_no',
            ],
            [
                'type' => 'RFQ',
                'model' => RFQ::class,
                'route' => 'rfqs.show',
                'labelKey' => 'svp_no',
            ],
            [
                'type' => 'NOA',
                'model' => NOA::class,
                'route' => 'noas.show',
                'labelKey' => 'noa_no',
            ],
            [
                'type' => 'PO',
                'model' => PurchaseOrder::class,
                'route' => 'purchase-orders.show',
                'labelKey' => 'po_no',
            ],
            [
                'type' => 'BAC Resolution',
                'model' => BACResolution::class,
                'route' => 'bac-resolutions.show',
                'labelKey' => 'resolution_no',
            ],
            [
                'type' => 'Supplier',
                'model' => Supplier::class,
                'route' => 'suppliers.show',
                'labelKey' => 'name',
            ],
            [
                'type' => 'AIR',
                'model' => AcceptanceInspection::class,
                'route' => 'acceptance-inspections.show',
                'labelKey' => 'air_no',
            ],
            [
                'type' => 'Transmittal',
                'model' => POTransmittal::class,
                'route' => 'po-transmittals.show',
                'labelKey' => 'transmittal_no',
            ],
        ];

        foreach ($searches as $search) {
            $modelClass = $search['model'];

            try {
                $records = $modelClass::search($query)->take(5)->get();
            } catch (\Exception) {
                continue;
            }

            foreach ($records as $record) {
                $results->push([
                    'type' => $search['type'],
                    'label' => (string) $record->{$search['labelKey']},
                    'url' => route($search['route'], $record),
                ]);
            }
        }

        return response()->json($results->take(20)->values());
    }
}
