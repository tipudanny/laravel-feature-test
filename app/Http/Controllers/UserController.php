<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function store(Request $request)
    {
        //return $request->all();
        //return response()->json([],201);
        $request->validate([
           'name' => 'required',
           'email' => 'required|email|unique:users'
        ]);

    }

    public function index()
    {
        return response()->json([
            'users' => User::all()->except(auth()->id())
        ]);
    }
}
