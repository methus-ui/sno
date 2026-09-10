<!DOCTYPE html>
<?php
    $log_email_succ = session()->get('log_email_succ');
    $store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first();
    $business_name = \App\Models\BusinessSetting::where(['key' => 'business_name'])->first();
    $app_version = config('app.version', '1.0.0');

    // Get available languages
    $languages = \App\Models\BusinessSetting::where('key', 'system_language')->first();
    $languagesArray = json_decode($languages->value ?? '[]', true);

    // Initialize Unsplash Service for brand panel backgrounds
    $unsplashService = new \App\Services\UnsplashImageService();

    // Define all 16 image/fact combinations
    $allImageData = [
        [
            'query' => 'milky way galaxy stars night sky space',
            'fallback' => 'https://images.unsplash.com/photo-1419242902214-272b3f66ee7a?w=1200&q=75',
            'icon' => 'tio-star',
            'title' => 'Connected Across the Universe',
            'description' => 'There are more stars in the universe than grains of sand on all Earth\'s beaches - imagine the possibilities!',
            'stats' => [
                ['number' => '200B', 'label' => 'Galaxies'],
                ['number' => '13.8B', 'label' => 'Years Old'],
                ['number' => '∞', 'label' => 'Potential']
            ]
        ],
        [
            'query' => 'hummingbird flying flowers colorful',
            'fallback' => 'https://images.unsplash.com/photo-1444464666168-49d633b86797?w=1200&q=75',
            'icon' => 'tio-favorite',
            'title' => 'The Fastest Hearts',
            'description' => 'A hummingbird\'s heart beats 1,200 times per minute - that\'s dedication to staying alive and thriving!',
            'stats' => [
                ['number' => '1,200', 'label' => 'Beats/Min'],
                ['number' => '80', 'label' => 'Wing Beats/Sec'],
                ['number' => '100%', 'label' => 'Energy']
            ]
        ],
        [
            'query' => 'northern lights aurora borealis night sky',
            'fallback' => 'https://images.unsplash.com/photo-1579033461380-adb47c3eb938?w=1200&q=75',
            'icon' => 'tio-flash',
            'title' => 'Nature\'s Light Show',
            'description' => 'The Northern Lights are created by solar winds traveling at 45 million miles per hour - pure cosmic beauty!',
            'stats' => [
                ['number' => '45M', 'label' => 'MPH Speed'],
                ['number' => '100', 'label' => 'Miles High'],
                ['number' => '∞', 'label' => 'Wonder']
            ]
        ],
        [
            'query' => 'elephant family wildlife nature',
            'fallback' => 'https://images.unsplash.com/photo-1564760055775-d63b17a55c44?w=1200&q=75',
            'icon' => 'tio-memories',
            'title' => 'They Never Forget',
            'description' => 'Elephants can remember the location of water sources from decades ago - true masters of memory!',
            'stats' => [
                ['number' => '70', 'label' => 'Years Lifespan'],
                ['number' => '12,000', 'label' => 'Pounds Weight'],
                ['number' => '100%', 'label' => 'Memory Recall']
            ]
        ]
    ];

    // Randomly select 1 image for brand panel
    $randomKey = array_rand($allImageData);
    $selectedTopic = $allImageData[$randomKey];
    
    // Fetch image from Unsplash (with fallback)
    $brandImageUrl = $unsplashService->getImageForTopic($selectedTopic['query'], $selectedTopic['fallback']);
    $brandImage = [
        'url' => $brandImageUrl,
        'icon' => $selectedTopic['icon'],
        'title' => $selectedTopic['title'],
        'description' => $selectedTopic['description'],
        'stats' => $selectedTopic['stats']
    ];
?>

<html dir="{{ $site_direction }}" lang="{{ $locale }}" class="{{ $site_direction === 'rtl'?'active':'' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{translate('login')}} | {{ $business_name?->value ?? 'SnowCart' }}</title>
    <link rel="shortcut icon" href="{{asset('public/favicon.ico')}}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">

    <style>
        :root {
            --red:        #D82E5E;
            --red-dark:   #B8254A;
            --red-glow:   rgba(216,46,94,.15);
            --ink:        #0f172a;
            --ink-2:      #334155;
            --muted:      #94a3b8;
            --border:     #e2e8f0;
            --surface:    #f8fafc;
            --white:      #ffffff;
            --r:          12px;
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Outfit', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            background: var(--white);
            overflow-x: hidden;
        }

        @keyframes spin  { to { transform: rotate(360deg); } }
        @keyframes slide-up {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .animated.fadeIn { animation: slide-up .35s ease-out both; }

        /* ─── LEFT: PHOTO PANEL ─────────────────────── */
        .brand-side {
            width: 45%;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-bg {
            position: absolute;
            inset: 0;
            background-image: url('<?php echo $brandImage['url']; ?>');
            background-size: cover;
            background-position: center;
            transition: transform 8s ease;
        }
        .brand-side:hover .brand-bg { transform: scale(1.03); }

        /* bottom-only vignette — no full gradient, image shows clearly */
        .brand-vignette {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0,0,0,.72) 0%,
                rgba(0,0,0,.18) 45%,
                transparent 70%
            );
            pointer-events: none;
        }

        /* language pill — top-left */
        .lang-pill {
            position: absolute;
            top: 24px;
            left: 24px;
            z-index: 20;
        }
        .lang-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.15);
            backdrop-filter: blur(12px);
            color: #fff;
            border: 1px solid rgba(255,255,255,.25);
            padding: 7px 14px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            transition: background .2s;
        }
        .lang-btn:hover { background: rgba(255,255,255,.25); }
        .lang-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            background: #fff;
            border-radius: var(--r);
            box-shadow: 0 12px 40px rgba(0,0,0,.2);
            min-width: 180px;
            overflow: hidden;
            display: none;
        }
        .lang-dropdown.active { display: block; }
        .lang-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            color: var(--ink);
            text-decoration: none;
            font-size: 14px;
            transition: background .15s;
        }
        .lang-item:hover { background: var(--surface); }
        .lang-item.active { color: var(--red); font-weight: 600; background: var(--red-glow); }

        /* content layered above the vignette */
        .brand-body {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 40px 40px 44px;
            z-index: 10;
        }

        .brand-headline {
            margin-bottom: 28px;
        }
        .brand-headline h2 {
            font-size: clamp(22px, 2.4vw, 30px);
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            letter-spacing: -.4px;
            text-shadow: 0 2px 16px rgba(0,0,0,.4);
            margin-bottom: 8px;
        }
        .brand-headline p {
            font-size: 14px;
            color: rgba(255,255,255,.75);
            line-height: 1.6;
            text-shadow: 0 1px 8px rgba(0,0,0,.4);
        }

        /* Feature pills */
        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 28px;
        }
        .feature-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,.2);
            color: #fff;
            padding: 7px 14px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 500;
        }
        .feature-pill i { color: var(--red); font-size: 15px; }

        /* Fact card */
        .fact-card {
            background: rgba(255,255,255,.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 16px;
            padding: 20px 24px;
        }
        .fact-top {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 14px;
        }
        .fact-dot {
            width: 38px;
            height: 38px;
            background: var(--red);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .fact-dot i { color:#fff; font-size:18px; }
        .fact-text h4 {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
            text-shadow: 0 1px 4px rgba(0,0,0,.3);
        }
        .fact-text p {
            font-size: 13px;
            color: rgba(255,255,255,.8);
            line-height: 1.5;
        }
        .fact-nums {
            display: flex;
            gap: 0;
            border-top: 1px solid rgba(255,255,255,.15);
            padding-top: 14px;
        }
        .fact-num {
            flex: 1;
            text-align: center;
        }
        .fact-num:not(:last-child) {
            border-right: 1px solid rgba(255,255,255,.15);
        }
        .fact-num strong {
            display: block;
            font-size: 18px;
            font-weight: 800;
            color: #fff;
        }
        .fact-num span {
            font-size: 11px;
            color: rgba(255,255,255,.6);
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        /* ─── RIGHT: FORM PANEL ─────────────────────── */
        .login-side {
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            background: var(--white);
            overflow-y: auto;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        /* Logo on the right panel */
        .form-logo {
            text-align: left;
            margin-bottom: 36px;
        }
        .form-logo img {
            max-height: 56px;
            max-width: 180px;
            object-fit: contain;
        }

        .login-header { margin-bottom: 28px; }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--red-glow);
            color: var(--red);
            border: 1px solid rgba(216,46,94,.2);
            padding: 5px 12px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 16px;
        }

        .login-header h2 {
            font-size: 28px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.4px;
            margin-bottom: 6px;
        }

        .login-header p {
            font-size: 14px;
            color: var(--muted);
        }

        /* ── Form ── */
        .form-group { margin-bottom: 18px; position: relative; }

        .form-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-2);
            margin-bottom: 7px;
        }

        .help-tooltip { cursor:help; color:var(--muted); font-size:14px; }

        .input-wrapper { position: relative; }

        .form-input {
            width: 100%;
            height: 48px;
            padding: 0 16px;
            font-size: 14.5px;
            font-family: 'Outfit', sans-serif;
            color: var(--ink);
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: var(--r);
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
        }
        .form-input:hover { border-color: #cbd5e1; background: #fff; }
        .form-input:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px var(--red-glow);
            background: #fff;
        }
        .form-input::placeholder { color: #c0c9d6; }
        .input-wrapper .form-input { padding-right: 48px; }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 4px;
            font-size: 18px;
            transition: color .2s;
        }
        .toggle-password:hover { color: var(--red); }

        /* Checkbox & forgot */
        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }
        .checkbox-wrapper input { display: none; }
        .checkmark {
            width: 18px;
            height: 18px;
            border: 2px solid var(--border);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s;
            flex-shrink: 0;
        }
        .checkmark i { font-size: 10px; color: #fff; opacity:0; transition: opacity .15s; }
        .checkbox-wrapper input:checked + .checkmark { background: var(--red); border-color: var(--red); }
        .checkbox-wrapper input:checked + .checkmark i { opacity: 1; }
        .checkbox-label { font-size: 13px; color: var(--ink-2); }

        .forgot-link {
            font-size: 13px;
            font-weight: 600;
            color: var(--red);
            cursor: pointer;
            transition: opacity .2s;
        }
        .forgot-link:hover { opacity: .75; }

        /* Captcha */
        .captcha-box {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: var(--r);
            padding: 14px;
            margin-bottom: 20px;
        }
        .captcha-row { display: flex; gap: 10px; align-items: center; }
        .captcha-row .form-input { height: 42px; flex: 1; }
        .captcha-image {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            padding: 7px 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        .captcha-image img { height: 28px; }
        .refresh-captcha { cursor:pointer; color:var(--muted); font-size:16px; transition:color .2s; }
        .refresh-captcha:hover { color:var(--red); }
        .refresh-captcha.active i { animation: spin 1s linear infinite; }

        /* Submit */
        .btn-submit {
            width: 100%;
            height: 50px;
            background: var(--red);
            border: none;
            border-radius: var(--r);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: background .2s, transform .15s, box-shadow .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: .2px;
        }
        .btn-submit:hover:not(:disabled) {
            background: var(--red-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px var(--red-glow);
        }
        .btn-submit:active:not(:disabled) { transform: translateY(0); }
        .btn-submit:disabled { opacity: .6; cursor: not-allowed; }

        .btn-spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .75s linear infinite;
            display: none;
        }
        .btn-submit.loading .btn-spinner { display: block; }
        .btn-submit.loading .btn-text   { display: none; }

        /* Keyboard hint */
        .keyboard-hint {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 14px;
            font-size: 12px;
            color: var(--muted);
        }
        .kbd {
            background: var(--surface);
            padding: 2px 7px;
            border-radius: 5px;
            border: 1px solid var(--border);
            font-size: 11px;
            font-weight: 600;
            color: var(--ink-2);
        }

        /* Demo box */
        .demo-box {
            margin-top: 18px;
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: var(--r);
            padding: 13px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .demo-info { font-size: 12px; color: var(--muted); line-height: 1.65; }
        .demo-info strong { color: var(--ink-2); font-weight: 600; }
        .btn-copy {
            flex-shrink: 0;
            width: 36px; height: 36px;
            background: var(--red);
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            transition: background .2s;
        }
        .btn-copy:hover { background: var(--red-dark); }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--muted);
        }
        .login-footer a { color: var(--red); font-weight: 600; text-decoration: none; }
        .login-footer a:hover { text-decoration: underline; }
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-bottom: 10px;
        }
        .footer-link { color: var(--muted); text-decoration: none; font-size: 12px; }
        .footer-link:hover { color: var(--red); }
        .version-badge {
            display: inline-block;
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 11px;
            color: var(--muted);
            margin-top: 8px;
        }

        /* Modal */
        .modal-content { border: none; border-radius: 16px; overflow: hidden; }
        .modal-header { border: none; padding: 16px 20px; }
        .close-btn {
            width: 32px; height: 32px;
            background: var(--surface);
            border: none; border-radius: 8px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s, color .2s;
        }
        .close-btn:hover { background: var(--red); color: #fff; }
        .modal-body-content { text-align: center; padding: 20px 30px 40px; }
        .modal-body-content img { width: 60px; margin-bottom: 20px; }
        .modal-body-content h4 { font-size: 18px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
        .modal-body-content p  { font-size: 14px; color: var(--muted); margin-bottom: 20px; }
        .modal-body-content .form-control { height: 48px; border-radius: var(--r); margin-bottom: 16px; font-family: 'Outfit', sans-serif; }
        .modal-body-content .btn--primary { height: 48px; border-radius: var(--r); background: var(--red); border: none; font-weight: 700; font-family: 'Outfit', sans-serif; }

        /* ─── RESPONSIVE ──────────────────────────── */
        @media (max-width: 900px) {
            .brand-side { width: 40%; }
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }

            .brand-side {
                width: 100%;
                min-height: 240px;
                max-height: 280px;
                flex-shrink: 0;
            }

            /* on mobile: vignette from bottom only, shorter */
            .brand-vignette {
                background: linear-gradient(
                    to top,
                    rgba(0,0,0,.65) 0%,
                    rgba(0,0,0,.1) 60%,
                    transparent 100%
                );
            }

            .brand-body {
                padding: 20px 24px 28px;
                justify-content: flex-end;
            }

            .brand-headline h2 { font-size: 20px; }
            .brand-headline p  { display: none; }
            .fact-card { display: none; }
            .feature-pills { display: none; }
            .brand-headline { margin-bottom: 0; }

            .lang-pill { top: 16px; left: 16px; }

            .login-side {
                min-height: unset;
                flex: 1;
                padding: 32px 24px 40px;
                justify-content: flex-start;
            }

            .login-container { max-width: 100%; }

            /* logo stays on right panel */
            .form-logo { margin-bottom: 24px; }
            .form-logo img { max-height: 44px; }

            .login-header h2 { font-size: 24px; }

            .form-extras {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .captcha-row { flex-direction: column; }
            .captcha-image { width: 100%; justify-content: center; }
        }

        @media (max-width: 480px) {
            .brand-side { min-height: 200px; max-height: 220px; }
            .login-side { padding: 24px 20px 36px; }
        }

        /* RTL */
        html[dir="rtl"] .toggle-password { right: auto; left: 14px; }
        html[dir="rtl"] .input-wrapper .form-input { padding-right: 16px; padding-left: 48px; }
        html[dir="rtl"] .lang-pill { left: auto; right: 24px; }
        html[dir="rtl"] .lang-dropdown { left: auto; right: 0; }
    </style>
</head>

<body>
    <!-- LEFT: Photo Panel -->
    <div class="brand-side">
        <div class="brand-bg"></div>
        <div class="brand-vignette"></div>

        <!-- Language selector -->
        @if(count($languagesArray) > 1)
        <div class="lang-pill">
            <button class="lang-btn" id="languageBtn" type="button">
                <i class="tio-globe"></i>
                <span>{{ strtoupper($locale) }}</span>
                <i class="tio-chevron-down"></i>
            </button>
            <div class="lang-dropdown" id="languageDropdown">
                @foreach($languagesArray as $lang)
                <a href="{{ url()->current() }}?lang={{ $lang['code'] }}" class="lang-item {{ $locale==$lang['code'] ? 'active' : '' }}">
                    <i class="tio-checkmark-circle {{ $locale==$lang['code'] ? '' : 'invisible' }}"></i>
                    {{ $lang['name'] }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <div class="brand-body">
            <div class="brand-headline">
                <h2>The Everything App</h2>
                <p>Transforming Quick Commerce &amp; Empowering Local Vendors</p>
            </div>

            <div class="feature-pills">
                <span class="feature-pill"><i class="tio-store"></i> Local Vendors</span>
                <span class="feature-pill"><i class="tio-rocket"></i> Fast Delivery</span>
                <span class="feature-pill"><i class="tio-star"></i> Excellence</span>
            </div>

            <div class="fact-card">
                <div class="fact-top">
                    <div class="fact-dot"><i class="<?php echo $brandImage['icon']; ?>"></i></div>
                    <div class="fact-text">
                        <h4><?php echo $brandImage['title']; ?></h4>
                        <p><?php echo $brandImage['description']; ?></p>
                    </div>
                </div>
                <div class="fact-nums">
                    <?php foreach($brandImage['stats'] as $stat): ?>
                    <div class="fact-num">
                        <strong><?php echo $stat['number']; ?></strong>
                        <span><?php echo $stat['label']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Login Panel -->
    <div class="login-side">
        <div class="login-container">

            <!-- Logo on the right -->
            <div class="form-logo">
                <img
                    class="onerror-image"
                    data-onerror-image="{{asset('/public/assets/admin/img/favicon.png')}}"
                    src="{{\App\CentralLogics\Helpers::get_full_url('business', $store_logo?->value ?? '', $store_logo?->storage[0]?->value ?? 'public','favicon')}}"
                    alt="{{ $business_name?->value ?? 'SnowCart' }}"
                >
            </div>

            <div class="login-header">
                <span class="role-badge">
                    @if($role=='admin')             <i class="tio-user-big-outlined"></i>
                    @elseif($role=='admin_employee') <i class="tio-account-square-outlined"></i>
                    @elseif($role=='vendor')        <i class="tio-shop-outlined"></i>
                    @else                           <i class="tio-user-outlined"></i>
                    @endif
                    {{ translate($role) }}
                </span>
                <h2>{{translate('signin')}}</h2>
                <p>{{translate('welcome_back_login_to_your_panel')}}</p>
            </div>

            <form action="{{route('login_post')}}" method="post" id="form-id">
                @csrf
                <input type="hidden" name="role" value="{{ $role ?? null }}">

                <div class="form-group">
                    <label class="form-label">
                        {{translate('your_email')}}
                        <i class="tio-info help-tooltip" title="{{translate('Enter your registered email address')}}"></i>
                    </label>
                    <input
                        type="email"
                        class="form-input"
                        name="email"
                        id="signinSrEmail"
                        placeholder="email@address.com"
                        value="{{ $email ?? '' }}"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        {{translate('password')}}
                        <i class="tio-info help-tooltip" title="{{translate('Minimum 6 characters required')}}"></i>
                    </label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            class="form-input"
                            name="password"
                            id="signinSrPassword"
                            placeholder="{{translate('password_length_placeholder',['length'=>'6+'])}}"
                            value="{{ $password ?? '' }}"
                            required
                        >
                        <button type="button" class="toggle-password" title="{{translate('Toggle password visibility')}}">
                            <i class="tio-visible-outlined"></i>
                        </button>
                    </div>
                </div>

                <div class="form-extras">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span class="checkmark"><i class="tio-checkmark-circle"></i></span>
                        <span class="checkbox-label">{{translate('remember_me')}}</span>
                    </label>

                    @if($role == 'admin')
                        <span class="forgot-link" data-toggle="modal" data-target="#forgetPassModal">
                            {{translate('Forget Password')}}?
                        </span>
                    @elseif($role == 'vendor')
                        <span class="forgot-link" data-toggle="modal" data-target="#forgetPassModal1">
                            {{translate('Forget Password')}}?
                        </span>
                    @endif
                </div>

                @php($recaptcha = \App\CentralLogics\Helpers::get_business_settings('recaptcha'))
                @if(isset($recaptcha) && $recaptcha['status'] == 1)
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                    <input type="hidden" name="set_default_captcha" id="set_default_captcha_value" value="0">
                    <div class="captcha-box d-none" id="reload-captcha">
                        <div class="captcha-row">
                            <input
                                type="text"
                                class="form-input"
                                name="custome_recaptcha"
                                id="custome_recaptcha"
                                placeholder="{{translate('Enter captcha')}}"
                                autocomplete="off"
                                value="{{env('APP_MODE')=='dev'? session('six_captcha'):''}}"
                            >
                            <div class="captcha-image">
                                <img src="<?php echo $custome_recaptcha ? $custome_recaptcha->inline() : ''; ?>" alt="captcha">
                                <span class="refresh-captcha reloadCaptcha"><i class="tio-cached"></i></span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="captcha-box" id="reload-captcha">
                        <div class="captcha-row">
                            <input
                                type="text"
                                class="form-input"
                                name="custome_recaptcha"
                                id="custome_recaptcha"
                                required
                                placeholder="{{translate('Enter captcha')}}"
                                autocomplete="off"
                                value="{{env('APP_MODE')=='dev'? session('six_captcha'):''}}"
                            >
                            <div class="captcha-image">
                                <img src="<?php echo $custome_recaptcha ? $custome_recaptcha->inline() : ''; ?>" alt="captcha">
                                <span class="refresh-captcha reloadCaptcha"><i class="tio-cached"></i></span>
                            </div>
                        </div>
                    </div>
                @endif

                <button type="submit" class="btn-submit" id="signInBtn">
                    <div class="btn-spinner"></div>
                    <span class="btn-text">{{translate('login')}}</span>
                </button>

                <div class="keyboard-hint">
                    <span>Quick tip:</span>
                    <kbd class="kbd">Enter</kbd>
                    <span>to sign in</span>
                </div>
            </form>

            @if(env('APP_MODE') == 'demo')
                @if (isset($role) && $role == 'admin')
                    <div class="demo-box">
                        <div class="demo-info">
                            <strong>Email:</strong> admin@admin.com<br>
                            <strong>Password:</strong> 12345678
                        </div>
                        <button class="btn-copy copy_cred" type="button" title="{{translate('Copy credentials')}}"><i class="tio-copy"></i></button>
                    </div>
                @endif
                @if (isset($role) && $role == 'vendor')
                    <div class="demo-box">
                        <div class="demo-info">
                            <strong>Email:</strong> test.restaurant@gmail.com<br>
                            <strong>Password:</strong> 12345678
                        </div>
                        <button class="btn-copy copy_cred2" type="button" title="{{translate('Copy credentials')}}"><i class="tio-copy"></i></button>
                    </div>
                @endif
            @endif

            <div class="login-footer">
                <div class="footer-links">
                    <a href="{{ url('/terms') }}" class="footer-link">{{translate('terms')}}</a>
                    <span>•</span>
                    <a href="{{ url('/privacy-policy') }}" class="footer-link">{{translate('privacy')}}</a>
                    <span>•</span>
                    <a href="mailto:support@snocart.com" class="footer-link">{{translate('support')}}</a>
                </div>
                {{translate('powered_by')}} <a href="https://zitx.tech" target="_blank">ZITX.TECH</a>
                <div class="version-badge">{{translate('version')}} {{ $app_version }}</div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="forgetPassModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button class="close-btn" data-dismiss="modal"><i class="tio-clear"></i></button>
                </div>
                <div class="modal-body">
                    <div class="modal-body-content">
                        <img src="{{asset('/public/assets/admin/img/send-mail.svg')}}" alt="">
                        <h4>{{translate('Send_Mail_to_Your_Email')}}?</h4>
                        <p>{{translate('A mail will be send to your registered email with a link to change password')}}</p>
                        <a class="btn btn-lg btn-block btn--primary" href="{{route('reset-password')}}">
                            {{translate('Send Mail')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forgetPassModal1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button class="close-btn" data-dismiss="modal"><i class="tio-clear"></i></button>
                </div>
                <div class="modal-body">
                    <div class="modal-body-content">
                        <img src="{{asset('/public/assets/admin/img/send-mail.svg')}}" alt="">
                        <h4>{{translate('Send_Mail_to_Your_Email')}}?</h4>
                        <form action="{{ route('vendor-reset-password') }}" method="post">
                            @csrf
                            <input type="email" name="email" class="form-control" placeholder="{{translate('plesae_enter_your_registerd_email')}}" required>
                            <button type="submit" class="btn btn-lg btn-block btn--primary">{{translate('Send Mail')}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="successMailModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-end">
                    <button class="close-btn" data-dismiss="modal"><i class="tio-clear"></i></button>
                </div>
                <div class="modal-body">
                    <div class="modal-body-content">
                        <img src="{{asset('/public/assets/admin/img/sent-mail.svg')}}" alt="">
                        <h4>{{translate('A mail has been sent to your registered email')}}!</h4>
                        <p>{{translate('Click the link in the mail description to change password')}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
    {!! Toastr::message() !!}

    @if ($errors->any())
        <script>
            @foreach($errors->all() as $error)
            toastr.error('{{translate($error)}}', 'Error', { CloseButton: true, ProgressBar: true });
            @endforeach
        </script>
    @endif

    @if ($log_email_succ)
        @php(session()->forget('log_email_succ'))
        <script>$('#successMailModal').modal('show');</script>
    @endif

    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('.toggle-password').on('click', function() {
                var input = $(this).siblings('input');
                var icon = $(this).find('i');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('tio-visible-outlined').addClass('tio-hidden-outlined');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('tio-hidden-outlined').addClass('tio-visible-outlined');
                }
            });

            // Language selector
            $('#languageBtn').on('click', function(e) {
                e.stopPropagation();
                $('#languageDropdown').toggleClass('active');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.lang-pill').length) {
                    $('#languageDropdown').removeClass('active');
                }
            });

            // Form submission with loading state (skipped when reCAPTCHA handler manages it)
            $('#form-id').on('submit', function() {
                if ($('#set_default_captcha_value').length === 0 || $('#set_default_captcha_value').val() === '1') {
                    $('#signInBtn').addClass('loading').prop('disabled', true);
                }
            });

            // Keyboard shortcut (Enter key)
            $(document).on('keypress', function(e) {
                if (e.which === 13 && !$('#form-id').is(':focus-within')) {
                    $('#form-id').submit();
                }
            });

            // Image error handling
            $('.onerror-image').on('error', function() {
                $(this).attr('src', $(this).data('onerror-image'));
            });
        });

        // Refresh captcha
        $(document).on('click', '.reloadCaptcha', function() {
            $.ajax({
                url: "{{ route('reload-captcha') }}",
                type: "GET",
                dataType: 'json',
                beforeSend: function() { $('.refresh-captcha').addClass('active'); },
                success: function(data) { $('#reload-captcha').html(data.view); },
                complete: function() { $('.refresh-captcha').removeClass('active'); }
            });
        });
    </script>

    @if(isset($recaptcha) && $recaptcha['status'] == 1)
        <script src="https://www.google.com/recaptcha/api.js?render={{$recaptcha['site_key']}}"></script>
        <script>
            $('#form-id').on('submit', function(e) {
                // Custom captcha already active — let the form submit normally
                if ($('#set_default_captcha_value').val() == '1') {
                    return;
                }

                // Intercept to inject reCAPTCHA token first
                e.preventDefault();
                $('#signInBtn').addClass('loading').prop('disabled', true);

                // reCAPTCHA failed to load — fall back to custom captcha
                if (typeof grecaptcha === 'undefined') {
                    $('#reload-captcha').removeClass('d-none').addClass('animated fadeIn');
                    $('#custome_recaptcha').prop('required', true);
                    $('#set_default_captcha_value').val('1');
                    toastr.info('{{translate('complete_verification_below')}}', '{{translate('verification_required')}}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                    $('#signInBtn').removeClass('loading').prop('disabled', false);
                    return;
                }

                // Execute reCAPTCHA and submit with token
                grecaptcha.ready(function() {
                    grecaptcha.execute('{{$recaptcha['site_key']}}', {action: 'submit'}).then(function(token) {
                        if (!token) {
                            $('#signInBtn').removeClass('loading').prop('disabled', false);
                            toastr.error('{{translate('Google reCAPTCHA returned an empty token. Please check your site key configuration.')}}', '{{translate('reCAPTCHA Error')}}', {CloseButton: true});
                            return;
                        }
                        $('#g-recaptcha-response').val(token);
                        document.getElementById('form-id').submit();
                    }).catch(function(err) {
                        $('#signInBtn').removeClass('loading').prop('disabled', false);
                        console.error('reCAPTCHA execute failed:', err);
                        toastr.error('{{translate('Google reCAPTCHA failed. Ensure your site key is a v3 key registered for this domain.')}}', '{{translate('reCAPTCHA Error')}}', {CloseButton: true, timeOut: 8000});
                    });
                });
            });
        </script>
    @endif

    @if(env('APP_MODE')=='demo')
        <script>
            $('.copy_cred').on('click', function() {
                $('#signinSrEmail').val('admin@admin.com');
                $('#signinSrPassword').val('12345678');
                toastr.success('{{translate('credentials_copied')}}', '{{translate('success')}}', { CloseButton: true, ProgressBar: true });
            });
            $('.copy_cred2').on('click', function() {
                $('#signinSrEmail').val('test.restaurant@gmail.com');
                $('#signinSrPassword').val('12345678');
                toastr.success('{{translate('credentials_copied')}}', '{{translate('success')}}', { CloseButton: true, ProgressBar: true });
            });
        </script>
    @endif
</body>
</html>
