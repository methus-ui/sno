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

        .plan-selection-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .plan-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--title-clr);
            margin-bottom: 0.5rem;
        }

        .plan-header p {
            color: var(--gray-600);
            font-size: 1rem;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 2rem;
        }

        .plan-card {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 16px;
            padding: 2rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            text-align: center;
        }

        .plan-card:hover {
            border-color: var(--primary-clr);
            box-shadow: 0 8px 30px rgba(216, 46, 94, 0.15);
            transform: translateY(-4px);
        }

        .plan-card.selected {
            border-color: var(--primary-clr);
            background: linear-gradient(135deg, rgba(216, 46, 94, 0.05), rgba(255, 77, 122, 0.03));
            box-shadow: 0 8px 30px rgba(216, 46, 94, 0.2);
        }

        .plan-card.featured {
            border-color: var(--primary-clr);
        }

        .plan-card.featured::before {
            content: 'RECOMMENDED';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--primary-clr), #ff6b8a);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .plan-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .plan-icon {
            width: 70px;
            height: 70px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }

        .plan-card.selected .plan-icon {
            background: var(--primary-clr);
            color: white;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--title-clr);
            margin-bottom: 0.5rem;
        }

        .plan-tagline {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .plan-price {
            margin-bottom: 1.5rem;
        }

        .plan-price .amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-clr);
        }

        .plan-price .period {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem;
            text-align: left;
        }

        .plan-features li {
            padding: 0.6rem 0;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        .plan-features li i {
            width: 22px;
            height: 22px;
            background: var(--success-clr);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 0.7rem;
        }

        .plan-features li.disabled {
            color: var(--gray-500);
            text-decoration: line-through;
        }

        .plan-features li.disabled i {
            background: var(--gray-200);
            color: var(--gray-500);
        }

        .select-btn {
            width: 100%;
            padding: 14px;
            border: 2px solid var(--primary-clr);
            background: transparent;
            color: var(--primary-clr);
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .plan-card.selected .select-btn,
        .select-btn:hover {
            background: var(--primary-clr);
            color: white;
        }

        .commission-option {
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .commission-option:hover {
            border-color: var(--primary-clr);
        }

        .commission-option.selected {
            border-color: var(--primary-clr);
            background: linear-gradient(135deg, rgba(216, 46, 94, 0.05), rgba(255, 77, 122, 0.03));
        }

        .commission-option input[type="radio"] {
            width: 22px;
            height: 22px;
            margin-right: 1rem;
            accent-color: var(--primary-clr);
        }

        .commission-option .option-content h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--title-clr);
            margin-bottom: 0.25rem;
        }

        .commission-option .option-content p {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin: 0;
        }

        .commission-badge {
            background: var(--primary-clr);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: auto;
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
        }

        .btn-back:hover {
            background: var(--gray-200);
        }

        .btn-next {
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--primary-clr), #ff6b8a);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(216, 46, 94, 0.3);
        }

        .btn-next:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(216, 46, 94, 0.4);
        }

        @media (max-width: 768px) {
            .plans-grid {
                grid-template-columns: 1fr;
            }

            .plan-card.featured::before {
                font-size: 0.65rem;
                padding: 3px 12px;
            }

            .plan-price .amount {
                font-size: 2rem;
            }

            .commission-option {
                flex-direction: column;
                text-align: center;
            }

            .commission-option input[type="radio"] {
                margin-bottom: 0.5rem;
            }

            .commission-badge {
                margin: 0.5rem 0 0;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
@endpush
@section('content')
    <section class="m-0 py-5">
        <div class="container">
            <div class="plan-selection-container">
                <div class="plan-header">
                    <h2>{{ translate('Choose Your Business Plan') }}</h2>
                    <p>{{ translate('Select the plan that best fits your business needs') }}</p>
                </div>

                <form action="{{ route('restaurant.business_plan') }}" method="post" id="planForm">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $store_id }}">

                    @if(\App\CentralLogics\Helpers::commission_check())
                    <!-- Commission Option -->
                    <label class="commission-option" id="commissionOption">
                        <input type="radio" name="business_plan" value="commission-base">
                        <div class="option-content">
                            <h4>{{ translate('Commission Based') }}</h4>
                            <p>{{ translate('Pay only when you earn. No monthly fees, just') }} {{ $admin_commission ?? '10' }}% {{ translate('commission per order.') }}</p>
                        </div>
                        <span class="commission-badge">{{ $admin_commission ?? '10' }}% {{ translate('Fee') }}</span>
                    </label>
                    @endif

                    <div id="subscription-section">
                        <h4 style="text-align: center; margin-bottom: 1.5rem; color: var(--gray-700);">
                            {{ translate('Or choose a subscription plan') }}
                        </h4>

                        <!-- Subscription Plans Grid -->
                        <div class="plans-grid">
                            @foreach($packages as $index => $package)
                            <label class="plan-card {{ $package->default ? 'featured' : '' }}" data-package-id="{{ $package->id }}">
                                <input type="radio" name="package_id" value="{{ $package->id }}">
                                <input type="radio" name="business_plan" value="subscription-base" class="subscription-radio" style="display: none;">

                                <div class="plan-icon">
                                    @if($package->price <= 2000)
                                        <i class="fas fa-store"></i>
                                    @else
                                        <i class="fas fa-rocket"></i>
                                    @endif
                                </div>

                                <div class="plan-name">{{ $package->package_name }}</div>
                                <div class="plan-tagline">{{ $package->text ?? translate('Grow your business online') }}</div>

                                <div class="plan-price">
                                    <span class="amount">{{ \App\CentralLogics\Helpers::format_currency($package->price) }}</span>
                                    <span class="period">/ {{ $package->validity }} {{ translate('days') }}</span>
                                </div>

                                <ul class="plan-features">
                                    <li class="{{ $package->pos ? '' : 'disabled' }}">
                                        <i class="fas {{ $package->pos ? 'fa-check' : 'fa-times' }}"></i>
                                        {{ translate('POS System') }}
                                    </li>
                                    <li class="{{ $package->mobile_app ? '' : 'disabled' }}">
                                        <i class="fas {{ $package->mobile_app ? 'fa-check' : 'fa-times' }}"></i>
                                        {{ translate('Mobile App Access') }}
                                    </li>
                                    <li class="{{ $package->chat ? '' : 'disabled' }}">
                                        <i class="fas {{ $package->chat ? 'fa-check' : 'fa-times' }}"></i>
                                        {{ translate('Customer Chat') }}
                                    </li>
                                    <li class="{{ $package->review ? '' : 'disabled' }}">
                                        <i class="fas {{ $package->review ? 'fa-check' : 'fa-times' }}"></i>
                                        {{ translate('Review Section') }}
                                    </li>
                                    <li class="{{ $package->self_delivery ? '' : 'disabled' }}">
                                        <i class="fas {{ $package->self_delivery ? 'fa-check' : 'fa-times' }}"></i>
                                        {{ translate('Self Delivery') }}
                                    </li>
                                    <li>
                                        <i class="fas fa-check"></i>
                                        {{ $package->max_order == 'unlimited' ? translate('Unlimited Orders') : $package->max_order . ' ' . translate('Orders') }}
                                    </li>
                                    <li>
                                        <i class="fas fa-check"></i>
                                        {{ $package->max_product == 'unlimited' ? translate('Unlimited Products') : $package->max_product . ' ' . translate('Products') }}
                                    </li>
                                </ul>

                                <button type="button" class="select-btn">
                                    {{ translate('Select Plan') }}
                                </button>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('restaurant.back', ['store_id' => $store_id]) }}" class="btn-back">
                            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>{{ translate('Back') }}
                        </a>
                        <button type="submit" class="btn-next">
                            {{ translate('Continue') }} <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
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
        const planCards = document.querySelectorAll('.plan-card');
        const commissionOption = document.getElementById('commissionOption');
        const commissionRadio = commissionOption ? commissionOption.querySelector('input[type="radio"]') : null;

        // Handle plan card selection
        planCards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected from all cards and commission option
                planCards.forEach(c => c.classList.remove('selected'));
                if (commissionOption) {
                    commissionOption.classList.remove('selected');
                    if (commissionRadio) commissionRadio.checked = false;
                }

                // Select this card
                this.classList.add('selected');
                this.querySelector('input[name="package_id"]').checked = true;
                this.querySelector('.subscription-radio').checked = true;
            });
        });

        // Handle commission option selection
        if (commissionOption) {
            commissionOption.addEventListener('click', function() {
                // Remove selected from all cards
                planCards.forEach(c => {
                    c.classList.remove('selected');
                    c.querySelector('input[name="package_id"]').checked = false;
                });

                // Select commission option
                this.classList.add('selected');
                commissionRadio.checked = true;
            });
        }

        // Form validation
        document.getElementById('planForm').addEventListener('submit', function(e) {
            const businessPlan = document.querySelector('input[name="business_plan"]:checked');

            if (!businessPlan) {
                e.preventDefault();
                alert('{{ translate("Please select a business plan") }}');
                return;
            }

            if (businessPlan.value === 'subscription-base') {
                const packageId = document.querySelector('input[name="package_id"]:checked');
                if (!packageId) {
                    e.preventDefault();
                    alert('{{ translate("Please select a subscription package") }}');
                    return;
                }
            }
        });

        // Pre-select if coming back
        const checkedPlan = document.querySelector('input[name="business_plan"]:checked');
        if (checkedPlan) {
            if (checkedPlan.value === 'commission-base' && commissionOption) {
                commissionOption.classList.add('selected');
            } else {
                const checkedPackage = document.querySelector('input[name="package_id"]:checked');
                if (checkedPackage) {
                    checkedPackage.closest('.plan-card').classList.add('selected');
                }
            }
        }
    });
</script>
@endpush
