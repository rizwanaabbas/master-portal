<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Key validator
Route::group(["middleware" => "api_key"], function(){

    // API login route for bearer token generation
    Route::post("/login", [\App\Http\Controllers\API\AuthController::class, 'login'])->middleware('throttle:5,1');

    // protected routes only accessible for authenticated users, having bearer token
    Route::group(["middleware" => "auth:sanctum"], function(){

        Route::post("/vista-money/kyc-callback", [\App\Http\Controllers\API\KYCController::class, 'update']);
    });
});

