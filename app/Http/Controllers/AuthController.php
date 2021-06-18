<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\RegisterRequest;

class AuthController extends Controller
{
    public function register(RegisterRequest $registerRequest) {
        $user = User::create($registerRequest->all());
        return response()->json([
            'status' => true,
            'data' => $user
        ], 200);
    }

    public function login(Request $request) {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();
        if($user) {
            if(Hash::check($credentials['password'], $user->password)) {
                $tokenResult = $user->createToken('Personal Access Token');
                $token = $tokenResult->token;
                $token->save();
                return response()->json([
                    'message' => 'Login Successfully',
                    'token' => [
                        'keyApp' => $tokenResult->accessToken,
                        'expires_at' => Carbon::parse(
                            $tokenResult->token->expires_at
                        )->toDateTimeString(),
                    ],
                    'data' => $user
                ],200);
            }
        }

        return response()->json([
            'status' => false,
            'data' => [
                'head' => 'Fail',
                'body' => 'Data not found'
            ]
        ], 401);
    }

    public function check_token() {
        return response()->json(Auth::user());
    }

    public function logout() {
        Auth::user()->token()->revoke();
        return response()->json(['error' => FALSE, 'message' => 'Logout Successfully']);
    }
}
