<!DOCTYPE html>
<?php
    if (env('APP_MODE') == 'demo') {
        $site_direction = session()->get('site_direction_vendor');
    }else{
        $site_direction = session()->has('vendor_site_direction')?session()->get('vendor_site_direction'):'ltr';
    }
    $country=\App\Models\BusinessSetting::where('key','country')->first();
$countryCode= strtolower($country?$country->value:'auto');
?>
{{-- {{ dd($countryCode) }} --}}
<html dir="{{ $site_direction }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}"  class="{{ $site_direction === 'rtl'?'active':'' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title>@yield('title')</title>
    <!-- Favicon -->
    @php($logo=\App\Models\BusinessSetting::where(['key'=>'icon'])->first())
    <link rel="shortcut icon" href="">
    <link rel="icon" type="image/x-icon" href="{{\App\CentralLogics\Helpers::get_full_url('business', $logo?->value?? '', $logo?->storage[0]?->value ?? 'public','favicon')}}">
    <!-- Font -->
    <link href="{{asset('public/assets/admin/css/fonts.css')}}" rel="stylesheet">
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/theme.minc619.css?v=1.0">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/emogi-area.css')}}">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/style.css')}}">
    <link rel="stylesheet" href="{{asset('public/assets/admin/intltelinput/css/intlTelInput.css')}}">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/owl.min.css')}}">
    @stack('css_or_js')

    <script src="{{asset('public/assets/admin')}}/vendor/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js"></script>
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">

    <style>
        /* Order Notification Modal Styles */
        #popup-modal .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
        }
        #popup-modal .modal-content {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        #order-notification-content .table {
            margin-bottom: 0;
        }
        #order-notification-content .table th {
            border-top: none;
            font-weight: 600;
            color: #1e1e2d;
        }
        #order-notification-content .table td {
            vertical-align: middle;
        }
        .check-order {
            padding: 12px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .check-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        @keyframes pulse-ring {
            0% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(102, 126, 234, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }
        #popup-modal.show .modal-dialog {
            animation: pulse-ring 2s infinite;
        }
    </style>
</head>

<body class="footer-offset">
    @if (env('APP_MODE')=='demo')
    <div class="direction-toggle">
        <i class="tio-settings"></i>
        <span></span>
    </div>
    @endif
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="loading" class="initial-hidden">
                <div class="loading-inner">
                    <img width="200" src="{{asset('public/assets/admin/img/loader.gif')}}">
                </div>
            </div>
        </div>
    </div>
</div>
{{--loader--}}

<!-- Builder -->
@include('layouts.vendor.partials._front-settings')
<!-- End Builder -->

<!-- JS Preview mode only -->
@include('layouts.vendor.partials._header')
@include('layouts.vendor.partials._sidebar')
<!-- END ONLY DEV -->

<main id="content" role="main" class="main pointer-event">
    <!-- Content -->
@yield('content')

<!-- End Content -->

    <!-- Footer -->
@include('layouts.vendor.partials._footer')
<!-- End Footer -->


    {{-- Keyboard Shortcuts Modal --}}
    <div class="modal fade" id="keyboardShortcutsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="tio-keyboard"></i> {{ translate('Keyboard Shortcuts') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm">
                            <thead>
                                <tr>
                                    <th width="30%">{{ translate('Key') }}</th>
                                    <th>{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><kbd class="bg-dark text-white px-2 py-1 rounded">K</kbd></td>
                                    <td>{{ translate('Navigate to All Orders') }}</td>
                                </tr>
                                <tr>
                                    <td><kbd class="bg-dark text-white px-2 py-1 rounded">O</kbd></td>
                                    <td>{{ translate('Navigate to All Orders') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-soft-info mt-3 mb-0">
                        <i class="tio-info"></i>
                        <small>{{ translate('Shortcuts are disabled when typing in input fields') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        {{ translate('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="toggle-modal">
        <div class="modal-dialog status-warning-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true" class="tio-clear"></span>
                    </button>
                </div>
                <div class="modal-body pb-5 pt-0">
                    <div class="max-349 mx-auto mb-20">
                        <div>
                            <div class="text-center">
                                <img id="toggle-image" alt="" class="mb-20">
                                <h5 class="modal-title" id="toggle-title"></h5>
                            </div>
                            <div class="text-center" id="toggle-message">
                            </div>
                        </div>
                        <div class="btn--container justify-content-center">
                            <button type="button" id="toggle-ok-button" class="btn btn--primary min-w-120 confirm-Toggle" data-dismiss="modal">{{translate('Ok')}}</button>
                            <button id="reset_btn" type="reset" class="btn btn--cancel min-w-120" data-dismiss="modal">
                                {{translate("Cancel")}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="toggle-status-modal">
        <div class="modal-dialog status-warning-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true" class="tio-clear"></span>
                    </button>
                </div>
                <div class="modal-body pb-5 pt-0">
                    <div class="max-349 mx-auto mb-20">
                        <div>
                            <div class="text-center">
                                <img id="toggle-status-image" alt="" class="mb-20">
                                <h5 class="modal-title" id="toggle-status-title"></h5>
                            </div>
                            <div class="text-center" id="toggle-status-message">
                            </div>
                        </div>
                        <div class="btn--container justify-content-center">
                            <button type="button" id="toggle-status-ok-button" class="btn btn--primary min-w-120 confirm-Status-Toggle" data-dismiss="modal">{{translate('Ok')}}</button>
                            <button id="reset_btn" type="reset" class="btn btn--cancel min-w-120" data-dismiss="modal">
                                {{translate("Cancel")}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="popup-modal">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="tio-shopping-cart-outlined"></i> {{translate('messages.You have new order, Check Please.')}}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div id="order-notification-content">
                                <!-- Order details will be populated here -->
                            </div>
                            <hr>
                            <div class="text-center">
                                <button class="btn btn-primary btn-lg check-order">
                                    <i class="tio-checkmark-circle"></i> {{translate('messages.Ok, let me check')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="new-dynamic-submit-model">
        <div class="modal-dialog modal-dialog-centered status-warning-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true" class="tio-clear"></span>
                    </button>
                </div>
                <div class="modal-body pb-5 pt-0">
                    <div class="max-349 mx-auto mb-20">
                        <div>
                            <div class="text-center">
                                <img id="image-src" class="mb-20">
                                <h5 class="modal-title" id="toggle-title"></h5>
                            </div>
                            <div class="text-center" id="toggle-message">
                                <h3 id="modal-title"></h3>
                                <div id="modal-text"></div>
                            </div>

                            </div>
                            <div class="mb-4 d-none" id="note-data">
                                <textarea class="form-control" placeholder="{{ translate('your_note_here') }}" id="get-text-note" cols="5" ></textarea>
                            </div>
                        <div class="btn--container justify-content-center">
                            <div id="hide-buttons">
                                <div class="d-flex justify-content-center flex-wrap gap-3">
                                    <button data-dismiss="modal" id="cancel_btn_text" class="btn btn--cancel min-w-120" >{{translate("Not_Now")}}</button>
                                    <button type="button" id="new-dynamic-ok-button" class="btn btn-primary confirm-model min-w-120">{{translate('Yes')}}</button>
                                </div>
                            </div>

                            <button data-dismiss="modal"  type="button" id="new-dynamic-ok-button-show" class="btn btn--primary  d-none min-w-120">{{translate('Okay')}}</button>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>
<!-- ========== END MAIN CONTENT ========== -->

<!-- ========== END SECONDARY CONTENTS ========== -->
<script src="{{asset('public/assets/admin')}}/js/custom.js"></script>
<script src="{{asset('public/assets/admin')}}/js/firebase.min.js"></script>
<!-- JS Implementing Plugins -->

@stack('script')

<!-- JS Front -->
<script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/sweet_alert.js"></script>
<script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
<script src="{{asset('public/assets/admin')}}/js/emogi-area.js"></script>
<script src="{{asset('public/assets/admin/js/owl.min.js')}}"></script>
<script src="{{asset('public/assets/admin/js/app-blade/vendor.js')}}"></script>
{!! Toastr::message() !!}
<script src="{{asset('public/assets/admin/intltelinput/js/intlTelInput.min.js')}}"></script>

@if ($errors->any())

<script>
    "use strict";
    @foreach ($errors->all() as $error)
    toastr.error('{{ translate($error) }}', Error, {
        CloseButton: true,
        ProgressBar: true
    });
    @endforeach
</script>
@endif

@stack('script_2')
{{-- Image Editor --}}
<script src="{{asset('public/assets/admin/js/image-editor.js')}}"></script>
@include('layouts.partials._image-editor-modal')
<audio id="myAudio" loop>
    <source src="{{asset('public/assets/admin/sound/notification.mp3')}}" type="audio/mpeg">
</audio>
    <script src="{{asset('public/assets/admin/js/view-pages/common.js')}}"></script>

<script>
    var audio = document.getElementById("myAudio");
    var isRinging = false;

    function playAudio() {
        if (!isRinging) {
            isRinging = true;
            audio.loop = true;
            audio.play().catch(function(error) {
                console.log('Audio play failed:', error);
            });
        }
    }

    function pauseAudio() {
        isRinging = false;
        audio.pause();
        audio.currentTime = 0;
    }

    // Desktop notification support
    function showDesktopNotification(title, body) {
        if (!("Notification" in window)) {
            return;
        }

        if (Notification.permission === "granted") {
            new Notification(title, {
                body: body,
                icon: '{{asset("public/assets/admin/img/favicon.png")}}',
                badge: '{{asset("public/assets/admin/img/favicon.png")}}',
                tag: 'new-order',
                requireInteraction: true
            });
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(function (permission) {
                if (permission === "granted") {
                    new Notification(title, {
                        body: body,
                        icon: '{{asset("public/assets/admin/img/favicon.png")}}',
                        badge: '{{asset("public/assets/admin/img/favicon.png")}}',
                        tag: 'new-order',
                        requireInteraction: true
                    });
                }
            });
        }
    }

    // Request notification permission on page load
    if ("Notification" in window && Notification.permission === "default") {
        Notification.requestPermission();
    }
"use strict";


    $(window).on('load', function(){
        $('main > .container-fluid.content').prepend($('#renew-badge'));
    })



    $(document).on('ready', function(){
        // $('body').css('overflow','')
        $(".direction-toggle").on("click", function () {
            if($('html').hasClass('active')){
                $('html').removeClass('active')
                setDirection(1);
            }else {
                setDirection(0);
                $('html').addClass('active')
            }
        });
        if ($('html').attr('dir') === "rtl") {
            $(".direction-toggle").find('span').text('Toggle LTR')
        } else {
            $(".direction-toggle").find('span').text('Toggle RTL')
        }

        function setDirection(status) {
            if (status === 1) {
                $("html").attr('dir', 'ltr');
                $(".direction-toggle").find('span').text('Toggle RTL')
            } else {
                $("html").attr('dir', 'rtl');
                $(".direction-toggle").find('span').text('Toggle LTR')
            }
            $.get({
                    url: '{{ route('vendor.site_direction') }}',
                    dataType: 'json',
                    data: {
                        status: status,
                    },
                    success: function() {
                    },

                });
            }
        });


    function route_alert(route, message) {
        Swal.fire({
            title: '{{ translate('messages.Are you sure?') }}',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#FC6A57',
            cancelButtonText: '{{ translate('messages.no') }}',
            confirmButtonText: '{{ translate('messages.Yes') }}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                location.href = route;
            }
        })
    }

    $('.form-alert').on('click',function (){
        let id = $(this).data('id')
        let message = $(this).data('message')
        Swal.fire({
            title: '{{ translate('messages.Are you sure?') }}',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#FC6A57',
            cancelButtonText: '{{ translate('messages.no') }}',
            confirmButtonText: '{{ translate('messages.Yes') }}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $('#'+id).submit()
            }
        })
    })


    function set_filter(url, id, filter_by) {
        let nurl = new URL(url);
        nurl.searchParams.set(filter_by, id);
        location.href = nurl;
    }

    @php($fcm_credentials = \App\CentralLogics\Helpers::get_business_settings('fcm_credentials'))
    let firebaseConfig = {
        apiKey: "{{isset($fcm_credentials['apiKey']) ? $fcm_credentials['apiKey'] : ''}}",
        authDomain: "{{isset($fcm_credentials['authDomain']) ? $fcm_credentials['authDomain'] : ''}}",
        projectId: "{{isset($fcm_credentials['projectId']) ? $fcm_credentials['projectId'] : ''}}",
        storageBucket: "{{isset($fcm_credentials['storageBucket']) ? $fcm_credentials['storageBucket'] : ''}}",
        messagingSenderId: "{{isset($fcm_credentials['messagingSenderId']) ? $fcm_credentials['messagingSenderId'] : ''}}",
        appId: "{{isset($fcm_credentials['appId']) ? $fcm_credentials['appId'] : ''}}",
        measurementId: "{{isset($fcm_credentials['measurementId']) ? $fcm_credentials['measurementId'] : ''}}"
    };
    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();
{{--    function startFCM() {--}}

{{--messaging--}}
{{--    .requestPermission()--}}
{{--    .then(function () {--}}
{{--        return messaging.getToken()--}}

{{--    }).then(function (response) {--}}
{{--        @php($store_id=\App\CentralLogics\Helpers::get_store_id())--}}
{{--        subscribeTokenToTopic(response, "store_panel_{{$store_id}}_message");--}}
{{--    }).catch(function (error) {--}}
{{--        console.log(error);--}}
{{--    });--}}
{{--}--}}

{{--@php($key = \App\Models\BusinessSetting::where('key', 'push_notification_key')->first())--}}
{{--function subscribeTokenToTopic(token, topic) {--}}
{{--fetch('https://iid.googleapis.com/iid/v1/' + token + '/rel/topics/' + topic, {--}}
{{--    method: 'POST',--}}
{{--    headers: new Headers({--}}
{{--        'Authorization': 'key={{ $key ? $key->value : '' }}'--}}
{{--    })--}}
{{--}).then(response => {--}}
{{--    if (response.status < 200 || response.status >= 400) {--}}
{{--        throw 'Error subscribing to topic: ' + response.status + ' - ' + response.text();--}}
{{--    }--}}
{{--    console.log('Subscribed to "' + topic + '"');--}}
{{--}).catch(error => {--}}
{{--    console.error(error);--}}
{{--})--}}
{{--}--}}

    function startFCM() {
        messaging
            .requestPermission()
            .then(function() {
                return messaging.getToken();
            })
            .then(function(token) {
                @php($store_id=\App\CentralLogics\Helpers::get_store_id())
                // Send the token to your backend to subscribe to topic
                subscribeTokenToBackend(token, 'store_panel_{{$store_id}}_message');
            }).catch(function(error) {
            console.error('Error getting permission or token:', error);
        });
    }

    function subscribeTokenToBackend(token, topic) {
        fetch('{{url('/')}}/subscribeToTopic', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ token: token, topic: topic })
        }).then(response => {
            if (response.status < 200 || response.status >= 400) {
                return response.text().then(text => {
                    throw new Error(`Error subscribing to topic: ${response.status} - ${text}`);
                });
            }
            console.log(`Subscribed to "${topic}"`);
        }).catch(error => {
            console.error('Subscription error:', error);
        });
    }
    function getUrlParameter(sParam) {
            let sPageURL = window.location.search.substring(1);
            let sURLletiables = sPageURL.split('&');
            for (let i = 0; i < sURLletiables.length; i++) {
                let sParameterName = sURLletiables[i].split('=');
                if (sParameterName[0] === sParam) {
                    return sParameterName[1];
                }
            }
        }

        function conversationList() {
            $.ajax({
                url: "{{ route('vendor.message.list') }}",
                success: function(data) {
                    $('#conversation-list').empty();
                    $("#conversation-list").append(data.html);
                    let user_id = getUrlParameter('user');
                    $('.customer-list').removeClass('conv-active');
                    $('#customer-' + user_id).addClass('conv-active');
                }
            })
        }

        function conversationView() {
            let conversation_id = getUrlParameter('conversation');
            let user_id = getUrlParameter('user');
            let url= '{{url('/')}}/store-panel/message/view/'+conversation_id+'/' + user_id;
            $.ajax({
                url: url,
                success: function(data) {
                    $('#view-conversation').html(data.view);
                }
            })
        }
        @php($order_notification_type = \App\Models\BusinessSetting::where('key', 'order_notification_type')->first())
        @php($order_notification_type = $order_notification_type ? $order_notification_type->value : 'firebase')
        let order_type = 'all';
        messaging.onMessage(function (payload) {
            if(payload.data.order_id && payload.data.type === 'new_order'){
                @if(\App\CentralLogics\Helpers::employee_module_permission_check('order') && $order_notification_type == 'firebase')
                    order_type = payload.data.order_type;

                    // Fetch order details
                    $.get({
                        url: '{{route('vendor.get-store-data')}}',
                        dataType: 'json',
                        success: function (response) {
                            let data = response.data;
                            if (data.recent_orders && data.recent_orders.length > 0) {
                                displayOrderNotification(data.recent_orders, order_type, 1);
                            } else {
                                $('#order-notification-content').html('<div class="alert alert-info">New order #' + payload.data.order_id + ' received!</div>');
                            }
                        }
                    });

                    playAudio();
                    $('#popup-modal').appendTo("body").modal('show');
                @endif
            }else if(payload.data.type === 'message'){
                if (window.location.href.includes('message/list?conversation')) {
                    let conversation_id = getUrlParameter('conversation');
                    let user_id = getUrlParameter('user');
                    let url = '{{url('/')}}/store-panel/message/view/' + conversation_id + '/' + user_id;
                    $.ajax({
                        url: url,
                        success: function (data) {
                            $('#view-conversation').html(data.view);
                        }
                    })
                }
                toastr.success('{{ translate('messages.New message arrived') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                if($('#conversation-list').scrollTop() === 0){
                    conversationList();
                }
            }
        });

        @if(\App\CentralLogics\Helpers::employee_module_permission_check('order') && $order_notification_type == 'manual')
        setInterval(function () {
            $.get({
                url: '{{route('vendor.get-store-data')}}',
                dataType: 'json',
                success: function (response) {
                    let data = response.data;
                    if (data.new_pending_order > 0) {
                        order_type = 'pending';
                        displayOrderNotification(data.recent_orders, 'pending', data.new_pending_order);
                        playAudio();
                        $('#popup-modal').appendTo("body").modal('show');

                        // Show desktop notification
                        showDesktopNotification('New Pending Order!',
                            'You have ' + data.new_pending_order + ' new pending order(s). Check please!');
                    }
                    else if(data.new_confirmed_order > 0)
                    {
                        order_type = 'confirmed';
                        displayOrderNotification(data.recent_orders, 'confirmed', data.new_confirmed_order);
                        playAudio();
                        $('#popup-modal').appendTo("body").modal('show');

                        // Show desktop notification
                        showDesktopNotification('New Confirmed Order!',
                            'You have ' + data.new_confirmed_order + ' new confirmed order(s). Check please!');
                    }
                },
            });
        }, 10000);
        @endif

        // Function to display order details in notification
        function displayOrderNotification(orders, status, count) {
            let content = '<div class="alert alert-' + (status === 'pending' ? 'warning' : 'success') + ' mb-3">';
            content += '<h4 class="mb-0"><i class="tio-bell"></i> ' + count + ' New ' + status.toUpperCase() + ' Order(s)!</h4>';
            content += '</div>';

            if (orders && orders.length > 0) {
                content += '<div class="table-responsive">';
                content += '<table class="table table-bordered">';
                content += '<thead class="thead-light">';
                content += '<tr><th>Order ID</th><th>Items</th><th>Amount</th></tr>';
                content += '</thead><tbody>';

                orders.forEach(function(order) {
                    if (order.order_status === status || (status === 'confirmed' && (order.order_status === 'accepted' || order.order_status === 'confirmed'))) {
                        content += '<tr>';
                        content += '<td><strong>#' + order.id + '</strong></td>';
                        content += '<td>';

                        // Show first 3 items
                        let displayItems = order.items.slice(0, 3);
                        displayItems.forEach(function(item, index) {
                            content += '<div class="mb-1">';
                            content += '<i class="tio-circle-filled text-primary" style="font-size: 6px;"></i> ';
                            content += '<strong>' + item.name + '</strong> × ' + item.quantity;
                            content += '</div>';
                        });

                        if (order.items.length > 3) {
                            content += '<small class="text-muted">+ ' + (order.items.length - 3) + ' more items</small>';
                        }

                        content += '</td>';
                        content += '<td class="text-right"><strong>৳' + parseFloat(order.order_amount).toFixed(2) + '</strong></td>';
                        content += '</tr>';
                    }
                });

                content += '</tbody></table>';
                content += '</div>';
            }

            $('#order-notification-content').html(content);
        }

        $('.check-order').on('click',function (){
            pauseAudio(); // Stop the ringing sound
            if(order_type){
                location.href = '{{url('/')}}/store-panel/order/list/'+order_type;
            }
        });

        // Stop sound when modal is closed
        $('#popup-modal').on('hidden.bs.modal', function () {
            pauseAudio();
        });
        startFCM();
        conversationList();
        if(getUrlParameter('conversation')){
            conversationView();
        }


    const inputs = document.querySelectorAll('input[type="tel"]');
            inputs.forEach(input => {
                window.intlTelInput(input, {
                    initialCountry: "{{$countryCode}}",
                    utilsScript: "{{ asset('public/assets/admin/intltelinput/js/utils.js') }}",
                    autoInsertDialCode: true,
                    nationalMode: false,
                    formatOnDisplay: false,
                });
            });


            function keepNumbersAndPlus(inputString) {
                let regex = /[0-9+]/g;
                let filteredString = inputString.match(regex);
            return filteredString ? filteredString.join('') : '';
            }

            $(document).on('keyup', 'input[type="tel"]', function () {
                $(this).val(keepNumbersAndPlus($(this).val()));
                });


</script>

<!-- IE Support -->
<script>
    if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write('<script src="{{asset('public/assets/admin')}}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
</script>

{{-- Keyboard Shortcuts --}}
<script>
    // Global keyboard shortcuts
    document.addEventListener('keydown', function(event) {
        // Don't trigger if user is typing in an input field, textarea, or contenteditable
        const activeElement = document.activeElement;
        const isTyping = activeElement && (
            activeElement.tagName === 'INPUT' ||
            activeElement.tagName === 'TEXTAREA' ||
            activeElement.isContentEditable
        );

        if (!isTyping) {
            // K - Navigate to All Orders (current module)
            if (event.key === 'k' || event.key === 'K') {
                event.preventDefault();
                window.location.href = '{{ route('vendor.order.list', ['all']) }}';
            }

            // O - Navigate to All Orders (alternative shortcut)
            if (event.key === 'o' || event.key === 'O') {
                event.preventDefault();
                window.location.href = '{{ route('vendor.order.list', ['all']) }}';
            }
        }
    });
</script>

</body>
</html>
