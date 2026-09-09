<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php($business_name = \App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value ?? 'Snocart')
    @php($favicon = \App\Models\BusinessSetting::where(['key'=>'icon'])->first())

    <title>{{ translate('messages.check_application_status') }} | {{ $business_name }}</title>
    <meta name="description" content="{{ translate('messages.enter_application_id_to_check_status') }}">

    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ translate('messages.check_application_status') }} | {{ $business_name }}">
    <meta property="og:description" content="{{ translate('messages.track_your_progress') }}">
    <meta property="og:site_name" content="{{ $business_name }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ translate('messages.check_application_status') }} | {{ $business_name }}">

    <!-- Favicon -->
    <link rel="shortcut icon" href="">
    <link rel="icon" type="image/x-icon" href="{{\App\CentralLogics\Helpers::get_full_url('business', $favicon?->value ?? '', $favicon?->storage[0]?->value ?? 'public','favicon')}}">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="h3 mb-2" style="color: #D82E5E;">{{ translate('messages.check_application_status') }}</h2>
                            <p class="text-muted">{{ translate('messages.enter_application_id_to_check_status') }}</p>
                        </div>

                        <form method="GET" action="{{ route('employee.application.check') }}">
                            <div class="form-group mb-4">
                                <label for="application_id" class="form-label">{{ translate('messages.application_id') }}</label>
                                <input type="text"
                                       class="form-control form-control-lg"
                                       id="application_id"
                                       name="application_id"
                                       placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
                                       required>
                                <small class="form-text text-muted">
                                    {{ translate('messages.application_id_help_text') }}
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                {{ translate('messages.check_status') }}
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="{{ route('employee.register') }}" class="text-muted">
                                ← {{ translate('messages.back_to_registration') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
