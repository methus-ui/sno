<!DOCTYPE html>
<?php
    $store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first();
    $business_name = \App\Models\BusinessSetting::where(['key' => 'business_name'])->first();
?>

<html dir="{{ $site_direction }}" lang="{{ $locale }}" class="{{ $site_direction === 'rtl'?'active':'' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{translate('messages.verify_otp')}} | {{ $business_name?->value ?? 'SnowCart' }}</title>
    <link rel="shortcut icon" href="{{asset('public/favicon.ico')}}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">

    <style>
        :root {
            --primary: #D82E5E;
            --primary-dark: #B8254A;
            --text-dark: #1a1a2e;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg-light: #f9fafb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            background: #fff;
        }

        .brand-side {
            width: 42%;
            min-height: 100vh;
            background: linear-gradient(160deg, #1a1a2e 0%, #16213e 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 50px;
            position: relative;
        }

        .brand-side::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .brand-content { position: relative; z-index: 1; text-align: center; width: 100%; max-width: 400px; }

        .logo-container {
            background: #fff;
            border-radius: 20px;
            padding: 24px 32px;
            margin-bottom: 40px;
            display: inline-block;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .logo-container img { max-width: 200px; height: auto; display: block; }

        .brand-tagline { color: #fff; }
        .brand-tagline h1 { font-size: 32px; font-weight: 700; margin-bottom: 12px; }
        .brand-tagline p { font-size: 16px; color: rgba(255,255,255,0.7); line-height: 1.6; }

        .form-side {
            width: 58%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: var(--bg-light);
        }

        .form-container { width: 100%; max-width: 420px; text-align: center; }

        .form-header { margin-bottom: 32px; }
        .form-header .icon-box {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .form-header .icon-box img { width: 32px; filter: brightness(0) invert(1); }
        .form-header h2 { font-size: 24px; font-weight: 700; color: var(--text-dark); margin-bottom: 10px; }
        .form-header p { font-size: 14px; color: var(--text-muted); line-height: 1.6; }
        .form-header strong { color: var(--text-dark); }

        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .otp-field {
            width: 52px; height: 56px;
            border: 2px solid var(--border);
            border-radius: 10px;
            text-align: center;
            font-size: 24px; font-weight: 700;
            color: var(--text-dark);
            background: #fff;
            transition: all 0.2s;
            outline: none;
        }
        .otp-field:hover { border-color: #d1d5db; }
        .otp-field:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(216, 46, 94, 0.1); }

        .btn-submit {
            width: 100%; height: 50px;
            background: var(--primary); border: none; border-radius: 10px;
            color: #fff; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            margin-bottom: 24px;
        }
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(216, 46, 94, 0.3);
        }

        .resend-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        .resend-section span { font-size: 14px; color: var(--text-muted); }
        .resend-btn {
            background: none; border: none;
            font-size: 14px; font-weight: 600;
            color: var(--primary);
            cursor: pointer;
        }
        .resend-btn:hover:not(:disabled) { text-decoration: underline; }
        .resend-btn:disabled { color: var(--text-muted); cursor: not-allowed; }

        @media (max-width: 900px) {
            body { flex-direction: column; }
            .brand-side { width: 100%; min-height: auto; padding: 40px 24px; }
            .logo-container { padding: 16px 24px; margin-bottom: 24px; }
            .logo-container img { max-width: 150px; }
            .brand-tagline h1 { font-size: 22px; }
            .form-side { width: 100%; min-height: auto; padding: 30px 20px; }
        }

        @media (max-width: 480px) {
            .otp-inputs { gap: 8px; }
            .otp-field { width: 44px; height: 48px; font-size: 20px; }
            .resend-section { flex-direction: column; gap: 12px; }
        }
    </style>
</head>

<body>
    <div class="brand-side">
        <div class="brand-content">
            <div class="logo-container">
                <img class="onerror-image" data-onerror-image="{{asset('/public/assets/admin/img/favicon.png')}}"
                    src="{{\App\CentralLogics\Helpers::get_full_url('business', $store_logo?->value ?? '', $store_logo?->storage[0]?->value ?? 'public','favicon')}}"
                    alt="{{ $business_name?->value ?? 'SnowCart' }}">
            </div>
            <div class="brand-tagline">
                <h1>{{ $business_name?->value ?? 'SnowCart' }}</h1>
                <p>{{translate('The Everything App - Your Security, Our Priority')}}</p>
            </div>
        </div>
    </div>

    <div class="form-side">
        <div class="form-container">
            <div class="form-header">
                <div class="icon-box">
                    <img src="{{asset('/public/assets/admin/img/lock.svg')}}" alt="lock">
                </div>
                <h2>{{translate('Verify Your Identity')}}</h2>
                <p>
                    {{translate('a_5_digit_verification_code_has_been')}}<br>
                    {{translate('sent_to')}} <strong>{{ substr($admin->phone, 0, 3) . str_repeat('X', strlen($admin->phone) - 5) . substr($admin->phone, -2) }}</strong>
                </p>
            </div>

            <form action="{{ route('verify-otp') }}" method="POST" class="otp-form">
                @csrf
                <input type="hidden" name="reset_token" value="{{ $token }}" id="reset_token">
                <input type="hidden" name="phone" value="{{ $admin->phone }}">

                <div class="otp-inputs">
                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1" autocomplete="off">
                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1" autocomplete="off">
                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1" autocomplete="off">
                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1" autocomplete="off">
                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1" autocomplete="off">
                </div>

                <input class="otp-value" type="hidden" name="opt-value">
                <button type="submit" class="btn-submit">{{translate('Verify')}}</button>
            </form>

            <div class="resend-section">
                <span>{{translate('Didn`t receive the code?')}}</span>
                <button class="resend-btn otp_resend" disabled id="otp-button">{{translate('Resend_it')}}</button>
            </div>
        </div>
    </div>

    <script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
    {!! Toastr::message() !!}

    @if ($errors->any())
        <script>
            @foreach($errors->all() as $error)
            toastr.error('{{$error}}', Error, { CloseButton: true, ProgressBar: true });
            @endforeach
        </script>
    @endif

    <script>
        $('.otp_resend').on('click', function() {
            $.ajax({
                url: "{{ route('otp_resent') }}",
                type: "GET",
                dataType: 'json',
                data: { "token": $('#reset_token').val() },
                success: function(data) {
                    if (data.errors == 'link_expired') toastr.error('{{translate('Link_expired')}}');
                    if (data.otp_fail == 'otp_fail') toastr.error('{{translate('Failed_to_sent_otp')}}');
                    if (data.success == 'otp_send') { startCountdown(); toastr.success('{{translate('Otp_successfull_sent')}}'); }
                }
            });
        });

        $(document).ready(function() {
            $('.onerror-image').on('error', function() {
                $(this).attr('src', $(this).data('onerror-image'));
            });

            $(".otp-form *:input[type!=hidden]:first").focus();
            let otp_fields = $(".otp-form .otp-field"),
                otp_value_field = $(".otp-form .otp-value");
            otp_fields
                .on("input", function() {
                    $(this).val($(this).val().replace(/[^0-9]/g, ""));
                    let opt_value = "";
                    otp_fields.each(function() {
                        if ($(this).val() != "") opt_value += $(this).val();
                    });
                    otp_value_field.val(opt_value);
                })
                .on("keyup", function(e) {
                    let key = e.keyCode || e.charCode;
                    if (key == 8 || key == 46 || key == 37 || key == 40) $(this).prev().focus();
                    else if (key == 38 || key == 39 || $(this).val() != "") $(this).next().focus();
                })
                .on("paste", function(e) {
                    let paste_data = e.originalEvent.clipboardData.getData("text").split("");
                    $.each(paste_data, function(index, value) { otp_fields.eq(index).val(value); });
                });

            let otpButton = $("#otp-button");
            function startCountdown() {
                otpButton.prop("disabled", true);
                let countdown = 30;
                let timer = setInterval(function() {
                    otpButton.text("Resend it (" + countdown + ")");
                    countdown--;
                    if (countdown < 0) {
                        clearInterval(timer);
                        otpButton.prop("disabled", false);
                        otpButton.text("Resend it");
                    }
                }, 1000);
            }
            startCountdown();
        });
    </script>
</body>
</html>
