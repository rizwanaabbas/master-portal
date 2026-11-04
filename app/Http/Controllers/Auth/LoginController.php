<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\EmailNotification;
use App\Helpers\OnboardingHelper;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');


        if (Auth::attempt($credentials, false)) {

            $request->session()->put('portal_last_activity', time());

            $onboarding = new OnboardingHelper();
            $user = Auth::user();

            if (env("GOOGLE_AUTHENTICATOR")) {
                $request->session()->put('user_2fa', 'true');
            }

            try {
                if ($user->roles->count() == 1 && ($user->hasRole("client") || $user->hasRole("draft client") || $user->hasRole("basic client"))) {
                    return redirect("login")->with("error", 'Oppes! You have entered invalid credentials');
                    // $onboarding->loginAPI($request->input('email'), $request->input('password'));
                } else {
                    $onboarding->loginAdminAPI($request->input('email'), $request->input('password'));
                }
            } catch (\Exception $e) {
                Log::error('Exception thrown while loggin in the user on API Server. | Exception Message: ' . $e->getMessage());
            }

            if (Auth::user()->email_verified_at == null) {
                if (!env("GOOGLE_AUTHENTICATOR")) {
                    EmailNotification::sendOTP();
                }

                return redirect()->route('2fa.index');
            } else {
                if (env("MFA") == true) {
                    if (!env("GOOGLE_AUTHENTICATOR")) {
                        EmailNotification::sendOTP();
                    }

                    return redirect()->route('2fa.index');
                }
            }
        }

        return redirect("login")->with("error", 'Oppes! You have entered invalid credentials');
    }
}
