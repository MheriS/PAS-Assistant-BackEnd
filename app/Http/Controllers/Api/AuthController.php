<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Use Laravel Auth with username
        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']])) {
            $user = Auth::user();
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'user' => [
                    'name' => $user->name,
                    'username' => $user->username
                ],
                'is_admin' => true
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid username or password'
        ], 401);
    }
}
