<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify($user_id, Request $request) {

        //return $request->all();

        if (!$request->hasValidSignature()) {
            return response()->json(["msg" => "Invalid/Expired url provided."], 401);
        }

        $user = User::findOrFail($user_id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json([
            'message' => 'Yahoo..!! Verification Done !!'
        ]);
    }

    public function resend() {
        if (auth()->user()->hasVerifiedEmail()) {
            return response()->json(["msg" => "Email already verified."], 400);
        }

        auth()->user()->sendEmailVerificationNotification();

        return response()->json(["msg" => "Email verification link sent on your email id"]);
    }
}
