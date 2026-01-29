<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Funds;

class FundController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $lengthAwarePaginator = Funds::with("office")
            ->withCount("office")
            ->when($request->search, function ($query, string $search): void {
                $query->where("name", "like", sprintf("%%%s%%", $search));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        dd($lengthAwarePaginator->toArray());

        return Inertia::render("Funds/Index", [
            "funds" => $lengthAwarePaginator,
            "filters" => [
                "search" => $request->search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
