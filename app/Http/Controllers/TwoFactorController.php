<?php

namespace App\Http\Controllers;

use App\Helpers\EmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwoFactorController extends Controller
{
    public function show2faForm(Request $request)
    {
        // Check if MFA is enabled
        if (env("GOOGLE_AUTHENTICATOR") == false) {
            return redirect()->route('login');
        }

        $user = $request->user();
        $google2fa = app('pragmarx.google2fa');
        $transaction_req_id = session('transaction_req_id');
        if (!empty($transaction_req_id)) {
            $request->session()->put('user_2fa', 'false');
            session(['transaction_2fa' => 'false']);
        } else {
            session(['transaction_req_id' => '']);
            session(['form_request' => '']);
            session()->forget('form_request');
            session()->forget('transaction_req_id');
            session(['transaction_2fa' => 'false']);
        }
        //dd( session('form_request'));
        $QR_Image = '';
        $verified = $user->google_auth_verified;
        if (!$user->google_auth_verified) {
            if (!$user->google2fa_secret) {
                $user->google2fa_secret = $google2fa->generateSecretKey();
                $user->save();
            }

            $QR_Image = $google2fa->getQRCodeInline(
                config('app.name'),
                $user->email,
                $user->google2fa_secret
            );

            $QR_Image = 'data:image/svg+xml;base64,' . base64_encode($QR_Image);
        }
        //echo $user->google2fa_secret = $secret;
        //$user->save();
        return view('2fa.setup', ['QR_Image' => $QR_Image, 'secret' =>  $user->google2fa_secret, 'verified' => $verified, 'transaction_req_id' => $transaction_req_id]);
    }

    public function verify2fa(Request $request)
    {
        // Check if MFA is enabled
        if (env("GOOGLE_AUTHENTICATOR") == false) {
            return redirect()->route('login');
        }

        $google2fa = app('pragmarx.google2fa');

        $user = $request->user();
        $secret = $request->input('secret');
        Log::info('Stored Secret: ' . $user->google2fa_secret);
        Log::info('Entered Code: ' . $secret);
        $valid = $google2fa->verifyKey($user->google2fa_secret, $secret);

        if ($valid) {
            $user->google_auth_verified = true;
            $user->save();
            $request->session()->put('2fa', 'true');
            $request->session()->put('user_2fa', 'true');
            $request->session()->put('portal_last_activity', time());
            $transaction_req_id = session('transaction_req_id');
            //dd($transaction_req_id);
            if (!empty($transaction_req_id)) {
                session(['transaction_2fa' => true]);
                //$transaction_id = session('transaction_id');
                return redirect('/crypto-management/crypto-pending-transaction/approve/withdraw-otp');
            }
            session()->forget('form_submitted');
            return redirect()->route('home');
        }
        session()->forget('form_submitted');
        return redirect()->back()->with(['error' => 'Invalid OTP, please try again.']);
    }

    public function sendOtpToEmail()
    {
        // Check if MFA is enabled
        if (env("GOOGLE_AUTHENTICATOR") == false) {
            return redirect()->route('login');
        }

        if (env("MFA") == true) {
            if (!session()->has('otp_sent')) {
                session(['otp_sent' => true]);
                EmailNotification::sendOTP();
            }
            return view('auth.2fa');
        }
    }
}
