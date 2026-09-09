@extends('layouts.admin.app')

@section('title', translate('messages.delivery_man_settings'))


@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title mr-3">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('business_setup')}}
                </span>
            </h1>
            @include('admin-views.business-settings.partials.nav-menu')
        </div>
        <!-- Page Header -->

        <!-- End Page Header -->
        <form action="{{ route('admin.business-settings.update-dm') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row g-2">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6 col-lg-4">
                                    @php($dm_tips_status = \App\Models\BusinessSetting::where('key', 'dm_tips_status')->first())
                                    @php($dm_tips_status = $dm_tips_status ? $dm_tips_status->value : 'deliveryman')
                                    <div class="form-group mb-0">

                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    {{ translate('messages.Tips_for_Deliveryman') }}
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.Customer_can_give_tips_to_deliveryman_during_checkout_from_the_customer_app_&_website._From_this,_admin_has_no_commission.') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.dm_tips_model_hint') }}"> * </span>
                                            </span>
                                            <input type="checkbox"
                                                   data-id="dm_tips_status"
                                                   data-type="toggle"
                                                   data-image-on="{{ asset('/public/assets/admin/img/modal/dm-tips-on.png') }}"
                                                   data-image-off="{{ asset('/public/assets/admin/img/modal/dm-tips-off.png') }}"
                                                   data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Tips_for_Deliveryman_feature?') }}</strong>"
                                                   data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Tips_for_Deliveryman_feature?') }}</strong>"
                                                   data-text-on="<p>{{ translate('messages.If_you_enable_this,_Customers_can_give_tips_to_a_deliveryman_during_checkout.') }}</p>"
                                                   data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_Tips_for_Deliveryman_feature_will_be_hidden_from_the_Customer_App_and_Website.') }}</p>"
                                                   class="status toggle-switch-input dynamic-checkbox-toggle"
                                                    value="1"
                                                name="dm_tips_status" id="dm_tips_status"
                                                {{ $dm_tips_status == '1' ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    @php($show_dm_earning = \App\Models\BusinessSetting::where('key', 'show_dm_earning')->first())
                                    @php($show_dm_earning = $show_dm_earning ? $show_dm_earning->value : 0)
                                    <div class="form-group mb-0">

                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    {{ translate('Show Earnings in App') }}
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.With_this_feature,_Deliverymen_can_see_their_earnings_on_a_specific_order_while_accepting_it.') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.customer_verification_toggle') }}"> *
                                                </span>
                                            </span>
                                            <input type="checkbox"
                                                   data-id="show_dm_earning"
                                                   data-type="toggle"
                                                   data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                                   data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                                   data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Show_Earnings_in_App?') }}</strong>"
                                                   data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Show_Earnings_in_App?') }}</strong>"
                                                   data-text-on="<p>{{ translate('messages.If_you_enable_this,_Deliverymen_can_see_their_earning_per_order_request_from_the_Order_Details_page_in_the_Deliveryman_App.') }}</p>"
                                                   data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_feature_will_be_hidden_from_the_Deliveryman_App.') }}</p>"
                                                   class="status toggle-switch-input dynamic-checkbox-toggle"

                                                   value="1"
                                                name="show_dm_earning" id="show_dm_earning"
                                                {{ $show_dm_earning == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    @php($dm_self_registration = \App\Models\BusinessSetting::where('key', 'toggle_dm_registration')->first())
                                    {{-- {{ dd($dm_self_registration) }} --}}
                                    @php($dm_self_registration = $dm_self_registration ? $dm_self_registration->value : 0)
                                    <div class="form-group mb-0">

                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    {{ translate('messages.dm_self_registration') }}
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.With_this_feature,_deliverymen_can_register_themselves_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page._The_admin_will_receive_an_email_notification_and_can_accept_or_reject_the_request.') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.dm_self_registration') }}"> * </span>
                                            </span>
                                            <input type="checkbox"
                                                   data-id="dm_self_registration1"
                                                   data-type="toggle"
                                                   data-image-on="{{ asset('/public/assets/admin/img/modal/dm-self-reg-on.png') }}"
                                                   data-image-off="{{ asset('/public/assets/admin/img/modal/dm-self-reg-off.png') }}"
                                                   data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Deliveryman_Self_Registration?') }}</strong>"
                                                   data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Deliveryman_Self_Registration?') }}</strong>"
                                                   data-text-on="<p>{{ translate('messages.If_you_enable_this,_users_can_register_as_Deliverymen_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page.') }}</p>"
                                                   data-text-off="<p>{{ translate('messages.If_you_disable_this,_this_feature_will_be_hidden_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page.') }}</p>"
                                                   class="status toggle-switch-input dynamic-checkbox-toggle"

                                                   value="1"
                                                name="dm_self_registration" id="dm_self_registration1"
                                                {{ $dm_self_registration == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    @php($dm_maximum_orders = \App\Models\BusinessSetting::where('key', 'dm_maximum_orders')->first())
                                    <div class="form-group mb-0">
                                        <label class="form-label text-capitalize"
                                            for="dm_maximum_orders">
                                            <div class="d-flex align-items-center">
                                                <span class="line--limit-1 flex-grow">{{ translate('Maximum Assigned Order Limit') }} </span> <small
                                                class="text-danger d-flex align-items-center mt-1"> *<span class="form-label-secondary"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.Set_the_maximum_order_limit_a_Deliveryman_can_take_at_a_time.') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.dm_maximum_order_hint') }}"></span>
                                                </small>
                                            </div>
                                        </label>
                                        <input type="number" name="dm_maximum_orders" class="form-control"
                                            id="dm_maximum_orders" min="1"
                                            value="{{ $dm_maximum_orders ? $dm_maximum_orders->value : 1 }}" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    @php($canceled_by_deliveryman = \App\Models\BusinessSetting::where('key', 'canceled_by_deliveryman')->first())
                                    @php($canceled_by_deliveryman = $canceled_by_deliveryman ? $canceled_by_deliveryman->value : 0)
                                    <div class="form-group mb-0">
                                        <label class="input-label text-capitalize d-flex align-items-center"><span
                                                class="line--limit-1">{{ translate('messages.Can_A_Deliveryman_Cancel_Order?') }}</span>
                                            <span class="form-label-secondary"
                                            data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('messages.Admin_can_enable/disable_Deliveryman’s_order_cancellation_option_in_the_respective_app.') }}"><img
                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                alt="{{ translate('messages.dm_cancel_order_hint') }}"></span></label>
                                        <div class="resturant-type-group border">
                                            <label class="form-check form--check mr-2 mr-md-4">
                                                <input class="form-check-input" type="radio" value="1"
                                                    name="canceled_by_deliveryman" id="canceled_by_deliveryman"
                                                    {{ $canceled_by_deliveryman == 1 ? 'checked' : '' }}>
                                                <span class="form-check-label">
                                                    {{ translate('yes') }}
                                                </span>
                                            </label>
                                            <label class="form-check form--check mr-2 mr-md-4">
                                                <input class="form-check-input" type="radio" value="0"
                                                    name="canceled_by_deliveryman" id="canceled_by_deliveryman2"
                                                    {{ $canceled_by_deliveryman == 0 ? 'checked' : '' }}>
                                                <span class="form-check-label">
                                                    {{ translate('no') }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4 mt-5">
                                    @php($dm_picture_upload_status = \App\Models\BusinessSetting::where('key', 'dm_picture_upload_status')->first())
                                    @php($dm_picture_upload_status = $dm_picture_upload_status ? $dm_picture_upload_status->value : 0)

                                    <div class="form-group mb-0">
                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    {{ translate('messages.Take_Picture_For_Completing_Delivery') }}
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.If_enabled,_deliverymen_will_see_an_option_to_take_pictures_of_the_delivered_products_when_he_swipes_the_delivery_confirmation_slide.') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.dm_picture_upload_status') }}"> * </span>
                                            </span>
                                            <input type="checkbox"
                                                   data-id="dm_picture_upload_status"
                                                   data-type="toggle"
                                                   data-image-on="{{ asset('/public/assets/admin/img/modal/dm-self-reg-on.png') }}"
                                                   data-image-off="{{ asset('/public/assets/admin/img/modal/dm-self-reg-off.png') }}"
                                                   data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.picture_upload_before_complete?') }}</strong>"
                                                   data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.picture_upload_before_complete?') }}</strong>"
                                                   data-text-on="<p>{{ translate('messages.If_you_enable_this,_delivery_man_can_upload_order_proof_before_order_delivery.') }}</p>"
                                                   data-text-off="<p>{{ translate('messages.If_you_disable_this,_this_feature_will_be_hidden_from_the_delivery_man_app.') }}</p>"
                                                   class="status toggle-switch-input dynamic-checkbox-toggle"
                                                   value="1"
                                                name="dm_picture_upload_status" id="dm_picture_upload_status"
                                                {{ $dm_picture_upload_status == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>




                                <div class="col-sm-6 col-lg-4">
                                    @php($cash_in_hand_overflow = \App\Models\BusinessSetting::where('key', 'cash_in_hand_overflow_delivery_man')->first())
                                    @php($cash_in_hand_overflow = $cash_in_hand_overflow ? $cash_in_hand_overflow->value : 0)
                                    <div class="form-label  mb-0 ">
                                        <label>&nbsp;</label>
                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    {{ translate('messages.Cash_In_Hand_Overflow') }}
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex"
                                                      data-toggle="tooltip" data-placement="right"
                                                      data-original-title="{{ translate('If_enabled,_delivery_men_will_be_automatically_suspended_by_the_system_when_their_‘Cash_in_Hand’_limit_is_exceeded.') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.cash_in_hand_overflow') }}"> *
                                                </span>
                                            </span>
                                            <input type="checkbox"
                                                   data-id="cash_in_hand_overflow"
                                                   data-type="toggle"
                                                   data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                                   data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                                   data-title-on="{{ translate('Want_to_enable') }} <strong>{{ translate('Cash_In_Hand_Overflow') }}</strong>?"
                                                   data-title-off="{{ translate('Want_to_disable') }} <strong>{{ translate('Cash_In_Hand_Overflow') }}</strong>?"
                                                   data-text-on="<p>{{ translate('If_enabled,_delivery_men_have_to_provide_collected_cash_by_themselves.') }}</p>"
                                                   data-text-off="<p>{{ translate('If_disabled,_delivery_men_do_not_have_to_provide_collected_cash_by_themselves.') }}</p>"
                                                   class="status toggle-switch-input dynamic-checkbox-toggle"
                                                   value="1"
                                                   name="cash_in_hand_overflow_delivery_man" id="cash_in_hand_overflow"
                                                {{ $cash_in_hand_overflow == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    @php($dm_max_cash_in_hand = \App\Models\BusinessSetting::where('key', 'dm_max_cash_in_hand')->first())
                                    <div class="form-label mb-0">
                                        <label class="d-flex text-capitalize"
                                               for="dm_max_cash_in_hand">
                                            <span class="line--limit-1">
                                                {{translate('Delivery_Man_Maximum_Cash_in_Hand')}} ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                            </span>
                                            <span data-toggle="tooltip" data-placement="right" data-original-title="{{translate('Deliveryman_can_not_accept_any_orders_when_the_Cash_In_Hand_limit_exceeds_and_must_deposit_the_amount_to_the_admin_before_accepting_new_orders')}}" class="input-label-secondary"><img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="{{ translate('messages.dm_maximum_order_hint') }}"></span>
                                        </label>
                                        <input type="number" name="dm_max_cash_in_hand" class="form-control"
                                               id="dm_max_cash_in_hand" min="0" step=".001"
                                               value="{{ $dm_max_cash_in_hand ? $dm_max_cash_in_hand->value : '' }}" {{ $cash_in_hand_overflow  == 1 ? 'required' : 'readonly' }} >
                                    </div>
                                </div>



                                <div class="col-sm-6 col-lg-4">
                                    @php($min_amount_to_pay_dm = \App\Models\BusinessSetting::where('key', 'min_amount_to_pay_dm')->first())
                                    <div class="form-label mb-0">
                                        <label class="text-capitalize"
                                               for="min_amount_to_pay_dm">
                                            <span>
                                                {{ translate('Minimum_Amount_To_Pay') }} ({{ \App\CentralLogics\Helpers::currency_symbol() }})

                                            </span>

                                            <span class="form-label-secondary"
                                                  data-toggle="tooltip" data-placement="right"
                                                  data-original-title="{{ translate('Enter_the_minimum_cash_amount_delivery_men_can_pay') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.dm_cancel_order_hint') }}"></span>
                                        </label>
                                        <input type="number" name="min_amount_to_pay_dm" class="form-control"
                                               id="min_amount_to_pay_dm" min="0" step=".001"
                                               value="{{ $min_amount_to_pay_dm ? $min_amount_to_pay_dm->value : '' }}"  {{ $cash_in_hand_overflow  == 1 ? 'required' : 'readonly' }} >
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="mt-4 mb-3">
                                    <h5 class="card-title mb-3">
                                        <span class="card-header-icon mr-2">
                                            <i class="tio-money"></i>
                                        </span>
                                        <span>{{ translate('messages.cancellation_compensation') }}</span>
                                    </h5>
                                </div>

                                <div class="col-sm-6 col-lg-4">
                                    @php($dm_compensation_enabled = \App\Models\BusinessSetting::where('key', 'dm_cancellation_compensation_enabled')->first())
                                    @php($dm_compensation_enabled = $dm_compensation_enabled ? $dm_compensation_enabled->value : 1)
                                    <div class="form-group mb-0">
                                        <label class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    {{ translate('messages.dm_cancellation_compensation') }}
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.pay_delivery_man_partial_compensation_when_order_cancelled_at_customer_location') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.dm_cancellation_compensation') }}">
                                                </span>
                                            </span>
                                            <input type="checkbox" class="toggle-switch-input" value="1"
                                                name="dm_cancellation_compensation_enabled"
                                                {{ $dm_compensation_enabled == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-sm-6 col-lg-4">
                                    @php($compensation_percentage = \App\Models\BusinessSetting::where('key', 'dm_cancellation_compensation_percentage')->first())
                                    @php($compensation_percentage = $compensation_percentage ? $compensation_percentage->value : 50)
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="dm_cancellation_compensation_percentage">
                                            {{ translate('messages.compensation_percentage') }}
                                            <span class="text-danger">*</span>
                                            <span class="form-label-secondary text-danger d-flex"
                                                data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.percentage_of_delivery_charge_to_compensate') }}">
                                                <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                            </span>
                                        </label>
                                        <input type="number" name="dm_cancellation_compensation_percentage"
                                            id="dm_cancellation_compensation_percentage"
                                            class="form-control h--45px" value="{{ $compensation_percentage }}"
                                            min="0" max="100" step="5" required>
                                        <small class="form-text text-muted">
                                            {{ translate('messages.percentage_of_delivery_charge_to_compensate') }}
                                        </small>
                                    </div>
                                </div>

                                <div class="col-sm-6 col-lg-4">
                                    @php($compensation_proximity = \App\Models\BusinessSetting::where('key', 'dm_cancellation_compensation_proximity')->first())
                                    @php($compensation_proximity = $compensation_proximity ? $compensation_proximity->value * 1000 : 200)
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="dm_cancellation_compensation_proximity">
                                            {{ translate('messages.compensation_proximity_radius') }} ({{ translate('messages.meters') }})
                                            <span class="form-label-secondary text-danger d-flex"
                                                data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.dm_must_be_within_this_distance_to_qualify_for_compensation') }}">
                                                <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                            </span>
                                        </label>
                                        <input type="number" name="dm_cancellation_compensation_proximity"
                                            id="dm_cancellation_compensation_proximity"
                                            class="form-control h--45px" value="{{ $compensation_proximity }}"
                                            min="50" max="1000" step="50">
                                        <small class="form-text text-muted">
                                            {{ translate('messages.dm_must_be_within_this_distance_to_qualify_for_compensation') }}
                                        </small>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="mt-4 mb-3">
                                    <h5 class="card-title mb-3">
                                        <span class="card-header-icon mr-2">
                                            <i class="tio-money"></i>
                                        </span>
                                        <span>{{ translate('messages.security_deposit') }}</span>
                                    </h5>
                                </div>

                                <div class="col-sm-6 col-lg-4">
                                    @php($security_deposit_enabled = \App\Models\BusinessSetting::where('key', 'security_deposit_enabled')->first())
                                    @php($security_deposit_enabled = $security_deposit_enabled ? $security_deposit_enabled->value : 0)
                                    <div class="form-group mb-0">
                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    {{ translate('messages.enable_security_deposit') }}
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.delivery_men_must_pay_security_deposit_before_start_working') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.security_deposit') }}"> *
                                                </span>
                                            </span>
                                            <input type="checkbox"
                                                   data-id="security_deposit_enabled"
                                                   data-type="toggle"
                                                   data-image-on="{{ asset('/public/assets/admin/img/modal/dm-tips-on.png') }}"
                                                   data-image-off="{{ asset('/public/assets/admin/img/modal/dm-tips-off.png') }}"
                                                   data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.security_deposit') }}?</strong>"
                                                   data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.security_deposit') }}?</strong>"
                                                   data-text-on="<p>{{ translate('messages.If_enabled_delivery_men_must_pay_security_deposit_before_they_can_start_working') }}</p>"
                                                   data-text-off="<p>{{ translate('messages.If_disabled_delivery_men_can_start_working_without_paying_security_deposit') }}</p>"
                                                   class="status toggle-switch-input dynamic-checkbox-toggle"
                                                   value="1"
                                                name="security_deposit_enabled" id="security_deposit_enabled"
                                                {{ $security_deposit_enabled == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-sm-6 col-lg-4">
                                    @php($security_deposit_amount = \App\Models\BusinessSetting::where('key', 'security_deposit_amount')->first())
                                    <div class="form-group mb-0">
                                        <label class="form-label text-capitalize"
                                            for="security_deposit_amount">
                                            <div class="d-flex align-items-center">
                                                <span class="line--limit-1 flex-grow">{{ translate('messages.security_deposit_amount') }} ({{ \App\CentralLogics\Helpers::currency_symbol() }})</span>
                                                <small class="text-danger d-flex align-items-center mt-1"> *
                                                    <span class="form-label-secondary"
                                                        data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('messages.amount_delivery_men_must_pay_as_security_deposit') }}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                            alt="{{ translate('messages.security_deposit_amount') }}"></span>
                                                </small>
                                            </div>
                                        </label>
                                        <input type="number" name="security_deposit_amount" class="form-control"
                                            id="security_deposit_amount" min="0" step="0.01"
                                            value="{{ $security_deposit_amount ? $security_deposit_amount->value : 500 }}"
                                            {{ $security_deposit_enabled == 1 ? 'required' : '' }}>
                                    </div>
                                </div>

                            </div>
                            <div class="btn--container justify-content-end mt-5">
                                <button type="reset" id="reset_btn" class="btn btn--reset location-reload">{{ translate('messages.reset') }}</button>
                                <button type="submit" id="submit" class="btn btn--primary">{{ translate('messages.save_information') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

