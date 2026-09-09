<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    @php($business_name = \App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value ?? 'Snocart')
    @php($favicon = \App\Models\BusinessSetting::where(['key'=>'icon'])->first())
    @php($logo = \App\Models\BusinessSetting::where(['key'=>'logo'])->first())

    <title>{{ translate('messages.admin_employee_registration') }} | {{ $business_name }}</title>
    <meta name="description" content="{{ translate('messages.admin_employee_application') }} - {{ translate('messages.fill_form_to_apply') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{\App\CentralLogics\Helpers::get_full_url('business', $favicon?->value ?? '', $favicon?->storage[0]?->value ?? 'public','favicon')}}">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">

    <style>
        :root {
            --primary-color: #D82E5E;
            --primary-dark: #b5254c;
            --primary-light: #ffe8ef;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            font-size: 15px;
            line-height: 1.6;
            color: var(--gray-700);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Container */
        .registration-container {
            max-width: 100%;
            padding: 0;
            margin: 0;
        }

        /* Header */
        .registration-header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-container {
            width: 50px;
            height: 50px;
            background: var(--primary-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .logo-container img {
            max-width: 35px;
            max-height: 35px;
        }

        .header-text h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
            line-height: 1.2;
        }

        .header-text p {
            font-size: 13px;
            color: var(--gray-500);
            margin: 3px 0 0 0;
        }

        /* Progress Bar */
        .progress-container {
            background: white;
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 90px;
            z-index: 99;
        }

        .progress-wrapper {
            max-width: 800px;
            margin: 0 auto;
        }

        .progress-bar-custom {
            height: 6px;
            background: var(--gray-200);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--gray-500);
        }

        .progress-step {
            text-align: center;
            flex: 1;
        }

        .progress-step.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Form Container */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            padding-bottom: 100px;
        }

        /* Section Card */
        .section-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .section-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--gray-100);
        }

        .section-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 18px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }

        .section-subtitle {
            font-size: 13px;
            color: var(--gray-500);
            margin: 4px 0 0 0;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
        }

        .form-label .required {
            color: var(--danger-color);
            margin-left: 2px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 15px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            transition: all 0.2s ease;
            background: white;
            color: var(--gray-900);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .form-control::placeholder {
            color: var(--gray-400);
        }

        .form-control.is-invalid {
            border-color: var(--danger-color);
        }

        .form-control.is-valid {
            border-color: var(--success-color);
        }

        .invalid-feedback {
            display: block;
            font-size: 12px;
            color: var(--danger-color);
            margin-top: 6px;
        }

        .form-hint {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 6px;
            display: block;
        }

        /* Input Icon */
        .input-group-icon {
            position: relative;
        }

        .input-group-icon .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 16px;
        }

        .input-group-icon .form-control {
            padding-left: 45px;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            display: block;
        }

        .file-upload-input {
            position: absolute;
            width: 0.1px;
            height: 0.1px;
            opacity: 0;
            overflow: hidden;
            z-index: -1;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 2px dashed var(--gray-300);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--gray-50);
        }

        .file-upload-label:hover {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }

        .file-upload-icon {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 20px;
            flex-shrink: 0;
        }

        .file-upload-text h6 {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .file-upload-text p {
            font-size: 12px;
            color: var(--gray-500);
            margin: 2px 0 0 0;
        }

        .file-preview {
            display: none;
            margin-top: 10px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
            align-items: center;
            gap: 10px;
        }

        .file-preview.show {
            display: flex;
        }

        .file-preview-icon {
            color: var(--success-color);
            font-size: 20px;
        }

        .file-preview-name {
            flex: 1;
            font-size: 13px;
            color: var(--gray-700);
            word-break: break-all;
        }

        .file-preview-remove {
            color: var(--danger-color);
            cursor: pointer;
            font-size: 18px;
        }

        /* Checkbox/Radio */
        .custom-checkbox {
            display: flex;
            align-items: start;
            gap: 12px;
            cursor: pointer;
            padding: 16px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .custom-checkbox:hover {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }

        .custom-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .checkbox-label {
            font-size: 14px;
            color: var(--gray-700);
            line-height: 1.5;
        }

        .checkbox-label a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .checkbox-label a:hover {
            text-decoration: underline;
        }

        /* Submit Button */
        .submit-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 16px 20px;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
            z-index: 98;
        }

        .submit-wrapper {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            gap: 12px;
        }

        .btn-submit {
            flex: 1;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(216, 46, 94, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(216, 46, 94, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-submit .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        .btn-submit.loading .spinner {
            display: block;
        }

        .btn-submit.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-back {
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-700);
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            border-color: var(--gray-300);
            background: var(--gray-50);
        }

        /* Info Badge */
        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--primary-light);
            color: var(--primary-color);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            body {
                font-size: 14px;
            }

            .registration-header {
                padding: 15px;
            }

            .header-content {
                gap: 12px;
            }

            .logo-container {
                width: 45px;
                height: 45px;
            }

            .header-text h1 {
                font-size: 18px;
            }

            .header-text p {
                font-size: 12px;
            }

            .progress-container {
                padding: 12px 15px;
                top: 75px;
            }

            .form-container {
                padding: 15px;
                padding-bottom: 90px;
            }

            .section-card {
                padding: 20px 16px;
                border-radius: 12px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .section-icon {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }

            .section-title {
                font-size: 16px;
            }

            .form-control {
                font-size: 16px; /* Prevents iOS zoom */
                padding: 14px;
            }

            .submit-container {
                padding: 12px 15px;
            }

            .submit-wrapper {
                flex-direction: column-reverse;
            }

            .btn-submit, .btn-back {
                width: 100%;
                padding: 14px;
            }
        }

        /* iOS Specific */
        @supports (-webkit-touch-callout: none) {
            .form-control {
                font-size: 16px !important;
            }
        }

        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid white;
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Success Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-card {
            animation: fadeIn 0.5s ease;
        }

        /* Collapsible Section */
        .section-collapsible .section-header {
            cursor: pointer;
            user-select: none;
        }

        .section-collapsible .section-header::after {
            content: '\f077';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: auto;
            color: var(--gray-400);
            transition: transform 0.3s ease;
        }

        .section-collapsible.collapsed .section-header::after {
            transform: rotate(180deg);
        }

        .section-collapsible.collapsed .section-content {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="registration-header">
        <div class="header-content">
            <div class="logo-container">
                @if($logo && $logo->value)
                    <img src="{{ \App\CentralLogics\Helpers::get_full_url('business', $logo->value, $logo->storage[0]?->value ?? 'public') }}" alt="{{ $business_name }}">
                @else
                    <i class="fas fa-briefcase"></i>
                @endif
            </div>
            <div class="header-text">
                <h1>{{ translate('messages.admin_employee_application') }}</h1>
                <p>{{ translate('messages.fill_form_to_apply') }}</p>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-container">
        <div class="progress-wrapper">
            <div class="progress-bar-custom">
                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
            </div>
            <div class="progress-steps">
                <span class="progress-step active" data-step="1">Personal</span>
                <span class="progress-step" data-step="2">Contact</span>
                <span class="progress-step" data-step="3">Employment</span>
                <span class="progress-step" data-step="4">Documents</span>
                <span class="progress-step" data-step="5">Complete</span>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="registration-container">
        <form method="POST" action="{{ route('employee.register.admin.submit') }}" enctype="multipart/form-data" id="registrationForm">
            @csrf
            @if($invitation ?? null)
                <input type="hidden" name="invitation_token" value="{{ $invitation->token }}">
            @endif

            <div class="form-container">

                <!-- Section 1: Personal Information -->
                <div class="section-card" data-section="1">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h2 class="section-title">{{ translate('messages.personal_information') }}</h2>
                            <p class="section-subtitle">{{ translate('messages.basic_details_about_you') }}</p>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.first_name') }} <span class="required">*</span></label>
                                    <input type="text" name="f_name" class="form-control" value="{{ old('f_name') }}" required placeholder="{{ translate('messages.enter_first_name') }}">
                                    @error('f_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.last_name') }} <span class="required">*</span></label>
                                    <input type="text" name="l_name" class="form-control" value="{{ old('l_name') }}" required placeholder="{{ translate('messages.enter_last_name') }}">
                                    @error('l_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.email') }} <span class="required">*</span></label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $invitationData['email'] ?? '') }}" {{ ($invitation ?? null) ? 'readonly' : '' }} required placeholder="your@email.com">
                                    </div>
                                    @error('email')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.date_of_birth') }}</label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-calendar input-icon"></i>
                                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                                    </div>
                                    @error('date_of_birth')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <span style="display: inline-flex; align-items: center; gap: 10px;">
                                    <img src="{{ asset('public/assets/admin/img/aadhaar-logo.png') }}" alt="Aadhaar" style="height: 24px; width: auto; vertical-align: middle;">
                                    {{ translate('messages.aadhar_number') }}
                                </span>
                                <span class="required">*</span>
                            </label>
                            <div class="input-group-icon">
                                <i class="fas fa-id-card input-icon"></i>
                                <input type="text" name="aadhar_number" class="form-control" value="{{ old('aadhar_number') }}" pattern="[0-9]{12}" maxlength="12" required placeholder="123456789012">
                            </div>
                            <span class="form-hint"><i class="fas fa-info-circle"></i> {{ translate('messages.twelve_digit_aadhar') }}</span>
                            @error('aadhar_number')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ translate('messages.profile_image') }}</label>
                            <div class="file-upload">
                                <input type="file" name="image" id="imageUpload" class="file-upload-input" accept="image/*">
                                <label for="imageUpload" class="file-upload-label">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <div class="file-upload-text">
                                        <h6>{{ translate('messages.upload_photo') }}</h6>
                                        <p>{{ translate('messages.jpg_png_max_2mb') }}</p>
                                    </div>
                                </label>
                                <div class="file-preview" id="imagePreview">
                                    <i class="fas fa-check-circle file-preview-icon"></i>
                                    <span class="file-preview-name"></span>
                                    <i class="fas fa-times file-preview-remove" onclick="removeFile('imageUpload')"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Contact Information -->
                <div class="section-card" data-section="2">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h2 class="section-title">{{ translate('messages.contact_information') }}</h2>
                            <p class="section-subtitle">{{ translate('messages.how_we_can_reach_you') }}</p>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.phone') }} <span class="required">*</span></label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-mobile-alt input-icon"></i>
                                        <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" pattern="[0-9\s\-\+\(\)]*" minlength="10" maxlength="20" required placeholder="+91 98765 43210">
                                    </div>
                                    @error('phone')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.alternate_phone') }}</label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-phone input-icon"></i>
                                        <input type="tel" name="alternate_phone" class="form-control" value="{{ old('alternate_phone') }}" pattern="[0-9\s\-\+\(\)]*" minlength="10" maxlength="20" placeholder="{{ translate('messages.optional') }}">
                                    </div>
                                    @error('alternate_phone')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="info-badge mb-3">
                            <i class="fas fa-users"></i> {{ translate('messages.family_verification_contacts') }}
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.father_working_number') }}</label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-user-tie input-icon"></i>
                                        <input type="tel" name="father_phone" class="form-control" value="{{ old('father_phone') }}" pattern="[0-9\s\-\+\(\)]*" minlength="10" maxlength="20" placeholder="{{ translate('messages.optional') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.family_contact_name') }}</label>
                                    <input type="text" name="family_contact_name" class="form-control" value="{{ old('family_contact_name') }}" maxlength="100" placeholder="{{ translate('messages.optional') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ translate('messages.family_contact_phone') }}</label>
                            <div class="input-group-icon">
                                <i class="fas fa-phone-square input-icon"></i>
                                <input type="tel" name="family_contact_phone" class="form-control" value="{{ old('family_contact_phone') }}" pattern="[0-9\s\-\+\(\)]*" minlength="10" maxlength="20" placeholder="{{ translate('messages.optional') }}">
                            </div>
                            <span class="form-hint"><i class="fas fa-info-circle"></i> {{ translate('messages.any_relative_for_verification') }}</span>
                        </div>

                        <div class="info-badge mb-3">
                            <i class="fas fa-map-marker-alt"></i> {{ translate('messages.address_as_per_aadhar') }}
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ translate('messages.address_line_1') }} <span class="required">*</span></label>
                            <input type="text" name="address_line1" class="form-control" value="{{ old('address_line1') }}" required maxlength="255" placeholder="{{ translate('messages.street_building') }}">
                            @error('address_line1')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ translate('messages.address_line_2') }}</label>
                            <input type="text" name="address_line2" class="form-control" value="{{ old('address_line2') }}" maxlength="255" placeholder="{{ translate('messages.landmark_optional') }}">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.city') }} <span class="required">*</span></label>
                                    <input type="text" name="city" class="form-control" value="{{ old('city') }}" required maxlength="100">
                                    @error('city')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.state') }} <span class="required">*</span></label>
                                    <input type="text" name="state" class="form-control" value="{{ old('state') }}" required maxlength="100">
                                    @error('state')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.pincode') }} <span class="required">*</span></label>
                                    <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}" pattern="[0-9]{6}" maxlength="6" required placeholder="000000">
                                    @error('pincode')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.emergency_contact_name') }}</label>
                                    <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name') }}" maxlength="100" placeholder="{{ translate('messages.optional') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.emergency_contact_phone') }}</label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-exclamation-triangle input-icon"></i>
                                        <input type="tel" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone') }}" pattern="[0-9\s\-\+\(\)]*" minlength="10" maxlength="20" placeholder="{{ translate('messages.optional') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Employment Details -->
                <div class="section-card" data-section="3">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div>
                            <h2 class="section-title">{{ translate('messages.employment_details') }}</h2>
                            <p class="section-subtitle">{{ translate('messages.role_and_work_information') }}</p>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.role') }} <span class="required">*</span></label>
                                    <select name="role_id" class="form-control" required>
                                        <option value="">{{ translate('messages.select_role') }}</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ old('role_id', $invitationData['role_id'] ?? '') == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.zone') }}</label>
                                    <select name="zone_id" class="form-control">
                                        <option value="">{{ translate('messages.select_zone') }}</option>
                                        @foreach($zones as $zone)
                                            <option value="{{ $zone->id }}" {{ old('zone_id', $invitationData['zone_id'] ?? '') == $zone->id ? 'selected' : '' }}>
                                                {{ $zone->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.date_of_joining') }}</label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-calendar-check input-icon"></i>
                                        <input type="date" name="date_of_joining" class="form-control" value="{{ old('date_of_joining') }}" min="{{ date('Y-m-d') }}">
                                    </div>
                                    <span class="form-hint"><i class="fas fa-info-circle"></i> {{ translate('messages.expected_or_actual_joining_date') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.past_experience') }}</label>
                                    <textarea name="past_experience" class="form-control" rows="3" placeholder="{{ translate('messages.previous_company_role_duration') }}">{{ old('past_experience') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Documents -->
                <div class="section-card" data-section="4">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <h2 class="section-title">{{ translate('messages.documents') }}</h2>
                            <p class="section-subtitle">{{ translate('messages.upload_required_documents') }}</p>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.resume') }} (PDF)</label>
                                    <div class="file-upload">
                                        <input type="file" name="resume" id="resumeUpload" class="file-upload-input" accept=".pdf">
                                        <label for="resumeUpload" class="file-upload-label">
                                            <div class="file-upload-icon">
                                                <i class="fas fa-file-pdf"></i>
                                            </div>
                                            <div class="file-upload-text">
                                                <h6>{{ translate('messages.upload_resume') }}</h6>
                                                <p>{{ translate('messages.pdf_max_5mb') }}</p>
                                            </div>
                                        </label>
                                        <div class="file-preview" id="resumePreview">
                                            <i class="fas fa-check-circle file-preview-icon"></i>
                                            <span class="file-preview-name"></span>
                                            <i class="fas fa-times file-preview-remove" onclick="removeFile('resumeUpload')"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.id_proof') }}</label>
                                    <div class="file-upload">
                                        <input type="file" name="id_proof" id="idProofUpload" class="file-upload-input" accept="image/*">
                                        <label for="idProofUpload" class="file-upload-label">
                                            <div class="file-upload-icon">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                            <div class="file-upload-text">
                                                <h6>{{ translate('messages.upload_id_proof') }}</h6>
                                                <p>{{ translate('messages.jpg_png_max_2mb') }}</p>
                                            </div>
                                        </label>
                                        <div class="file-preview" id="idProofPreview">
                                            <i class="fas fa-check-circle file-preview-icon"></i>
                                            <span class="file-preview-name"></span>
                                            <i class="fas fa-times file-preview-remove" onclick="removeFile('idProofUpload')"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.cancelled_cheque') }}</label>
                                    <div class="file-upload">
                                        <input type="file" name="cancelled_cheque" id="chequeUpload" class="file-upload-input" accept="image/*,.pdf">
                                        <label for="chequeUpload" class="file-upload-label">
                                            <div class="file-upload-icon">
                                                <i class="fas fa-money-check"></i>
                                            </div>
                                            <div class="file-upload-text">
                                                <h6>{{ translate('messages.upload_cancelled_cheque') }}</h6>
                                                <p>{{ translate('messages.optional_can_submit_later') }}</p>
                                            </div>
                                        </label>
                                        <div class="file-preview" id="chequePreview">
                                            <i class="fas fa-check-circle file-preview-icon"></i>
                                            <span class="file-preview-name"></span>
                                            <i class="fas fa-times file-preview-remove" onclick="removeFile('chequeUpload')"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.certificates') }} (PDF)</label>
                                    <div class="file-upload">
                                        <input type="file" name="certificates[]" id="certificatesUpload" class="file-upload-input" accept=".pdf" multiple>
                                        <label for="certificatesUpload" class="file-upload-label">
                                            <div class="file-upload-icon">
                                                <i class="fas fa-certificate"></i>
                                            </div>
                                            <div class="file-upload-text">
                                                <h6>{{ translate('messages.upload_certificates') }}</h6>
                                                <p>{{ translate('messages.multiple_files_allowed') }}</p>
                                            </div>
                                        </label>
                                        <div class="file-preview" id="certificatesPreview">
                                            <i class="fas fa-check-circle file-preview-icon"></i>
                                            <span class="file-preview-name"></span>
                                            <i class="fas fa-times file-preview-remove" onclick="removeFile('certificatesUpload')"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Account Security -->
                <div class="section-card" data-section="5">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>
                            <h2 class="section-title">{{ translate('messages.account_security') }}</h2>
                            <p class="section-subtitle">{{ translate('messages.create_secure_password') }}</p>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.password') }} <span class="required">*</span></label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-key input-icon"></i>
                                        <input type="password" name="password" id="password" class="form-control" minlength="6" required placeholder="{{ translate('messages.min_6_characters') }}">
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('messages.confirm_password') }} <span class="required">*</span></label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-key input-icon"></i>
                                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" minlength="6" required placeholder="{{ translate('messages.re_enter_password') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-checkbox">
                                <input type="checkbox" id="terms" name="terms" required>
                                <div class="checkbox-label">
                                    {{ translate('messages.i_agree_to_terms') }}
                                    <a href="#" onclick="openTermsPopup(event)">{{ translate('messages.view_terms') }}</a>
                                    <br>
                                    <small class="form-hint"><i class="fas fa-info-circle"></i> {{ translate('messages.click_view_terms_to_read_and_accept') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Submit Container -->
            <div class="submit-container">
                <div class="submit-wrapper">
                    <button type="button" class="btn-back" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> {{ translate('messages.back') }}
                    </button>
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <div class="spinner"></div>
                        <span class="btn-text">
                            {{ translate('messages.submit_application') }}
                            <i class="fas fa-arrow-right"></i>
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <script src="{{ asset('public/assets/admin/js/vendor.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/toastr.js') }}"></script>
    {!! Toastr::message() !!}

    <script>
        // Progress tracking
        function updateProgress() {
            const sections = document.querySelectorAll('.section-card');
            const totalSections = sections.length;
            let completedSections = 0;

            sections.forEach((section, index) => {
                const inputs = section.querySelectorAll('input[required], select[required], textarea[required]');
                let sectionComplete = true;

                inputs.forEach(input => {
                    if (input.type === 'checkbox') {
                        if (!input.checked) sectionComplete = false;
                    } else if (!input.value) {
                        sectionComplete = false;
                    }
                });

                if (sectionComplete) completedSections++;

                // Update step indicator
                const step = document.querySelector(`.progress-step[data-step="${index + 1}"]`);
                if (step) {
                    if (sectionComplete) {
                        step.classList.add('active');
                    } else {
                        step.classList.remove('active');
                    }
                }
            });

            const progressPercent = (completedSections / totalSections) * 100;
            document.getElementById('progressFill').style.width = progressPercent + '%';
        }

        // File upload preview
        function setupFilePreview(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);

            if (input && preview) {
                input.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        const fileName = this.files.length > 1
                            ? `${this.files.length} files selected`
                            : this.files[0].name;
                        preview.querySelector('.file-preview-name').textContent = fileName;
                        preview.classList.add('show');
                    } else {
                        preview.classList.remove('show');
                    }
                    updateProgress();
                });
            }
        }

        function removeFile(inputId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(inputId.replace('Upload', 'Preview'));
            if (input) input.value = '';
            if (preview) preview.classList.remove('show');
            updateProgress();
        }

        // Setup all file previews
        setupFilePreview('imageUpload', 'imagePreview');
        setupFilePreview('resumeUpload', 'resumePreview');
        setupFilePreview('idProofUpload', 'idProofPreview');
        setupFilePreview('chequeUpload', 'chequePreview');
        setupFilePreview('certificatesUpload', 'certificatesPreview');

        // Real-time validation
        document.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', updateProgress);
            input.addEventListener('change', updateProgress);
        });

        // Form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            // Show loading overlay
            document.getElementById('loadingOverlay').classList.add('show');
        });

        // Terms popup
        function openTermsPopup(event) {
            event.preventDefault();

            const width = Math.min(1000, window.innerWidth - 100);
            const height = Math.min(800, window.innerHeight - 100);
            const left = (window.innerWidth - width) / 2;
            const top = (window.innerHeight - height) / 2;

            window.open(
                '{{ route("employee.terms") }}',
                'termsWindow',
                `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`
            );
        }

        // Listen for terms acceptance
        window.addEventListener('message', function(event) {
            if (event.data && event.data.termsAccepted === true) {
                const termsCheckbox = document.getElementById('terms');
                if (termsCheckbox) {
                    termsCheckbox.checked = true;
                    updateProgress();
                    toastr.success('{{ translate("messages.terms_accepted_successfully") }}');
                }
            }
        });

        // Password match validation
        document.getElementById('password_confirmation').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmation = this.value;

            if (confirmation && password !== confirmation) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Initial progress update
        updateProgress();

        // Smooth scroll to first error
        @if($errors->any())
            setTimeout(function() {
                const firstError = document.querySelector('.is-invalid, .invalid-feedback');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        @endif
    </script>
</body>
</html>
