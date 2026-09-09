<!DOCTYPE html>
<?php
    $store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first();
    $business_name = \App\Models\BusinessSetting::where(['key' => 'business_name'])->first();
?>

<html dir="{{ $site_direction }}" lang="{{ $locale }}" class="{{ $site_direction === 'rtl'?'active':'' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{translate('messages.reset_password')}} | {{ $business_name?->value ?? 'SnowCart' }}</title>
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

        .form-container { width: 100%; max-width: 420px; }

        .form-header { text-align: center; margin-bottom: 32px; }
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
        .form-header h2 { font-size: 24px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px; }
        .form-header p { font-size: 14px; color: var(--text-muted); }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;
        }
        .form-label img { width: 14px; opacity: 0.5; }

        .input-wrapper { position: relative; }

        .form-input {
            width: 100%; height: 48px; padding: 0 16px;
            font-size: 14px; color: var(--text-dark);
            background: #fff; border: 1.5px solid var(--border);
            border-radius: 10px; transition: all 0.2s; outline: none;
        }
        .form-input:hover { border-color: #d1d5db; }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(216, 46, 94, 0.1); }
        .input-wrapper .form-input { padding-right: 48px; }

        .toggle-password {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--text-muted);
            cursor: pointer; padding: 4px; font-size: 18px; text-decoration: none;
        }
        .toggle-password:hover { color: var(--primary); }

        .btn-submit {
            width: 100%; height: 50px;
            background: var(--primary); border: none; border-radius: 10px;
            color: #fff; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(216, 46, 94, 0.3);
        }

        .back-link {
            text-align: center; margin-top: 24px;
            font-size: 14px; color: var(--text-muted);
        }
        .back-link a { color: var(--primary); font-weight: 600; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }

        @media (max-width: 900px) {
            body { flex-direction: column; }
            .brand-side { width: 100%; min-height: auto; padding: 40px 24px; }
            .logo-container { padding: 16px 24px; margin-bottom: 24px; }
            .logo-container img { max-width: 150px; }
            .brand-tagline h1 { font-size: 22px; }
            .form-side { width: 100%; min-height: auto; padding: 30px 20px; }
        }

        html[dir="rtl"] .toggle-password { right: auto; left: 14px; }
        html[dir="rtl"] .input-wrapper .form-input { padding-right: 16px; padding-left: 48px; }
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
                <p>{{translate('The Everything App - Empowering Your Digital Journey')}}</p>
            </div>
        </div>
    </div>

    <div class="form-side">
        <div class="form-container">
            <div class="form-header">
                <div class="icon-box">
                    <img src="{{asset('/public/assets/admin/img/lock.svg')}}" alt="lock">
                </div>
                <h2>{{translate('Reset Password')}}</h2>
                <p>{{translate('Enter your new password below')}}</p>
            </div>

            <form action="{{ route('reset-password-submit') }}" method="POST">
                @csrf
                <input type="hidden" name="reset_token" value="{{ $token }}">

                <div class="form-group">
                    <label class="form-label">
                        {{translate('New Password')}}
                        <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                            data-toggle="tooltip" data-placement="right"
                            title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}" alt="">
                    </label>
                    <div class="input-wrapper">
                        <input type="password" class="form-input" name="password" id="newPassword"
                            placeholder="{{translate('messages.password_length_placeholder',['length'=>'6+'])}}" required>
                        <a href="javascript:" class="toggle-password"><i class="tio-visible-outlined"></i></a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">{{translate('Confirm Password')}}</label>
                    <div class="input-wrapper">
                        <input type="password" class="form-input" name="confirm_password" id="confirmPassword"
                            placeholder="{{translate('messages.password_length_placeholder',['length'=>'6+'])}}" required>
                        <a href="javascript:" class="toggle-password"><i class="tio-visible-outlined"></i></a>
                    </div>
                </div>

                <button type="submit" class="btn-submit">{{translate('Change Password')}}</button>
            </form>

            <div class="back-link">
                {{translate('Remember your password?')}} <a href="{{route('login')}}">{{translate('Back to Login')}}</a>
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
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
            $('.toggle-password').on('click', function(e) {
                e.preventDefault();
                var input = $(this).siblings('input');
                var icon = $(this).find('i');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('tio-visible-outlined').addClass('tio-hidden-outlined');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('tio-hidden-outlined').addClass('tio-visible-outlined');
                }
            });
            $('.onerror-image').on('error', function() {
                $(this).attr('src', $(this).data('onerror-image'));
            });
        });
    </script>
</body>
</html>
