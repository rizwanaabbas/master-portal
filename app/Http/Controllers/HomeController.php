<?php

namespace App\Http\Controllers;

use App\Helpers\ClientHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (Auth::user()->hasRole("super admin")) {
            return redirect("/dashboards/admin");
        }

        if (Auth::user()->hasRole("client") || Auth::user()->hasRole("basic client")) {
            // client portal is disabled 
            // return redirect("/client/dashboard");
            Auth::logout();
            return redirect("login")->with("error", 'Client portal is currently disabled.');
        }

        if (Auth::user()->hasRole(["finance manager", "finance"])) {
            return redirect("/dashboards/finance");
        }

        if (Auth::user()->hasRole(["operations manager", "operations"])) {
            return redirect("/dashboards/operations");
        }

        if (Auth::user()->hasRole("draft client")) {
            // client draft portal is disabled
            // return redirect("/dashboard/draft-client");
            Auth::logout();
            return redirect("login")->with("error", 'Client draft portal is currently disabled.');
        }
        if (Auth::user()->hasRole("compliance")) {
            return redirect("/dashboards/compliance");
        }
        // draft client
        $client = ClientHelper::getClientByUserId(Auth::user()->id);

        return view('home');
    }
}
