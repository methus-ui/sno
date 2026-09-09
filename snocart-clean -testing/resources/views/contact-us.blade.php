@extends('layouts.landing.app')

@section('title', translate('messages.contact_us'))

@push('css_or_js')
<style>
    .page-hero {
        padding: 80px 0 60px;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 50%, #fef2f6 100%);
        position: relative;
        overflow: hidden;
    }
    .page-hero::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(214,27,102,0.06) 0%, transparent 70%);
        border-radius: 50%;
    }
    .page-hero-inner {
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
    }
    .page-hero h1 {
        font-size: 42px;
        font-weight: 800;
        color: #0a0a0a;
        margin-bottom: 20px;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }
    .page-hero .lead {
        font-size: 18px;
        color: #666;
        line-height: 1.7;
        max-width: 650px;
        margin: 0 auto;
    }
    .since-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        background: linear-gradient(135deg, rgba(214,27,102,0.1), rgba(233,74,127,0.05));
        border: 1px solid rgba(214,27,102,0.2);
        border-radius: 999px;
        color: var(--base-1, #d61b66);
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .contact-body {
        padding: 60px 0 80px;
        background: #fff;
    }

    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1.6fr;
        gap: 40px;
        max-width: 1100px;
        margin: 0 auto;
    }

    .contact-info-card {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 20px;
        background: #fafafa;
        border-radius: 14px;
        border: 1px solid #e5e5e5;
        margin-bottom: 16px;
        transition: all 0.3s ease;
    }
    .contact-info-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.06);
        border-color: var(--base-1, #d61b66);
    }
    .contact-info-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 18px;
        color: white;
    }
    .contact-info-icon.pink { background: linear-gradient(135deg, var(--base-1, #d61b66), var(--base-2, #e94a7f)); }
    .contact-info-icon.green { background: linear-gradient(135deg, #00aa6d, #00d68f); }
    .contact-info-icon.blue { background: linear-gradient(135deg, #0096ff, #4db8ff); }
    .contact-info-icon.orange { background: linear-gradient(135deg, #ff7500, #ffa040); }

    .contact-info-text h4 {
        font-size: 15px;
        font-weight: 700;
        color: #0a0a0a;
        margin: 0 0 4px;
    }
    .contact-info-text p, .contact-info-text a {
        font-size: 14px;
        color: #666;
        margin: 0;
        text-decoration: none;
        line-height: 1.5;
    }
    .contact-info-text a:hover { color: var(--base-1, #d61b66); }

    .contact-form-box {
        background: #fafafa;
        border: 1px solid #e5e5e5;
        border-radius: 20px;
        padding: 36px;
    }
    .contact-form-box h3 {
        font-size: 22px;
        font-weight: 700;
        color: #0a0a0a;
        margin-bottom: 6px;
    }
    .contact-form-box .form-subtitle {
        font-size: 14px;
        color: #666;
        margin-bottom: 24px;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    .form-row.full {
        grid-template-columns: 1fr;
    }
    .form-input {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        font-size: 15px;
        color: #0a0a0a;
        background: white;
        transition: border-color 0.2s;
        font-family: inherit;
        outline: none;
    }
    .form-input:focus {
        border-color: var(--base-1, #d61b66);
        box-shadow: 0 0 0 3px rgba(214,27,102,0.08);
    }
    .form-input::placeholder { color: #aaa; }
    textarea.form-input { min-height: 130px; resize: vertical; }

    .captcha-box {
        background: #f0f0f0;
        border: 1px dashed #ccc;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
    }
    .captcha-box label {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 12px;
        display: block;
    }
    .captcha-row {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .captcha-row input {
        flex: 1;
    }
    .captcha-row img {
        border-radius: 8px;
        max-height: 50px;
    }

    .btn-submit {
        display: block;
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--base-1, #d61b66), var(--base-2, #e94a7f));
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(214,27,102,0.3);
    }
    .btn-submit i { margin-right: 8px; }

    .btn-loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }
    .btn-loading::after {
        content: "";
        position: absolute;
        width: 20px; height: 20px;
        top: 50%; left: 50%;
        margin: -10px 0 0 -10px;
        border: 2px solid white;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 768px) {
        .page-hero { padding: 50px 0 40px; }
        .page-hero h1 { font-size: 28px; }
        .page-hero .lead { font-size: 15px; }
        .contact-body { padding: 40px 0 60px; }
        .contact-grid { grid-template-columns: 1fr; }
        .contact-form-box { padding: 24px; }
        .form-row { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    @php($contact_us_title = \App\Models\DataSetting::where(['key' => 'contact_us_title'])->first())
    @php($contact_us_title = isset($contact_us_title->value) ? $contact_us_title->value : null)
    @php($contact_us_sub_title = \App\Models\DataSetting::where(['key' => 'contact_us_sub_title'])->first())
    @php($contact_us_sub_title = isset($contact_us_sub_title->value) ? $contact_us_sub_title->value : null)

    <!-- Hero -->
    <section class="page-hero">
        <div class="container">
            <div class="page-hero-inner">
                <div class="since-badge">
                    <i class="fas fa-headset"></i>
                    <span>Here to Help — Since 2021</span>
                </div>
                <h1>{{$contact_us_title ?: translate('messages.contact_us')}}</h1>
                <p class="lead">{{$contact_us_sub_title ?: 'Have questions, feedback, or need support? We\'d love to hear from you. Reach out and we\'ll respond as soon as possible.'}}</p>
            </div>
        </div>
    </section>

    <!-- Contact Body -->
    <section class="contact-body">
        <div class="container">
            <div class="contact-grid">
                <!-- Left: Info cards -->
                <div>
                    <div class="contact-info-card">
                        <div class="contact-info-icon pink"><i class="fas fa-phone"></i></div>
                        <div class="contact-info-text">
                            <h4>{{translate("messages.Call_Us")}}</h4>
                            <a href="tel:{{ \App\CentralLogics\Helpers::get_settings('phone') }}">
                                {{ \App\CentralLogics\Helpers::get_settings('phone') }}
                            </a>
                        </div>
                    </div>

                    <div class="contact-info-card">
                        <div class="contact-info-icon green"><i class="fas fa-envelope"></i></div>
                        <div class="contact-info-text">
                            <h4>{{translate("messages.Email")}}</h4>
                            <a href="mailto:{{ \App\CentralLogics\Helpers::get_settings('email_address') }}">
                                {{ \App\CentralLogics\Helpers::get_settings('email_address') }}
                            </a>
                        </div>
                    </div>

                    <div class="contact-info-card">
                        <div class="contact-info-icon blue"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="contact-info-text">
                            <h4>{{translate("messages.Address")}}</h4>
                            @php($default_location = \App\CentralLogics\Helpers::get_settings('default_location'))
                            <a href="https://www.google.com/maps/search/?api=1&query={{ data_get($default_location,'lat',0)}},{{ data_get($default_location,'lng',0)}}" target="_blank">
                                {{ \App\CentralLogics\Helpers::get_settings('address') }}
                            </a>
                        </div>
                    </div>

                    <div class="contact-info-card">
                        <div class="contact-info-icon orange"><i class="fas fa-clock"></i></div>
                        <div class="contact-info-text">
                            <h4>{{translate("messages.Business_Hours")}}</h4>
                            <p>
                                {{ucfirst(\App\CentralLogics\Helpers::get_settings('opening_day'))}}&ndash;{{ucfirst(\App\CentralLogics\Helpers::get_settings('closing_day'))}}<br>
                                {{date("g:i A", strtotime(\App\CentralLogics\Helpers::get_settings('opening_time')))}}&ndash;{{date("g:i A", strtotime(\App\CentralLogics\Helpers::get_settings('closing_time')))}}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right: Form -->
                <div class="contact-form-box">
                    <h3>{{translate("messages.Send_Message")}}</h3>
                    <p class="form-subtitle">Fill out the form below and we'll get back to you shortly.</p>

                    <form method="post" action="{{route('send-message')}}" id="form-id">
                        @csrf
                        <div class="form-row">
                            <input type="text" required name="name" id="name" placeholder="{{translate('messages.Your_Name')}} *" class="form-input">
                            <input type="email" required name="email" id="email" placeholder="{{translate('messages.Email')}} *" class="form-input">
                        </div>
                        <div class="form-row full">
                            <input type="text" required name="subject" id="subject" placeholder="{{translate('messages.Subject')}} *" class="form-input">
                        </div>
                        <div class="form-row full">
                            <textarea name="message" required id="message" placeholder="{{translate('messages.Message')}} *" class="form-input"></textarea>
                        </div>

                        @php($recaptcha = \App\CentralLogics\Helpers::get_business_settings('recaptcha'))
                        @if(isset($recaptcha) && $recaptcha['status'] == 1)
                            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                        @else
                            <div class="captcha-box">
                                <label><i class="fas fa-shield-alt"></i> {{translate('messages.Security_Verification')}}</label>
                                <div class="captcha-row" id="reload-captcha">
                                    <input type="text" class="form-input" name="custome_recaptcha" id="custome_recaptcha" required placeholder="{{translate('messages.Enter_captcha_value')}}" autocomplete="off" value="{{env('APP_MODE')=='dev'? session('six_captcha'):''}}">
                                    <img src="<?php echo $custome_recaptcha->inline(); ?>" alt="captcha" />
                                </div>
                            </div>
                        @endif

                        <button class="btn-submit" type="submit" id="signInBtn">
                            <i class="fas fa-paper-plane"></i>{{translate("messages.Send_Message")}}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@if(isset($recaptcha) && $recaptcha['status'] == 1)
    <script src="https://www.google.com/recaptcha/api.js?render={{$recaptcha['site_key']}}"></script>
@endif

@push('scripts')
<script>
$(document).ready(function() {
    $('#form-id').on('submit', function(e) {
        const submitBtn = $('#signInBtn');
        submitBtn.addClass('btn-loading').prop('disabled', true);
        setTimeout(() => {
            submitBtn.removeClass('btn-loading').prop('disabled', false);
        }, 10000);
    });

    @if(isset($recaptcha) && $recaptcha['status'] == 1)
        $('#signInBtn').click(function (e) {
            e.preventDefault();
            if (typeof grecaptcha === 'undefined') {
                toastr.error('Invalid recaptcha key provided. Please check the recaptcha configuration.');
                $(this).removeClass('btn-loading').prop('disabled', false);
                return;
            }
            grecaptcha.ready(function () {
                grecaptcha.execute('{{$recaptcha['site_key']}}', {action: 'submit'}).then(function (token) {
                    $('#g-recaptcha-response').val(token);
                    $('#form-id').off('submit').submit();
                });
            });
            window.onerror = function (message) {
                let errorMessage = 'An unexpected error occurred. Please check the recaptcha configuration';
                if (message.includes('Invalid site key')) {
                    errorMessage = 'Invalid site key provided. Please check the recaptcha configuration.';
                } else if (message.includes('not loaded in api.js')) {
                    errorMessage = 'reCAPTCHA API could not be loaded. Please check the recaptcha API configuration.';
                }
                toastr.error(errorMessage);
                $('#signInBtn').removeClass('btn-loading').prop('disabled', false);
                return true;
            };
        });
    @endif
});
</script>
@endpush
