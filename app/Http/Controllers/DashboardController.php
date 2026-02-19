<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Models\APP;
use App\Models\Canvas;
use App\Models\Emanating;
use App\Models\MasterListItem;
use App\Models\PPMP;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        $data = [];

        if ($role && RoleType::isSystemRole($role->name)) {
            // System roles see all data
            if ($role->name === RoleType::SUPERADMIN->value) {
                $data['totalApps'] = APP::count();
                $data['totalPpmps'] = PPMP::count();
                $data['totalEmanating'] = Emanating::count();
            } elseif ($role->name === RoleType::BAC_RESO_ADMIN->value) {
                $data['totalApps'] = APP::count();
                $data['recentApps'] = APP::latest()->limit(5)->get();
            } elseif ($role->name === RoleType::BUDGETING_ADMIN->value) {
                $data['totalPpmps'] = PPMP::count();
                $data['totalEmanating'] = Emanating::count();
                $data['recentPpmps'] = PPMP::latest()->limit(5)->get();
                $data['recentEmanating'] = Emanating::latest()->limit(5)->get();
            } elseif ($role->name === RoleType::CANVASSING_ADMIN->value) {
                $data['totalCanvasses'] = Canvas::count();
                $data['pendingCanvasses'] = Canvas::where('status', 'pending')->count();
                $data['completedCanvasses'] = Canvas::where('status', 'completed')->count();
                $data['totalMasterListItems'] = MasterListItem::count();
                $data['recentCanvasses'] = Canvas::with('emanating.project')->latest()->limit(5)->get();
            }
        } else {
            // Office roles see only their office's data
            $officeId = $user->office_id;
            $data['totalApps'] = APP::where('office_id', $officeId)->count();
            $data['totalPpmps'] = PPMP::where('office_id', $officeId)->count();
            $data['totalEmanating'] = Emanating::where('office_id', $officeId)->count();
            $data['recentApps'] = APP::where('office_id', $officeId)->latest()->limit(5)->get();
            $data['recentPpmps'] = PPMP::where('office_id', $officeId)->latest()->limit(5)->get();
            $data['recentEmanating'] = Emanating::where('office_id', $officeId)->latest()->limit(5)->get();
        }

        return Inertia::render('Dashboard', $data);
    }
}
