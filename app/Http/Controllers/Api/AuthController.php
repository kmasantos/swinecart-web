<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Response;
use \Illuminate\Http\Response as Res;
use Validator;
use JWTAuth;
use Auth;
use App\Models\User;
use App\Models\UserLog;
use Carbon\Carbon;
use Tymon\JWTAuth\Exceptions\JWTException;


class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt:auth', ['except' => [
            'login',
            'logout'
        ]]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if(! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'error' => 'Invalid Email or Password!'
            ], 400);
        }

        $user = JWTAuth::user();

        $userLog = new UserLog;
        $userLog->user_id = $user->id;
        $userLog->user_type = $user->roles()->first()->title;
        $userLog->ip_address = $request->ip();
        $userLog->activity = 'login';
        $userLog->created_at = Carbon::now();
        $userLog->save();

        $formatted = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'accountType' => explode('\\', $user->userable_type)[2],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $formatted,
                'token' => $token,
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = JWTAuth::user();

        if (!$user) {
            return response()->json([
                'success' => true
            ], 200);
        }

        $userLog = new UserLog;
        $userLog->user_id = $user->id;
        $userLog->user_type = $user->roles()->first()->title;
        $userLog->ip_address = $request->ip();
        $userLog->activity = 'logout';
        $userLog->created_at = Carbon::now();
        $userLog->save();

        $token = JWTAuth::fromUser($user);

        JWTAuth::invalidate($token);

        return response()->json([
            'success' => true
        ], 200);
    }
}
