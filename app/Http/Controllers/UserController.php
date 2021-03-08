<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', $request->get('email'))->first();
        if ($user != null){
            if ($user->hasVerifiedEmail()){
                return response()->json(['message' => 'success.'],200);
            }
            return response()->json(['message' => 'you are not verified yet.'],401);
        }
        return response()->json(['error'=>'Unauthorized'],404);
    }

    public function store(Request $request)
    {
        //return $request->all();
        //return response()->json([],201);
        $validData = $request->validate([
           'name' => 'required',
           'email' => 'required|email|unique:users'
        ]);
        return response()->json($validData,201);

    }

    public function index()
    {
        return response()->json([
            'users' => User::all()->except(auth()->id())
        ]);
    }
    public function view($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['user' => $user['name']],200);
    }
}
