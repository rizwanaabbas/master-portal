<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Sanction;
use App\Models\Sanctions;
use Illuminate\Http\Request;

class ComplianceDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suspiciousScreenings = Sanctions::where("is_suspicious", "1")->count();
        $screenings = Sanctions::all()->count();
        return view("dashboards.compliance", compact('suspiciousScreenings', 'screenings'));
        //
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
    }

    /**
     * Display the specified resource.
     */


    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        //
    }
}
