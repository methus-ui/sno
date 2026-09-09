<?php

namespace App\CentralLogics;

use App\Models\AccountTransaction;
use App\Models\Admin;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\AdminWallet;
use App\Models\BusinessSetting;
use App\Models\Store;
use App\Models\StoreWallet;
use App\Models\DeliveryManWallet;
use App\Models\DeliveryTrackingStat;
use App\CentralLogics\CustomerLogic;
use App\Models\OrderPayment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderLogic
{
    public static function gen_unique_id()
    {
        return rand(1000, 9999) . '-' . Str::random(5) . '-' . time();
    }

    public static function track_order($order_id)
    {
        return Helpers::order_data_formatting(Order::with(['details', 'delivery_man.rating'])->where(['id' => $order_id])->first(), false);
    }

    public static function updated_order_calculation($order)
    {
        return true;
    }
    public static function create_transaction($order, $received_by=false, $status = null)
    {
        $type = $order->order_type;
        $dm_tips_manage_status = BusinessSetting::where('key', 'dm_tips_status')->first()->value;
        $admin_subsidy = 0;
        $amount_admin = 0;
        $store_d_amount = 0;
        $admin_coupon_discount_subsidy =0;
        $store_subsidy =0;
        $store_coupon_discount_subsidy =0;
        $store_discount_amount=0;
        $flash_admin_discount_amount=0;
        $flash_store_discount_amount=0;
        $comission_on_store_amount=0;
        $ref_bonus_amount=0;
        $subscription_mode = 0;
        $commission_percentage = 0;

        $store= $order?->store;
        $store_sub = $order?->store?->store_sub;
        // free delivery by admin
        if($order->free_delivery_by == 'admin')
        {
            $admin_subsidy = $order->original_delivery_charge;
            Helpers::expenseCreate(amount:$order->original_delivery_charge,type:'free_delivery',datetime:now(),created_by:$order->free_delivery_by,order_id:$order->id);
        }
        // free delivery by store
        if($order->free_delivery_by == 'vendor')
        {
            $store_subsidy = $order->original_delivery_charge;
            Helpers::expenseCreate(amount:$order->original_delivery_charge,type:'free_delivery',datetime:now(),created_by:$order->free_delivery_by,order_id:$order->id,store_id:$order->store->id);
        }
        // coupon discount by Admin
        if($order->coupon_created_by == 'admin')
        {
            $admin_coupon_discount_subsidy = $order->coupon_discount_amount;
            Helpers::expenseCreate(amount:$admin_coupon_discount_subsidy,type:'coupon_discount',datetime:now(),created_by:$order->coupon_created_by,order_id:$order->id);
        }
        // 1st order discount by Admin
        if($order->ref_bonus_amount > 0)
        {
            $ref_bonus_amount = $order->ref_bonus_amount;
            Helpers::expenseCreate(amount:$ref_bonus_amount,type:'referral_discount',datetime:now(),created_by:'admin',order_id:$order->id);
        }
        // coupon discount by store
        if($order->coupon_created_by == 'vendor')
        {
            $store_coupon_discount_subsidy = $order->coupon_discount_amount;
            Helpers::expenseCreate(amount:$store_coupon_discount_subsidy,type:'coupon_discount',datetime:now(),created_by:$order->coupon_created_by, order_id:$order->id,store_id:$order->store->id);
        }

        if($order?->cashback_history){
            self::cashbackToWallet($order);
        }

        if($type=='parcel')
        {
            $comission = \App\Models\BusinessSetting::where('key','parcel_commission_dm')->first();
            $dm_tips = $dm_tips_manage_status ? $order->dm_tips : 0;
            $comission = isset($comission) ? $comission->value : 0;
            $order_amount = $order->order_amount - $dm_tips - $order->additional_charge - $order->extra_packaging_amount;
            $dm_commission = $comission?($order_amount/ 100) * $comission:0;
            $comission_amount = $order_amount - $dm_commission;
        }
        else
        {
            $comission = isset($order->store->comission) == null?\App\Models\BusinessSetting::where('key','admin_commission')->first()->value:$order->store->comission;
            $dm_tips = $dm_tips_manage_status ? $order->dm_tips : 0;
            // $order_amount = $order->order_amount - $order->delivery_charge - $order->total_tax_amount - $dm_tips;

            if($order->store_discount_amount > 0  && $order->discount_on_product_by == 'vendor')
            {
                if($store->store_business_model == 'subscription' && isset($store_sub)){
                    $store_d_amount=  $order->store_discount_amount;
                    Helpers::expenseCreate(amount:$store_d_amount,type:'discount_on_product',datetime:now(),created_by:'vendor',order_id:$order->id,store_id:$order->store->id);
                }
                else{
                    $amount_admin = $comission?($order->store_discount_amount/ 100) * $comission:0;
                    $store_d_amount=  $order->store_discount_amount- $amount_admin;
                    Helpers::expenseCreate(amount:$store_d_amount,type:'discount_on_product',datetime:now(),created_by:'vendor',order_id:$order->id,store_id:$order->store->id);
                    Helpers::expenseCreate(amount:$amount_admin,type:'discount_on_product',datetime:now(),created_by:'admin',order_id:$order->id);
                }

            }

            if($order->store_discount_amount > 0  && $order->discount_on_product_by == 'admin')
            {
                $store_discount_amount=$order->store_discount_amount;
                Helpers::expenseCreate(amount:$store_discount_amount,type:'discount_on_product',datetime:now(),created_by:'admin',order_id:$order->id);
            }

            if($order->flash_admin_discount_amount > 0)
            {
                $flash_admin_discount_amount=$order->flash_admin_discount_amount;
                Helpers::expenseCreate(amount:$flash_admin_discount_amount,type:'flash_sale_discount',datetime:now(),created_by:'admin',order_id:$order->id);
            }

            if($order->flash_store_discount_amount > 0)
            {
                $flash_store_discount_amount=$order->flash_store_discount_amount;
                Helpers::expenseCreate(amount:$flash_store_discount_amount,type:'flash_sale_discount',datetime:now(),created_by:'vendor',order_id:$order->id,store_id:$order->store->id);
            }


            $order_amount = $order->order_amount - $order->additional_charge - $order->extra_packaging_amount - $order->delivery_charge - $order->total_tax_amount - $dm_tips + $flash_admin_discount_amount + $order->coupon_discount_amount + $store_discount_amount + $flash_store_discount_amount + $ref_bonus_amount;
            // comission in delivery charge
            $delivery_charge_comission = BusinessSetting::where('key', 'delivery_charge_comission')->first();
            $delivery_charge_comission_percentage = $delivery_charge_comission ? $delivery_charge_comission->value : 0;
            $comission_on_delivery = $delivery_charge_comission_percentage * ( $order->original_delivery_charge / 100 );

            if($order->store->sub_self_delivery)
            {
                $comission_on_actual_delivery_fee = 0;
            }else{

                $comission_on_actual_delivery_fee = ($order->delivery_charge > 0) ? $comission_on_delivery : 0;
            }

            if($order->free_delivery_by == 'admin')
            {
                if($order->store->sub_self_delivery)
                {
                    $comission_on_actual_delivery_fee = 0;
                }else{
                    $comission_on_actual_delivery_fee = ($order->original_delivery_charge > 0) ? $comission_on_delivery : 0;
                }
            }

            //final comission
            if($store->store_business_model == 'subscription' && isset($store_sub)){
                $comission_on_store_amount =0;
                $subscription_mode= 1;
                $commission_percentage= 0;
            } else{
                $comission_on_store_amount = ($comission?($order_amount/ 100) * $comission:0);
                $subscription_mode= 0;
                $commission_percentage= $comission;
            }

            $comission_amount = $comission_on_store_amount + $comission_on_actual_delivery_fee;
            $dm_commission = $order->original_delivery_charge - $comission_on_actual_delivery_fee;
        }
        $store_amount = $order_amount + $order->total_tax_amount + $order->extra_packaging_amount - $comission_on_store_amount - $store_coupon_discount_subsidy - $flash_store_discount_amount;

        // Outside Purchase: exclude outside purchase items from vendor earnings
        $outside_purchase_customer_total = 0;
        $outside_purchase_cost_total = $order->outside_purchase_amount ?? 0;
        $outside_purchase_store_credits = []; // store_id => amount to credit
        $outside_purchase_random_cost = 0; // cost from random (non-listed) stores only
        if($type != 'parcel' && $outside_purchase_cost_total > 0) {
            foreach($order->details as $detail) {
                if($detail->is_outside_purchase) {
                    $outside_purchase_customer_total += $detail->price * $detail->quantity;
                    $detail_cost = $detail->outside_purchase_cost * $detail->quantity;
                    // Track credits for listed stores, otherwise count as random
                    if($detail->outside_purchase_store_id) {
                        if(!isset($outside_purchase_store_credits[$detail->outside_purchase_store_id])) {
                            $outside_purchase_store_credits[$detail->outside_purchase_store_id] = 0;
                        }
                        $outside_purchase_store_credits[$detail->outside_purchase_store_id] += $detail_cost;
                    } else {
                        $outside_purchase_random_cost += $detail_cost;
                    }
                }
            }
            // Remove vendor's portion of outside purchase items from store_amount
            $op_commission = $comission ? ($outside_purchase_customer_total / 100) * $comission : 0;
            $op_vendor_portion = $outside_purchase_customer_total - $op_commission;
            $store_amount = $store_amount - $op_vendor_portion;
        }

        try{
            OrderTransaction::insert([
                'vendor_id' =>$type=='parcel'?null:$order->store->vendor->id,
                'delivery_man_id'=>$order->delivery_man_id,
                'order_id' =>$order->id,
                'order_amount'=>$order->order_amount,
                'store_amount'=>$type=='parcel' ? 0 : $store_amount,
                // 'store_amount'=>$type=='parcel' ? 0 : $order_amount + $order->total_tax_amount - $comission_on_store_amount,
                'admin_commission'=>$comission_amount + $order->additional_charge - $admin_subsidy - $admin_coupon_discount_subsidy - $ref_bonus_amount,
                'delivery_charge'=>$order->delivery_charge,
                'original_delivery_charge'=>$dm_commission,
                'tax'=>$order->total_tax_amount,
                'received_by'=> $received_by?$received_by:'admin',
                'zone_id'=>$order->zone_id,
                'module_id'=>$order->module_id,
                'admin_expense'=>$admin_subsidy + $admin_coupon_discount_subsidy + $store_discount_amount + $flash_admin_discount_amount + $amount_admin + $ref_bonus_amount,
                'store_expense'=>$store_subsidy + $store_coupon_discount_subsidy + $flash_store_discount_amount,
                'status'=> $status,
                'dm_tips'=> $dm_tips,
                'created_at' => now(),
                'updated_at' => now(),
                'delivery_fee_comission'=>isset($comission_on_actual_delivery_fee)?$comission_on_actual_delivery_fee: 0,
                'discount_amount_by_store' => $store_coupon_discount_subsidy + $store_d_amount + $store_subsidy,
                'additional_charge' => $order->additional_charge,
                'extra_packaging_amount' => $order->extra_packaging_amount,
                'ref_bonus_amount' => $order->ref_bonus_amount,
                 // for store business model
                'is_subscribed'=> $subscription_mode,
                'commission_percentage'=> $commission_percentage,
                'outside_purchase_amount'=> $outside_purchase_cost_total,
            ]);
            $adminWallet = AdminWallet::firstOrNew(
                ['admin_id' => Admin::where('role_id', 1)->first()->id]
            );

            $adminWallet->total_commission_earning = $adminWallet->total_commission_earning + $comission_amount + $order->additional_charge - $admin_subsidy- $admin_coupon_discount_subsidy -$store_discount_amount - $flash_admin_discount_amount - $ref_bonus_amount;

            if($type != 'parcel')
            {
                $vendorWallet = StoreWallet::firstOrNew(
                    ['vendor_id' => $order->store->vendor->id]
                );
                if($order->store->sub_self_delivery)
                {
                    $vendorWallet->total_earning = $vendorWallet->total_earning + $order->delivery_charge + $dm_tips;
                }
                else{
                    $adminWallet->delivery_charge = $adminWallet->delivery_charge+$order->delivery_charge;
                }
                // $vendorWallet->total_earning = $vendorWallet->total_earning+($order_amount + $order->total_tax_amount - $comission_on_store_amount);
                $vendorWallet->total_earning = $vendorWallet->total_earning+$store_amount;
            }
            if($order->delivery_man && ($type == 'parcel' || ($order->store && !$order->store->sub_self_delivery))){
                $dmWallet = DeliveryManWallet::firstOrNew(
                    ['delivery_man_id' => $order->delivery_man_id]
                );
                if($order->delivery_man->earning == 1){
                    $dmWallet->total_earning = $dmWallet->total_earning + $dm_commission+ $dm_tips;
                }else {
                    $adminWallet->total_commission_earning = $adminWallet->total_commission_earning + $dm_commission + $dm_tips;
                }
            } else {
                $adminWallet->total_commission_earning = $adminWallet->total_commission_earning + $dm_commission + $dm_tips;
            }

            try
            {
                DB::beginTransaction();
                $unpaid_payment = OrderPayment::where('payment_status','unpaid')->where('order_id',$order->id)->first()?->payment_method;
                $unpaid_pay_method = 'digital_payment';
                if($unpaid_payment){
                    $unpaid_pay_method = $unpaid_payment;
                }
                if($received_by=='admin')
                {
                    $adminWallet->digital_received = $adminWallet->digital_received+($order->order_amount-$order->partially_paid_amount);

                    // Outside Purchase: deduct only random (non-listed) store costs from DM cash
                    if($outside_purchase_random_cost > 0 && isset($dmWallet)) {
                        $dmWallet->collected_cash = $dmWallet->collected_cash - $outside_purchase_random_cost;
                    }
                }
                else if($received_by=='store' && $type != 'parcel' && ($order->payment_method == "cash_on_delivery" || $unpaid_pay_method == 'cash_on_delivery'))
                {
                    // Check if order was paid via QR code
                    $qrPayment = \App\Models\DeliveryQRPayment::where('order_id', $order->id)
                        ->where('status', 'paid')
                        ->first();

                    if ($qrPayment) {
                        // QR payment - money goes to admin digital account, NOT store cash
                        $adminWallet->digital_received = $adminWallet->digital_received + ($order->order_amount - $order->partially_paid_amount);

                        \Log::info('QR Payment (store delivery) - added to digital_received', [
                            'order_id' => $order->id,
                            'qr_payment_id' => $qrPayment->id
                        ]);
                    } else {
                        // Regular cash payment - add to store's collected cash
                        $store_over_flow =  true ;
                        $vendorWallet->collected_cash = $vendorWallet->collected_cash+($order->order_amount-$order->partially_paid_amount);
                    }
                }
                else if($received_by==false)
                {
                    $adminWallet->manual_received = $adminWallet->manual_received+($order->order_amount-$order->partially_paid_amount);
                }
                else if($received_by=='deliveryman' && $order->delivery_man && $order->delivery_man->type == 'zone_wise')
                {
                    // Check if order was paid via QR code
                    $qrPayment = \App\Models\DeliveryQRPayment::where('order_id', $order->id)
                        ->where('status', 'paid')
                        ->first();

                    if ($qrPayment) {
                        // QR payment - money goes to admin digital account, NOT delivery man's cash
                        $adminWallet->digital_received = $adminWallet->digital_received + ($order->order_amount - $order->partially_paid_amount);

                        // Still deduct outside purchase costs from DM if applicable
                        if($outside_purchase_random_cost > 0) {
                            $dmWallet->collected_cash = $dmWallet->collected_cash - $outside_purchase_random_cost;
                        }

                        \Log::info('QR Payment - added to digital_received, not collected_cash', [
                            'order_id' => $order->id,
                            'qr_payment_id' => $qrPayment->id,
                            'amount' => $order->order_amount - $order->partially_paid_amount
                        ]);
                    } else {
                        // Regular cash payment - add to delivery man's collected cash
                        $dm_old_collected_cash = $dmWallet->collected_cash;
                        $dmWallet->collected_cash = $dmWallet->collected_cash + ($order->order_amount - ($order->partially_paid_amount ?? 0) - $outside_purchase_random_cost);
                        $dm_over_flow =  true ;
                    }
                }

                $adminWallet->save();
                if($type != 'parcel')
                {
                    $vendorWallet->save();
                }
                if(isset($dmWallet)){
                    $dmWallet->save();
                }

                // Credit other stores' wallets for outside purchases linked to them
                if(!empty($outside_purchase_store_credits)) {
                    foreach($outside_purchase_store_credits as $op_store_id => $op_cost_amount) {
                        $opStore = \App\Models\Store::with('vendor')->find($op_store_id);
                        if($opStore && $opStore->vendor) {
                            $op_store_commission = $comission ? ($op_cost_amount / 100) * $comission : 0;
                            $op_store_earning = $op_cost_amount - $op_store_commission;
                            $opStoreWallet = StoreWallet::firstOrNew(['vendor_id' => $opStore->vendor->id]);
                            $opStoreWallet->total_earning = $opStoreWallet->total_earning + $op_store_earning;
                            $opStoreWallet->save();
                            $adminWallet->total_commission_earning = $adminWallet->total_commission_earning + $op_store_commission;
                            $adminWallet->save();
                        }
                    }
                }

                if(isset($store_over_flow) ){
                    self::create_account_transaction_for_collect_cash(old_collected_cash:$vendorWallet->collected_cash , from_type:'store' , from_id: $order->store->vendor->id , amount: $order->order_amount - $order->partially_paid_amount ,order_id: $order->id);
                }
                if(isset($dm_over_flow)){
                    self::create_account_transaction_for_collect_cash(old_collected_cash: $dm_old_collected_cash ?? 0 , from_type:'deliveryman' , from_id: $order->delivery_man_id , amount: $order->order_amount - ($order->partially_paid_amount ?? 0) - $outside_purchase_random_cost ,order_id: $order->id);
                }

                self::update_unpaid_order_payment(order_id:$order->id, payment_method:$order->payment_method);

                DB::commit();

                if($order->is_guest  == 0){
                    $ref_status = BusinessSetting::where('key','ref_earning_status')->first()->value;
                    if(isset($order->customer->ref_by) && $order->customer->order_count == 0  && $ref_status == 1){
                        $ref_code_exchange_amt = BusinessSetting::where('key','ref_earning_exchange_rate')->first()->value;
                        $referar_user=User::where('id',$order->customer->ref_by)->first();
                        $refer_wallet_transaction = CustomerLogic::create_wallet_transaction($referar_user->id, $ref_code_exchange_amt, 'referrer',$order->customer->phone);

                        $notification_data = [
                            'title' => translate('messages.Congratulation'),
                            'description' => translate('You have received').' '.Helpers::format_currency($ref_code_exchange_amt).' '.translate('in your wallet as').' '.$order?->customer?->f_name.' '.$order?->customer?->l_name.' '.translate('you referred completed thier first order') ,
                            'order_id' => 1,
                            'image' => '',
                            'type' => 'referral_code',
                        ];

                        if(Helpers::getNotificationStatusData('customer','customer_referral_bonus_earning','push_notification_status') && $referar_user?->cm_firebase_token){
                            Helpers::send_push_notif_to_device($referar_user?->cm_firebase_token, $notification_data);
                            DB::table('user_notifications')->insert([
                                'data' => json_encode($notification_data),
                                'user_id' => $referar_user?->id,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }


                        try{
                            Helpers::add_fund_push_notification($referar_user->id);
                            if(config('mail.status') && Helpers::get_mail_status('add_fund_mail_status_user') == '1' && Helpers::getNotificationStatusData('customer','customer_add_fund_to_wallet','mail_status') ) {
                                Mail::to($referar_user->email)->send(new \App\Mail\AddFundToWallet($refer_wallet_transaction));
                            }
                        } catch(\Exception $ex){
                            info($ex->getMessage());
                        }
                    }

                   $create_loyalty_point_transaction= CustomerLogic::create_loyalty_point_transaction($order->user_id, $order->id, $order->order_amount, 'order_place');
                    if($create_loyalty_point_transaction > 0) {
                        $notification_data = [
                            'title' => translate('messages.Congratulation'),
                            'description' => translate('You_have_received').' '.$create_loyalty_point_transaction.' '.translate('points_as_loyalty_point'),
                            'order_id' => $order->id,
                            'image' => '',
                            'type' => 'loyalty_point',
                        ];

                        if(Helpers::getNotificationStatusData('customer','customer_loyalty_point_earning','push_notification_status') && $order->customer?->cm_firebase_token){
                            Helpers::send_push_notif_to_device($order->customer?->cm_firebase_token, $notification_data);
                            DB::table('user_notifications')->insert([
                                'data' => json_encode($notification_data),
                                'user_id' => $order->user_id,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }

                    }
                }


            }
            catch(\Exception $e)
            {
                DB::rollBack();
                info($e->getMessage());
                return false;
            }
        }
        catch(\Exception $e){
            info($e->getMessage());
            return false;
        }

        return true;
    }

    public static function refund_before_delivered($order){
        $adminWallet = AdminWallet::firstOrNew(
            ['admin_id' => Admin::where('role_id', 1)->first()->id]
        );
        if ($order->payment_method == 'cash_on_delivery') {
            return false;
        }
        if(($order->payment_status == "paid")){

            $adminWallet->digital_received = $adminWallet->digital_received - $order->order_amount;
            $adminWallet->save();
            if (BusinessSetting::where('key', 'wallet_add_refund')->first()->value == 1 && $order->is_guest  == 0) {
                CustomerLogic::create_wallet_transaction($order->user_id, $order->order_amount, 'order_refund', $order->id);
            }
        }elseif(($order->payment_status == "partially_paid")){

            $adminWallet->digital_received = $adminWallet->digital_received - $order->partially_paid_amount;
            $adminWallet->save();
            if (BusinessSetting::where('key', 'wallet_add_refund')->first()->value == 1  &&  $order->is_guest  == 0) {
                CustomerLogic::create_wallet_transaction($order->user_id, $order->partially_paid_amount, 'order_refund', $order->id);
            }
        }
        return true;
    }

    public static function refund_order($order)
    {
        $order_transaction = $order->transaction;
        if($order_transaction == null || $order->store == null)
        {
            return false;
        }
        $received_by = $order_transaction->received_by;

        $adminWallet = AdminWallet::firstOrNew(
            ['admin_id' => Admin::where('role_id', 1)->first()->id]
        );

        $vendorWallet = StoreWallet::firstOrNew(
            ['vendor_id' => $order->store->vendor->id]
        );

        $adminWallet->total_commission_earning = $adminWallet->total_commission_earning - $order_transaction->admin_commission + $order_transaction->delivery_fee_comission;

        $vendorWallet->total_earning = $vendorWallet->total_earning - $order_transaction->store_amount;

        $refund_amount = $order->order_amount - $order->additional_charge - $order->extra_packaging_amount;

        $status = 'refunded_with_delivery_charge';
        if($order->order_status == 'delivered' || $order->order_status == 'refund_requested')
        {
            $refund_amount = $order->order_amount - $order->additional_charge - $order->extra_packaging_amount - $order->delivery_charge -$order->dm_tips;
            $status = 'refunded_without_delivery_charge';
        }
        else
        {
            $adminWallet->delivery_charge = $adminWallet->delivery_charge - $order_transaction->delivery_charge;
        }
        try
        {
            DB::beginTransaction();
            $partially_paid = OrderPayment::where('payment_method','cash_on_delivery')->where('order_id',$order->id)->exists() ?? false;

            if($partially_paid){
                $refund_amount = $refund_amount - $order->partially_paid_amount;
            }
            if($received_by=='admin')
            {
                if($order->delivery_man_id && $order->payment_method != "cash_on_delivery")
                {
                    $adminWallet->digital_received = $adminWallet->digital_received - $refund_amount;
                }
                else
                {
                    $adminWallet->manual_received = $adminWallet->manual_received - $refund_amount;
                }

            }
            else if($received_by=='store')
            {
                $vendorWallet->collected_cash = $vendorWallet->collected_cash - $refund_amount;
            }
            else if($received_by=='deliveryman' && $order->delivery_man_id)
            {
                $dmWallet = DeliveryManWallet::firstOrNew(
                    ['delivery_man_id' => $order->delivery_man_id]
                );
                $dmWallet->collected_cash = $dmWallet->collected_cash - $refund_amount;
                $dmWallet->save();
            }
            $order_transaction->status = $status;
            $order_transaction->save();
            $adminWallet->save();
            $vendorWallet->save();
            DB::commit();
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            info($e->getMessage());
            return false;
        }
        return true;

    }

    /**
     * Process delivery man cancellation compensation when DM is at customer location
     *
     * @param Order $order
     * @return float Compensation amount paid (0 if none)
     */
    public static function process_dm_cancellation_compensation($order)
    {
        // Check if feature is enabled
        $compensationEnabled = BusinessSetting::where('key', 'dm_cancellation_compensation_enabled')->first();
        if (!$compensationEnabled || $compensationEnabled->value != 1) {
            return 0;
        }

        // Must have delivery man assigned
        if (!$order->delivery_man_id || !$order->delivery_man) {
            return 0;
        }

        // Get delivery address coordinates
        $deliveryAddress = json_decode($order->delivery_address, true);
        if (!isset($deliveryAddress['latitude']) || !isset($deliveryAddress['longitude'])) {
            return 0;
        }

        // Get DM's last location from tracking stats
        $trackingStat = DeliveryTrackingStat::where('order_id', $order->id)->first();
        if (!$trackingStat || !$trackingStat->last_latitude || !$trackingStat->last_longitude) {
            // No location data - no compensation
            return 0;
        }

        $customerLat = (float) $deliveryAddress['latitude'];
        $customerLng = (float) $deliveryAddress['longitude'];
        $dmLat = $trackingStat->last_latitude;
        $dmLng = $trackingStat->last_longitude;

        // Calculate distance using Haversine formula
        $earthRadius = 6371; // km
        $dLat = deg2rad($customerLat - $dmLat);
        $dLon = deg2rad($customerLng - $dmLng);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($dmLat)) * cos(deg2rad($customerLat)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distanceKm = $earthRadius * $c;

        // Get proximity threshold from settings
        $proximitySetting = BusinessSetting::where('key', 'dm_cancellation_compensation_proximity')->first();
        $proximityThreshold = $proximitySetting ? (float)$proximitySetting->value : 0.2; // default 200m

        // Check if DM was within proximity
        $wasAtLocation = $distanceKm <= $proximityThreshold;

        // Store proximity data in order
        $order->dm_was_at_location_on_cancel = $wasAtLocation ? 1 : 0;
        $order->dm_distance_on_cancel = round($distanceKm * 1000, 2); // Store in meters

        if (!$wasAtLocation) {
            $order->save();
            return 0; // Not close enough - no compensation
        }

        // Calculate compensation amount
        $compensationPercentage = BusinessSetting::where('key', 'dm_cancellation_compensation_percentage')->first();
        $percentage = $compensationPercentage ? (float)$compensationPercentage->value : 50;

        // Base compensation on original delivery charge
        $baseAmount = $order->original_delivery_charge > 0 ? $order->original_delivery_charge : $order->delivery_charge;
        $compensationAmount = ($baseAmount * $percentage) / 100;

        if ($compensationAmount <= 0) {
            $order->save();
            return 0;
        }

        // Process payment to DM wallet
        try {
            DB::beginTransaction();

            $dmWallet = DeliveryManWallet::firstOrNew(['delivery_man_id' => $order->delivery_man_id]);
            $previousBalance = $dmWallet->total_earning ?? 0;

            // Add compensation to DM earnings
            $dmWallet->total_earning = $previousBalance + $compensationAmount;
            $dmWallet->save();

            // Record in order
            $order->dm_cancellation_compensation = $compensationAmount;
            $order->save();

            // Create account transaction for audit trail
            AccountTransaction::create([
                'from_type' => 'deliveryman',
                'from_id' => $order->delivery_man_id,
                'type' => 'credit',
                'ref' => 'cancellation_compensation',
                'amount' => $compensationAmount,
                'current_balance' => $previousBalance,
                'balance_after' => $previousBalance + $compensationAmount,
                'method' => 'system',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Also record in OrderTransaction for completeness
            $orderTransaction = $order->transaction;
            if ($orderTransaction) {
                $orderTransaction->update([
                    'dm_cancellation_compensation' => $compensationAmount,
                    'status' => 'cancelled_with_dm_compensation'
                ]);
            }

            DB::commit();

            \Log::info("DM Cancellation Compensation Paid", [
                'order_id' => $order->id,
                'delivery_man_id' => $order->delivery_man_id,
                'amount' => $compensationAmount,
                'distance_meters' => round($distanceKm * 1000, 2),
                'percentage' => $percentage
            ]);

            return $compensationAmount;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to process DM cancellation compensation: " . $e->getMessage(), [
                'order_id' => $order->id,
                'delivery_man_id' => $order->delivery_man_id
            ]);
            return 0;
        }
    }

    public static function format_export_data($orders, $type='order')
    {
        $data = [];
        foreach($orders as $key=>$order)
        {

            $data[]=[
                '#'=>$key+1,
                translate('messages.order')=>$order['id'],
                translate('messages.date')=>date('d M Y',strtotime($order['created_at'])),
                translate('messages.customer')=>$order->customer?$order->customer['f_name'].' '.$order->customer['l_name']:translate('messages.invalid_customer_data'),
                translate($type=='order'?'messages.store':'messages.parcel_category')=>Str::limit($type=='order'?($order->store?$order->store->name:translate('messages.store deleted!')):($order->parcel_category?$order->parcel_category->name:translate('messages.not_found')),20,'...'),
                translate('messages.payment_status')=>$order->payment_status=='paid'?translate('messages.paid'):translate('messages.unpaid'),
                translate('messages.total')=>\App\CentralLogics\Helpers::format_currency($order['order_amount']),
                translate('messages.order_status')=>translate('messages.'. $order['order_status']),
                translate('messages.order_type')=>translate('messages.'.$order['order_type'])
            ];
        }
        return $data;
    }
    public static function format_store_order_export_data($orders)
    {
        $data = [];
        foreach($orders as $key=>$order)
        {

            $data[]=[
                '#'=>$key+1,
                translate('messages.order')=>$order['id'],
                translate('messages.date')=>date('d M Y',strtotime($order['created_at'])),
                translate('messages.customer')=>$order->customer?$order->customer['f_name'].' '.$order->customer['l_name']:translate('messages.invalid_customer_data'),
                translate('messages.payment_status')=>$order->payment_status=='paid'?translate('messages.paid'):translate('messages.unpaid'),
                translate('messages.total')=>\App\CentralLogics\Helpers::format_currency($order['order_amount']),
                translate('messages.order_status')=>translate('messages.'. $order['order_status']),
                translate('messages.order_type')=>translate('messages.'.$order['order_type']),
                translate('messages.discount_amount')=>$order['coupon_discount_amount']+$order['store_discount_amount'],
                translate('messages.total_tax_amount')=>$order['total_tax_amount'],
                translate('messages.delivery_charge')=>$order['original_delivery_charge']
            ];
        }
        return $data;
    }

    public static function format_order_report_export_data($orders)
    {
        $data = [];
        foreach($orders as $key=>$order)
        {

            $data[]=[
                '#'=>$key+1,
                translate('messages.order')=>$order['id'],
                translate('messages.store')=>$order->store?$order->store->name:translate('messages.invalid'),
                translate('messages.customer_name')=>$order->customer?$order->customer['f_name'].' '.$order->customer['l_name']:translate('messages.invalid_customer_data'),
                translate('Total Item Amount')=>\App\CentralLogics\Helpers::format_currency($order['order_amount']-$order['dm_tips']-$order['total_tax_amount']-$order['delivery_charge']+$order['coupon_discount_amount'] + $order['store_discount_amount']),
                translate('Item Discount')=>\App\CentralLogics\Helpers::format_currency($order->details->sum('discount_on_item')),
                translate('Coupon Discount')=>\App\CentralLogics\Helpers::format_currency($order['coupon_discount_amount']),
                translate('Discounted Amount')=>\App\CentralLogics\Helpers::format_currency($order['coupon_discount_amount'] + $order['store_discount_amount']),
                translate('messages.tax')=>\App\CentralLogics\Helpers::format_currency($order['total_tax_amount']),
                translate('messages.delivery_charge')=>\App\CentralLogics\Helpers::format_currency($order['original_delivery_charge']),
                translate('messages.order_amount')=>\App\CentralLogics\Helpers::format_currency($order['order_amount']),
                translate('messages.amount_received_by')=>isset($order->transaction) ? $order->transaction->received_by : translate('messages.unpaid'),
                translate('messages.payment_method')=>translate(str_replace('_', ' ', $order['payment_method'])),
                translate('messages.order_status')=>translate('messages.'. $order['order_status']),
                translate('messages.order_type')=>translate('messages.'.$order['order_type']),
            ];
        }
        return $data;
    }

    public static function create_order_payment($order_id, $amount, $payment_status, $payment_method)
    {
        $payment = new OrderPayment();
        $payment->order_id = $order_id;
        $payment->amount = $amount;
        $payment->payment_status = $payment_status;
        $payment->payment_method = $payment_method;
        if($payment->save()){
            return true;
        }

        return false;

    }

    public static function update_unpaid_order_payment($order_id,$payment_method)
    {
        // FIX: Update ALL unpaid payment records, not just the first one
        $unpaidPayments = OrderPayment::where('payment_status','unpaid')->where('order_id',$order_id)->get();

        if($unpaidPayments->isNotEmpty()){
            $updateData = ['payment_status' => 'paid'];

            // Only update payment_method if it's not a partial payment
            if($payment_method != 'partial_payment'){
                $updateData['payment_method'] = $payment_method;
            }

            // Update all unpaid records at once for this order
            $updated = OrderPayment::where('payment_status','unpaid')
                ->where('order_id',$order_id)
                ->update($updateData);

            \Log::info('Updated unpaid order payments', [
                'order_id' => $order_id,
                'payment_method' => $payment_method,
                'records_updated' => $updated
            ]);

            return $updated > 0;
        }

        return true;
    }



    public static function create_account_transaction_for_collect_cash($old_collected_cash, $from_type ,$from_id ,$amount, $order_id){
        $account_transaction = new AccountTransaction();
        $account_transaction->from_type =$from_type;
        $account_transaction->from_id = $from_id;
        $account_transaction->created_by = $from_type;
        $account_transaction->method = 'cash_collection';
        $account_transaction->ref = $order_id;
        $account_transaction->amount = $amount ?? 0;
        $account_transaction->current_balance = $old_collected_cash ?? 0;
        $account_transaction->type = 'cash_in';
        $account_transaction->save();


        if($from_type  ==  'store'){
            $vendor= Vendor::find($from_id);
            $Payable_Balance = $vendor?->wallet?->collected_cash   > 0 ? 1: 0;
            $cash_in_hand_overflow= BusinessSetting::where('key' ,'cash_in_hand_overflow_store')->first()?->value;
            $cash_in_hand_overflow_store_amount = BusinessSetting::where('key' ,'cash_in_hand_overflow_store_amount')->first()?->value;

            if ($Payable_Balance == 1 &&  $cash_in_hand_overflow && $vendor?->wallet?->balance<0 &&  $cash_in_hand_overflow_store_amount <= abs($vendor?->wallet?->collected_cash)){
                $rest= Store::where('vendor_id', $vendor->id)->first();
                $rest->status = 0 ;
                $rest->save();
            }

        } elseif($from_type  ==  'deliveryman' ){
            $cash_in_hand_overflow= BusinessSetting::where('key' ,'cash_in_hand_overflow_delivery_man')->first()?->value;
            $cash_in_hand_overflow_delivery_man = BusinessSetting::where('key' ,'dm_max_cash_in_hand')->first()?->value;
            // $val=  $cash_in_hand_overflow_delivery_man - (($cash_in_hand_overflow_delivery_man * 10)/100);

            $dm = DeliveryMan::find($from_id);
            $wallet_balance = $dm?->wallet?->total_earning - ($dm?->wallet?->total_withdrawn +$dm?->wallet?->pending_withdraw + $dm?->wallet?->collected_cash);
            $over_flow_balance =  $dm?->wallet?->collected_cash;
            $Payable_Balance =  $over_flow_balance   > 0 ? 1: 0;
            if ($Payable_Balance == 1 &&  $cash_in_hand_overflow  && $wallet_balance<0 &&  $cash_in_hand_overflow_delivery_man < abs($over_flow_balance)){
                $dm->status = 0 ;
                // $dm->auth_token = null;
                $dm->save();
            }

        }
        return true;
    }


    public static function cashbackToWallet($order){

        $refer_wallet_transaction = CustomerLogic::create_wallet_transaction($order?->cashback_history?->user_id, $order?->cashback_history?->calculated_amount, 'CashBack',$order->id);
        if($refer_wallet_transaction != false){
            Helpers::expenseCreate(amount:$order?->cashback_history?->calculated_amount,type:'CashBack',datetime:now(),created_by:'admin', order_id:$order->id);
            $order?->cashback_history?->cashBack?->increment('total_used');

            $notification_data = [
                'title' => translate('messages.Congratulation_you_have_received').' '.$order?->cashback_history?->calculated_amount.' '.translate('cashback'),
                'description' => translate('The_cashback_amount_successfully_added_to_your_wallet') ,
                'order_id' => $order->id,
                'image' => '',
                'type' => 'cashback',
            ];

            if($order->customer?->cm_firebase_token && Helpers::getNotificationStatusData('customer','customer_cashback','push_notification_status')){
                Helpers::send_push_notif_to_device($order->customer?->cm_firebase_token, $notification_data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($notification_data),
                    'user_id' => $order->customer?->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

        }

        return true;
    }

}
