<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
}
