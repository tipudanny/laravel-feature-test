<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Contracts\Providers\Auth;

//Email Verification
    Auth::routes(['verify' => true]);
    Route::get('email/verify/{id}', [EmailVerificationController::class,'verify'])->name('verification.verify');
    Route::get('email/resend',      [EmailVerificationController::class,'resend'])->name('verification.resend');

    Route::post('login',        [AuthController::class,'login'])->middleware('verified');
    Route::post('registration', [AuthController::class,'store']);
    Route::post('login-verify', [UserController::class,'login']);

    Route::group(['middleware' => ['auth:api','verified']], function () {

        Route::post('logout', [AuthController::class,'logout']);
        Route::post('refresh', [AuthController::class,'refresh']);
        Route::post('me', [AuthController::class,'me']);

        Route::post('user-create',[UserController::class,'store']);
        Route::get('user-create',[UserController::class,'index']);
        Route::get('user-create/{id}',[UserController::class,'view']);

    });
