@php
    $businessName = \App\Models\BusinessSetting::where('key', 'business_name')->first()?->value ?? 'Business';
    $autoApproved = $auto_approved ?? true;
    $paymentSuccess = ($payment_status ?? 'success') === 'success';
    $type = $type ?? 'subscription';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $businessName }} - Registration Complete</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-clr: #D82E5E;
            --secondary-clr: #1f1f1f;
            --primary: #B8254A;
            --title-clr: #1a1a1a;
            --dark-clr: #000000;
            --base-clr: #D82E5E;
            --base-clr-2: #FF4D7A;
            --border-clr: #e5e5e5;
            --warning-clr: #ff7500;
            --danger-clr: #ff6d6d;
            --success-clr: #00aa6d;
            --info-clr: #0096ff;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #e5e5e5;
            --gray-300: #d4d4d4;
            --gray-400: #a3a3a3;
            --gray-500: #737373;
            --gray-600: #525252;
            --gray-700: #404040;
            --gray-800: #262626;
            --gray-900: #171717;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--success-clr) 0%, #00d084 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .completion-container {
            width: 100%;
            max-width: 700px;
            position: relative;
        }

        .completion-card {
            background: white;
            border-radius: 24px;
            padding: 3rem 2rem;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .completion-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--success-clr), #00d084, #00e68a);
        }

        .confetti {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 100;
        }

        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--primary-clr);
            animation: confettiDrop 3s linear forwards;
        }

        @keyframes confettiDrop {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }

        .success-animation {
            position: relative;
            margin-bottom: 2rem;
        }

        .success-icon {
            width: 140px;
            height: 140px;
            background: linear-gradient(45deg, var(--success-clr), #00d084);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 4rem;
            position: relative;
            overflow: hidden;
            animation: successBounce 1s ease-out;
            box-shadow: 0 10px 40px rgba(0, 170, 109, 0.4);
        }

        .success-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s ease-in-out infinite;
        }

        @keyframes successBounce {
            0% { transform: scale(0) rotate(-180deg); opacity: 0; }
            50% { transform: scale(1.2) rotate(-90deg); }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .live-badge {
            display: inline-block;
            background: linear-gradient(45deg, var(--success-clr), #00d084);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0, 170, 109, 0.4); }
            50% { box-shadow: 0 0 0 15px rgba(0, 170, 109, 0); }
        }

        .completion-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--title-clr);
            margin-bottom: 0.75rem;
            animation: slideUp 0.8s ease-out 0.3s both;
        }

        .completion-subtitle {
            font-size: 1.3rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
            line-height: 1.6;
            animation: slideUp 0.8s ease-out 0.5s both;
        }

        .status-message {
            background: linear-gradient(135deg, rgba(0, 170, 109, 0.1), rgba(0, 208, 132, 0.05));
            border: 2px solid rgba(0, 170, 109, 0.3);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            animation: slideUp 0.8s ease-out 0.7s both;
        }

        .status-text {
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--gray-700);
        }

        .quick-tips {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 2rem 0;
            animation: slideUp 0.8s ease-out 0.9s both;
        }

        .tip-item {
            text-align: center;
            padding: 1rem 0.5rem;
        }

        .tip-icon {
            width: 50px;
            height: 50px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            color: var(--primary-clr);
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .tip-item:hover .tip-icon {
            background: var(--primary-clr);
            color: white;
            transform: scale(1.1);
        }

        .tip-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--title-clr);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
            animation: slideUp 0.8s ease-out 1.1s both;
        }

        .btn {
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-clr), var(--base-clr-2));
            color: white;
            box-shadow: 0 4px 15px rgba(216, 46, 94, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(216, 46, 94, 0.5);
        }

        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border: 2px solid var(--border-clr);
        }

        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--primary-clr);
            color: var(--primary-clr);
            transform: translateY(-2px);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: -1;
        }

        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            right: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 10%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            width: 40px;
            height: 40px;
            top: 50%;
            left: 5%;
            animation-delay: 4s;
        }

        .floating-element:nth-child(4) {
            width: 80px;
            height: 80px;
            bottom: 10%;
            right: 15%;
            animation-delay: 1s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .completion-card {
                padding: 2rem 1.5rem;
                border-radius: 16px;
            }

            .completion-title {
                font-size: 2rem;
            }

            .success-icon {
                width: 110px;
                height: 110px;
                font-size: 3rem;
            }

            .quick-tips {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .tip-item {
                display: flex;
                align-items: center;
                gap: 1rem;
                text-align: left;
                padding: 0.75rem;
            }

            .tip-icon {
                margin: 0;
                flex-shrink: 0;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .completion-title {
                font-size: 1.7rem;
            }

            .completion-subtitle {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>

    <div class="confetti" id="confetti"></div>

    <div class="completion-container">
        <div class="completion-card">
            <!-- Success Animation -->
            <div class="success-animation">
                <div class="success-icon">
                    <i class="fas fa-store"></i>
                </div>

                <div class="live-badge">
                    <i class="fas fa-circle" style="font-size: 8px; margin-right: 6px; animation: blink 1s infinite;"></i>
                    STORE IS LIVE
                </div>

                <h1 class="completion-title">
                    You're All Set!
                </h1>

                <p class="completion-subtitle">
                    Your store is now live on {{ $businessName }}
                </p>
            </div>

            <!-- Status Message -->
            <div class="status-message">
                <div class="status-text">
                    Congratulations! Your store has been successfully registered and is ready to start accepting orders.
                    Go to your dashboard to add products, set up your menu, and start growing your business today!
                </div>
            </div>

            <!-- Quick Setup Tips -->
            <div class="quick-tips">
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <span class="tip-title">Add Products</span>
                </div>
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span class="tip-title">Set Timings</span>
                </div>
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <span class="tip-title">Create Offers</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="{{ route('vendor.dashboard') }}" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i>
                    Go to Dashboard
                </a>

                <a href="{{ route('home') }}" class="btn btn-secondary">
                    <i class="fas fa-home"></i>
                    Visit Homepage
                </a>
            </div>
        </div>
    </div>

    <script>
        // Confetti Animation
        function createConfetti() {
            const confettiContainer = document.getElementById('confetti');
            const colors = ['#D82E5E', '#FF4D7A', '#00aa6d', '#00d084', '#FFD700', '#FF6B6B'];

            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti-piece';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                confetti.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
                confetti.style.width = (Math.random() * 10 + 5) + 'px';
                confetti.style.height = (Math.random() * 10 + 5) + 'px';
                confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                confettiContainer.appendChild(confetti);
            }

            // Remove confetti after animation
            setTimeout(() => {
                confettiContainer.innerHTML = '';
            }, 5000);
        }

        // Run confetti on page load
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();

            // Add click animation to buttons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = btn.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.3);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    `;

                    btn.appendChild(ripple);

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }

            @keyframes blink {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.3; }
            }

            .btn {
                position: relative;
                overflow: hidden;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
