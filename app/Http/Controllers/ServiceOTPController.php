<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Models\MobileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ServiceOTPController extends Controller
{
    public function showOTP($id)
    {
        $service = MobileRequest::find($id);

        return view("client-portal.service-request.otp", compact('service'));
    }

    public function validateOTP(Request $request)
    {
        $service = MobileRequest::find($request->get("service_id"));

        $otp = Common::verifyRequestOTP($request->get("otp"), $service->id, 'SERVICE_REQUEST');

        if ($otp) {

            $service->status = $service->status;
            $service->is_otp_verified = true;
            $service->save();

            $otp->delete();

            EmailNotification::notifyOperationsManagersForServiceRequest($service);

            return redirect("/client/services/requests/list")->with("success", "Service created successfully.");
        } else {
            return back()->with("error", "Invalid OTP provided, please try again with correct OTP");
        }
    }

    public function generateOTP(Request $request)
    {
        $service = MobileRequest::find($request->get("otp_id"));

        if ($service) {

            Common::deletePreviousOTP($service->id, 'SERVICE_REQUEST');

            EmailNotification::RequestOTP($service, 'SERVICE_REQUEST');

            return redirect("/client/services/requests/otp/" . $service->id)->with("success", "OTP has been generated and sent to your registered email");
        } else {
            return redirect("/client/services/requests/otp/" . $service->id)->with("error", "Service not found");
        }
    }
}
