<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Generate Sanctum token for authenticated admin employee
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function token(Request $request)
    {
        try {
            // Get authenticated admin from session
            $admin = auth('admin')->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated. Please login first.',
                ], 401);
            }

            // Allow super admins (role_id = 1) or approved employees (status = 1)
            if ($admin->role_id != 1 && $admin->status != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not approved.',
                ], 403);
            }

            // Create Sanctum token
            $token = $admin->createToken('employee-chat')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'admin' => [
                    'id' => $admin->id,
                    'name' => trim($admin->f_name . ' ' . $admin->l_name),
                    'email' => $admin->email,
                    'phone' => $admin->phone,
                    'image' => $admin->image_full_url ?? null,
                    'role' => $admin->role?->name ?? 'Employee',
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee chat - token generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify token validity
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        try {
            $admin = auth('sanctum')->user();

            // Allow super admins (role_id = 1) or approved employees (status = 1)
            if (!$admin || ($admin->role_id != 1 && $admin->status != 1)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'admin' => [
                    'id' => $admin->id,
                    'name' => trim($admin->f_name . ' ' . $admin->l_name),
                    'email' => $admin->email,
                    'role' => $admin->role?->name ?? 'Employee',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token verification failed',
            ], 500);
        }
    }
}
