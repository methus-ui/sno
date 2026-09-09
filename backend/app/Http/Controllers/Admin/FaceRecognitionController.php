<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaceRecognitionController extends Controller
{
    /**
     * Register face data for an employee
     */
    public function registerFace(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:admins,id',
            'face_data' => 'required|string',
        ]);

        try {
            $admin = Admin::findOrFail($request->employee_id);

            $admin->update([
                'face_data' => $request->face_data,
                'face_registered' => true,
                'face_registered_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => translate('messages.face_registered_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Face registration failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => translate('messages.face_registration_failed'),
            ], 500);
        }
    }

    /**
     * Verify face for attendance
     */
    public function verifyFace(Request $request)
    {
        $request->validate([
            'face_data' => 'required|string',
        ]);

        try {
            $user = auth('admin')->user();

            if (!$user->face_registered || !$user->face_data) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.face_not_registered'),
                    'face_not_registered' => true,
                ], 400);
            }

            // The face comparison will be done on the client side using face-api.js
            // Here we just return the stored face descriptor for comparison
            return response()->json([
                'success' => true,
                'stored_face_data' => $user->face_data,
            ]);
        } catch (\Exception $e) {
            Log::error('Face verification failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => translate('messages.face_verification_failed'),
            ], 500);
        }
    }

    /**
     * Get face data for the current user
     */
    public function getFaceData()
    {
        try {
            $user = auth('admin')->user();

            return response()->json([
                'success' => true,
                'face_registered' => $user->face_registered,
                'face_data' => $user->face_data,
                'face_registered_at' => $user->face_registered_at,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update face data for current user (re-register)
     */
    public function updateFace(Request $request)
    {
        $request->validate([
            'face_data' => 'required|string',
        ]);

        try {
            $user = auth('admin')->user();

            $user->update([
                'face_data' => $request->face_data,
                'face_registered' => true,
                'face_registered_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => translate('messages.face_updated_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Face update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => translate('messages.face_update_failed'),
            ], 500);
        }
    }

    /**
     * Remove face data
     */
    public function removeFace(Request $request)
    {
        $employeeId = $request->employee_id ?? auth('admin')->user()->id;

        try {
            $admin = Admin::findOrFail($employeeId);

            // Only allow master admin to remove others' face data
            if ($employeeId != auth('admin')->user()->id && auth('admin')->user()->role_id != 1) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.unauthorized'),
                ], 403);
            }

            $admin->update([
                'face_data' => null,
                'face_registered' => false,
                'face_registered_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => translate('messages.face_removed_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Face removal failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => translate('messages.face_removal_failed'),
            ], 500);
        }
    }

    /**
     * Check if face attendance is required
     */
    public function checkFaceRequired()
    {
        $user = auth('admin')->user();

        // Master admin (role_id = 1) doesn't need face attendance
        if ($user->role_id == 1) {
            return response()->json([
                'face_required' => false,
                'face_registered' => true,
            ]);
        }

        return response()->json([
            'face_required' => true,
            'face_registered' => $user->face_registered,
        ]);
    }
}
