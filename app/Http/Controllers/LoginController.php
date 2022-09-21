<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return 'missing email and password';
        }

        // Try to login
        if (Auth::attempt($request->only(['email', 'password'])) === false) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '401',
                        'source' => ['pointer' => $request->url()],
                        'title' => 'Email and/or password is incorrect',
                        'detail' => 'The given credentials do not match. Check the email and password.',
                    ],
                ],
            ], 401);
        }

        // Remove existing tokens and create new one
        $request->user()->tokens()->delete();
        $token = $request->user()->createToken('auth_token');

        return response()->json(['token' => $token->plainTextToken]);
    }

    /**
     * Handle the logout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Remove existing tokens
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
