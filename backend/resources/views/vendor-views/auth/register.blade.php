@extends('layouts.landing.app')
@section('title', translate('messages.store_registration'))
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/vendor-registration.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/select2.min.css') }}"/>
@endpush
@section('content')
    @php($language=\App\Models\BusinessSetting::where('key','language')->first())
    @php($language = $language->value ?? null)
    @php($defaultLang = 'en')

    <!-- Hero Section with Progress Stepper -->
    <section class="registration-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">{{ translate('messages.store') }} {{ translate('application') }}</h1>
                <p class="hero-subtitle">{{ translate('messages.join_our_platform_and_grow_your_business') }}</p>
            </div>

            <!-- Progress Stepper -->
            <div class="registration-stepper">
                <div class="step-item active">
                    <div class="step-circle">1</div>
                    <span class="step-label">{{ translate('messages.store_info') }}</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item">
                    <div class="step-circle">2</div>
                    <span class="step-label">{{ translate('messages.business_plan') }}</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item">
                    <div class="step-circle">3</div>
                    <span class="step-label">{{ translate('messages.complete') }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Form -->
    <section class="registration-form-container">
        <div class="container">
            <form class="js-validate" action="{{ route('restaurant.store') }}" method="post" enctype="multipart/form-data" id="form-id">
                @csrf

                <!-- Store Information Card -->
                <div class="reg-card">
                    <div class="reg-card-header">
                        <div class="reg-card-icon">
                            <svg viewBox="0 0 68 68" xmlns="http://www.w3.org/2000/svg">
                                <path d="m62.99 57.53h-1.17v-29.22c-1.09-.47-2.02-1.25-2.67-2.23-1.08 1.63-2.93 2.71-5.03 2.71s-3.95-1.08-5.03-2.71c-1.08 1.63-2.93 2.71-5.03 2.71s-3.95-1.08-5.03-2.71c-1.08 1.63-2.93 2.71-5.03 2.71s-3.95-1.08-5.03-2.71c-1.08 1.63-2.92 2.71-5.02 2.71-2.11 0-3.97-1.09-5.05-2.74-1.09 1.61-2.92 2.67-5.01 2.67-2.1 0-3.95-1.08-5.03-2.71-.65.98-1.58 1.77-2.68 2.23v29.29h-1.17c-1.21 0-2.19.98-2.19 2.19v4.16h62.36v-4.16c0-1.21-.98-2.19-2.19-2.19zm-33.55 0h-16.45v-20.29c0-1.36 1.1-2.47 2.47-2.47h11.51c1.36 0 2.47 1.11 2.47 2.47zm24.43-9.54c0 .88-.71 1.59-1.59 1.59h-13.41c-.88 0-1.6-.71-1.6-1.59v-12.13c0-.88.72-1.59 1.6-1.59h13.41c.88 0 1.59.71 1.59 1.59z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="reg-card-title">{{ translate('messages.store_info') }}</h2>
                            <p class="reg-card-subtitle">{{ translate('messages.provide_your_store_details') }}</p>
                        </div>
                    </div>
                    <div class="reg-card-body">
                        <!-- Language Tabs -->
                        @if($language)
                        <div class="lang-tabs-modern">
                            <a class="lang-tab-item lang_link active" href="#" id="default-link">{{ translate('Default') }}</a>
                            @foreach (json_decode($language) as $lang)
                            <a class="lang-tab-item lang_link" href="#" id="{{ $lang }}-link">
                                {{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}
                            </a>
                            @endforeach
                        </div>
                        @endif

                        <div class="reg-row reg-row-2">
                            <!-- Left Column -->
                            <div>
                                @if ($language)
                                <!-- Default Language Form -->
                                <div class="lang_form" id="default-form">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">
                                            {{ translate('messages.store_name') }} ({{ translate('messages.Default') }})
                                            <span class="required-dot">*</span>
                                        </label>
                                        <input type="text" name="name[]" id="default_name" class="form-control-modern"
                                            placeholder="{{ translate('messages.enter_store_name') }}" required>
                                        <input type="hidden" name="lang[]" value="default">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">
                                            {{ translate('messages.address') }} ({{ translate('messages.default') }})
                                        </label>
                                        <textarea name="address[]" id="address" class="form-control-modern"
                                            placeholder="{{ translate('messages.enter_store_address') }}"></textarea>
                                    </div>
                                </div>

                                <!-- Other Language Forms -->
                                @foreach (json_decode($language) as $lang)
                                <div class="d-none lang_form" id="{{ $lang }}-form">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">
                                            {{ translate('messages.store_name') }} ({{ strtoupper($lang) }})
                                        </label>
                                        <input type="text" name="name[]" id="{{ $lang }}_name" class="form-control-modern"
                                            placeholder="{{ translate('messages.enter_store_name') }}">
                                        <input type="hidden" name="lang[]" value="{{ $lang }}">
                                    </div>
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">
                                            {{ translate('messages.address') }} ({{ strtoupper($lang) }})
                                        </label>
                                        <textarea name="address[]" id="address{{$lang}}" class="form-control-modern"
                                            placeholder="{{ translate('messages.enter_store_address') }}"></textarea>
                                    </div>
                                </div>
                                @endforeach
                                @endif

                                <!-- Zone Selection -->
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        {{ translate('messages.zone') }}
                                        <span class="required-dot">*</span>
                                        <span class="info-tooltip" data-toggle="tooltip" data-placement="right"
                                            title="{{ translate('messages.select_zone_for_map') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                    </label>
                                    <select name="zone_id" id="choice_zones" required
                                        class="form-control-modern select2-modern js-select2-custom js-example-basic-single"
                                        data-placeholder="{{ translate('messages.select_zone') }}">
                                        <option value="" selected disabled>{{ translate('messages.select_zone') }}</option>
                                        @foreach (\App\Models\Zone::active()->get() as $zone)
                                        @if (isset(auth('admin')->user()->zone_id))
                                        @if (auth('admin')->user()->zone_id == $zone->id)
                                        <option value="{{ $zone->id }}" selected>{{ $zone->name }}</option>
                                        @endif
                                        @else
                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                        @endif
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Module Selection -->
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        {{ translate('messages.module') }}
                                        <span class="required-dot">*</span>
                                        <span class="label-hint">({{ translate('messages.Select_zone_first') }})</span>
                                    </label>
                                    <select name="module_id" required id="module_id"
                                        class="form-control-modern select2-modern js-data-example-ajax"
                                        data-placeholder="{{ translate('messages.select_module') }}">
                                    </select>
                                </div>

                                <!-- VAT/Tax -->
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        {{ translate('messages.vat/tax') }} (%)
                                        <span class="required-dot">*</span>
                                    </label>
                                    <input type="number" id="tax" name="tax" class="form-control-modern"
                                        placeholder="{{ translate('messages.enter_vat_tax_percentage') }}" min="0" step=".01" required
                                        value="{{ old('tax') }}">
                                </div>

                                <!-- Delivery Time -->
                                <div class="form-group-modern">
                                    <label class="form-label-modern">{{ translate('messages.approx_delivery_time') }}</label>
                                    <div class="time-input-group">
                                        <input type="number" id="minimum_delivery_time" name="minimum_delivery_time"
                                            class="form-control-modern" placeholder="{{ translate('messages.min') }}: 10"
                                            value="{{ old('minimum_delivery_time') }}">
                                        <span class="time-separator">-</span>
                                        <input type="number" name="maximum_delivery_time"
                                            class="form-control-modern" placeholder="{{ translate('messages.max') }}: 30"
                                            value="{{ old('maximum_delivery_time') }}">
                                        <select name="delivery_time_type" class="form-control-modern" required>
                                            <option value="min">{{ translate('messages.minutes') }}</option>
                                            <option value="hours">{{ translate('messages.hours') }}</option>
                                            <option value="days">{{ translate('messages.days') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div>
                                <!-- Map Section -->
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        {{ translate('messages.store_location') }}
                                        <span class="required-dot">*</span>
                                    </label>
                                    <div class="map-container-modern">
                                        <input id="pac-input" class="map-search-input" type="text"
                                            placeholder="{{ translate('messages.search_location') }}"
                                            title="{{ translate('messages.search_your_location_here') }}">
                                        <div id="map"></div>
                                        <div class="map-hint">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ translate('messages.click_on_map_to_select_location') }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Coordinates -->
                                <div class="form-group-modern">
                                    <div class="coord-fields">
                                        <div class="coord-field">
                                            <label class="form-label-modern">{{ translate('messages.latitude') }}</label>
                                            <input type="text" id="latitude" name="latitude" class="form-control-modern"
                                                placeholder="{{ translate('messages.Ex:') }} -94.22213" value="{{ old('latitude') }}"
                                                required readonly>
                                        </div>
                                        <div class="coord-field">
                                            <label class="form-label-modern">{{ translate('messages.longitude') }}</label>
                                            <input type="text" name="longitude" class="form-control-modern"
                                                placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude"
                                                value="{{ old('longitude') }}" required readonly>
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Upload Section -->
                                <div class="form-group-modern">
                                    <div class="upload-grid">
                                        <div>
                                            <label class="form-label-modern">
                                                {{ translate('messages.cover_photo') }}
                                                <span class="required-dot">*</span>
                                                <span class="label-hint">({{ translate('messages.ratio') }} 2:1)</span>
                                            </label>
                                            <div class="upload-zone upload-zone-cover" id="coverUploadZone">
                                                <input type="file" name="cover_photo" id="coverImageUpload"
                                                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                <div class="upload-placeholder">
                                                    <div class="upload-icon-wrapper">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                    <span class="upload-text">{{ translate('messages.upload_cover_photo') }}</span>
                                                    <span class="upload-hint">{{ translate('messages.drag_and_drop_or_click') }}</span>
                                                </div>
                                                <img class="upload-preview" id="coverImageViewer" src="" alt="Cover preview">
                                                <span class="upload-change-btn">{{ translate('messages.change') }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label-modern">
                                                {{ translate('messages.store_logo') }}
                                                <span class="required-dot">*</span>
                                                <span class="label-hint">({{ translate('messages.ratio') }} 1:1)</span>
                                            </label>
                                            <div class="upload-zone upload-zone-logo" id="logoUploadZone">
                                                <input type="file" name="logo" id="customFileEg1"
                                                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                                <div class="upload-placeholder">
                                                    <div class="upload-icon-wrapper">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                    <span class="upload-text">{{ translate('messages.upload_logo') }}</span>
                                                    <span class="upload-hint">1:1</span>
                                                </div>
                                                <img class="upload-preview" id="logoImageViewer" src="" alt="Logo preview">
                                                <span class="upload-change-btn">{{ translate('messages.change') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Owner Information Card -->
                <div class="reg-card">
                    <div class="reg-card-header">
                        <div class="reg-card-icon">
                            <svg viewBox="0 0 460.8 460.8" xmlns="http://www.w3.org/2000/svg">
                                <path d="M230.432,239.282c65.829,0,119.641-53.812,119.641-119.641C350.073,53.812,296.261,0,230.432,0 S110.792,53.812,110.792,119.641S164.604,239.282,230.432,239.282z"/>
                                <path d="M435.755,334.89c-3.135-7.837-7.314-15.151-12.016-21.943c-24.033-35.527-61.126-59.037-102.922-64.784 c-5.224-0.522-10.971,0.522-15.151,3.657c-21.943,16.196-48.065,24.555-75.233,24.555s-53.29-8.359-75.233-24.555 c-4.18-3.135-9.927-4.702-15.151-3.657c-41.796,5.747-79.412,29.257-102.922,64.784c-4.702,6.792-8.882,14.629-12.016,21.943 c-1.567,3.135-1.045,6.792,0.522,9.927c4.18,7.314,9.404,14.629,14.106,20.898c7.314,9.927,15.151,18.808,24.033,27.167 c7.314,7.314,15.673,14.106,24.033,20.898c41.273,30.825,90.906,47.02,142.106,47.02s100.833-16.196,142.106-47.02 c8.359-6.269,16.718-13.584,24.033-20.898c8.359-8.359,16.718-17.241,24.033-27.167c5.224-6.792,9.927-13.584,14.106-20.898 C436.8,341.682,437.322,338.024,435.755,334.89z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="reg-card-title">{{ translate('messages.owner_info') }}</h2>
                            <p class="reg-card-subtitle">{{ translate('messages.provide_owner_details') }}</p>
                        </div>
                    </div>
                    <div class="reg-card-body">
                        <div class="reg-row reg-row-3">
                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    {{ translate('messages.first_name') }}
                                    <span class="required-dot">*</span>
                                </label>
                                <div class="input-icon-wrapper">
                                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <input type="text" id="f_name" name="f_name" class="form-control-modern has-icon"
                                        placeholder="{{ translate('messages.enter_first_name') }}" value="{{ old('f_name') }}" required>
                                </div>
                            </div>
                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    {{ translate('messages.last_name') }}
                                    <span class="required-dot">*</span>
                                </label>
                                <div class="input-icon-wrapper">
                                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <input type="text" id="l_name" name="l_name" class="form-control-modern has-icon"
                                        placeholder="{{ translate('messages.enter_last_name') }}" value="{{ old('l_name') }}" required>
                                </div>
                            </div>
                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    {{ translate('messages.phone') }}
                                    <span class="required-dot">*</span>
                                </label>
                                <div class="input-icon-wrapper">
                                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <input type="tel" id="phone" name="phone" class="form-control-modern has-icon"
                                        placeholder="{{ translate('messages.Ex:') }} +1234567890" value="{{ old('phone') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Login Information Card -->
                <div class="reg-card">
                    <div class="reg-card-header">
                        <div class="reg-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 1C8.676 1 6 3.676 6 7V9H5C3.895 9 3 9.895 3 11V21C3 22.105 3.895 23 5 23H19C20.105 23 21 22.105 21 21V11C21 9.895 20.105 9 19 9H18V7C18 3.676 15.324 1 12 1ZM12 3C14.276 3 16 4.724 16 7V9H8V7C8 4.724 9.724 3 12 3ZM12 13C13.105 13 14 13.895 14 15C14 15.738 13.597 16.371 13 16.723V19C13 19.552 12.552 20 12 20C11.448 20 11 19.552 11 19V16.723C10.403 16.371 10 15.738 10 15C10 13.895 10.895 13 12 13Z" fill="white"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="reg-card-title">{{ translate('messages.login_info') }}</h2>
                            <p class="reg-card-subtitle">{{ translate('messages.setup_your_login_credentials') }}</p>
                        </div>
                    </div>
                    <div class="reg-card-body">
                        <div class="reg-row reg-row-3">
                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    {{ translate('messages.email') }}
                                    <span class="required-dot">*</span>
                                </label>
                                <div class="input-icon-wrapper">
                                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <input type="email" id="email" name="email" class="form-control-modern has-icon"
                                        placeholder="{{ translate('messages.Ex:') }} email@example.com" value="{{ old('email') }}" required>
                                </div>
                            </div>
                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    {{ translate('messages.password') }}
                                    <span class="required-dot">*</span>
                                    <span class="label-hint">({{ translate('messages.minimum') }} 6 {{ translate('messages.characters') }})</span>
                                </label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="exampleInputPassword"
                                        class="form-control-modern" minlength="6" required value="{{ old('password') }}"
                                        placeholder="{{ translate('messages.enter_password') }}">
                                    <button type="button" class="password-toggle" onclick="togglePassword('exampleInputPassword', this)">
                                        <svg class="icon-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg class="icon-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    {{ translate('messages.confirm_password') }}
                                    <span class="required-dot">*</span>
                                </label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm-password" id="exampleRepeatPassword"
                                        class="form-control-modern" minlength="6" required value="{{ old('confirm-password') }}"
                                        placeholder="{{ translate('messages.confirm_your_password') }}">
                                    <button type="button" class="password-toggle" onclick="togglePassword('exampleRepeatPassword', this)">
                                        <svg class="icon-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg class="icon-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="password-match-indicator" id="passwordMatchIndicator">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span id="passwordMatchText"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Recaptcha Section -->
                        <div class="mt-3">
                            @php($recaptcha = \App\CentralLogics\Helpers::get_business_settings('recaptcha'))
                            @if(isset($recaptcha) && $recaptcha['status'] == 1)
                            <div id="recaptcha_element" class="w-100" data-type="image"></div>
                            @else
                            <div class="recaptcha-wrapper">
                                <div class="recaptcha-input">
                                    <label class="form-label-modern">{{ translate('messages.enter_captcha') }}</label>
                                    <input type="text" class="form-control-modern" name="custome_recaptcha"
                                        id="custome_recaptcha" required
                                        placeholder="{{ translate('messages.enter_captcha_value') }}" autocomplete="off"
                                        value="{{ env('APP_DEBUG') ? session('six_captcha') : '' }}">
                                </div>
                                <div class="recaptcha-image">
                                    <img src="{!! $custome_recaptcha->inline() ?? '' !!}" alt="captcha">
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Submit Section -->
                <div class="submit-section">
                    <p class="submit-hint">
                        {{ translate('messages.by_submitting_you_agree_to_our') }}
                        <a href="{{ route('terms-and-conditions') }}" target="_blank">{{ translate('messages.terms_and_condition') }}</a>
                    </p>
                    <button type="submit" class="btn-submit-modern">
                        {{ translate('messages.submit_application') }}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </section>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=drawing,places&v=3.45.8"></script>

    <script type="text/javascript">
        "use strict";

        // Map initialization
        @php($default_location = \App\Models\BusinessSetting::where('key', 'default_location')->first())
        @php($default_location = $default_location->value ? json_decode($default_location->value, true) : 0)
        let myLatlng = {
            lat: {{ $default_location ? $default_location['lat'] : '23.757989' }},
            lng: {{ $default_location ? $default_location['lng'] : '90.360587' }}
        };
        let map = new google.maps.Map(document.getElementById("map"), {
            zoom: 13,
            center: myLatlng,
            styles: [
                {
                    "featureType": "administrative",
                    "elementType": "geometry",
                    "stylers": [{"visibility": "off"}]
                },
                {
                    "featureType": "poi",
                    "stylers": [{"visibility": "off"}]
                },
                {
                    "featureType": "road",
                    "elementType": "labels.icon",
                    "stylers": [{"visibility": "off"}]
                },
                {
                    "featureType": "transit",
                    "stylers": [{"visibility": "off"}]
                }
            ]
        });
        let zonePolygon = null;
        let infoWindow = new google.maps.InfoWindow({
            content: "{{ translate('messages.click_the_map_to_get_location') }}",
            position: myLatlng,
        });
        let bounds = new google.maps.LatLngBounds();

        // Zone selection handler
        $('#choice_zones').on('change', function() {
            let id = $(this).val();
            $.get({
                url: '{{ url('/') }}/admin/zone/get-coordinates/' + id,
                dataType: 'json',
                success: function(data) {
                    if (zonePolygon) {
                        zonePolygon.setMap(null);
                    }
                    zonePolygon = new google.maps.Polygon({
                        paths: data.coordinates,
                        strokeColor: "#FF6B35",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: '#FF6B35',
                        fillOpacity: 0.1,
                    });
                    zonePolygon.setMap(map);
                    zonePolygon.getPaths().forEach(function(path) {
                        path.forEach(function(latlng) {
                            bounds.extend(latlng);
                            map.fitBounds(bounds);
                        });
                    });
                    map.setCenter(data.center);
                    google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                        infoWindow.close();
                        infoWindow = new google.maps.InfoWindow({
                            position: mapsMouseEvent.latLng,
                            content: JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2),
                        });
                        let coordinates = JSON.parse(JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2));
                        document.getElementById('latitude').value = coordinates['lat'];
                        document.getElementById('longitude').value = coordinates['lng'];
                        infoWindow.open(map);
                    });
                },
            });
        });

        // Module selection with Select2
        $(document).ready(function() {
            $('.js-example-basic-single').select2();

            $('#module_id').select2({
                ajax: {
                    url: '{{ url('/') }}/store/get-all-modules/',
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page,
                            zone_id: zone_id
                        };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    __port: function(params, success, failure) {
                        let $request = $.ajax(params);
                        $request.then(success);
                        $request.fail(failure);
                        return $request;
                    }
                }
            });
        });
    </script>

    <script src="{{ asset('public/assets/admin/js/view-pages/vendor-registration.js') }}"></script>

    <!-- Enhanced JavaScript for modern UI -->
    <script>
        "use strict";

        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.classList.toggle('active', isPassword);
        }

        // Password match indicator
        $('#exampleInputPassword, #exampleRepeatPassword').on('keyup', function() {
            const pass = $("#exampleInputPassword").val();
            const passRepeat = $("#exampleRepeatPassword").val();
            const indicator = $('#passwordMatchIndicator');
            const text = $('#passwordMatchText');

            if (passRepeat.length > 0) {
                indicator.addClass('show');
                if (pass === passRepeat) {
                    indicator.removeClass('no-match').addClass('match');
                    text.text('{{ translate("messages.passwords_match") }}');
                } else {
                    indicator.removeClass('match').addClass('no-match');
                    text.text('{{ translate("messages.passwords_do_not_match") }}');
                }
            } else {
                indicator.removeClass('show');
            }
        });

        // Image upload preview with zone highlighting
        function setupImageUpload(inputId, previewId, zoneId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const zone = document.getElementById(zoneId);

            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        zone.classList.add('has-image');
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        setupImageUpload('coverImageUpload', 'coverImageViewer', 'coverUploadZone');
        setupImageUpload('customFileEg1', 'logoImageViewer', 'logoUploadZone');

        // Language tab switching
        $(".lang_link").click(function(e) {
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');
            let form_id = this.id;
            let lang = form_id.substring(0, form_id.length - 5);
            $("#" + lang + "-form").removeClass('d-none');
        });

        // Initialize map
        function initMap() {
            infoWindow.open(map);
            infoWindow = new google.maps.InfoWindow();

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        myLatlng = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        infoWindow.setPosition(myLatlng);
                        infoWindow.setContent("{{ translate('messages.location_found') }}");
                        infoWindow.open(map);
                        map.setCenter(myLatlng);
                    },
                    () => {
                        handleLocationError(true, infoWindow, map.getCenter());
                    }
                );
            } else {
                handleLocationError(false, infoWindow, map.getCenter());
            }

            // Search box
            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
            let markers = [];

            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();
                if (places.length === 0) return;

                markers.forEach((marker) => marker.setMap(null));
                markers = [];

                const bounds = new google.maps.LatLngBounds();
                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) return;

                    markers.push(new google.maps.Marker({
                        map,
                        title: place.name,
                        position: place.geometry.location,
                    }));

                    if (place.geometry.viewport) {
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
        }

        function handleLocationError(browserHasGeolocation, infoWindow, pos) {
            infoWindow.setPosition(pos);
            infoWindow.setContent(
                browserHasGeolocation
                    ? "{{ translate('messages.geolocation_service_failed') }}"
                    : "{{ translate('messages.browser_no_geolocation') }}"
            );
            infoWindow.open(map);
        }

        let zone_id = 0;
        $('#choice_zones').on('change', function() {
            if ($(this).val()) {
                zone_id = $(this).val();
            }
        });

        initMap();
    </script>

    @if(isset($recaptcha) && $recaptcha['status'] == 1)
    <script type="text/javascript">
        "use strict";
        let onloadCallback = function() {
            grecaptcha.render('recaptcha_element', {
                'sitekey': '{{ \App\CentralLogics\Helpers::get_business_settings('recaptcha')['site_key'] }}'
            });
        };
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
    <script>
        "use strict";
        $("#form-id").on('submit', function(e) {
            let response = grecaptcha.getResponse();
            if (response.length === 0) {
                e.preventDefault();
                toastr.error("{{ translate('messages.Please_check_the_recaptcha') }}");
            }
        });
    </script>
    @endif

    <script src="{{ asset('public/assets/landing/js/select2.min.js') }}"></script>
@endpush
