<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    use ApiResponse;

    protected function guard()
    {
        return auth('api');
    }

    #[OA\Post(
        path: "/api/v1/register",
        summary: "Registrasi entitas pengguna baru (Role: User)",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Operator Tambahan"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "operator@wowoclean.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Registrasi berhasil"),
            new OA\Response(response: 422, description: "Validasi gagal")
        ]
    )]
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user'
        ]);

        return $this->successResponse($user, 'User registered successfully', 201);
    }

    #[OA\Post(
        path: "/api/v1/login",
        summary: "Otentikasi kredensial dan akuisisi Token JWT",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@wowoclean.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Otentikasi sukses"),
            new OA\Response(response: 401, description: "Kredensial tidak valid")
        ]
    )]
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

    #[OA\Get(
        path: "/api/v1/profile",
        summary: "Inspeksi data profil pengguna (Token Required)",
        security: [["bearerAuth" => []]],
        tags: ["Authentication"],
        responses: [
            new OA\Response(response: 200, description: "Data profil berhasil dimuat"),
            new OA\Response(response: 401, description: "Akses ditolak (Unauthorized)")
        ]
    )]
    public function profile()
    {
        return $this->successResponse($this->guard()->user(), 'Profile retrieved successfully');
    }

    #[OA\Post(
        path: "/api/v1/logout",
        summary: "Terminasi sesi (Invalidasi Token JWT)",
        security: [["bearerAuth" => []]],
        tags: ["Authentication"],
        responses: [
            new OA\Response(response: 200, description: "Sesi berhasil diakhiri"),
            new OA\Response(response: 401, description: "Akses ditolak (Unauthorized)")
        ]
    )]
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