<?php

namespace App\Http\Controllers;

use App\Helpers\EmailNotification;
use App\Models\UserCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class TwoFAController extends Controller
{
    public function index()
    {
        // check user OTP record
        $code = UserCode::where('user_id', auth()->user()->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        if (!is_null($code)) {
            return view('auth.2fa');
        } else {

            // delete previously generated codes
            UserCode::where('user_id', auth()->user()->id)->delete();

            Auth::logout();
            Session::remove("user_2fa");
            Session::forget("user_2fa");
            Session::flush();
            return redirect("/logout");
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);

        $find = UserCode::where('user_id', auth()->user()->id)
            ->where('code', $request->code)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        if (!is_null($find)) {
            Session::put('user_2fa', auth()->user()->id);
            Session::put('2fa', 'true');
            Session::put('portal_last_activity', time());

            // remove the OTP code
            $find->delete();
            $user = Auth::user();
            if ($user->email_verified_at == null) {
                $user->email_verified_at = Carbon::now();
                $user->save();
            }

            return redirect()->route('home');
        }

        return back()->with('error', 'You entered wrong code.');
    }

    public function resend()
    {
        EmailNotification::sendOTP();
        return back()->with('success', 'We sent you code on your email.');
    }

    public function cancel()
    {
        $code = UserCode::where('user_id', auth()->user()->id);
        $code->delete();
        Auth::logout();
        Session::forget('user_2fa');
        Session::remove("user_2fa");
        Session::flush();

        return redirect("/login");
    }
}
