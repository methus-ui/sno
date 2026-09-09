<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\CentralLogics\SMS_module;
use App\Mail\EmailVerification;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\PhoneVerification;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\EmailVerifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\Gateways\Traits\SmsGateway;

class AccountDeletionController extends Controller
{
    /**
     * Show account deletion page for customers
     */
    public function customerDeletionPage()
    {
        return view('account-deletion.customer');
    }

    /**
     * Show account deletion page for stores/vendors
     */
    public function storeDeletionPage()
    {
        return view('account-deletion.store');
    }

    /**
     * Show account deletion page for delivery men
     */
    public function deliveryManDeletionPage()
    {
        return view('account-deletion.delivery-man');
    }

    /**
     * Request OTP for customer account deletion
     */
    public function customerRequestOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required', // email or phone
            'type' => 'required|in:email,phone'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = null;
        if ($request->type === 'email') {
            $user = User::where('email', $request->identifier)->first();
        } else {
            $user = User::where('phone', $request->identifier)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        if (env('APP_MODE') == 'test') {
            $otp = '123456';
        }

        // Store OTP
        if ($request->type === 'email') {
            DB::table('email_verifications')->updateOrInsert(
                ['email' => $request->identifier],
                [
                    'token' => $otp,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Send email
            try {
                Mail::to($request->identifier)->send(new EmailVerification($otp, $user->f_name . ' ' . $user->l_name, 'account_deletion'));
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to your email'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP email'
                ], 500);
            }
        } else {
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $request->identifier],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Send SMS
            try {
                $payment_published_status = config('get_payment_publish_status');
                $published_status = 0;
                if (isset($payment_published_status[0]['is_published'])) {
                    $published_status = $payment_published_status[0]['is_published'];
                }

                if ($published_status == 1) {
                    $response = SmsGateway::send($request->identifier, $otp);
                } else {
                    $response = SMS_module::send($request->identifier, $otp);
                }
            } catch (\Exception $e) {
                $response = SMS_module::send($request->identifier, $otp);
            }

            if ($response === 'success' || env('APP_MODE') == 'test') {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to your phone'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP'
                ], 500);
            }
        }
    }

    /**
     * Verify OTP and delete customer account
     */
    public function customerDeleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'type' => 'required|in:email,phone',
            'otp' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify OTP
        if ($request->type === 'email') {
            $verification = EmailVerifications::where([
                'email' => $request->identifier,
                'token' => $request->otp
            ])->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP'
                ], 403);
            }

            $user = User::where('email', $request->identifier)->first();
            $verification->delete();
        } else {
            $verification = PhoneVerification::where([
                'phone' => $request->identifier,
                'token' => $request->otp
            ])->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP'
                ], 403);
            }

            $user = User::where('phone', $request->identifier)->first();
            $verification->delete();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check for ongoing orders
        if (Order::where('user_id', $user->id)
            ->where('is_guest', 0)
            ->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])
            ->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your ongoing and accepted orders before deleting your account'
            ], 403);
        }

        // Delete user
        try {
            DB::beginTransaction();

            // Revoke all tokens
            DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();

            // Delete user info
            if ($user->userinfo) {
                $user->userinfo->delete();
            }

            // Delete user
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Your account has been successfully deleted'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account. Please try again later.'
            ], 500);
        }
    }

    /**
     * Request OTP for store/vendor account deletion
     */
    public function storeRequestOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'type' => 'required|in:email,phone'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vendor = null;
        if ($request->type === 'email') {
            $vendor = Vendor::where('email', $request->identifier)->first();
        } else {
            $vendor = Vendor::where('phone', $request->identifier)->first();
        }

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Store account not found'
            ], 404);
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        if (env('APP_MODE') == 'test') {
            $otp = '123456';
        }

        // Store OTP
        if ($request->type === 'email') {
            DB::table('email_verifications')->updateOrInsert(
                ['email' => $request->identifier],
                [
                    'token' => $otp,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            try {
                Mail::to($request->identifier)->send(new EmailVerification($otp, $vendor->f_name . ' ' . $vendor->l_name, 'account_deletion'));
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to your email'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP email'
                ], 500);
            }
        } else {
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $request->identifier],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            try {
                $payment_published_status = config('get_payment_publish_status');
                $published_status = 0;
                if (isset($payment_published_status[0]['is_published'])) {
                    $published_status = $payment_published_status[0]['is_published'];
                }

                if ($published_status == 1) {
                    $response = SmsGateway::send($request->identifier, $otp);
                } else {
                    $response = SMS_module::send($request->identifier, $otp);
                }
            } catch (\Exception $e) {
                $response = SMS_module::send($request->identifier, $otp);
            }

            if ($response === 'success' || env('APP_MODE') == 'test') {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to your phone'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP'
                ], 500);
            }
        }
    }

    /**
     * Verify OTP and delete store/vendor account
     */
    public function storeDeleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'type' => 'required|in:email,phone',
            'otp' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify OTP
        if ($request->type === 'email') {
            $verification = EmailVerifications::where([
                'email' => $request->identifier,
                'token' => $request->otp
            ])->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP'
                ], 403);
            }

            $vendor = Vendor::where('email', $request->identifier)->with('stores')->first();
            $verification->delete();
        } else {
            $verification = PhoneVerification::where([
                'phone' => $request->identifier,
                'token' => $request->otp
            ])->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP'
                ], 403);
            }

            $vendor = Vendor::where('phone', $request->identifier)->with('stores')->first();
            $verification->delete();
        }

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        // Check for ongoing orders
        if ($vendor->stores()->count() > 0) {
            $storeId = $vendor->stores[0]->id;
            if (Order::where('store_id', $storeId)
                ->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])
                ->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete your ongoing and accepted orders before deleting your account'
                ], 403);
            }

            // Check for cash in hand
            if ($vendor->wallet && $vendor->wallet->collected_cash > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have cash in hand. You have to pay the due to delete your account.'
                ], 403);
            }
        }

        // Delete vendor and store
        try {
            DB::beginTransaction();

            // Delete vendor images
            Helpers::check_and_delete('vendor/', $vendor->image);

            // Delete store and associated data
            if ($vendor->stores()->count() > 0) {
                foreach ($vendor->stores as $store) {
                    Helpers::check_and_delete('store/', $store->logo);
                    Helpers::check_and_delete('store/cover/', $store->cover_photo);

                    // Delete store delivery men
                    foreach ($store->deliverymen as $dm) {
                        Helpers::check_and_delete('delivery-man/', $dm->image);
                        if ($dm->identity_image) {
                            foreach (json_decode($dm->identity_image, true) as $img) {
                                Helpers::check_and_delete('delivery-man/', $img);
                            }
                        }
                    }
                    $store->deliverymen()->delete();
                }
                $vendor->stores()->delete();
            }

            // Delete user info
            if ($vendor->userinfo) {
                $vendor->userinfo->delete();
            }

            // Delete vendor
            $vendor->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Your store account has been successfully deleted'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account. Please try again later.'
            ], 500);
        }
    }

    /**
     * Request OTP for delivery man account deletion
     */
    public function deliveryManRequestOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'type' => 'required|in:email,phone'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryMan = null;
        if ($request->type === 'email') {
            $deliveryMan = DeliveryMan::where('email', $request->identifier)->first();
        } else {
            $deliveryMan = DeliveryMan::where('phone', $request->identifier)->first();
        }

        if (!$deliveryMan) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery man account not found'
            ], 404);
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        if (env('APP_MODE') == 'test') {
            $otp = '123456';
        }

        // Store OTP
        if ($request->type === 'email') {
            DB::table('email_verifications')->updateOrInsert(
                ['email' => $request->identifier],
                [
                    'token' => $otp,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            try {
                Mail::to($request->identifier)->send(new EmailVerification($otp, $deliveryMan->f_name . ' ' . $deliveryMan->l_name, 'account_deletion'));
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to your email'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP email'
                ], 500);
            }
        } else {
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $request->identifier],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            try {
                $payment_published_status = config('get_payment_publish_status');
                $published_status = 0;
                if (isset($payment_published_status[0]['is_published'])) {
                    $published_status = $payment_published_status[0]['is_published'];
                }

                if ($published_status == 1) {
                    $response = SmsGateway::send($request->identifier, $otp);
                } else {
                    $response = SMS_module::send($request->identifier, $otp);
                }
            } catch (\Exception $e) {
                $response = SMS_module::send($request->identifier, $otp);
            }

            if ($response === 'success' || env('APP_MODE') == 'test') {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to your phone'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP'
                ], 500);
            }
        }
    }

    /**
     * Verify OTP and delete delivery man account
     */
    public function deliveryManDeleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'type' => 'required|in:email,phone',
            'otp' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify OTP
        if ($request->type === 'email') {
            $verification = EmailVerifications::where([
                'email' => $request->identifier,
                'token' => $request->otp
            ])->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP'
                ], 403);
            }

            $deliveryMan = DeliveryMan::where('email', $request->identifier)->first();
            $verification->delete();
        } else {
            $verification = PhoneVerification::where([
                'phone' => $request->identifier,
                'token' => $request->otp
            ])->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP'
                ], 403);
            }

            $deliveryMan = DeliveryMan::where('phone', $request->identifier)->first();
            $verification->delete();
        }

        if (!$deliveryMan) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery man not found'
            ], 404);
        }

        // Check for ongoing orders
        if (Order::where('delivery_man_id', $deliveryMan->id)
            ->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])
            ->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your ongoing and accepted orders before deleting your account'
            ], 403);
        }

        // Check for cash in hand
        if ($deliveryMan->wallet && $deliveryMan->wallet->collected_cash > 0) {
            return response()->json([
                'success' => false,
                'message' => 'You have cash in hand. You have to pay the due to delete your account.'
            ], 403);
        }

        // Delete delivery man
        try {
            DB::beginTransaction();

            // Delete images
            Helpers::check_and_delete('delivery-man/', $deliveryMan->image);

            if ($deliveryMan->identity_image) {
                foreach (json_decode($deliveryMan->identity_image, true) as $img) {
                    Helpers::check_and_delete('delivery-man/', $img);
                }
            }

            // Delete user info
            if ($deliveryMan->userinfo) {
                $deliveryMan->userinfo->delete();
            }

            // Delete delivery man
            $deliveryMan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Your delivery man account has been successfully deleted'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account. Please try again later.'
            ], 500);
        }
    }
}
