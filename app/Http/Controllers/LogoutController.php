<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LogoutController extends Controller
{
    public function logout()
    {
        // logout user
        if (Auth::check()){
            Auth::logout();
            Session::remove("user_2fa");
            Session::forget("user_2fa");
            Session::flush();

            return redirect("/login");
        }else{
            return redirect(url()->previous());
        }
    }
}
