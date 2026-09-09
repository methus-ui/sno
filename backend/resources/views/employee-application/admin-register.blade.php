<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php($business_name = \App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value ?? 'Snocart')
    @php($favicon = \App\Models\BusinessSetting::where(['key'=>'icon'])->first())

    <title>{{ translate('messages.admin_employee_registration') }} | {{ $business_name }}</title>
    <meta name="description" content="{{ translate('messages.admin_employee_application') }} - {{ translate('messages.fill_form_to_apply') }}">

    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ translate('messages.admin_employee_registration') }} | {{ $business_name }}">
    <meta property="og:description" content="{{ translate('messages.join_kashmir_success_story') }}">
    <meta property="og:site_name" content="{{ $business_name }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ translate('messages.admin_employee_registration') }} | {{ $business_name }}">

    <!-- Favicon -->
    <link rel="shortcut icon" href="">
    <link rel="icon" type="image/x-icon" href="{{\App\CentralLogics\Helpers::get_full_url('business', $favicon?->value ?? '', $favicon?->storage[0]?->value ?? 'public','favicon')}}">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="h3" style="color: #D82E5E;">{{ translate('messages.admin_employee_application') }}</h2>
                            <p class="text-muted">{{ translate('messages.fill_form_to_apply') }}</p>
                        </div>

                        <form method="POST" action="{{ route('employee.register.admin.submit') }}" enctype="multipart/form-data">
                            @csrf
                            @if($invitation)
                                <input type="hidden" name="invitation_token" value="{{ $invitation->token }}">
                            @endif

                            <!-- Personal Information -->
                            <h5 class="mb-3">{{ translate('messages.personal_information') }}</h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.first_name') }} *</label>
                                    <input type="text" name="f_name" class="form-control" value="{{ old('f_name') }}" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.last_name') }} *</label>
                                    <input type="text" name="l_name" class="form-control" value="{{ old('l_name') }}" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.email') }} *</label>
                                    <input type="email" name="email" class="form-control"
                                           value="{{ old('email', $invitationData['email'] ?? '') }}"
                                           {{ $invitation ? 'readonly' : '' }} required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.phone') }} *</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.date_of_birth') }}</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.profile_image') }}</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ translate('messages.address') }}</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                            </div>

                            <hr class="my-4">

                            <!-- Employment Details -->
                            <h5 class="mb-3">{{ translate('messages.employment_details') }}</h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.role') }} *</label>
                                    <select name="role_id" class="form-control" required>
                                        <option value="">{{ translate('messages.select_role') }}</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}"
                                                    {{ old('role_id', $invitationData['role_id'] ?? '') == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.zone') }}</label>
                                    <select name="zone_id" class="form-control">
                                        <option value="">{{ translate('messages.select_zone') }}</option>
                                        @foreach($zones as $zone)
                                            <option value="{{ $zone->id }}"
                                                    {{ old('zone_id', $invitationData['zone_id'] ?? '') == $zone->id ? 'selected' : '' }}>
                                                {{ $zone->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.emergency_contact_name') }}</label>
                                    <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.emergency_contact_phone') }}</label>
                                    <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone') }}">
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Documents -->
                            <h5 class="mb-3">{{ translate('messages.documents') }}</h5>
                            <div class="form-group">
                                <label>{{ translate('messages.resume') }} (PDF)</label>
                                <input type="file" name="resume" class="form-control" accept=".pdf">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('messages.id_proof') }} (Image)</label>
                                <input type="file" name="id_proof" class="form-control" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('messages.certificates') }} (PDF, Multiple)</label>
                                <input type="file" name="certificates[]" class="form-control" accept=".pdf" multiple>
                            </div>

                            <hr class="my-4">

                            <!-- Password -->
                            <h5 class="mb-3">{{ translate('messages.account_security') }}</h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.password') }} *</label>
                                    <input type="password" name="password" class="form-control" minlength="6" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ translate('messages.confirm_password') }} *</label>
                                    <input type="password" name="password_confirmation" class="form-control" minlength="6" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="terms" required>
                                    <label class="custom-control-label" for="terms">
                                        {{ translate('messages.i_agree_to_terms') }}
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                {{ translate('messages.submit_application') }}
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="{{ route('employee.register') }}" class="text-muted">
                                ← {{ translate('messages.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('public/assets/admin/js/vendor.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/toastr.js') }}"></script>
    {!! Toastr::message() !!}
</body>
</html>
