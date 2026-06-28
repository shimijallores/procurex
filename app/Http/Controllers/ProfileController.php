<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()?->loadMissing(['roles', 'office']);

        return Inertia::render('Profile/Index', [
            'user' => $user,
        ]);
    }

    public function settings(Request $request): Response
    {
        $user = $request->user()?->loadMissing(['roles', 'office']);

        return Inertia::render('Settings/Index', [
            'user' => $user,
        ]);
    }

    public function acknowledgeCompliance(Request $request): RedirectResponse
    {
        $request->user()?->update(['compliance_notice_seen' => true]);

        return back();
    }
}
