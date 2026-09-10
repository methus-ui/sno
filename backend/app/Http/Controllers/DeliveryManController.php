<?php

namespace App\Http\Controllers;

use App\Models\DeliveryMan;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\CentralLogics\SMS_module;
use App\Models\Admin;
use App\Models\BusinessSetting;
// Captcha generation removed
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Password;

class DeliveryManController extends Controller
{

    public function create()
    {
        $status = BusinessSetting::where('key', 'toggle_dm_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            Toastr::error(translate('messages.not_found'));
            return back();
        }

        // Captcha removed - keep variable for view compatibility
        $custome_recaptcha = null;

        return view('dm-registration', compact('custome_recaptcha'));
    }

    public function store(Request $request)
    {
        $isAjax = $request->expectsJson();

        $status = BusinessSetting::where('key', 'toggle_dm_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => translate('messages.not_found')], 403);
            }
            Toastr::error(translate('messages.not_found'));
            return back();
        }

        // Captcha validation removed — allow DM registration without captcha checks

        // Verify phone was OTP-verified
        if (!Session::has('dm_verified_phone')) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => translate('messages.please_verify_your_phone_number')], 422);
            }
            Toastr::error(translate('messages.please_verify_your_phone_number'));
            return back();
        }

        // Use the verified phone from session
        $request->merge(['phone' => Session::get('dm_verified_phone')]);

        $validator = Validator::make($request->all(), [
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'identity_number' => 'required|max:30',
            'email' => 'required|unique:delivery_men',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:delivery_men',
            'zone_id' => 'required',
            'vehicle_id' => 'required',
            'earning' => 'required',
            'password' => ['required', Password::min(6)->mixedCase()->letters()->numbers()->symbols()],
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'zone_id.required' => translate('messages.select_a_zone'),
            'vehicle_id.required' => translate('messages.select_a_vehicle'),
            'earning.required' => translate('messages.select_dm_type')
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        if ($request->has('image')) {
            $image_name = Helpers::upload('delivery-man/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $id_img_names = [];
        if (!empty($request->file('identity_image'))) {
            foreach ($request->identity_image as $img) {
                $identity_image = Helpers::upload('delivery-man/', 'png', $img);
                array_push($id_img_names, ['img'=>$identity_image, 'storage'=> Helpers::getDisk()]);
            }
            $identity_image = json_encode($id_img_names);
        } else {
            $identity_image = json_encode([]);
        }

        $dm = New DeliveryMan();
        $dm->f_name = $request->f_name;
        $dm->l_name = $request->l_name;
        $dm->email = $request->email;
        $dm->phone = $request->phone;
        $dm->identity_number = $request->identity_number;
        $dm->identity_type = $request->identity_type;
        $dm->vehicle_id = $request->vehicle_id;
        $dm->zone_id = $request->zone_id;
        $dm->identity_image = $identity_image;
        $dm->image = $image_name;
        $dm->active = 0;
        $dm->earning = $request->earning;
        $dm->password = bcrypt($request->password);
        $dm->application_status= 'pending';
        $dm->save();

        try{
            $admin= Admin::where('role_id', 1)->first();

            if(config('mail.status') &&  Helpers::get_mail_status('registration_mail_status_dm') == '1' && Helpers::getNotificationStatusData('deliveryman','deliveryman_registration','mail_status')  ){
                Mail::to($request->email)->send(new \App\Mail\DmSelfRegistration('pending', $dm->f_name.' '.$dm->l_name));
            }
            if(config('mail.status') && Helpers::get_mail_status('dm_registration_mail_status_admin') == '1' && Helpers::getNotificationStatusData('admin','deliveryman_self_registration','mail_status')) {
                Mail::to($admin['email'])->send(new \App\Mail\DmRegistration('pending', $dm->f_name.' '.$dm->l_name));
            }
        }catch(\Exception $ex){
            info($ex->getMessage());
        }
        // Clear verified phone session
        Session::forget('dm_verified_phone');
        Session::forget('dm_phone_verified_at');

        if ($isAjax) {
            return response()->json(['success' => true, 'message' => translate('messages.application_placed_successfully')]);
        }
        Toastr::success(translate('messages.application_placed_successfully'));
        return back();
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        ], [
            'phone.required' => 'Phone number is required',
            'phone.regex' => 'Please provide a valid phone number',
            'phone.min' => 'Phone number must be at least 10 digits',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $phone = preg_replace('/[\s\-\(\)]/', '', $request->phone);

        // Check if phone already exists in delivery_men table
        $existing = DeliveryMan::where('phone', $phone)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered'
            ], 422);
        }

        // Rate limiting (60 seconds between requests)
        $existingOtp = DB::table('phone_verifications')->where('phone', $phone)->first();
        if ($existingOtp && $existingOtp->created_at) {
            $timeDiff = Carbon::parse($existingOtp->created_at)->diffInSeconds(now());
            if ($timeDiff < 60) {
                $waitTime = 60 - $timeDiff;
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait ' . $waitTime . ' seconds before requesting a new OTP',
                    'wait_time' => $waitTime
                ], 429);
            }
        }

        $otp = rand(100000, 999999);

        // Test phone numbers
        $testPhones = ['7006059519', '+917006059519', '917006059519'];
        $isTestPhone = in_array(preg_replace('/^\+/', '', $phone), $testPhones) ||
                       in_array($phone, $testPhones);

        if ($isTestPhone) {
            $otp = 123456;
        }

        try {
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $phone],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'is_temp_blocked' => 0,
                    'temp_block_time' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            if ($isTestPhone) {
                info('DM Test OTP for phone ' . $phone . ': ' . $otp);
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully (Test: ' . $otp . ')',
                    'phone' => $phone
                ]);
            }

            $smsResponse = SMS_module::send($phone, $otp);
            info('DM SMS OTP Response for phone ' . $phone . ': ' . $smsResponse);

            if ($smsResponse === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'phone' => $phone
                ]);
            }

            $errorMessage = 'Failed to send OTP. Please try again.';
            if ($smsResponse === 'not_found') {
                $errorMessage = 'SMS gateway not configured. Please contact support.';
            } elseif ($smsResponse === 'error') {
                $errorMessage = 'SMS service error. Please try again later.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'debug' => config('app.debug') ? $smsResponse : null
            ], 500);

        } catch (\Exception $e) {
            info('DM OTP Send Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'otp' => 'required|digits:6',
        ], [
            'phone.required' => 'Phone number is required',
            'otp.required' => 'OTP is required',
            'otp.digits' => 'OTP must be 6 digits',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $phone = preg_replace('/[\s\-\(\)]/', '', $request->phone);
        $otp = $request->otp;

        $maxOtpHit = 5;
        $tempBlockTime = 600;

        $verificationData = DB::table('phone_verifications')->where('phone', $phone)->first();

        if (!$verificationData) {
            return response()->json([
                'success' => false,
                'message' => 'Please request an OTP first'
            ], 404);
        }

        // Check if temporarily blocked
        if ($verificationData->is_temp_blocked == 1) {
            if ($verificationData->temp_block_time && Carbon::parse($verificationData->temp_block_time)->diffInSeconds(now()) < $tempBlockTime) {
                $remainingTime = $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->diffInSeconds(now());
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed attempts. Please try again after ' . ceil($remainingTime / 60) . ' minutes',
                    'blocked' => true,
                    'remaining_time' => $remainingTime
                ], 429);
            }

            DB::table('phone_verifications')->where('phone', $phone)->update([
                'otp_hit_count' => 0,
                'is_temp_blocked' => 0,
                'temp_block_time' => null,
                'updated_at' => now(),
            ]);
        }

        if ($verificationData->otp_hit_count >= $maxOtpHit) {
            DB::table('phone_verifications')->where('phone', $phone)->update([
                'is_temp_blocked' => 1,
                'temp_block_time' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please try again later.',
                'blocked' => true
            ], 429);
        }

        if ($verificationData->token == $otp) {
            DB::table('phone_verifications')->where('phone', $phone)->delete();

            Session::put('dm_verified_phone', $phone);
            Session::put('dm_phone_verified_at', now());

            return response()->json([
                'success' => true,
                'message' => 'Phone number verified successfully',
                'phone' => $phone
            ]);
        }

        DB::table('phone_verifications')->where('phone', $phone)->update([
            'otp_hit_count' => $verificationData->otp_hit_count + 1,
            'updated_at' => now(),
        ]);

        $remainingAttempts = $maxOtpHit - ($verificationData->otp_hit_count + 1);

        return response()->json([
            'success' => false,
            'message' => 'Invalid OTP. ' . $remainingAttempts . ' attempts remaining.',
            'remaining_attempts' => $remainingAttempts
        ], 400);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $phone = preg_replace('/[\s\-\(\)]/', '', $request->phone);

        $existingOtp = DB::table('phone_verifications')->where('phone', $phone)->first();
        if ($existingOtp && $existingOtp->updated_at) {
            $timeDiff = Carbon::parse($existingOtp->updated_at)->diffInSeconds(now());
            if ($timeDiff < 60) {
                $waitTime = 60 - $timeDiff;
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait ' . $waitTime . ' seconds before requesting a new OTP',
                    'wait_time' => $waitTime
                ], 429);
            }
        }

        $testPhones = ['7006059519', '+917006059519', '917006059519'];
        $cleanPhoneForTest = preg_replace('/^\+/', '', $phone);
        $isTestPhone = in_array($cleanPhoneForTest, $testPhones) || in_array($phone, $testPhones);

        $otp = $isTestPhone ? 123456 : rand(100000, 999999);

        DB::table('phone_verifications')->updateOrInsert(
            ['phone' => $phone],
            [
                'token' => $otp,
                'otp_hit_count' => 0,
                'is_temp_blocked' => 0,
                'temp_block_time' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if ($isTestPhone) {
            return response()->json([
                'success' => true,
                'message' => 'OTP resent successfully (Test mode: use 123456)',
                'phone' => $phone
            ]);
        }

        try {
            $smsResponse = SMS_module::send($phone, $otp);

            if ($smsResponse === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP resent successfully',
                    'phone' => $phone
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage()
            ], 500);
        }
    }
}
