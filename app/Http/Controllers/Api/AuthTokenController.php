<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], (string) $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 422);
        }

        $deviceName = trim((string) ($validated['device_name'] ?? 'api-client'));
        $token = $user->createToken($deviceName !== '' ? $deviceName : 'api-client')->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'hrId' => $user->hrId,
                'email' => $user->email,
                'fullname' => $user->fullname,
                'role' => $user->role,
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()?->tokens()?->delete();

        return response()->json([
            'message' => 'Token revoked.',
        ]);
    }
}
