<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php($business_name = \App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value ?? 'Snocart')
    @php($favicon = \App\Models\BusinessSetting::where(['key'=>'icon'])->first())

    <title>{{ translate('messages.application_status') }} | {{ $business_name }}</title>
    <meta name="description" content="{{ translate('messages.application_details') }}">

    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ translate('messages.application_status') }} | {{ $business_name }}">
    <meta property="og:description" content="{{ translate('messages.track_your_progress') }}">
    <meta property="og:site_name" content="{{ $business_name }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ translate('messages.application_status') }} | {{ $business_name }}">
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicon -->
    <link rel="shortcut icon" href="">
    <link rel="icon" type="image/x-icon" href="{{\App\CentralLogics\Helpers::get_full_url('business', $favicon?->value ?? '', $favicon?->storage[0]?->value ?? 'public','favicon')}}">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <h2 class="h3 mb-3">{{ translate('messages.application_status') }}</h2>

                            @if($application->isPending())
                                <span class="badge badge-warning" style="font-size: 18px; padding: 10px 20px;">
                                    ⏳ {{ translate('messages.pending_review') }}
                                </span>
                            @elseif($application->isApproved())
                                <span class="badge badge-success" style="font-size: 18px; padding: 10px 20px;">
                                    ✓ {{ translate('messages.approved') }}
                                </span>
                            @elseif($application->isDenied())
                                <span class="badge badge-danger" style="font-size: 18px; padding: 10px 20px;">
                                    ✗ {{ translate('messages.denied') }}
                                </span>
                            @endif
                        </div>

                        <!-- Application Details -->
                        <div class="mb-4">
                            <h5 class="mb-3">{{ translate('messages.application_details') }}</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted">{{ translate('messages.application_id') }}:</td>
                                    <td><strong>{{ $application->application_id }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ translate('messages.name') }}:</td>
                                    <td><strong>{{ $application->f_name }} {{ $application->l_name }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ translate('messages.email') }}:</td>
                                    <td>{{ $application->email }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ translate('messages.type') }}:</td>
                                    <td>{{ ucfirst($type) }} {{ translate('messages.employee') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ translate('messages.applied_date') }}:</td>
                                    <td>{{ $application->applied_at?->format('M d, Y') }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Status-specific Content -->
                        @if($application->isPending())
                            <div class="alert alert-info">
                                <strong>{{ translate('messages.under_review') }}</strong>
                                <p class="mb-0 mt-2">{{ translate('messages.pending_message') }}</p>
                            </div>
                        @elseif($application->isApproved())
                            <?php
                                $adminLoginSlug = \App\Models\DataSetting::where('key', 'admin_login_url')->value('value');
                                $vendorLoginSlug = \App\Models\DataSetting::where('key', 'store_employee_login_url')->value('value');
                                $loginHref = $type === 'admin'
                                    ? ($adminLoginSlug ? route('login', [$adminLoginSlug]) : url('/'))
                                    : ($vendorLoginSlug ? route('login', [$vendorLoginSlug]) : url('/'));
                            ?>
                            <div class="alert alert-success">
                                <strong>🎉 {{ translate('messages.congratulations') }}</strong>
                                <p class="mt-2">{{ translate('messages.approved_message') }}</p>
                                <p class="mb-0"><strong>{{ translate('messages.role') }}:</strong> {{ $application->role?->name }}</p>
                            </div>
                            <div class="text-center mt-4">
                                <a href="{{ $loginHref }}" class="btn btn-success btn-lg">
                                    {{ translate('messages.login_to_account') }} →
                                </a>
                            </div>
                        @elseif($application->isDenied())
                            <div class="alert alert-danger">
                                <strong>{{ translate('messages.application_not_approved') }}</strong>
                                @if($application->rejection_reason)
                                    <p class="mt-2 mb-0">
                                        <strong>{{ translate('messages.reason') }}:</strong><br>
                                        {{ $application->rejection_reason }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        <div class="text-center mt-4">
                            <a href="{{ route('employee.register') }}" class="text-muted">
                                ← {{ translate('messages.back_to_home') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
