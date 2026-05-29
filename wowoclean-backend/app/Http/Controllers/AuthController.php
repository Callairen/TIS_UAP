<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;
    protected function guard()
    {
        return auth('api');
    }

    /**
     * @OA\Post(
     * path="/api/v1/login",
     * tags={"Authentication"},
     * summary="Login user dan mendapatkan Token JWT",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"email","password"},
     * @OA\Property(property="email", type="string", format="email", example="admin@wowoclean.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123")
     * )
     * ),
     * @OA\Response(response=200, description="Login successful"),
     * @OA\Response(response=401, description="Invalid credentials"),
     * @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $credentials = $request->only('email', 'password');

        if (!$token = $this->guard()->attempt($credentials)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        return $this->respondWithToken($token, 'Login successful');
    }

    public function profile()
    {
        return $this->successResponse($this->guard()->user(), 'Profile retrieved successfully');
    }

    public function logout()
    {
        $this->guard()->logout();
        
        return $this->successResponse(null, 'Successfully logged out');
    }

    protected function respondWithToken($token, $message)
    {
        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'user' => $this->guard()->user()
        ], $message);
    }
}