<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            // attempt login
            if (!Auth::attempt($request->only(['email', 'password']))){
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials provided.'
                ], 401);
            }

            $user = $request->user();

            if (!$user->hasRole("API")){
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user, please contact system admin for more details.'
                ], 403);
            }

            // delete old token
            if ($user->tokens()){
                $user->tokens()->delete();
            }

            // generate new token
            return response()->json([
                'success' => true,
                'message' => 'User login successfully.',
                'token' => $user->createToken(env('TOKEN_NAME'))->plainTextToken
            ], 200);

        }catch (throwable $th){
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
