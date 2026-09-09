@extends('layouts.landing.app')
@section('title', translate('messages.store_registration'))
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/vendor-registration.css') }}">
    <style>
        :root {
            --primary-clr: #D82E5E;
            --primary-dark: #B8254A;
            --success-clr: #00aa6d;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #e5e5e5;
            --gray-500: #737373;
            --gray-600: #525252;
            --gray-700: #404040;
            --title-clr: #1a1a1a;
        }

        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .payment-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--title-clr);
            margin-bottom: 0.5rem;
        }

        .payment-header p {
            color: var(--gray-600);
            font-size: 1rem;
        }

        .plan-summary {
            background: linear-gradient(135deg, #f8f9fa, #fff);
            border: 2px solid var(--gray-200);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .plan-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .plan-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-clr);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .plan-details h4 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--title-clr);
            margin: 0 0 4px;
        }

        .plan-details p {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin: 0;
        }

        .plan-price-tag {
            background: var(--primary-clr);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .payment-section {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .payment-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--title-clr);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-section-title i {
            color: var(--primary-clr);
        }

        .free-trial-option {
            background: linear-gradient(135deg, rgba(0, 170, 109, 0.1), rgba(0, 170, 109, 0.05));
            border: 2px solid var(--success-clr);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .free-trial-option:hover {
            box-shadow: 0 4px 15px rgba(0, 170, 109, 0.2);
        }

        .free-trial-option.selected {
            background: linear-gradient(135deg, rgba(0, 170, 109, 0.15), rgba(0, 170, 109, 0.1));
            box-shadow: 0 4px 15px rgba(0, 170, 109, 0.25);
        }

        .free-trial-option input[type="radio"] {
            width: 22px;
            height: 22px;
            accent-color: var(--success-clr);
        }

        .free-trial-content h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--success-clr);
            margin: 0 0 4px;
        }

        .free-trial-content p {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin: 0;
        }

        .free-trial-badge {
            background: var(--success-clr);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-left: auto;
        }

        .payment-methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }

        .payment-method-card {
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-align: center;
        }

        .payment-method-card:hover {
            border-color: var(--primary-clr);
            box-shadow: 0 4px 15px rgba(216, 46, 94, 0.1);
        }

        .payment-method-card.selected {
            border-color: var(--primary-clr);
            background: linear-gradient(135deg, rgba(216, 46, 94, 0.05), rgba(255, 77, 122, 0.03));
            box-shadow: 0 4px 15px rgba(216, 46, 94, 0.15);
        }

        .payment-method-card input[type="radio"] {
            display: none;
        }

        .payment-method-card img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .payment-method-card span {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .payment-method-card .check-icon {
            display: none;
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: var(--primary-clr);
            border-radius: 50%;
            color: white;
            font-size: 0.7rem;
            align-items: center;
            justify-content: center;
        }

        .payment-method-card.selected .check-icon {
            display: flex;
        }

        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }

        .trust-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        .trust-badge i {
            color: var(--success-clr);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            gap: 1rem;
        }

        .btn-back {
            padding: 14px 28px;
            background: var(--gray-100);
            color: var(--gray-600);
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-back:hover {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-pay {
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--primary-clr), #ff6b8a);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(216, 46, 94, 0.3);
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(216, 46, 94, 0.4);
        }

        .btn-pay:disabled {
            background: var(--gray-200);
            color: var(--gray-500);
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        @media (max-width: 768px) {
            .plan-summary {
                flex-direction: column;
                text-align: center;
            }

            .plan-info {
                flex-direction: column;
            }

            .free-trial-option {
                flex-direction: column;
                text-align: center;
            }

            .free-trial-badge {
                margin: 0.5rem 0 0;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
@endpush
@section('content')
    @php
        $package = \App\Models\SubscriptionPackage::find($package_id);
        if (data_get($free_trial_settings, 'subscription_free_trial_type') == 'year') {
            $trial_period = data_get($free_trial_settings, 'subscription_free_trial_days') > 0 ? data_get($free_trial_settings, 'subscription_free_trial_days') / 365 : 0;
            $trial_unit = translate('year');
        } elseif (data_get($free_trial_settings, 'subscription_free_trial_type') == 'month') {
            $trial_period = data_get($free_trial_settings, 'subscription_free_trial_days') > 0 ? data_get($free_trial_settings, 'subscription_free_trial_days') / 30 : 0;
            $trial_unit = translate('month');
        } else {
            $trial_period = data_get($free_trial_settings, 'subscription_free_trial_days') > 0 ? data_get($free_trial_settings, 'subscription_free_trial_days') : 0;
            $trial_unit = translate('days');
        }
    @endphp

    <section class="m-0 py-5">
        <div class="container">
            <div class="payment-container">
                <div class="payment-header">
                    <h2>{{ translate('Complete Your Payment') }}</h2>
                    <p>{{ translate('Choose a payment method to activate your subscription') }}</p>
                </div>

                <!-- Plan Summary -->
                <div class="plan-summary">
                    <div class="plan-info">
                        <div class="plan-icon">
                            @if($package && $package->price <= 2000)
                                <i class="fas fa-store"></i>
                            @else
                                <i class="fas fa-rocket"></i>
                            @endif
                        </div>
                        <div class="plan-details">
                            <h4>{{ $package->package_name ?? translate('Selected Plan') }}</h4>
                            <p>{{ $package->validity ?? 30 }} {{ translate('days validity') }}</p>
                        </div>
                    </div>
                    <div class="plan-price-tag">
                        {{ \App\CentralLogics\Helpers::format_currency($package->price ?? 0) }}
                    </div>
                </div>

                <form action="{{ route('restaurant.payment') }}" method="post" id="paymentForm">
                    @csrf
                    @method('post')
                    <input type="hidden" name="store_id" value="{{ $store_id }}">
                    <input type="hidden" name="package_id" value="{{ $package_id }}">

                    <div class="payment-section">
                        <!-- Free Trial Option -->
                        @if (data_get($free_trial_settings, 'subscription_free_trial_status') == 1 && data_get($free_trial_settings, 'subscription_free_trial_days') > 0)
                            <label class="free-trial-option" id="freeTrialOption">
                                <input type="radio" checked value="free_trial" name="payment">
                                <div class="free-trial-content">
                                    <h4><i class="fas fa-gift" style="margin-right: 8px;"></i>{{ translate('Start with Free Trial') }}</h4>
                                    <p>{{ translate('Try all features free for') }} {{ $trial_period }} {{ $trial_unit }} {{ translate('before paying') }}</p>
                                </div>
                                <span class="free-trial-badge">{{ translate('FREE') }}</span>
                            </label>
                        @endif

                        <!-- Payment Methods -->
                        <div class="payment-section-title">
                            <i class="fas fa-credit-card"></i>
                            {{ translate('Pay Via Online') }}
                            <span style="font-weight: 400; color: var(--gray-500); font-size: 0.85rem;">
                                ({{ translate('Faster & secure way to pay') }})
                            </span>
                        </div>

                        <div class="payment-methods-grid">
                            @foreach ($payment_methods as $item)
                                <label class="payment-method-card" style="position: relative;">
                                    <input type="radio" value="{{ $item['gateway'] }}" name="payment">
                                    <span class="check-icon"><i class="fas fa-check"></i></span>
                                    <img src="{{ \App\CentralLogics\Helpers::get_full_url('payment_modules/gateway_image', $item['gateway_image'], $item['storage'] ?? 'public') }}" alt="{{ $item['gateway_title'] }}">
                                    <span>{{ $item['gateway_title'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        <!-- Trust Badges -->
                        <div class="trust-badges">
                            <div class="trust-badge">
                                <i class="fas fa-lock"></i>
                                {{ translate('Secure Payment') }}
                            </div>
                            <div class="trust-badge">
                                <i class="fas fa-shield-alt"></i>
                                {{ translate('256-bit Encryption') }}
                            </div>
                            <div class="trust-badge">
                                <i class="fas fa-undo"></i>
                                {{ translate('Easy Refunds') }}
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('restaurant.back', ['store_id' => $store_id]) }}" class="btn-back">
                            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>{{ translate('Back') }}
                        </a>
                        <button type="submit" class="btn-pay" id="payButton">
                            <i class="fas fa-lock" style="margin-right: 8px;"></i>{{ translate('Pay Securely') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('script_2')
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentCards = document.querySelectorAll('.payment-method-card');
        const freeTrialOption = document.getElementById('freeTrialOption');
        const payButton = document.getElementById('payButton');

        // Handle payment method selection
        paymentCards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected from all
                paymentCards.forEach(c => c.classList.remove('selected'));
                if (freeTrialOption) {
                    freeTrialOption.classList.remove('selected');
                    freeTrialOption.querySelector('input').checked = false;
                }

                // Select this card
                this.classList.add('selected');
                this.querySelector('input').checked = true;

                // Update button text
                payButton.innerHTML = '<i class="fas fa-lock" style="margin-right: 8px;"></i>{{ translate("Pay Securely") }}';
            });
        });

        // Handle free trial selection
        if (freeTrialOption) {
            freeTrialOption.addEventListener('click', function() {
                // Remove selected from payment cards
                paymentCards.forEach(c => c.classList.remove('selected'));

                // Select free trial
                this.classList.add('selected');
                this.querySelector('input').checked = true;

                // Update button text
                payButton.innerHTML = '<i class="fas fa-gift" style="margin-right: 8px;"></i>{{ translate("Start Free Trial") }}';
            });

            // If free trial is default checked, mark it as selected
            if (freeTrialOption.querySelector('input').checked) {
                freeTrialOption.classList.add('selected');
                payButton.innerHTML = '<i class="fas fa-gift" style="margin-right: 8px;"></i>{{ translate("Start Free Trial") }}';
            }
        }

        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const selectedPayment = document.querySelector('input[name="payment"]:checked');
            if (!selectedPayment) {
                e.preventDefault();
                alert('{{ translate("Please select a payment method") }}');
            }
        });
    });
</script>
@endpush
