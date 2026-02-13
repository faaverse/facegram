<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string',
            'bio'           => 'required|string',
            'username'      => 'required|string',
            'password'      => 'required|string',
            'is_private'    => 'boolean',
        ]);

        $userId = DB::table('users')->insertGetId([
            'name'          => $validated['name'],
            'bio'           => $validated['bio'],
            'username'      => $validated['username'],
            'password'      => Hash::make($validated['password']),
            'is_private'    => $validated['is_private'] ?? false,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $user = \App\Models\User::find($userId);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'register succes',
            'token'   => $token,
            'user'    => [
                'id'        => $user->id,
                'name'      => $user->name,
                'bio'       => $user->bio,
                'username'  => $user->username,
                'is_private'=> $user->is_private,
            ]    
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username'  => 'required|string',
            'password'  => 'required|string',
        ]);
        $user = \App\Models\User::where('username', $validated['username'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'   => 'login success',
            'token'     => $token,
            'user'      => [
                'id'        => $user->id,
                'name'      => $user->name,
                'bio'       => $user->bio,
                'username'  => $user->username,
                'is_private'=> $user->is_private,
            ]
        ], 200);
    }

}
