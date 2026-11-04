<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class KYCController extends Controller
{
    public function update(Request $request)
    {
        try{
            $validation = validator::make($request->all(), [
                'username' => 'required|max:100',
                'kyc_status' => 'required',
                'reject_reason' => 'max:100',
                'user_status' => 'required|max:100'
            ]);

            if ($validation->fails()){
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validation->errors()
                ]);
            }

            // need to assign instant card to the KYC approved username

            // need to send email notification to operations

            // need to send email to customer

            return response()->json([
                'success' => true,
                'message' => 'KYC update received successfully.',
            ], 200);

        }catch (throwable $th){
            return response()->json([
                'success' => false,
                'message' => 'We have received a system error, please try again in a while.',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
