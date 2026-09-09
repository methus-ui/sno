<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $business_name ?? 'Business' }} - Vendor Registration</title>
    <meta name="description" content="Join {{ $business_name ?? 'our platform' }} and reach more customers. We help local vendors in Kashmir grow their business with our powerful tools and efficient delivery network.">
    <meta name="keywords" content="vendor registration, kashmir vendors, local business, online store, ecommerce, snocart">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 576 512%22><path fill=%22%23D82E5E%22 d=%22M575.8 255.5C575.8 273.5 560.8 287.6 543.8 287.6H511.8L512.5 447.7C512.5 450.5 512.3 453.1 512 455.8L479.9 455.8C479.9 453.3 480 450.7 480 448C480 373.1 412.4 314.5 336 304.1V287.6H271.9L272.5 447.7C272.5 450.5 272.3 453.1 272 455.8L239.9 455.8C239.9 453.3 240 450.7 240 448C240 373.1 172.4 314.5 96 304.1V287.6H48.03C31.03 287.6 16 273.5 16 255.5C16 237.5 31.03 223.4 48.03 223.4H543.8C560.8 223.4 575.8 237.5 575.8 255.5zM320 223.4H32.05C14.95 223.4 0 209.4 0 191.4C0 174.4 14.31 159.1 32.05 159.1H544.1C561.1 159.1 576 174.4 576 191.4C576 209.4 561.1 223.4 544.1 223.4H320z%22/></svg>">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/vendor-registration.css') }}">
    <style>
        /* Page-specific overrides — base styles come from vendor-registration.css */

        /* All base styles handled by vendor-registration.css (mobile-first) */
        /* Only page-specific additions below */

        /* OTP digit inputs - page specific */
        .otp-inputs { display: flex; gap: 8px; justify-content: center; }
        .otp-digit {
            width: 46px; height: 54px;
            text-align: center; font-size: 1.5rem; font-weight: 700;
            border: 2px solid var(--gray-200, #e5e5e5);
            border-radius: 12px; background: var(--gray-50, #fafafa);
            transition: 0.25s ease;
        }
        .otp-digit:focus {
            border-color: var(--primary-clr, #D82E5E);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(216,46,94,0.1);
            outline: none;
        }

        /* Corporate info on desktop */
        .info-item { display: flex; align-items: flex-start; margin-bottom: 1.5rem; }
        .info-item i { margin-right: 12px; margin-top: 2px; }

        /* Pricing cards grid */
        .pricing-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .pricing-card {
            border: 2px solid var(--gray-200, #e5e5e5);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            background: #fff;
            cursor: pointer;
            transition: 0.25s ease;
            position: relative;
        }
        .pricing-card:hover { border-color: var(--primary-clr, #D82E5E); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .pricing-card.selected { border-color: var(--primary-clr, #D82E5E); box-shadow: 0 0 20px rgba(216,46,94,0.3); }
        .pricing-card.featured {
            background: linear-gradient(135deg, #262626, #171717);
            color: #fff;
            border-color: transparent;
        }
        .pricing-card.featured::before {
            content: "POPULAR";
            position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg, var(--primary-clr, #D82E5E), #FF4D7A);
            color: #fff; padding: 4px 12px; border-radius: 999px;
            font-size: 0.625rem; font-weight: 700; letter-spacing: 0.5px;
        }
        .pricing-card input[type="radio"] { position: absolute; opacity: 0; }
        .plan-name { font-size: 1.125rem; font-weight: 700; margin-bottom: 8px; }
        .plan-price-big { font-size: 2.5rem; font-weight: 800; margin-bottom: 4px; }
        .plan-period { font-size: 0.8125rem; opacity: 0.7; margin-bottom: 16px; }
        .plan-features { list-style: none; padding: 0; margin: 0; text-align: left; }
        .plan-features li { display: flex; align-items: center; gap: 8px; padding: 8px 0; font-size: 0.8125rem; }
        .plan-features li i { color: var(--success, #10b981); font-size: 0.875rem; }
        .pricing-card.featured .plan-features li i { color: #6ee7b7; }

        /* Business plan selection */
        .plan-options { display: flex; flex-direction: column; gap: 12px; }
        .plan-option {
            border: 2px solid var(--gray-200, #e5e5e5);
            border-radius: 16px; padding: 16px;
            background: #fff; cursor: pointer; transition: 0.25s ease;
            position: relative;
        }
        .plan-option:hover { border-color: var(--gray-300, #d4d4d4); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .plan-option.selected { border-color: var(--primary-clr, #D82E5E); background: rgba(216,46,94,0.02); }
        .plan-option input[type="radio"] { position: absolute; opacity: 0; }
        .plan-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .plan-title { font-size: 1rem; font-weight: 600; color: #171717; }
        .plan-price { font-size: 0.9375rem; font-weight: 700; color: var(--primary-clr, #D82E5E); }
        .plan-description { font-size: 0.8125rem; color: #737373; line-height: 1.5; }
        .subscription-plans { display: none; margin-top: 16px; }
        .subscription-plans.show { display: block; animation: fadeIn 0.3s ease; }

        /* Slide badge animation */
        .slide-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-clr, #D82E5E), #FF4D7A);
            color: #fff; padding: 6px 16px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 12px;
        }
        .slide .slide-badge, .slide .slide-title, .slide .slide-description { opacity: 0; transform: translateY(20px); }
        .slide.active .slide-badge, .slide.active .slide-title, .slide.active .slide-description {
            animation: slideUp 0.6s ease forwards;
        }
        .slide.active .slide-badge { animation-delay: 0.2s; }
        .slide.active .slide-title { animation-delay: 0.4s; }
        .slide.active .slide-description { animation-delay: 0.6s; }

        @keyframes slideUp { to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Tablet+ */
        @media (min-width: 640px) {
            .pricing-cards { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        }

        /* Desktop */
        @media (min-width: 1024px) {
            .pricing-card.featured { transform: scale(1.02); }
        }

    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

        <div class="registration-container">

            <!-- Image Slider Section - 65% -->

            <div class="image-section kashmir-slider">

                <div class="slider-container" role="region" aria-label="Kashmir business showcase" aria-live="polite">

                    <!-- Slide 1: Kashmir's Digital Marketplace -->
                    <div class="slide active" style="background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.5)), url('{{ asset('public/assets/admin/img/kashmir/slide_1.jpg') }}');" aria-hidden="false">

                        <div class="slide-overlay">

                            <div class="slide-text">
                                <span class="slide-badge">Kashmir Valley</span>
                                <h2 class="slide-title">Bring Your Business to Kashmir's Digital Marketplace</h2>

                                <p class="slide-description">Connect with customers across the valley. From Srinagar to Anantnag, reach every corner of Kashmir with your products.</p>

                            </div>

                        </div>

                    </div>

                    <!-- Slide 2: Kashmir's Rich Heritage -->
                    <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.5)), url('{{ asset('public/assets/admin/img/kashmir/slide_2.jpg') }}');" aria-hidden="true">

                        <div class="slide-overlay">

                            <div class="slide-text">
                                <span class="slide-badge">Authentic Kashmir</span>
                                <h2 class="slide-title">Share Kashmir's Rich Heritage</h2>

                                <p class="slide-description">From saffron to pashmina shawls, reach customers who value authenticity. Showcase what makes Kashmir special to the world.</p>

                            </div>

                        </div>

                    </div>

                    <!-- Slide 3: Online Shop with Tracking -->
                    <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.5)), url('{{ asset('public/assets/admin/img/kashmir/slide_3.jpg') }}');" aria-hidden="true">

                        <div class="slide-overlay">

                            <div class="slide-text">
                                <span class="slide-badge">Fast Delivery</span>
                                <h2 class="slide-title">Your Shop, Now Online</h2>

                                <p class="slide-description">Quick deliveries across Kashmir with real-time tracking. From Dal Lake to Gulmarg, we deliver happiness to every doorstep.</p>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- Slide Navigation Dots -->
                <div class="slider-dots" role="tablist" aria-label="Slide navigation">
                    <button class="slider-dot active" role="tab" aria-selected="true" aria-label="Slide 1"></button>
                    <button class="slider-dot" role="tab" aria-selected="false" aria-label="Slide 2"></button>
                    <button class="slider-dot" role="tab" aria-selected="false" aria-label="Slide 3"></button>
                </div>

            </div>

    

            <!-- Form Section - 35% -->

            <div class="form-section">

                <div class="form-container">

                                    <div class="form-header" style="text-align: center; margin-bottom: 1.5rem;">
                                        <a href="/">
                                            <img src="https://new.snocart.com/storage/app/public/business/2025-05-08-681bb7e7462a1.png" alt="Snocart" style="height: 48px; width: auto;">
                                        </a>
                                    </div>

    

                    <div class="corporate-info-section" style="margin-bottom: 2rem; text-align: left;">

                        <div class="info-item" style="display: flex; align-items: flex-start; margin-bottom: 1.5rem;">

                            <i class="fas fa-mountain" style="font-size: 1.5rem; color: var(--primary-clr); margin-right: 1rem; margin-top: 5px;" aria-hidden="true"></i>

                            <div>

                                <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem;">Grow Across Kashmir</h4>

                                <p style="font-size: 0.9rem; color: var(--gray-600); line-height: 1.5;">Connect with customers from Srinagar to Anantnag. Expand your reach across the entire valley with our platform.</p>

                            </div>

                        </div>

                        <div class="info-item" style="display: flex; align-items: flex-start; margin-bottom: 1.5rem;">

                            <i class="fas fa-hand-holding-heart" style="font-size: 1.5rem; color: var(--primary-clr); margin-right: 1rem; margin-top: 5px;" aria-hidden="true"></i>

                            <div>

                                <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem;">Showcase Kashmiri Heritage</h4>

                                <p style="font-size: 0.9rem; color: var(--gray-600); line-height: 1.5;">Whether it's saffron, dry fruits, or handicrafts - share authentic Kashmiri products with customers who value quality.</p>

                            </div>

                        </div>

                        <div class="info-item" style="display: flex; align-items: flex-start; margin-bottom: 1.5rem;">

                            <i class="fas fa-truck" style="font-size: 1.5rem; color: var(--primary-clr); margin-right: 1rem; margin-top: 5px;" aria-hidden="true"></i>

                            <div>

                                <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem;">Valley-Wide Delivery</h4>

                                <p style="font-size: 0.9rem; color: var(--gray-600); line-height: 1.5;">Fast deliveries across Kashmir with real-time tracking. Your products reach customers quickly, every time.</p>

                            </div>

                        </div>

                    </div>

    

                    <!-- Progress Stepper with ARIA -->
                    <nav class="progress-bar" role="tablist" aria-label="Registration progress">

                        <div class="progress-step active" role="tab" aria-selected="true" aria-label="Step 1: Phone Verification" data-step="1"></div>

                        <div class="progress-step" role="tab" aria-selected="false" aria-label="Step 2: Owner Information" data-step="2"></div>

                        <div class="progress-step" role="tab" aria-selected="false" aria-label="Step 3: Zone Selection" data-step="3"></div>

                        <div class="progress-step" role="tab" aria-selected="false" aria-label="Step 4: Store Details" data-step="4"></div>

                        <div class="progress-step" role="tab" aria-selected="false" aria-label="Step 5: Store Images" data-step="5"></div>

                        <div class="progress-step" role="tab" aria-selected="false" aria-label="Step 6: Location" data-step="6"></div>

                        <div class="progress-step" role="tab" aria-selected="false" aria-label="Step 7: Account Setup" data-step="7"></div>

                        <div class="progress-step" role="tab" aria-selected="false" aria-label="Step 8: Business Plan" data-step="8"></div>

                    </nav>

                    <!-- Screen Reader Announcements -->
                    <div id="sr-announcements" class="sr-only" aria-live="polite" aria-atomic="true"></div>



                    <form id="registrationForm" action="{{ route('restaurant.store') }}" method="POST" enctype="multipart/form-data">

                        @csrf



                        <!-- Step 1: Phone OTP Verification -->

                        <div class="form-step active" id="step1">

                            <div class="form-card">

                                <h3><i class="fas fa-mobile-alt" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Verify Your Phone</h3>

                                <p style="color: var(--gray-600); margin-bottom: 1.5rem; font-size: 0.9rem;">Enter your phone number to receive a verification code</p>



                                <div id="phoneInputSection">

                                    <div class="form-group">

                                        <label for="phone">Phone Number *</label>

                                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+91 9876543210" required aria-describedby="phone-helper">

                                        <div class="form-helper-text" id="phone-helper">
                                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                                            <span>Enter your 10-digit mobile number. We'll send an OTP for verification.</span>
                                        </div>

                                        <div class="error-message">Valid phone number is required</div>

                                    </div>



                                    <div class="form-actions">

                                        <div></div>

                                        <button type="button" class="btn btn-primary" id="sendOtpBtn" onclick="sendOtp()">

                                            Send OTP <i class="fas fa-paper-plane" style="margin-left: 0.5rem;"></i>

                                        </button>

                                    </div>

                                </div>



                                <div id="otpInputSection" style="display: none;">

                                    <p style="color: var(--gray-600); margin-bottom: 1rem; font-size: 0.9rem;">

                                        OTP sent to <strong id="otpSentPhone"></strong>

                                        <a href="#" onclick="editPhone()" style="color: var(--primary-clr); margin-left: 0.5rem;">Edit</a>

                                    </p>



                                    <div class="form-group">

                                        <label>Enter 6-digit OTP *</label>

                                        <div class="otp-inputs" style="display: flex; gap: 8px; justify-content: center;">

                                            <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" style="width: 50px; height: 55px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid var(--border-clr); border-radius: 10px;">

                                            <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" style="width: 50px; height: 55px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid var(--border-clr); border-radius: 10px;">

                                            <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" style="width: 50px; height: 55px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid var(--border-clr); border-radius: 10px;">

                                            <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" style="width: 50px; height: 55px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid var(--border-clr); border-radius: 10px;">

                                            <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" style="width: 50px; height: 55px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid var(--border-clr); border-radius: 10px;">

                                            <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" style="width: 50px; height: 55px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid var(--border-clr); border-radius: 10px;">

                                        </div>

                                        <input type="hidden" id="otpValue" name="otp" value="">

                                        <div class="error-message" id="otpError" style="text-align: center;"></div>

                                    </div>



                                    <div style="text-align: center; margin: 1rem 0;">

                                        <span id="resendTimer" style="color: var(--gray-500); font-size: 0.9rem;"></span>

                                        <button type="button" id="resendBtn" class="btn btn-secondary" onclick="resendOtp()" style="display: none; margin-top: 0.5rem; padding: 8px 16px; font-size: 0.85rem;">

                                            Resend OTP

                                        </button>

                                    </div>



                                    <div class="form-actions">

                                        <div></div>

                                        <button type="button" class="btn btn-primary" id="verifyOtpBtn" onclick="verifyOtp()">

                                            Verify & Continue <i class="fas fa-check-circle" style="margin-left: 0.5rem;"></i>

                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>



                        <!-- Step 2: Owner Information -->

                        <div class="form-step" id="step2">

                            <div class="form-card">

                                <h3><i class="fas fa-user-circle" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Owner Information</h3>

                                <div class="form-row">

                                    <div class="form-group">

                                        <label for="firstName">First Name *</label>

                                        <input type="text" id="firstName" name="f_name" value="{{ old('f_name') }}" required>

                                        <div class="error-message">First name is required</div>

                                    </div>

                                    <div class="form-group">

                                        <label for="lastName">Last Name *</label>

                                        <input type="text" id="lastName" name="l_name" value="{{ old('l_name') }}" required>

                                        <div class="error-message">Last name is required</div>

                                    </div>

                                </div>



                                <div class="form-actions">

                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">

                                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Back

                                    </button>

                                    <button type="button" class="btn btn-primary" onclick="nextStep()">

                                        Next <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

    

                        <!-- Step 3: Zone & Module Selection -->

                        <div class="form-step" id="step3">

                            <div class="form-card">

                                <h3><i class="fas fa-map-marked-alt" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Zone & Service Type</h3>

                                

                                <div class="form-group">

                                    <label for="zone_id">Select Zone *</label>

                                    <div class="select-wrapper">

                                        <select id="zone_id" name="zone_id" required onchange="loadModules()">

                                            <option value="">Choose your service zone</option>

                                            @foreach(\App\Models\Zone::active()->get() as $zone)

                                                <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>

                                                    {{ $zone->name }}

                                                </option>

                                            @endforeach

                                        </select>

                                    </div>

                                    <div class="error-message">Please select a zone</div>

                                </div>

    

                                <div class="form-group">

                                    <label for="module_id">Service Type *</label>

                                    <div class="select-wrapper">

                                        <select id="module_id" name="module_id" required onchange="loadPackages()">

                                            <option value="">Select service type first</option>

                                        </select>

                                    </div>

                                    <div class="error-message">Please select a service type</div>

                                </div>

                                

                                <div class="form-actions">

                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">

                                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Back

                                    </button>

                                    <button type="button" class="btn btn-primary" onclick="nextStep()">

                                        Next <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

    

                        <!-- Step 4: Store Information -->

                        <div class="form-step" id="step4">

                            <div class="form-card">

                                <h3><i class="fas fa-store" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Store Information</h3>

                                

                                <div class="form-group">

                                    <label for="storeName">Store Name *</label>

                                    <input type="text" id="storeName" name="name[default]" value="{{ old('name.default') }}" required aria-describedby="store-name-helper" placeholder="e.g., Kashmir Dry Fruits Store">

                                    <div class="form-helper-text" id="store-name-helper">
                                        <i class="fas fa-lightbulb" aria-hidden="true"></i>
                                        <span>Choose a memorable name that represents your business in Kashmir.</span>
                                    </div>

                                    <div class="error-message">Store name is required</div>

                                </div>

    

                                <div class="form-group">

                                    <label for="address">Address *</label>

                                    <textarea id="address" name="address[default]" rows="3" required>{{ old('address.default') }}</textarea>

                                    <div class="error-message">Address is required</div>

                                </div>

    

                                <div class="form-group">

                                    <label for="tax">VAT/Tax (%) *</label>

                                    <input type="number" id="tax" name="tax" min="0" step="0.01" value="{{ old('tax') }}" required>

                                    <div class="error-message">Tax rate is required</div>

                                </div>

    

                                <div class="form-row">

                                    <div class="form-group">

                                        <label for="minimum_delivery_time">Min Delivery Time *</label>

                                        <input type="number" id="minimum_delivery_time" name="minimum_delivery_time" min="1" value="{{ old('minimum_delivery_time') }}" required>

                                        <div class="error-message">Minimum delivery time is required</div>

                                    </div>

                                    <div class="form-group">

                                        <label for="maximum_delivery_time">Max Delivery Time *</label>

                                        <input type="number" id="maximum_delivery_time" name="maximum_delivery_time" min="1" value="{{ old('maximum_delivery_time') }}" required>

                                        <div class="error-message">Maximum delivery time is required</div>

                                    </div>

                                </div>

    

                                <div class="form-group">

                                    <label for="delivery_time_type">Delivery Time Unit *</label>

                                    <div class="select-wrapper">

                                        <select id="delivery_time_type" name="delivery_time_type" required>

                                            <option value="">Select time unit</option>

                                            <option value="min" {{ old('delivery_time_type') == 'min' ? 'selected' : '' }}>Minutes</option>

                                            <option value="hours" {{ old('delivery_time_type') == 'hours' ? 'selected' : '' }}>Hours</option>

                                            <option value="days" {{ old('delivery_time_type') == 'days' ? 'selected' : '' }}>Days</option>

                                        </select>

                                    </div>

                                    <div class="error-message">Please select delivery time unit</div>

                                </div>

                                

                                <div class="form-actions">

                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">

                                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Back

                                    </button>

                                    <button type="button" class="btn btn-primary" onclick="nextStep()">

                                        Next <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

    

                        <!-- Step 5: Store Images -->

                        <div class="form-step" id="step5">

                            <div class="form-card">

                                <h3><i class="fas fa-images" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Store Images</h3>

                                

                                <div class="form-group">

                                    <label for="logo">Store Logo *</label>

                                    <div class="file-upload" onclick="document.getElementById('logo').click()">

                                        <input type="file" id="logo" name="logo" accept="image/*" required onchange="handleFileSelect(this, 'logo')">

                                        <div class="file-upload-icon">

                                            <i class="fas fa-upload"></i>

                                        </div>

                                        <div class="file-upload-text">Click to upload store logo</div>

                                        <div class="file-selected" id="logo-selected"></div>

                                    </div>

                                    <div class="error-message">Store logo is required</div>

                                </div>

    

                                <div class="form-group">

                                    <label for="cover_photo">Cover Photo (Optional)</label>

                                    <div class="file-upload" onclick="document.getElementById('cover_photo').click()">

                                        <input type="file" id="cover_photo" name="cover_photo" accept="image/*" onchange="handleFileSelect(this, 'cover_photo')">

                                        <div class="file-upload-icon">

                                            <i class="fas fa-image"></i>

                                        </div>

                                        <div class="file-upload-text">Click to upload cover photo</div>

                                        <div class="file-selected" id="cover_photo-selected"></div>

                                    </div>

                                </div>

                                

                                <div class="form-actions">

                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">

                                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Back

                                    </button>

                                    <button type="button" class="btn btn-primary" onclick="nextStep()">

                                        Next <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

    

                        <!-- Step 6: Location -->

                        <div class="form-step" id="step6">

                            <div class="form-card">

                                <h3><i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Pin Your Location</h3>

                                <div class="form-row">

                                    <div class="form-group">

                                        <label for="latitude">Latitude *</label>

                                        <input type="text" id="latitude" name="latitude" value="{{ old('latitude') }}" readonly required>

                                        <div class="error-message">Location is required</div>

                                    </div>

                                    <div class="form-group">

                                        <label for="longitude">Longitude *</label>

                                        <input type="text" id="longitude" name="longitude" value="{{ old('longitude') }}" readonly required>

                                        <div class="error-message">Location is required</div>

                                    </div>

                                </div>

                                <div class="location-pin">

                                    <div class="location-icon">

                                        <i class="fas fa-map-marker-alt"></i>

                                    </div>

                                    <p style="margin-bottom: 1rem; font-size: 1rem;">Click to pin your store location</p>

                                    <button type="button" class="btn btn-primary" onclick="getLocation()">

                                        <i class="fas fa-crosshairs"></i> Get My Location

                                    </button>

                                </div>

                                

                                <div class="form-actions">

                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">

                                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Back

                                    </button>

                                    <button type="button" class="btn btn-primary" onclick="nextStep()">

                                        Next <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

    

                        <!-- Step 7: Account Information -->

                        <div class="form-step" id="step7">

                            <div class="form-card">

                                <h3><i class="fas fa-user-shield" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Account Information</h3>

                                

                                <div class="form-group">

                                    <label for="email">Email Address *</label>

                                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>

                                    <div class="error-message">Valid email address is required</div>

                                </div>

                                

                                <div class="form-row">

                                    <div class="form-group">

                                        <label for="password">Password *</label>

                                        <input type="password" id="password" name="password" minlength="8" required aria-describedby="password-requirements">

                                        <div class="password-strength" id="passwordStrength">
                                            <div class="password-strength-bar">
                                                <div class="password-strength-fill" id="passwordStrengthFill"></div>
                                            </div>
                                            <div class="password-checklist" id="password-requirements">
                                                <div class="password-checklist-item" data-requirement="length">
                                                    <span class="check-icon"></span>
                                                    <span>8+ characters</span>
                                                </div>
                                                <div class="password-checklist-item" data-requirement="uppercase">
                                                    <span class="check-icon"></span>
                                                    <span>Uppercase</span>
                                                </div>
                                                <div class="password-checklist-item" data-requirement="lowercase">
                                                    <span class="check-icon"></span>
                                                    <span>Lowercase</span>
                                                </div>
                                                <div class="password-checklist-item" data-requirement="number">
                                                    <span class="check-icon"></span>
                                                    <span>Number</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="error-message">Password must be at least 8 characters with mixed case and numbers</div>

                                    </div>

                                    <div class="form-group">

                                        <label for="confirmPassword">Confirm Password *</label>

                                        <input type="password" id="confirmPassword" name="password_confirmation" minlength="8" required aria-describedby="password-match-status">

                                        <div class="password-match-indicator" id="password-match-status">
                                            <i class="fas fa-check-circle" id="matchIcon"></i>
                                            <span id="matchText">Passwords match</span>
                                        </div>

                                        <div class="error-message">Passwords must match</div>

                                    </div>

                                </div>

                                

                                <div class="form-actions">

                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">

                                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Back

                                    </button>

                                    <button type="button" class="btn btn-primary" onclick="nextStep()">

                                        Next <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

    

                        <!-- Step 8: Business Plan -->

                        <div class="form-step" id="step8">

                            <div class="form-card">

                                <h3><i class="fas fa-chart-line" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Choose Your Business Plan</h3>

                                

                                <div class="plan-options">

                                    <div class="plan-option" onclick="selectBusinessPlan('commission-base')">

                                        <input type="radio" name="business_plan" value="commission-base" id="commission">

                                        <div class="plan-header">

                                            <div class="plan-title"><i class="fas fa-percentage" style="margin-right: 0.5rem;"></i>Commission Based</div>

                                            <div class="plan-price">{{ $admin_commission ?? '10' }}% Fee</div>

                                        </div>

                                        <div class="plan-description">

                                            Pay {{ $admin_commission ?? '10' }}% commission from each order. Get access to all features and interact with users without monthly fees.

                                        </div>

                                    </div>

    

                                    @php

                                        $subscription_enabled = false;

                                        try {

                                            $subscription_enabled = \App\CentralLogics\Helpers::subscription_check();

                                        } catch (Exception $e) {

                                            // Check if subscription packages exist as fallback

                                            $subscription_enabled = isset($packages) && count($packages) > 0;

                                        }

                                    @endphp

                                    

                                    @if($subscription_enabled)

                                    <div class="plan-option" onclick="selectBusinessPlan('subscription-base')">

                                        <input type="radio" name="business_plan" value="subscription-base" id="subscription">

                                        <div class="plan-header">

                                            <div class="plan-title"><i class="fas fa-calendar-alt" style="margin-right: 0.5rem;"></i>Subscription Based</div>

                                            <div class="plan-price">Monthly Plans</div>

                                        </div>

                                        <div class="plan-description">

                                            Choose from our flexible subscription packages. Access features according to your selected plan.

                                        </div>

                                    </div>

                                    @endif

                                </div>

    

                                <div class="subscription-plans" id="subscriptionPlans">

                                    <h4 style="margin-bottom: 1rem; color: var(--title-clr); font-size: 1.1rem;">Choose Subscription Package</h4>

                                    <div class="pricing-cards" id="packagesContainer">

                                        <!-- Packages will be loaded dynamically -->

                                    </div>

                                </div>

                                

                                <div class="form-actions">

                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">

                                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Back

                                    </button>

                                    <button type="submit" class="btn btn-primary" id="submitBtn">

                                        <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> Complete Registration

                                    </button>

                                </div>

                            </div>

                        </div>

    

                        <!-- Success Step -->

                        <div class="form-step" id="success">

                            <div class="form-card">

                                <div class="success-message">

                                    <div class="success-icon">

                                        <i class="fas fa-check"></i>

                                    </div>

                                    <div class="success-title">Registration Complete!</div>

                                    <div class="success-text">

                                        Thank you for registering with {{ $business_name ?? 'us' }}. Our team will review your application and contact you within 24-48 hours to complete the setup process and get you started.

                                    </div>

                                </div>

                            </div>

                        </div>

    

                        <!-- Hidden inputs for language support -->

                        <input type="hidden" name="lang[]" value="default">

                    </form>

                </div>

            </div>

        </div>

    <script>
        let currentStep = 1;
        const totalSteps = 8;
        let slideIndex = 0;
        let isTransitioning = false;
        let availableModules = [];
        let availablePackages = [];
        let phoneVerified = false;
        let resendCountdown = 0;
        let resendTimer = null;

        // Setup CSRF token for AJAX requests
        let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        // Fallback to form token if meta tag doesn't exist
        if (!csrfToken) {
            csrfToken = document.querySelector('input[name="_token"]')?.value;
        }
        console.log('CSRF Token loaded:', csrfToken ? 'Yes' : 'No');

        // OTP Functions
        async function sendOtp() {
            const phone = document.getElementById('phone').value.trim();

            if (!phone || phone.length < 10) {
                showNotification('Please enter a valid phone number', 'error');
                return;
            }

            const btn = document.getElementById('sendOtpBtn');
            const originalText = btn.innerHTML;
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            btn.disabled = true;

            console.log('Sending OTP to:', phone);
            console.log('CSRF Token:', csrfToken);

            try {
                // Use FormData for better CSRF token handling
                const formData = new FormData();
                formData.append('phone', phone);
                formData.append('_token', csrfToken);

                const response = await fetch('{{ route("restaurant.send-otp") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);

                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    console.log('Response data:', data);

                    if (data.success) {
                        showNotification(data.message, 'success');
                        document.getElementById('phoneInputSection').style.display = 'none';
                        document.getElementById('otpInputSection').style.display = 'block';
                        document.getElementById('otpSentPhone').textContent = phone;
                        startResendTimer(60);
                        document.querySelectorAll('.otp-digit')[0].focus();
                    } else {
                        showNotification(data.message || 'Failed to send OTP', 'error');
                        if (data.wait_time) {
                            startResendTimer(data.wait_time);
                        }
                    }
                } else {
                    // Response is not JSON - might be HTML error page
                    const text = await response.text();
                    console.error('Non-JSON response:', text.substring(0, 500));
                    showNotification('Server error. Please try again.', 'error');
                }
            } catch (error) {
                console.error('OTP Error:', error);
                showNotification('Network error. Please check your connection.', 'error');
            } finally {
                btn.classList.remove('loading');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function verifyOtp() {
            const otpDigits = document.querySelectorAll('.otp-digit');
            let otp = '';
            otpDigits.forEach(digit => otp += digit.value);

            if (otp.length !== 6) {
                showNotification('Please enter complete 6-digit OTP', 'error');
                return;
            }

            const phone = document.getElementById('phone').value.trim();
            const btn = document.getElementById('verifyOtpBtn');
            const originalText = btn.innerHTML;
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            btn.disabled = true;

            console.log('Verifying OTP for phone:', phone, 'OTP:', otp);

            try {
                const formData = new FormData();
                formData.append('phone', phone);
                formData.append('otp', otp);
                formData.append('_token', csrfToken);

                const response = await fetch('{{ route("restaurant.verify-otp") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                console.log('Verify OTP response status:', response.status);
                const data = await response.json();
                console.log('Verify OTP response data:', data);

                if (data.success) {
                    phoneVerified = true;
                    showNotification(data.message, 'success');
                    // Move to next step
                    currentStep++;
                    showStep(currentStep);
                    updateProgress();
                } else {
                    showNotification(data.message, 'error');
                    if (data.blocked) {
                        // Disable verify button temporarily
                        btn.disabled = true;
                        setTimeout(() => { btn.disabled = false; }, data.remaining_time * 1000 || 60000);
                    }
                    // Clear OTP inputs on error
                    otpDigits.forEach(digit => digit.value = '');
                    otpDigits[0].focus();
                }
            } catch (error) {
                console.error('Verify OTP error:', error);
                showNotification('Failed to verify OTP. Please try again.', 'error');
            } finally {
                btn.classList.remove('loading');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function resendOtp() {
            if (resendCountdown > 0) return;

            const phone = document.getElementById('phone').value.trim();
            const btn = document.getElementById('resendBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            console.log('Resending OTP to phone:', phone);

            try {
                const formData = new FormData();
                formData.append('phone', phone);
                formData.append('_token', csrfToken);

                const response = await fetch('{{ route("restaurant.resend-otp") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                console.log('Resend OTP response status:', response.status);
                const data = await response.json();
                console.log('Resend OTP response data:', data);

                if (data.success) {
                    showNotification(data.message, 'success');
                    startResendTimer(60);
                    // Clear previous OTP inputs
                    document.querySelectorAll('.otp-digit').forEach(digit => digit.value = '');
                    document.querySelectorAll('.otp-digit')[0].focus();
                } else {
                    showNotification(data.message, 'error');
                    if (data.wait_time) {
                        startResendTimer(data.wait_time);
                    }
                }
            } catch (error) {
                console.error('Resend OTP error:', error);
                showNotification('Failed to resend OTP. Please try again.', 'error');
            } finally {
                btn.innerHTML = 'Resend OTP';
            }
        }

        function editPhone() {
            document.getElementById('otpInputSection').style.display = 'none';
            document.getElementById('phoneInputSection').style.display = 'block';
            // Clear OTP inputs
            document.querySelectorAll('.otp-digit').forEach(digit => digit.value = '');
            if (resendTimer) {
                clearInterval(resendTimer);
            }
        }

        function startResendTimer(seconds) {
            resendCountdown = seconds;
            const timerEl = document.getElementById('resendTimer');
            const resendBtn = document.getElementById('resendBtn');

            timerEl.style.display = 'block';
            resendBtn.style.display = 'none';

            if (resendTimer) clearInterval(resendTimer);

            resendTimer = setInterval(() => {
                resendCountdown--;
                if (resendCountdown > 0) {
                    timerEl.textContent = `Resend OTP in ${resendCountdown}s`;
                } else {
                    clearInterval(resendTimer);
                    timerEl.style.display = 'none';
                    resendBtn.style.display = 'inline-block';
                    resendBtn.disabled = false;
                }
            }, 1000);

            timerEl.textContent = `Resend OTP in ${resendCountdown}s`;
        }

        // OTP Input Auto-focus
        document.addEventListener('DOMContentLoaded', function() {
            const otpDigits = document.querySelectorAll('.otp-digit');
            otpDigits.forEach((digit, index) => {
                digit.addEventListener('input', function(e) {
                    if (e.target.value.length === 1 && index < otpDigits.length - 1) {
                        otpDigits[index + 1].focus();
                    }
                    // Update hidden OTP value
                    let fullOtp = '';
                    otpDigits.forEach(d => fullOtp += d.value);
                    document.getElementById('otpValue').value = fullOtp;
                });

                digit.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        otpDigits[index - 1].focus();
                    }
                });

                // Handle paste
                digit.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                    pastedData.split('').forEach((char, i) => {
                        if (otpDigits[i]) {
                            otpDigits[i].value = char;
                        }
                    });
                    if (pastedData.length > 0) {
                        otpDigits[Math.min(pastedData.length, otpDigits.length) - 1].focus();
                    }
                    // Update hidden OTP value
                    document.getElementById('otpValue').value = pastedData;
                });
            });
        });

        // Enhanced Image slider functionality
        function showSlides() {
            if (isTransitioning) return;
            
            const slides = document.querySelectorAll('.slide');
            slides.forEach((slide, index) => {
                slide.classList.remove('active', 'prev');
                if (index === slideIndex) {
                    slide.classList.add('prev');
                }
            });
            
            slideIndex++;
            if (slideIndex >= slides.length) slideIndex = 0;
            
            setTimeout(() => {
                slides[slideIndex].classList.add('active');
                const slideText = slides[slideIndex].querySelectorAll('.slide-text h2, .slide-text p');
                slideText.forEach(element => {
                    element.style.animation = 'none';
                    element.offsetHeight;
                    element.style.animation = null;
                });
            }, 100);
            
            setTimeout(showSlides, 5000);
        }

        function syncSlideWithStep(step) {
            if (step <= 3) {
                slideIndex = step - 1;
                const slides = document.querySelectorAll('.slide');
                slides.forEach(slide => slide.classList.remove('active'));
                slides[slideIndex].classList.add('active');
                
                const slideText = slides[slideIndex].querySelectorAll('.slide-text h2, .slide-text p');
                slideText.forEach(element => {
                    element.style.animation = 'none';
                    element.offsetHeight;
                    element.style.animation = null;
                });
            }
        }

        setTimeout(showSlides, 5000);

        function updateProgress() {
            const steps = document.querySelectorAll('.progress-step');
            steps.forEach((step, index) => {
                if (index < currentStep) {
                    step.classList.add('active');
                    step.setAttribute('aria-selected', index === currentStep - 1 ? 'true' : 'false');
                } else {
                    step.classList.remove('active');
                    step.setAttribute('aria-selected', 'false');
                }
            });

            // Announce step change to screen readers
            if (typeof window.announceStepChange === 'function') {
                window.announceStepChange(currentStep, totalSteps);
            }

            // Update mobile navigation if present
            if (typeof window.updateMobileNav === 'function') {
                window.updateMobileNav();
            }
        }

        function showStep(step) {
            if (isTransitioning) return;
            isTransitioning = true;
            
            const currentStepElement = document.querySelector('.form-step.active');
            const nextStepElement = document.getElementById(`step${step}`);
            
            if (currentStepElement) {
                currentStepElement.classList.add('slide-out');
                setTimeout(() => {
                    currentStepElement.classList.remove('active', 'slide-out');
                }, 400);
            }
            
            setTimeout(() => {
                nextStepElement.classList.add('active');
                syncSlideWithStep(step);
                isTransitioning = false;
                focusFirstInput();
            }, 200);
        }

        function nextStep() {
            if (isTransitioning) return;
            
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                    updateProgress();
                }
            }
        }

        function prevStep() {
            if (isTransitioning) return;
            
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
                updateProgress();
            }
        }

        function validateCurrentStep() {
            const currentStepElement = document.getElementById(`step${currentStep}`);
            const inputs = currentStepElement.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;

            // Clear previous error states
            currentStepElement.querySelectorAll('.form-group').forEach(group => {
                group.classList.remove('error');
            });

            inputs.forEach(input => {
                const formGroup = input.closest('.form-group');
                if (!input.value.trim()) {
                    formGroup.classList.add('error');
                    isValid = false;
                    
                    // Add shake animation
                    input.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        input.style.animation = '';
                    }, 500);
                } else {
                    formGroup.classList.remove('error');
                }
            });

            // Special validation for step 1 (OTP) - cannot proceed without verification
            if (currentStep === 1 && !phoneVerified) {
                showNotification('Please verify your phone number first', 'error');
                return false;
            }

            // Special validation for step 7 (passwords)
            if (currentStep === 7) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const passwordGroup = document.getElementById('password').closest('.form-group');
                const confirmPasswordGroup = document.getElementById('confirmPassword').closest('.form-group');

                if (password.length < 8) {
                    passwordGroup.classList.add('error');
                    isValid = false;
                }

                if (password !== confirmPassword) {
                    confirmPasswordGroup.classList.add('error');
                    showNotification('Passwords do not match!', 'error');
                    isValid = false;
                }
            }

            // Validate business plan selection for step 8
            if (currentStep === 8) {
                const businessPlan = document.querySelector('input[name="business_plan"]:checked');
                if (!businessPlan) {
                    showNotification('Please select a business plan', 'error');
                    isValid = false;
                }
                
                if (businessPlan && businessPlan.value === 'subscription-base') {
                    const packageSelection = document.querySelector('input[name="package_id"]:checked');
                    if (!packageSelection) {
                        showNotification('Please select a subscription package', 'error');
                        isValid = false;
                    }
                }
            }

            if (!isValid) {
                showNotification('Please fill in all required fields', 'error');
            }

            return isValid;
        }

        // Dynamic module loading
        async function loadModules() {
            const zoneId = document.getElementById('zone_id').value;
            const moduleSelect = document.getElementById('module_id');
            
            if (!zoneId) {
                moduleSelect.innerHTML = '<option value="">Select zone first</option>';
                return;
            }

            try {
                showLoading(true);
                const response = await fetch(`{{ route('restaurant.get-all-modules') }}?zone_id=${zoneId}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                
                const modules = await response.json();
                moduleSelect.innerHTML = '<option value="">Choose service type</option>';
                
                modules.forEach(module => {
                    const option = document.createElement('option');
                    option.value = module.id;
                    option.textContent = module.text;
                    moduleSelect.appendChild(option);
                });
                
                showLoading(false);
            } catch (error) {
                console.error('Error loading modules:', error);
                showNotification('Error loading service types', 'error');
                showLoading(false);
            }
        }

        // Dynamic package loading
        async function loadPackages() {
            const moduleId = document.getElementById('module_id').value;
            
            if (!moduleId) return;

            try {
                const response = await fetch(`{{ route('restaurant.get-module-type') }}?id=${moduleId}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                // Store packages for later use
                if (data.view) {
                    // Parse packages from the returned view
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.view;
                    availablePackages = Array.from(tempDiv.querySelectorAll('.package-option')).map(pkg => ({
                        id: pkg.dataset.id,
                        name: pkg.dataset.name,
                        price: pkg.dataset.price,
                        type: pkg.dataset.type,
                        validity: pkg.dataset.validity
                    }));
                }
            } catch (error) {
                console.error('Error loading packages:', error);
            }
        }

        function selectBusinessPlan(plan) {
            document.querySelectorAll('.plan-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
            document.getElementById(plan === 'commission-base' ? 'commission' : 'subscription').checked = true;
            
            const subscriptionPlans = document.getElementById('subscriptionPlans');
            if (plan === 'subscription-base') {
                loadSubscriptionPackages();
                subscriptionPlans.classList.add('show');
            } else {
                subscriptionPlans.classList.remove('show');
                clearPackageSelection();
            }
        }

        function loadSubscriptionPackages() {
            const container = document.getElementById('packagesContainer');
            container.innerHTML = '';

            // Use packages from Laravel backend
            @if(isset($packages) && count($packages) > 0)
                const packages = @json($packages);
                
                packages.forEach((pkg, index) => {
                    const isMiddle = index === Math.floor(packages.length / 2);
                    const card = document.createElement('div');
                    card.className = `pricing-card ${isMiddle ? 'featured' : ''}`;
                    card.onclick = () => selectPackage(pkg.id);
                    
                    card.innerHTML = `
                        <input type="radio" name="package_id" value="${pkg.id}" id="package_${pkg.id}">
                        <div class="plan-name">${pkg.package_name}</div>
                        <div class="plan-price-big">${pkg.price}</div>
                        <div class="plan-period">/ ${pkg.validity} days</div>
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> ${pkg.text || 'Full Access'}</li>
                            <li><i class="fas fa-check"></i> Priority Support</li>
                            <li><i class="fas fa-check"></i> Advanced Analytics</li>
                        </ul>
                    `;
                    
                    container.appendChild(card);
                });
            @else
                container.innerHTML = '<p style="text-align: center; color: var(--gray-600);">No packages available</p>';
            @endif
        }

        function selectPackage(packageId) {
            document.querySelectorAll('.pricing-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
            document.getElementById(`package_${packageId}`).checked = true;
        }

        function clearPackageSelection() {
            document.querySelectorAll('.pricing-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelectorAll('input[name="package_id"]').forEach(input => {
                input.checked = false;
            });
        }

        // Geolocation functionality
        function getLocation() {
            const button = event.target;
            const originalText = button.innerHTML;
            
            if (!navigator.geolocation) {
                showNotification('Geolocation is not supported by this browser', 'error');
                return;
            }
            
            button.classList.add('loading');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting Location...';
            button.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude.toFixed(6);
                    const lng = position.coords.longitude.toFixed(6);
                    
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    
                    button.innerHTML = '<i class="fas fa-check"></i> Location Found';
                    button.style.background = 'var(--success-clr)';
                    button.classList.remove('loading');
                    
                    showNotification('Location successfully captured!', 'success');
                    
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.style.background = '';
                        button.disabled = false;
                    }, 3000);
                },
                function(error) {
                    button.innerHTML = originalText;
                    button.classList.remove('loading');
                    button.disabled = false;
                    
                    let errorMessage = 'Unable to get location';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access denied by user';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information unavailable';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out';
                            break;
                    }
                    showNotification(errorMessage, 'error');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        // File upload handling
        function handleFileSelect(input, type) {
            const file = input.files[0];
            const selectedDiv = document.getElementById(`${type}-selected`);
            
            if (file) {
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('File size must be less than 2MB', 'error');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                if (!file.type.match(/image\/(jpeg|jpg|png|webp)/)) {
                    showNotification('Please select a valid image file (JPEG, PNG, WEBP)', 'error');
                    input.value = '';
                    return;
                }
                
                selectedDiv.textContent = `Selected: ${file.name}`;
                selectedDiv.style.display = 'block';
                input.closest('.form-group').classList.remove('error');
            } else {
                selectedDiv.style.display = 'none';
            }
        }

        // Form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateCurrentStep()) {
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            showLoading(true);
            
            // Submit the form
            this.submit();
        });

        // Enhanced notification system
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            document.querySelectorAll('.notification').forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        // Loading overlay
        function showLoading(show) {
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = show ? 'flex' : 'none';
        }

        // Form validation helpers
        document.addEventListener('DOMContentLoaded', function() {
            // Phone number formatting
            const phoneInput = document.getElementById('phone');
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // remove non-digits
                if (value.length >= 6) {
                    value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2-$3');
                }
                e.target.value = value;
            });


            // Real-time email validation
            const emailInput = document.getElementById('email');
            emailInput.addEventListener('input', function(e) {
                const email = e.target.value;
                const formGroup = e.target.closest('.form-group');
                
                if (email && isValidEmail(email)) {
                    formGroup.classList.remove('error');
                    e.target.style.borderColor = 'var(--success-clr)';
                } else if (email) {
                    e.target.style.borderColor = 'var(--danger-clr)';
                } else {
                    e.target.style.borderColor = 'var(--border-clr)';
                    formGroup.classList.remove('error');
                }
            });

            // Password strength validation
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            
            passwordInput.addEventListener('input', function(e) {
                const strength = calculatePasswordStrength(e.target.value);
                updatePasswordStrength(e.target, strength);
            });
            
            confirmPasswordInput.addEventListener('input', function(e) {
                const password = passwordInput.value;
                const confirmPassword = e.target.value;
                const formGroup = e.target.closest('.form-group');
                
                if (confirmPassword && password === confirmPassword) {
                    e.target.style.borderColor = 'var(--success-clr)';
                    formGroup.classList.remove('error');
                } else if (confirmPassword) {
                    e.target.style.borderColor = 'var(--danger-clr)';
                } else {
                    e.target.style.borderColor = 'var(--border-clr)';
                    formGroup.classList.remove('error');
                }
            });

            // Delivery time validation
            const minTimeInput = document.getElementById('minimum_delivery_time');
            const maxTimeInput = document.getElementById('maximum_delivery_time');
            
            function validateDeliveryTimes() {
                const minTime = parseInt(minTimeInput.value);
                const maxTime = parseInt(maxTimeInput.value);
                
                if (minTime && maxTime && minTime >= maxTime) {
                    maxTimeInput.style.borderColor = 'var(--danger-clr)';
                    showNotification('Maximum delivery time must be greater than minimum', 'error');
                } else {
                    minTimeInput.style.borderColor = 'var(--border-clr)';
                    maxTimeInput.style.borderColor = 'var(--border-clr)';
                }
            }
            
            minTimeInput.addEventListener('input', validateDeliveryTimes);
            maxTimeInput.addEventListener('input', validateDeliveryTimes);

            // Handle validation errors from Laravel
            @if($errors->any())
                @foreach($errors->all() as $error)
                    showNotification('{{ $error }}', 'error');
                @endforeach
            @endif

            // Handle success message
            @if(session('success'))
                showNotification('{{ session('success') }}', 'success');
            @endif
        });

        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }

        function updatePasswordStrength(input, strength) {
            const colors = ['var(--danger-clr)', 'var(--warning-clr)', '#f39c12', 'var(--success-clr)', 'var(--success-clr)'];
            const formGroup = input.closest('.form-group');
            
            if (strength > 0 && input.value.length > 0) {
                input.style.borderColor = colors[strength - 1];
                input.style.boxShadow = `0 0 0 4px ${colors[strength - 1]}20`;
                
                if (strength >= 3) {
                    formGroup.classList.remove('error');
                }
            } else {
                input.style.borderColor = 'var(--border-clr)';
                input.style.boxShadow = '';
            }
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Focus management
        function focusFirstInput() {
            const currentStepElement = document.getElementById(`step${currentStep}`) || document.getElementById('step1');
            const firstInput = currentStepElement.querySelector('input:not([readonly]):not([type="hidden"]), select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.target.matches('textarea') && !e.target.matches('button[type="submit"]')) {
                e.preventDefault();
                const nextButton = document.querySelector('.form-step.active .btn-primary:not([type="submit"])');
                if (nextButton && !nextButton.disabled) {
                    nextButton.click();
                }
            }
        });

        // Mobile responsive adjustments
        function handleResize() {
            const isMobile = window.innerWidth <= 768;
            document.body.classList.toggle('mobile-view', isMobile);
        }

        window.addEventListener('resize', handleResize);
        window.addEventListener('load', () => {
            handleResize();
            focusFirstInput();
        });

        // Smooth scroll to top on step change (mobile)
        function scrollToTop() {
            if (window.innerWidth <= 768) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // Enhanced step transitions
        const originalShowStepFunc = showStep;
        showStep = function(step) {
            scrollToTop();
            originalShowStepFunc(step);
        };

        // Auto-save functionality using sessionStorage fallback
        function saveFormData() {
            const formData = new FormData(document.getElementById('registrationForm'));
            const data = Object.fromEntries(formData);
            
            try {
                sessionStorage.setItem('vendorRegistrationData', JSON.stringify(data));
            } catch (e) {
                // Fallback to memory storage if sessionStorage fails
                window.formBackup = data;
            }
        }

        function loadFormData() {
            let savedData = null;
            
            try {
                savedData = JSON.parse(sessionStorage.getItem('vendorRegistrationData'));
            } catch (e) {
                savedData = window.formBackup;
            }
            
            if (savedData) {
                Object.keys(savedData).forEach(key => {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input && savedData[key]) {
                        if (input.type === 'radio') {
                            if (input.value === savedData[key]) {
                                input.checked = true;
                            }
                        } else {
                            input.value = savedData[key];
                        }
                    }
                });
            }
        }

        function clearSavedData() {
            try {
                sessionStorage.removeItem('vendorRegistrationData');
            } catch (e) {
                window.formBackup = null;
            }
        }

        // Save form data on input change
        document.getElementById('registrationForm').addEventListener('input', saveFormData);

        // Initialize form
        window.addEventListener('load', function() {
            loadFormData();
            
            // Check if zone is pre-selected and load modules
            const zoneSelect = document.getElementById('zone_id');
            if (zoneSelect.value) {
                loadModules();
            }
            
            // Check if module is pre-selected and load packages
            const moduleSelect = document.getElementById('module_id');
            if (moduleSelect.value) {
                loadPackages();
            }
        });

        // Handle browser back button
        window.addEventListener('beforeunload', function(e) {
            if (currentStep > 1 && currentStep < totalSteps) {
                saveFormData();
            }
        });
    </script>
    <script src="{{ asset('public/assets/admin/js/view-pages/vendor-registration.js') }}"></script>
</body>
</html>