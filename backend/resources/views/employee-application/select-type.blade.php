<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php($business_name = \App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value ?? 'Snocart')
    @php($favicon = \App\Models\BusinessSetting::where(['key'=>'icon'])->first())

    <title>{{ translate('messages.join_snocart_team') }} | {{ $business_name }}</title>
    <meta name="description" content="{{ translate('messages.join_kashmir_success_story') }} - {{ translate('messages.empowering_local_business') }}">

    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ translate('messages.join_snocart_team') }} | {{ $business_name }}">
    <meta property="og:description" content="{{ translate('messages.built_by_kashmir_for_kashmir') }} - {{ translate('messages.local_employment_opportunities') }}">
    <meta property="og:site_name" content="{{ $business_name }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ translate('messages.join_snocart_team') }} | {{ $business_name }}">
    <meta name="twitter:description" content="{{ translate('messages.empowering_local_business') }}">

    <!-- Favicon -->
    <link rel="shortcut icon" href="">
    <link rel="icon" type="image/x-icon" href="{{\App\CentralLogics\Helpers::get_full_url('business', $favicon?->value ?? '', $favicon?->storage[0]?->value ?? 'public','favicon')}}">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
    <style>
        :root {
            --snocart-pink: #C2185B;
            --snocart-dark-pink: #AD1457;
            --snocart-light-pink: #F8BBD0;
            --text-dark: #2C3E50;
            --text-gray: #5A6C7D;
            --bg-light: #F8F9FA;
        }

        body {
            background: #FFFFFF;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text-dark);
        }

        .header-section {
            background: var(--bg-light);
            padding: 2rem 0 3rem;
            border-bottom: 3px solid var(--snocart-pink);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 1rem;
        }

        .brand-logo {
            color: var(--snocart-pink);
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 0.5rem;
        }

        .kashmir-badge {
            display: inline-block;
            background: var(--snocart-pink);
            color: white;
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-gray);
            margin-bottom: 0;
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--snocart-pink);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .content-section {
            padding: 4rem 0;
        }

        .career-card {
            background: white;
            border: 2px solid #E8E8E8;
            border-radius: 12px;
            padding: 2.5rem;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
        }

        .career-card:hover {
            border-color: var(--snocart-pink);
            box-shadow: 0 8px 24px rgba(194, 24, 91, 0.12);
            transform: translateY(-4px);
        }

        .card-badge {
            position: absolute;
            top: -12px;
            left: 2rem;
            background: var(--snocart-pink);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-icon {
            width: 70px;
            height: 70px;
            background: var(--snocart-light-pink);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
        }

        .card-description {
            font-size: 1rem;
            color: var(--text-gray);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
        }

        .benefits-list li {
            padding: 0.6rem 0;
            font-size: 0.95rem;
            color: var(--text-gray);
            display: flex;
            align-items: center;
        }

        .benefits-list li:before {
            content: "✓";
            display: inline-flex;
            width: 24px;
            height: 24px;
            background: var(--snocart-light-pink);
            color: var(--snocart-dark-pink);
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            margin-right: 0.8rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .apply-btn {
            width: 100%;
            background: var(--snocart-pink);
            color: white;
            font-weight: 600;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
        }

        .apply-btn:hover {
            background: var(--snocart-dark-pink);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(194, 24, 91, 0.3);
        }

        .status-section {
            background: var(--bg-light);
            border: 2px solid #E8E8E8;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin-top: 3rem;
        }

        .status-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .status-section p {
            color: var(--text-gray);
            margin-bottom: 0;
        }

        .status-section a {
            color: var(--snocart-pink);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 2px solid var(--snocart-pink);
            padding-bottom: 2px;
            transition: all 0.3s;
        }

        .status-section a:hover {
            color: var(--snocart-dark-pink);
            border-bottom-color: var(--snocart-dark-pink);
        }

        .kashmir-section {
            background: var(--snocart-pink);
            color: white;
            padding: 3rem 0;
            margin-top: 4rem;
            text-align: center;
        }

        .kashmir-section h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .kashmir-section p {
            font-size: 1.1rem;
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .value-cards {
            margin-top: 3rem;
        }

        .value-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            height: 100%;
            border: 2px solid #E8E8E8;
        }

        .value-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .value-card h5 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .value-card p {
            font-size: 0.9rem;
            color: var(--text-gray);
            margin: 0;
        }

        .card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-section">
        <div class="container">
            <div class="brand-header">
                <div class="brand-logo">SNOCART</div>
                <div class="kashmir-badge">
                    {{ translate('messages.made_in_kashmir') }}
                </div>
                <h1 class="hero-title">{{ translate('messages.join_kashmir_success_story') }}</h1>
                <p class="hero-subtitle">{{ translate('messages.empowering_local_business') }} • {{ translate('messages.local_employment_opportunities') }}</p>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-number">1000+</span>
                    <span class="stat-label">{{ translate('messages.kashmir_talent') }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">{{ translate('messages.local_vendors') }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">{{ translate('messages.cities') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Career Paths Section -->
    <div class="content-section">
        <div class="container">
            <div class="row g-4">
                <!-- Admin Employee Card -->
                <div class="col-lg-6">
                    <a href="{{ route('employee.register.admin') }}" class="card-link">
                        <div class="career-card">
                            <div class="card-badge">Full Time</div>
                            <div class="card-icon">💼</div>
                            <h3 class="card-title">{{ translate('messages.admin_employee') }}</h3>
                            <p class="card-description">
                                {{ translate('messages.lead_innovation_behind_scenes') }}
                            </p>

                            <ul class="benefits-list">
                                <li>{{ translate('messages.manage_multi_modules') }}</li>
                                <li>{{ translate('messages.strategic_decision_making') }}</li>
                                <li>{{ translate('messages.data_analytics_insights') }}</li>
                                <li>{{ translate('messages.team_collaboration') }}</li>
                                <li>{{ translate('messages.career_advancement') }}</li>
                            </ul>

                            <button class="apply-btn">
                                {{ translate('messages.apply_now') }}
                            </button>
                        </div>
                    </a>
                </div>

                <!-- Picker/Vendor Employee Card -->
                <div class="col-lg-6">
                    <a href="{{ route('employee.register.vendor') }}" class="card-link">
                        <div class="career-card">
                            <div class="card-badge">{{ translate('messages.warehouse_picker') }}</div>
                            <div class="card-icon">📦</div>
                            <h3 class="card-title">{{ translate('messages.order_fulfillment_specialist') }}</h3>
                            <p class="card-description">
                                {{ translate('messages.pick_pack_deliver') }}
                            </p>

                            <ul class="benefits-list">
                                <li>{{ translate('messages.efficient_order_picking') }}</li>
                                <li>{{ translate('messages.inventory_handling') }}</li>
                                <li>{{ translate('messages.fast_paced_environment') }}</li>
                                <li>{{ translate('messages.flexible_schedules') }}</li>
                                <li>{{ translate('messages.performance_bonuses') }}</li>
                            </ul>

                            <button class="apply-btn">
                                {{ translate('messages.apply_now') }}
                            </button>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Already Applied Section -->
            <div class="status-section">
                <h4>{{ translate('messages.already_submitted_application') }}</h4>
                <p>
                    {{ translate('messages.track_your_progress') }}
                    <a href="{{ route('employee.application.check') }}">
                        {{ translate('messages.check_application_status') }}
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Kashmir Values Section -->
    <div class="kashmir-section">
        <div class="container">
            <h3>{{ translate('messages.built_by_kashmir_for_kashmir') }}</h3>
            <p>
                {{ translate('messages.connecting_kashmir_communities') }}.
                {{ translate('messages.helping_local_vendors_thrive') }}.
                {{ translate('messages.empowering_kashmir_youth') }}.
            </p>
        </div>
    </div>

    <!-- Value Cards Section -->
    <div class="content-section">
        <div class="container">
            <div class="row value-cards">
                <div class="col-md-4 mb-4">
                    <div class="value-card">
                        <div class="value-icon">🎯</div>
                        <h5>{{ translate('messages.clear_career_path') }}</h5>
                        <p>{{ translate('messages.structured_growth_plan') }}</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="value-card">
                        <div class="value-icon">🤝</div>
                        <h5>{{ translate('messages.support_local_vendors') }}</h5>
                        <p>{{ translate('messages.be_part_of_kashmir_growth') }}</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="value-card">
                        <div class="value-icon">💪</div>
                        <h5>{{ translate('messages.kashmir_first') }}</h5>
                        <p>{{ translate('messages.supporting_kashmir_economy') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
