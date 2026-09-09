<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $business_name ?? 'Business' }} - Delivery Man Registration</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" defer>
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
            --theameColor: #D82E5E;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-clr) 0%, var(--primary) 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* === MOBILE-FIRST BASE STYLES === */
        .registration-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: relative;
        }

        /* Image section hidden on mobile */
        .image-section {
            display: none;
        }

        .form-section {
            flex: 1;
            background: white;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 1rem;
            padding-top: calc(1rem + var(--safe-top));
            padding-bottom: calc(80px + var(--safe-bottom));
            position: relative;
        }

        .form-container {
            width: 100%;
            max-width: 500px;
            position: relative;
        }

        .form-header {
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .form-header h1 {
            font-size: 1.4rem;
            color: var(--title-clr);
            margin-bottom: 0.3rem;
            font-weight: 700;
        }

        .form-header p {
            color: var(--gray-600);
            font-size: 0.85rem;
        }

        /* Progress bar - mobile: numbered steps */
        .progress-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.25rem;
            gap: 6px;
        }

        .progress-step {
            width: 32px;
            height: 32px;
            background: var(--gray-200);
            border-radius: 50%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            position: relative;
        }

        .progress-step.active {
            background: linear-gradient(45deg, var(--primary-clr), var(--base-clr-2));
            color: white;
        }

        .progress-step.completed {
            background: var(--success-clr);
            color: white;
        }

        .progress-step-connector {
            width: 16px;
            height: 2px;
            background: var(--gray-200);
            transition: background 0.4s ease;
        }

        .progress-step-connector.active {
            background: var(--success-clr);
        }

        .step-label {
            display: block;
            text-align: center;
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        /* Form steps */
        .form-step {
            display: none;
            animation: fadeSlideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-step.active {
            display: block;
        }

        .form-step.slide-out {
            animation: fadeSlideOut 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeSlideOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(-30px); }
        }

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-clr);
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(45deg, var(--primary-clr), var(--base-clr-2));
        }

        .form-card h3 {
            margin-bottom: 1.25rem;
            color: var(--title-clr);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            min-height: 48px;
            border: 2px solid var(--border-clr);
            border-radius: 10px;
            font-size: 16px; /* Prevents iOS zoom */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--gray-50);
            font-family: inherit;
            -webkit-appearance: none;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-clr);
            background: white;
            box-shadow: 0 0 0 4px rgba(216, 46, 94, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
        }

        .btn {
            padding: 14px 24px;
            min-height: 48px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            -webkit-tap-highlight-color: transparent;
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
            box-shadow: 0 4px 15px rgba(216, 46, 94, 0.3);
        }

        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(216, 46, 94, 0.4);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 2px solid var(--border-clr);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        /* Sticky bottom action bar on mobile */
        .form-actions {
            display: none; /* Hidden on mobile, shown in sticky bar instead */
        }

        /* Step 2 (OTP) has its own buttons since mobile action bar is hidden there */
        #step2 .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            gap: 1rem;
        }

        .mobile-action-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 12px 16px;
            padding-bottom: calc(12px + var(--safe-bottom));
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            z-index: 100;
        }

        .mobile-action-bar .btn {
            flex: 1;
        }

        .mobile-action-bar .btn-back {
            flex: 0 0 auto;
            width: 48px;
            padding: 14px 0;
        }

        /* Delivery type options */
        .type-options {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .type-option {
            border: 2px solid var(--border-clr);
            border-radius: 12px;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            background: var(--gray-50);
            display: flex;
            align-items: center;
            gap: 1rem;
            -webkit-tap-highlight-color: transparent;
        }

        .type-option:hover {
            border-color: var(--primary-clr);
        }

        .type-option.selected {
            border-color: var(--primary-clr);
            background: linear-gradient(135deg, rgba(216, 46, 94, 0.1), rgba(255, 77, 122, 0.05));
        }

        .type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .type-option i {
            font-size: 1.5rem;
            color: var(--primary-clr);
            flex-shrink: 0;
        }

        .type-option-content {
            flex: 1;
        }

        .type-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--title-clr);
            margin-bottom: 0.25rem;
        }

        .type-description {
            color: var(--gray-600);
            font-size: 0.8rem;
            line-height: 1.4;
        }

        /* Select wrapper */
        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-clr);
            pointer-events: none;
        }

        .select-wrapper select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 45px;
        }

        /* File upload */
        .file-upload-area {
            border: 2px dashed var(--border-clr);
            border-radius: 12px;
            padding: 1.25rem 1rem;
            text-align: center;
            transition: all 0.3s ease;
            background: var(--gray-50);
            position: relative;
        }

        .file-upload-area:hover {
            border-color: var(--primary-clr);
            background: rgba(216, 46, 94, 0.05);
        }

        .file-upload-area.has-preview {
            border-color: var(--success-clr);
            background: rgba(0, 170, 109, 0.03);
        }

        .file-upload-icon {
            font-size: 2rem;
            color: var(--primary-clr);
            margin-bottom: 0.5rem;
        }

        .file-upload-text {
            color: var(--gray-600);
            font-size: 0.85rem;
        }

        .profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--success-clr);
            margin: 0 auto 0.75rem;
            display: none;
        }

        .profile-preview.show {
            display: block;
        }

        .upload-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            min-height: 44px;
            border: 2px solid var(--border-clr);
            border-radius: 8px;
            background: white;
            color: var(--gray-700);
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            -webkit-tap-highlight-color: transparent;
            font-family: inherit;
        }

        .upload-btn:hover, .upload-btn:active {
            border-color: var(--primary-clr);
            color: var(--primary-clr);
            background: rgba(216, 46, 94, 0.05);
        }

        .upload-btn i {
            font-size: 1rem;
        }

        .file-selected {
            display: none;
            color: var(--success-clr);
            font-weight: 600;
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }

        .file-selected.show {
            display: block;
        }

        /* Multi file grid */
        .multi-file-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .multi-file-item {
            border: 2px dashed var(--border-clr);
            border-radius: 8px;
            padding: 0.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--gray-50);
            min-height: 90px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            -webkit-tap-highlight-color: transparent;
            position: relative;
            overflow: hidden;
        }

        .multi-file-item:hover {
            border-color: var(--primary-clr);
            background: rgba(216, 46, 94, 0.05);
        }

        .multi-file-item.has-file {
            border-style: solid;
            border-color: var(--success-clr);
            background: rgba(0, 170, 109, 0.05);
            padding: 0;
        }

        .doc-preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            border-radius: 6px;
        }

        .doc-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
            font-size: 0.65rem;
            padding: 16px 4px 4px;
            text-align: center;
            border-radius: 0 0 6px 6px;
        }

        .doc-check-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            background: var(--success-clr);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.55rem;
        }

        /* Scan modal */
        .scan-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .scan-modal-overlay.show {
            display: flex;
        }

        .scan-modal {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            animation: fadeSlideIn 0.3s ease;
        }

        .scan-modal-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .scan-modal-header h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--title-clr);
        }

        .scan-modal-close {
            width: 32px;
            height: 32px;
            border: none;
            background: var(--gray-100);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .scan-modal-body {
            padding: 1rem 1.25rem 1.25rem;
        }

        .scan-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 14px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 0.75rem;
            -webkit-tap-highlight-color: transparent;
        }

        .scan-option:last-child {
            margin-bottom: 0;
        }

        .scan-option:hover, .scan-option:active {
            border-color: var(--primary-clr);
            background: rgba(216, 46, 94, 0.05);
        }

        .scan-option-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-clr), var(--base-clr-2));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .scan-option-icon.green {
            background: linear-gradient(135deg, var(--success-clr), #00cc7a);
        }

        .scan-option-icon.blue {
            background: linear-gradient(135deg, var(--info-clr), #4db8ff);
        }

        .scan-option-text {
            flex: 1;
        }

        .scan-option-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--title-clr);
        }

        .scan-option-desc {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 2px;
        }

        /* Success message */
        .success-message {
            text-align: center;
            padding: 2rem 1rem;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(45deg, var(--success-clr), #00aa6d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            color: white;
            font-size: 2rem;
            animation: successPulse 1s ease-out;
        }

        @keyframes successPulse {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .success-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--title-clr);
            margin-bottom: 0.75rem;
        }

        .success-text {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.6;
            max-width: 400px;
            margin: 0 auto;
        }

        .info-text {
            margin-top: 1.5rem;
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        .highlight-text {
            display: inline-block;
            margin-top: 0.5rem;
            color: var(--primary-clr);
            font-weight: 600;
            text-decoration: none;
        }

        /* Error states */
        .form-group.error input,
        .form-group.error select,
        .form-group.error textarea {
            border-color: var(--danger-clr);
            box-shadow: 0 0 0 4px rgba(255, 109, 109, 0.1);
        }

        .error-message {
            color: var(--danger-clr);
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: none;
        }

        .form-group.error .error-message {
            display: block;
        }

        /* Notification - safe area aware */
        .notification {
            position: fixed;
            top: calc(12px + var(--safe-top));
            left: 12px;
            right: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            animation: slideInDown 0.3s ease-out, slideOutUp 0.3s ease-in 2.7s forwards;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            font-size: 0.85rem;
        }

        .notification-success { background: linear-gradient(45deg, var(--success-clr), #00aa6d); }
        .notification-error { background: linear-gradient(45deg, var(--danger-clr), #ff6d6d); }
        .notification-info { background: linear-gradient(45deg, var(--info-clr), #0096ff); }

        @keyframes slideInDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideOutUp {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(-100%); opacity: 0; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-3px); }
            75% { transform: translateX(3px); }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--gray-100);
            border-top: 4px solid var(--primary-clr);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Loading button state */
        .btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 14px;
            height: 14px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }


        /* === DESKTOP STYLES (769px+) === */
        @media (min-width: 769px) {
            .registration-container {
                flex-direction: row;
            }

            .image-section {
                display: block;
                flex: 0.6;
                position: relative;
                overflow: hidden;
                background: linear-gradient(45deg, var(--secondary-clr), var(--gray-800));
            }

            .form-section {
                flex: 0.4;
                align-items: center;
                padding: 2rem;
                padding-bottom: 2rem;
            }

            .form-header h1 {
                font-size: 1.8rem;
            }

            .form-header {
                margin-bottom: 2rem;
            }

            .progress-bar {
                margin-bottom: 2rem;
            }

            .progress-step {
                width: 36px;
                height: 36px;
                font-size: 0.75rem;
            }

            .form-card {
                border-radius: 20px;
                padding: 2rem;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            }

            .form-row {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .type-options {
                grid-template-columns: 1fr 1fr;
            }

            .type-option {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .multi-file-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            /* Desktop: show inline form actions, hide sticky bar */
            .form-actions {
                display: flex;
                justify-content: space-between;
                margin-top: 2rem;
                gap: 1rem;
            }

            .mobile-action-bar {
                display: none;
            }

            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus {
                transform: translateY(-1px);
            }

            .btn-primary:hover {
                transform: translateY(-2px);
            }

            .btn-secondary:hover {
                transform: translateY(-1px);
            }

            /* Desktop notification positioning */
            .notification {
                left: auto;
                right: 20px;
                top: 20px;
                max-width: 400px;
                animation: slideInRight 0.3s ease-out, slideOutRight 0.3s ease-in 2.7s forwards;
            }
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Slider styles (desktop only) */
        .slider-container {
            position: relative;
            width: 100%;
            height: 100vh;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: all 1.5s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(30px);
            background-size: cover;
            background-position: center;
        }

        .slide.active {
            opacity: 1;
            transform: translateX(0);
        }

        .slide.prev {
            transform: translateX(-30px);
        }

        .slide-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 40px;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
        }

        .slide-text {
            color: white;
            max-width: 400px;
        }

        .slide-text h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            line-height: 1.2;
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.8s ease-out 0.5s forwards;
        }

        .slide-text p {
            font-size: 1rem;
            opacity: 0;
            line-height: 1.6;
            transform: translateY(20px);
            animation: slideUp 0.8s ease-out 0.8s forwards;
        }

        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
        }

        /* OTP Digit Inputs */
        .otp-digit {
            width: 46px;
            height: 54px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid var(--gray-200, #e5e5e5);
            border-radius: 10px;
            background: var(--gray-50, #fafafa);
            transition: all 0.25s ease;
            font-family: inherit;
        }
        .otp-digit:focus {
            border-color: var(--primary-clr, #D82E5E);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(216, 46, 94, 0.1);
            outline: none;
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="registration-container">
        <!-- Image Slider Section - Desktop only, uses CSS gradients instead of external images -->
        <div class="image-section">
            <div class="slider-container">
                <div class="slide active" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);">
                    <div class="slide-overlay">
                        <div class="slide-text">
                            <h2>Join Our Delivery Team</h2>
                            <p>Become part of our professional delivery network and earn flexible income on your schedule.</p>
                        </div>
                    </div>
                </div>
                <div class="slide" style="background: linear-gradient(135deg, #2d1b69 0%, #11998e 100%);">
                    <div class="slide-overlay">
                        <div class="slide-text">
                            <h2>Flexible Work Schedule</h2>
                            <p>Work when you want, where you want. Set your own hours and achieve work-life balance.</p>
                        </div>
                    </div>
                </div>
                <div class="slide" style="background: linear-gradient(135deg, #373b44 0%, #4286f4 100%);">
                    <div class="slide-overlay">
                        <div class="slide-text">
                            <h2>Reliable Support System</h2>
                            <p>Get comprehensive training, 24/7 support, and tools to succeed in your delivery career.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="form-section">
            <div class="form-container">
                <div class="form-header" style="text-align: center;">
                    <a href="/">
                        <img src="https://new.snocart.com/storage/app/public/business/2025-05-08-681bb7e7462a1.png" alt="Snocart" style="height: 48px; width: auto;">
                    </a>
                </div>

                <div class="step-label" id="stepLabel">1/7 Personal Info</div>

                <div class="progress-bar" id="progressBar">
                    <div class="progress-step active" data-step="1">1</div>
                    <div class="progress-step-connector"></div>
                    <div class="progress-step" data-step="2">2</div>
                    <div class="progress-step-connector"></div>
                    <div class="progress-step" data-step="3">3</div>
                    <div class="progress-step-connector"></div>
                    <div class="progress-step" data-step="4">4</div>
                    <div class="progress-step-connector"></div>
                    <div class="progress-step" data-step="5">5</div>
                    <div class="progress-step-connector"></div>
                    <div class="progress-step" data-step="6">6</div>
                    <div class="progress-step-connector"></div>
                    <div class="progress-step" data-step="7">7</div>
                </div>

                <form id="registrationForm" action="{{ route('deliveryman.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- Hidden input for raw phone value -->
                    <input type="hidden" id="phone_raw" name="phone" value="{{ old('phone') }}">

                    <!-- Step 1: Personal Information -->
                    <div class="form-step active" id="step1">
                        <div class="form-card">
                            <h3><i class="fas fa-user-circle" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Personal Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName">First Name *</label>
                                    <input type="text" id="firstName" name="f_name" value="{{ old('f_name') }}" required>
                                    <div class="error-message">First name is required</div>
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name</label>
                                    <input type="text" id="lastName" name="l_name" value="{{ old('l_name') }}">
                                    <div class="error-message">Last name is required</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="phone_display">Phone Number *</label>
                                <input type="tel" id="phone_display" value="{{ old('phone') }}" placeholder="+91 555 000-0000" required>
                                <div class="error-message">Valid phone number is required</div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                                <div class="error-message">Valid email address is required</div>
                            </div>

                            <div class="form-actions">
                                <div></div>
                                <button type="button" class="btn btn-primary" onclick="nextStep()">
                                    Next <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Phone OTP Verification -->
                    <div class="form-step" id="step2">
                        <div class="form-card">
                            <h3><i class="fas fa-mobile-alt" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Verify Your Phone</h3>

                            <div id="otpSendSection">
                                <p style="color: var(--gray-600, #525252); font-size: 0.9rem; margin-bottom: 1rem;">
                                    We'll send a 6-digit verification code to your phone number.
                                </p>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" id="otp_phone_display" readonly style="background: var(--gray-100, #f5f5f5); cursor: not-allowed;">
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">
                                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Back
                                    </button>
                                    <button type="button" class="btn btn-primary" id="dmSendOtpBtn" onclick="dmSendOtp()">
                                        Send OTP <i class="fas fa-paper-plane" style="margin-left: 0.5rem;"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="otpVerifySection" style="display: none;">
                                <p style="color: var(--gray-600, #525252); font-size: 0.9rem; margin-bottom: 1rem;">
                                    Enter the 6-digit code sent to <strong id="otpPhoneLabel"></strong>
                                </p>
                                <div class="form-group">
                                    <div style="display: flex; gap: 8px; justify-content: center; margin-bottom: 1rem;">
                                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" data-otp-index="0">
                                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-otp-index="1">
                                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-otp-index="2">
                                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-otp-index="3">
                                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-otp-index="4">
                                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-otp-index="5">
                                    </div>
                                    <div class="error-message" id="otpError" style="text-align: center;"></div>
                                </div>
                                <div style="text-align: center; margin-bottom: 1rem;">
                                    <span id="otpTimer" style="font-size: 0.85rem; color: var(--gray-500, #737373);"></span>
                                    <button type="button" id="dmResendBtn" class="btn btn-secondary" onclick="dmResendOtp()" style="display: none; margin-top: 0.5rem; padding: 8px 16px; font-size: 0.85rem;">
                                        <i class="fas fa-redo"></i> Resend OTP
                                    </button>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary" onclick="dmBackToSend()">
                                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Change Number
                                    </button>
                                    <button type="button" class="btn btn-primary" id="dmVerifyOtpBtn" onclick="dmVerifyOtp()">
                                        Verify <i class="fas fa-check" style="margin-left: 0.5rem;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Zone & Vehicle Selection -->
                    <div class="form-step" id="step3">
                        <div class="form-card">
                            <h3><i class="fas fa-map-marked-alt" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Zone & Vehicle</h3>

                            <div class="form-group">
                                <label for="zone_id">Select Zone *</label>
                                <div class="select-wrapper">
                                    <select id="zone_id" name="zone_id" required>
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
                                <label for="vehicle_id">Vehicle Type *</label>
                                <div class="select-wrapper">
                                    <select id="vehicle_id" name="vehicle_id" required>
                                        <option value="">Choose your vehicle</option>
                                        @foreach(\App\Models\DMVehicle::where('status',1)->get(['id','type']) as $vehicle)
                                            <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                                {{ $vehicle->type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="error-message">Please select a vehicle type</div>
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

                    <!-- Step 4: Delivery Type -->
                    <div class="form-step" id="step4">
                        <div class="form-card">
                            <h3><i class="fas fa-briefcase" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Employment Type</h3>

                            <div class="type-options">
                                <div class="type-option" onclick="selectDeliveryType(event, 'freelancer')">
                                    <input type="radio" name="earning" value="1" id="freelancer" {{ old('earning') == '1' ? 'checked' : '' }}>
                                    <i class="fas fa-motorcycle"></i>
                                    <div class="type-option-content">
                                        <div class="type-title">Freelancer <span style="background: var(--success-clr); color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; margin-left: 4px; vertical-align: middle;">RECOMMENDED</span></div>
                                        <div class="type-description">
                                            Earn <strong style="color: var(--success-clr);">&#8377;50 - &#8377;150 per order</strong><br>
                                            Daily earning potential: <strong>&#8377;700 - &#8377;1,500</strong>
                                        </div>
                                        <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.4rem;">
                                            <span style="font-size: 0.7rem; background: var(--gray-100); border: 1px solid var(--gray-200); padding: 2px 8px; border-radius: 20px; color: var(--gray-700);"><i class="fas fa-clock" style="color: var(--primary-clr); margin-right: 3px;"></i> 7-8 hr shift</span>
                                            <span style="font-size: 0.7rem; background: var(--gray-100); border: 1px solid var(--gray-200); padding: 2px 8px; border-radius: 20px; color: var(--gray-700);"><i class="fas fa-calendar-check" style="color: var(--primary-clr); margin-right: 3px;"></i> Daily login required</span>
                                            <span style="font-size: 0.7rem; background: var(--gray-100); border: 1px solid var(--gray-200); padding: 2px 8px; border-radius: 20px; color: var(--gray-700);"><i class="fas fa-wallet" style="color: var(--success-clr); margin-right: 3px;"></i> Per order pay</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="type-option" onclick="selectDeliveryType(event, 'salary')">
                                    <input type="radio" name="earning" value="0" id="salary" {{ old('earning') == '0' ? 'checked' : '' }}>
                                    <i class="fas fa-user-clock"></i>
                                    <div class="type-option-content">
                                        <div class="type-title">Part-Time</div>
                                        <div class="type-description">
                                            Work <strong>4 hours daily</strong> on a flexible schedule
                                        </div>
                                        <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.4rem;">
                                            <span style="font-size: 0.7rem; background: var(--gray-100); border: 1px solid var(--gray-200); padding: 2px 8px; border-radius: 20px; color: var(--gray-700);"><i class="fas fa-clock" style="color: var(--primary-clr); margin-right: 3px;"></i> 4 hr daily</span>
                                            <span style="font-size: 0.7rem; background: var(--gray-100); border: 1px solid var(--gray-200); padding: 2px 8px; border-radius: 20px; color: var(--gray-700);"><i class="fas fa-calendar-check" style="color: var(--primary-clr); margin-right: 3px;"></i> Daily login required</span>
                                        </div>
                                    </div>
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

                    <!-- Step 5: Identity Information -->
                    <div class="form-step" id="step5">
                        <div class="form-card">
                            <h3><i class="fas fa-id-card" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Identity Information</h3>

                            <div class="form-group">
                                <label for="identity_type">Identity Type *</label>
                                <div class="select-wrapper">
                                    <select id="identity_type" name="identity_type" required>
                                        <option value="">Choose identity type</option>
                                        <option value="passport" {{ old('identity_type') == 'passport' ? 'selected' : '' }}>Passport</option>
                                        <option value="driving_license" {{ old('identity_type') == 'driving_license' ? 'selected' : '' }}>Driving License</option>
                                        <option value="nid" {{ old('identity_type') == 'nid' ? 'selected' : '' }}>National ID</option>
                                        <option value="restaurant_id" {{ old('identity_type') == 'restaurant_id' ? 'selected' : '' }}>Store ID</option>
                                    </select>
                                </div>
                                <div class="error-message">Please select an identity type</div>
                            </div>

                            <div class="form-group">
                                <label for="identity_number">Identity Number *</label>
                                <input type="text" id="identity_number" name="identity_number" value="{{ old('identity_number') }}" placeholder="e.g., DH-23434-LS" required>
                                <div class="error-message">Identity number is required</div>
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

                    <!-- Step 6: Documents & Photo -->
                    <div class="form-step" id="step6">
                        <div class="form-card">
                            <h3><i class="fas fa-images" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Documents & Photo</h3>

                            <!-- Profile Photo -->
                            <div class="form-group" id="profilePhotoGroup">
                                <label>Profile Photo *</label>
                                <input type="file" id="profile_image" name="image" accept="image/*" style="display:none;" onchange="handleProfilePhoto(this)">
                                <input type="file" id="profile_camera" name="_profile_cam" accept="image/*" capture="user" style="display:none;" onchange="transferToProfile(this)">
                                <div class="file-upload-area" id="profileUploadArea">
                                    <img class="profile-preview" id="profilePreview" alt="Profile preview">
                                    <div id="profilePlaceholder">
                                        <div class="file-upload-icon"><i class="fas fa-user-circle"></i></div>
                                        <div class="file-upload-text">Upload your profile photo<br><small>Square ratio preferred (1:1)</small></div>
                                    </div>
                                    <div class="upload-actions">
                                        <button type="button" class="upload-btn" onclick="document.getElementById('profile_camera').click()">
                                            <i class="fas fa-camera"></i> Camera
                                        </button>
                                        <button type="button" class="upload-btn" onclick="document.getElementById('profile_image').click()">
                                            <i class="fas fa-image"></i> Gallery
                                        </button>
                                    </div>
                                    <div class="file-selected" id="profile_image-selected"></div>
                                </div>
                                <div class="error-message">Profile photo is required</div>
                            </div>

                            <!-- Identity Documents -->
                            <div class="form-group" id="identityDocsGroup">
                                <label>Identity Documents *</label>
                                <p style="color: var(--gray-600); font-size: 0.8rem; margin-bottom: 0.5rem;">Upload up to 5 identity documents (both sides if applicable)</p>
                                <div style="margin-bottom: 0.75rem;">
                                    <button type="button" class="upload-btn" onclick="openDocScanner()" style="width: 100%; justify-content: center;">
                                        <i class="fas fa-camera" style="color: var(--primary-clr);"></i> Scan Document with Camera
                                    </button>
                                </div>
                                <div class="multi-file-grid" id="identityDocuments">
                                    @for($i = 0; $i < 5; $i++)
                                    <div class="multi-file-item" onclick="openDocUploadChoice({{ $i }})">
                                        <input type="file" name="identity_image[]" accept="image/*" style="display:none;" onchange="handleMultiFileSelect(this, {{ $i }})">
                                        <input type="file" class="doc-camera-input" accept="image/*" capture="environment" style="display:none;" data-index="{{ $i }}">
                                        <i class="fas fa-plus" style="font-size: 1.5rem; color: {{ $i === 0 ? 'var(--primary-clr)' : 'var(--gray-400)' }}; margin-bottom: 0.3rem;"></i>
                                        <div style="font-size: 0.75rem; color: var(--gray-500);">Doc {{ $i + 1 }}</div>
                                    </div>
                                    @endfor
                                </div>
                                <div class="error-message">At least one identity document is required</div>
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

                    <!-- Document upload choice modal -->
                    <div class="scan-modal-overlay" id="docChoiceModal">
                        <div class="scan-modal">
                            <div class="scan-modal-header">
                                <h4>Upload Document</h4>
                                <button type="button" class="scan-modal-close" onclick="closeDocModal()"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="scan-modal-body">
                                <div class="scan-option" onclick="docModalAction('camera')">
                                    <div class="scan-option-icon"><i class="fas fa-camera"></i></div>
                                    <div class="scan-option-text">
                                        <div class="scan-option-title">Scan with Camera</div>
                                        <div class="scan-option-desc">Take a photo of your document</div>
                                    </div>
                                </div>
                                <div class="scan-option" onclick="docModalAction('gallery')">
                                    <div class="scan-option-icon green"><i class="fas fa-image"></i></div>
                                    <div class="scan-option-text">
                                        <div class="scan-option-title">Choose from Gallery</div>
                                        <div class="scan-option-desc">Pick an existing photo</div>
                                    </div>
                                </div>
                                <div class="scan-option" onclick="docModalAction('file')">
                                    <div class="scan-option-icon blue"><i class="fas fa-file-pdf"></i></div>
                                    <div class="scan-option-text">
                                        <div class="scan-option-title">Browse Files</div>
                                        <div class="scan-option-desc">Select from your device storage</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 7: Password Setup -->
                    <div class="form-step" id="step7">
                        <div class="form-card">
                            <h3><i class="fas fa-lock" style="margin-right: 0.5rem; color: var(--primary-clr);"></i>Account Security</h3>

                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" minlength="6" required>
                                <div class="error-message">Password must be at least 6 characters</div>
                            </div>

                            <!-- Captcha Section -->
                            @php($recaptcha = \App\CentralLogics\Helpers::get_business_settings('recaptcha'))
                            @if(isset($recaptcha) && $recaptcha['status'] == 1 && !empty($recaptcha['site_key']))
                                <div class="form-group">
                                    <label>Security Verification</label>
                                    <div id="g-recaptcha" style="margin-top: 1rem;"></div>
                                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                                    <div class="error-message">Please complete the security verification</div>
                                </div>
                            @else
                                <div class="form-group">
                                    <label for="custome_recaptcha">Security Code *</label>
                                    <div class="form-row">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <input type="text" id="custome_recaptcha" name="custome_recaptcha" required placeholder="Enter security code" autocomplete="off" value="{{ env('APP_DEBUG') ? session('six_captcha') : '' }}">
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0; display: flex; align-items: center; justify-content: center; background: white; border: 2px solid var(--border-clr); border-radius: 10px; padding: 0.5rem;">
                                            @if(isset($custome_recaptcha))
                                                <img src="<?php echo $custome_recaptcha->inline(); ?>" style="max-width: 100%; height: auto; border-radius: 4px;"/>
                                            @else
                                                <div style="padding: 20px; color: var(--gray-600);">Captcha not available</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="error-message">Security code is required</div>
                                </div>
                            @endif

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
                                <div class="success-title">Application Submitted!</div>
                                <div class="success-text">
                                    Thank you for applying to join our delivery team. We've received your application and our team will review it within 24-48 hours. You'll receive an email notification once your application status is updated.
                                </div>
                                <p class="info-text">Download our app for a better experience on the go</p>
                                <a href="https://drive.google.com/file/d/14dHuMDs7Bj3hM5ZOVs96G6uPlvyHzppT/view?usp=sharing" class="highlight-text" target="_blank">Get the app</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Mobile sticky action bar -->
    <div class="mobile-action-bar" id="mobileActionBar">
        <button type="button" class="btn btn-secondary btn-back" id="mobileBackBtn" onclick="prevStep()" style="display: none;">
            <i class="fas fa-arrow-left"></i>
        </button>
        <button type="button" class="btn btn-primary" id="mobileNextBtn" onclick="nextStep()">
            Next <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
        </button>
    </div>

    <!-- Scripts -->
    @if(isset($recaptcha) && $recaptcha['status'] == 1 && !empty($recaptcha['site_key']))
        <script src="https://www.google.com/recaptcha/api.js?render={{$recaptcha['site_key']}}"></script>
    @endif

    <script>
        let currentStep = 1;
        const totalSteps = 7;
        let slideIndex = 0;
        let isTransitioning = false;
        let uploadedDocuments = 0;
        let phoneVerified = false;
        let otpCountdown = null;

        const stepLabels = [
            '1/7 Personal Info',
            '2/7 Verify Phone',
            '3/7 Zone & Vehicle',
            '4/7 Employment Type',
            '5/7 Identity',
            '6/7 Documents',
            '7/7 Security'
        ];

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Image slider (desktop)
        function showSlides() {
            if (isTransitioning) return;

            const slides = document.querySelectorAll('.slide');
            if (!slides.length) return;

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
            const slides = document.querySelectorAll('.slide');
            if (!slides.length) return;
            if (step <= 3) {
                slideIndex = step - 1;
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
            const connectors = document.querySelectorAll('.progress-step-connector');

            steps.forEach((step, index) => {
                const stepNum = index + 1;
                step.classList.remove('active', 'completed');
                if (stepNum < currentStep) {
                    step.classList.add('completed');
                    step.innerHTML = '<i class="fas fa-check" style="font-size: 0.6rem;"></i>';
                } else if (stepNum === currentStep) {
                    step.classList.add('active');
                    step.textContent = stepNum;
                } else {
                    step.textContent = stepNum;
                }
            });

            connectors.forEach((conn, index) => {
                if (index < currentStep - 1) {
                    conn.classList.add('active');
                } else {
                    conn.classList.remove('active');
                }
            });

            // Update step label
            const label = document.getElementById('stepLabel');
            if (label && currentStep <= totalSteps) {
                label.textContent = stepLabels[currentStep - 1];
            }

            // Update mobile action bar
            updateMobileActionBar();
        }

        function updateMobileActionBar() {
            const backBtn = document.getElementById('mobileBackBtn');
            const nextBtn = document.getElementById('mobileNextBtn');
            const bar = document.getElementById('mobileActionBar');

            if (currentStep === 1) {
                backBtn.style.display = 'none';
            } else {
                backBtn.style.display = '';
            }

            // Hide mobile action bar on OTP step (has its own buttons)
            if (currentStep === 2) {
                bar.style.display = 'none';
                return;
            } else {
                bar.style.display = '';
            }

            if (currentStep === totalSteps) {
                nextBtn.textContent = '';
                nextBtn.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> Submit';
                nextBtn.onclick = function() {
                    document.getElementById('registrationForm').requestSubmit();
                };
            } else {
                nextBtn.innerHTML = 'Next <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>';
                nextBtn.onclick = nextStep;
            }

            // Hide bar on success
            const successStep = document.getElementById('success');
            if (successStep && successStep.classList.contains('active')) {
                bar.style.display = 'none';
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
                scrollToTop();
            }, 200);
        }

        function nextStep() {
            if (isTransitioning) return;

            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    // Populate OTP phone display when entering step 2
                    if (currentStep === 2) {
                        populateOtpPhone();
                    }
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
            let firstErrorField = null;

            currentStepElement.querySelectorAll('.form-group').forEach(group => {
                group.classList.remove('error');
            });

            // For step 1, also validate the display phone
            if (currentStep === 1) {
                const phoneDisplay = document.getElementById('phone_display');
                const phoneRaw = document.getElementById('phone_raw');
                if (!phoneDisplay.value.trim()) {
                    phoneDisplay.closest('.form-group').classList.add('error');
                    isValid = false;
                    if (!firstErrorField) firstErrorField = phoneDisplay;
                } else {
                    // Sync raw phone value
                    phoneRaw.value = phoneDisplay.value.trim();
                }
            }

            inputs.forEach(input => {
                const formGroup = input.closest('.form-group');

                if (input.type === 'file' && input.hasAttribute('required')) {
                    if (input.name === 'image' && !input.files.length) {
                        formGroup.classList.add('error');
                        isValid = false;
                        if (!firstErrorField) firstErrorField = input;
                    } else if (input.name === 'identity_image[]') {
                        const allIdentityInputs = currentStepElement.querySelectorAll('input[name="identity_image[]"]');
                        const hasFiles = Array.from(allIdentityInputs).some(inp => inp.files.length > 0);
                        if (!hasFiles) {
                            formGroup.classList.add('error');
                            isValid = false;
                            if (!firstErrorField) firstErrorField = input;
                        }
                    }
                } else if (input.type !== 'file' && !input.value.trim()) {
                    formGroup.classList.add('error');
                    isValid = false;
                    if (!firstErrorField) firstErrorField = input;

                    input.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => { input.style.animation = ''; }, 500);
                }
            });

            // Step 2: OTP verification - block nextStep, handled by OTP flow
            if (currentStep === 2) {
                if (!phoneVerified) {
                    showNotification('Please verify your phone number first', 'error');
                    return false;
                }
            }

            // Step 4: delivery type
            if (currentStep === 4) {
                const deliveryType = document.querySelector('input[name="earning"]:checked');
                if (!deliveryType) {
                    showNotification('Please select an employment type', 'error');
                    isValid = false;
                }
            }

            // Step 7: password validation (must match backend: min 6, uppercase, lowercase, number, symbol)
            if (currentStep === 7) {
                const password = document.getElementById('password').value;
                const passwordGroup = document.getElementById('password').closest('.form-group');
                let passwordError = null;

                if (password.length < 6) {
                    passwordError = 'Password must be at least 6 characters';
                } else if (!/[a-z]/.test(password)) {
                    passwordError = 'Password must contain at least one lowercase letter';
                } else if (!/[A-Z]/.test(password)) {
                    passwordError = 'Password must contain at least one uppercase letter';
                } else if (!/[0-9]/.test(password)) {
                    passwordError = 'Password must contain at least one number';
                } else if (!/[^A-Za-z0-9]/.test(password)) {
                    passwordError = 'Password must contain at least one special character (e.g. !@#$%)';
                }

                if (passwordError) {
                    passwordGroup.classList.add('error');
                    showNotification(passwordError, 'error');
                    isValid = false;
                    if (!firstErrorField) firstErrorField = document.getElementById('password');
                }
            }

            if (!isValid) {
                // Haptic feedback on error
                if (navigator.vibrate) {
                    navigator.vibrate([50, 30, 50]);
                }
                if (!firstErrorField) {
                    showNotification('Please fill in all required fields', 'error');
                } else {
                    // Scroll to first error
                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    showNotification('Please fill in all required fields', 'error');
                }
            }

            return isValid;
        }

        function selectDeliveryType(e, type) {
            document.querySelectorAll('.type-option').forEach(option => {
                option.classList.remove('selected');
            });

            e.currentTarget.classList.add('selected');
            document.getElementById(type).checked = true;
        }

        // === OTP Verification Functions ===
        function dmSendOtp() {
            const phone = document.getElementById('phone_raw').value || document.getElementById('phone_display').value.trim();
            if (!phone) {
                showNotification('Phone number is missing. Go back and enter it.', 'error');
                return;
            }

            // Show phone in the read-only display
            document.getElementById('otp_phone_display').value = phone;
            document.getElementById('otpPhoneLabel').textContent = phone;

            const btn = document.getElementById('dmSendOtpBtn');
            btn.classList.add('loading');
            btn.disabled = true;

            fetch('{{ route("deliveryman.send-otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ phone: phone })
            })
            .then(r => r.json().then(data => ({ status: r.status, body: data })))
            .then(({ status, body }) => {
                btn.classList.remove('loading');
                btn.disabled = false;

                if (body.success) {
                    showNotification(body.message, 'success');
                    document.getElementById('otpSendSection').style.display = 'none';
                    document.getElementById('otpVerifySection').style.display = 'block';
                    startOtpTimer(60);
                    // Focus first OTP digit
                    const firstDigit = document.querySelector('#step2 .otp-digit[data-otp-index="0"]');
                    if (firstDigit) firstDigit.focus();
                } else {
                    showNotification(body.message || 'Failed to send OTP', 'error');
                }
            })
            .catch(err => {
                btn.classList.remove('loading');
                btn.disabled = false;
                showNotification('Network error. Please try again.', 'error');
            });
        }

        function dmVerifyOtp() {
            const digits = document.querySelectorAll('#step2 .otp-digit');
            let otp = '';
            digits.forEach(d => otp += d.value);

            if (otp.length !== 6) {
                showNotification('Please enter the complete 6-digit OTP', 'error');
                return;
            }

            const phone = document.getElementById('phone_raw').value || document.getElementById('phone_display').value.trim();
            const btn = document.getElementById('dmVerifyOtpBtn');
            btn.classList.add('loading');
            btn.disabled = true;

            fetch('{{ route("deliveryman.verify-otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ phone: phone, otp: otp })
            })
            .then(r => r.json().then(data => ({ status: r.status, body: data })))
            .then(({ status, body }) => {
                btn.classList.remove('loading');
                btn.disabled = false;

                if (body.success) {
                    phoneVerified = true;
                    showNotification('Phone verified successfully!', 'success');
                    // Auto-advance to next step
                    currentStep++;
                    showStep(currentStep);
                    updateProgress();
                } else {
                    showNotification(body.message || 'Invalid OTP', 'error');
                    const errorEl = document.getElementById('otpError');
                    if (errorEl) {
                        errorEl.textContent = body.message || 'Invalid OTP';
                        errorEl.style.display = 'block';
                    }
                }
            })
            .catch(err => {
                btn.classList.remove('loading');
                btn.disabled = false;
                showNotification('Network error. Please try again.', 'error');
            });
        }

        function dmResendOtp() {
            const phone = document.getElementById('phone_raw').value || document.getElementById('phone_display').value.trim();
            const btn = document.getElementById('dmResendBtn');
            btn.disabled = true;

            fetch('{{ route("deliveryman.resend-otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ phone: phone })
            })
            .then(r => r.json().then(data => ({ status: r.status, body: data })))
            .then(({ status, body }) => {
                btn.disabled = false;
                if (body.success) {
                    showNotification(body.message, 'success');
                    btn.style.display = 'none';
                    startOtpTimer(60);
                    // Clear OTP inputs
                    document.querySelectorAll('#step2 .otp-digit').forEach(d => d.value = '');
                    document.querySelector('#step2 .otp-digit[data-otp-index="0"]').focus();
                } else {
                    showNotification(body.message || 'Failed to resend OTP', 'error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                showNotification('Network error. Please try again.', 'error');
            });
        }

        function dmBackToSend() {
            document.getElementById('otpVerifySection').style.display = 'none';
            document.getElementById('otpSendSection').style.display = 'block';
            if (otpCountdown) clearInterval(otpCountdown);
        }

        function startOtpTimer(seconds) {
            const timerEl = document.getElementById('otpTimer');
            const resendBtn = document.getElementById('dmResendBtn');
            resendBtn.style.display = 'none';
            let remaining = seconds;

            if (otpCountdown) clearInterval(otpCountdown);

            timerEl.textContent = 'Resend OTP in ' + remaining + 's';
            otpCountdown = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(otpCountdown);
                    timerEl.textContent = '';
                    resendBtn.style.display = 'inline-block';
                } else {
                    timerEl.textContent = 'Resend OTP in ' + remaining + 's';
                }
            }, 1000);
        }

        // OTP digit auto-advance
        document.querySelectorAll('#step2 .otp-digit').forEach((input, index, inputs) => {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                // Auto-verify when all 6 digits entered
                if (index === inputs.length - 1 && this.value) {
                    let otp = '';
                    inputs.forEach(d => otp += d.value);
                    if (otp.length === 6) dmVerifyOtp();
                }
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                if (pasted.length === 6) {
                    inputs.forEach((inp, i) => inp.value = pasted[i] || '');
                    inputs[5].focus();
                    dmVerifyOtp();
                }
            });
        });

        // Populate OTP phone display when entering step 2
        function populateOtpPhone() {
            const phone = document.getElementById('phone_raw').value || document.getElementById('phone_display').value.trim();
            const otpDisplay = document.getElementById('otp_phone_display');
            const otpLabel = document.getElementById('otpPhoneLabel');
            if (otpDisplay) otpDisplay.value = phone;
            if (otpLabel) otpLabel.textContent = phone;
        }

        // === Profile photo handling ===
        function handleProfilePhoto(input) {
            const file = input.files[0];
            if (!file) return;

            if (!validateImageFile(file)) { input.value = ''; return; }

            const preview = document.getElementById('profilePreview');
            const placeholder = document.getElementById('profilePlaceholder');
            const selectedDiv = document.getElementById('profile_image-selected');
            const area = document.getElementById('profileUploadArea');

            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.add('show');
                placeholder.style.display = 'none';
                area.classList.add('has-preview');
            };
            reader.readAsDataURL(file);

            selectedDiv.textContent = file.name;
            selectedDiv.classList.add('show');
            document.getElementById('profilePhotoGroup').classList.remove('error');
        }

        // Transfer camera capture to the actual form input
        function transferToProfile(camInput) {
            const file = camInput.files[0];
            if (!file) return;
            if (!validateImageFile(file)) { camInput.value = ''; return; }

            // Use DataTransfer to move file to real input
            const dt = new DataTransfer();
            dt.items.add(file);
            const realInput = document.getElementById('profile_image');
            realInput.files = dt.files;
            handleProfilePhoto(realInput);
            camInput.value = '';
        }

        // === Document upload handling ===
        let activeDocIndex = 0;

        function openDocUploadChoice(index) {
            activeDocIndex = index;
            document.getElementById('docChoiceModal').classList.add('show');
        }

        function closeDocModal() {
            document.getElementById('docChoiceModal').classList.remove('show');
        }

        function docModalAction(action) {
            closeDocModal();
            const index = activeDocIndex;

            if (action === 'camera') {
                // Use camera capture input
                const camInput = document.querySelectorAll('.doc-camera-input')[index];
                camInput.onchange = function() {
                    if (this.files[0]) {
                        transferToDocInput(this, index);
                    }
                };
                camInput.click();
            } else {
                // gallery or file — use regular file input
                const fileInput = document.querySelectorAll('input[name="identity_image[]"]')[index];
                fileInput.click();
            }
        }

        function openDocScanner() {
            // Find the first empty slot
            const items = document.querySelectorAll('.multi-file-item');
            let targetIndex = 0;
            for (let i = 0; i < items.length; i++) {
                if (!items[i].classList.contains('has-file')) {
                    targetIndex = i;
                    break;
                }
                if (i === items.length - 1) {
                    showNotification('All 5 document slots are filled', 'info');
                    return;
                }
            }
            activeDocIndex = targetIndex;
            const camInput = document.querySelectorAll('.doc-camera-input')[targetIndex];
            camInput.onchange = function() {
                if (this.files[0]) {
                    transferToDocInput(this, targetIndex);
                }
            };
            camInput.click();
        }

        function transferToDocInput(camInput, index) {
            const file = camInput.files[0];
            if (!file) return;
            if (!validateImageFile(file)) { camInput.value = ''; return; }

            const dt = new DataTransfer();
            dt.items.add(file);
            const realInput = document.querySelectorAll('input[name="identity_image[]"]')[index];
            realInput.files = dt.files;
            handleMultiFileSelect(realInput, index);
            camInput.value = '';
        }

        function handleMultiFileSelect(input, index) {
            const file = input.files[0];
            const container = input.closest('.multi-file-item');
            if (!file) return;

            if (!validateImageFile(file)) { input.value = ''; return; }

            container.classList.add('has-file');

            // Show image preview thumbnail
            const reader = new FileReader();
            reader.onload = function(e) {
                // Remove old preview if exists
                const oldImg = container.querySelector('.doc-preview-img');
                if (oldImg) oldImg.remove();
                const oldOverlay = container.querySelector('.doc-overlay');
                if (oldOverlay) oldOverlay.remove();
                const oldBadge = container.querySelector('.doc-check-badge');
                if (oldBadge) oldBadge.remove();

                // Hide placeholder icon and text
                const icon = container.querySelector('i.fa-plus, i.fa-check');
                const label = container.querySelector('div:last-of-type');
                if (icon) icon.style.display = 'none';
                if (label) label.style.display = 'none';

                // Add preview image
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'doc-preview-img';
                img.alt = 'Document ' + (index + 1);
                container.appendChild(img);

                // Add overlay label
                const overlay = document.createElement('div');
                overlay.className = 'doc-overlay';
                overlay.textContent = file.name.substring(0, 15) + (file.name.length > 15 ? '...' : '');
                container.appendChild(overlay);

                // Add check badge
                const badge = document.createElement('div');
                badge.className = 'doc-check-badge';
                badge.innerHTML = '<i class="fas fa-check"></i>';
                container.appendChild(badge);
            };
            reader.readAsDataURL(file);

            // Enable next document slot
            if (index < 4) {
                const nextItem = document.querySelectorAll('.multi-file-item')[index + 1];
                const nextIcon = nextItem.querySelector('i');
                if (nextIcon) nextIcon.style.color = 'var(--primary-clr)';
            }

            uploadedDocuments = Math.max(uploadedDocuments, index + 1);
            document.getElementById('identityDocsGroup').classList.remove('error');
        }

        function validateImageFile(file) {
            if (file.size > 5 * 1024 * 1024) {
                showNotification('File size must be less than 5MB', 'error');
                return false;
            }
            if (!file.type.match(/image\/(jpeg|jpg|png|webp|gif|bmp)/)) {
                showNotification('Please select a valid image file (JPEG, PNG, WEBP)', 'error');
                return false;
            }
            return true;
        }

        // Form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (!validateCurrentStep()) return;

            // Sync phone value before submit
            document.getElementById('phone_raw').value = document.getElementById('phone_display').value.trim();

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            showLoading(true);

            @if(isset($recaptcha) && $recaptcha['status'] == 1 && !empty($recaptcha['site_key']))
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.ready(function() {
                        grecaptcha.execute('{{$recaptcha['site_key']}}', {action: 'submit'}).then(function(token) {
                            document.getElementById('g-recaptcha-response').value = token;
                            submitFormData();
                        }).catch(function(error) {
                            showLoading(false);
                            submitBtn.classList.remove('loading');
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                            showNotification('reCAPTCHA verification failed. Please try again.', 'error');
                        });
                    });
                } else {
                    showLoading(false);
                    submitBtn.classList.remove('loading');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    showNotification('reCAPTCHA not loaded. Please refresh the page and try again.', 'error');
                }
            @else
                submitFormData();
            @endif
        });

        function compressImage(file, maxWidth, quality) {
            return new Promise((resolve) => {
                if (!file || !file.type.startsWith('image/')) {
                    resolve(file);
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        if (width > maxWidth) {
                            height = Math.round(height * maxWidth / width);
                            width = maxWidth;
                        }
                        canvas.width = width;
                        canvas.height = height;
                        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                        canvas.toBlob(function(blob) {
                            resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                        }, 'image/jpeg', quality);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        async function submitFormData() {
            const form = document.getElementById('registrationForm');
            const formData = new FormData(form);

            // Compress profile image
            const profileInput = form.querySelector('input[name="image"]');
            if (profileInput && profileInput.files.length > 0) {
                const compressed = await compressImage(profileInput.files[0], 800, 0.7);
                formData.set('image', compressed);
            }

            // Compress identity images
            formData.delete('identity_image[]');
            const identityInputs = form.querySelectorAll('input[name="identity_image[]"]');
            for (const input of identityInputs) {
                for (const file of input.files) {
                    const compressed = await compressImage(file, 1200, 0.7);
                    formData.append('identity_image[]', compressed);
                }
            }

            fetch('{{ route('deliveryman.store') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                const contentType = response.headers.get('content-type') || '';
                const status = response.status;

                if (contentType.includes('application/json')) {
                    return response.json().then(data => {
                        if (!response.ok || data.success === false) {
                            let errMsg = '[HTTP ' + status + '] ' + (data.message || 'Unknown error');
                            if (data.errors) {
                                const allErrors = Object.values(data.errors).flat().join(' | ');
                                errMsg += ' — ' + allErrors;
                            }
                            throw new Error(errMsg);
                        }
                        return data;
                    });
                } else {
                    return response.text().then(html => {
                        if (response.ok) {
                            return { success: true };
                        }
                        // Try to extract error from HTML
                        const match = html.match(/<title>(.*?)<\/title>/i);
                        const hint = match ? match[1] : html.substring(0, 300);
                        throw new Error('[HTTP ' + status + '] Server returned HTML instead of JSON: ' + hint);
                    });
                }
            })
            .then(data => {
                showLoading(false);

                try { sessionStorage.removeItem('deliverymanRegistrationData'); } catch (e) { window.formBackup = null; }

                showSuccessScreen();
                showNotification(data.message || 'Application submitted successfully!', 'success');
            })
            .catch(error => {
                showLoading(false);
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> Complete Registration';
                submitBtn.disabled = false;

                console.error('DM Registration Error:', error);
                showNotification(error.message || 'An error occurred. Please try again.', 'error');
            });
        }

        function showSuccessScreen() {
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });

            document.getElementById('success').classList.add('active');

            document.querySelectorAll('.progress-step').forEach(step => {
                step.classList.add('completed');
                step.innerHTML = '<i class="fas fa-check" style="font-size: 0.6rem;"></i>';
            });
            document.querySelectorAll('.progress-step-connector').forEach(c => c.classList.add('active'));

            document.getElementById('stepLabel').textContent = 'Complete!';

            // Hide mobile action bar
            document.getElementById('mobileActionBar').style.display = 'none';

            scrollToTop();
        }

        function showNotification(message, type = 'info') {
            document.querySelectorAll('.notification').forEach(n => n.remove());

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                if (notification.parentNode) notification.parentNode.removeChild(notification);
            }, 3000);
        }

        function showLoading(show) {
            document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
        }

        // DOMContentLoaded setup
        document.addEventListener('DOMContentLoaded', function() {
            // Phone input: preserve raw value, no aggressive reformatting
            const phoneDisplay = document.getElementById('phone_display');
            const phoneRaw = document.getElementById('phone_raw');

            phoneDisplay.addEventListener('input', function(e) {
                // Sync raw value preserving whatever user typed (including +)
                phoneRaw.value = e.target.value.trim();
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

            // Laravel validation errors
            @if($errors->any())
                @foreach($errors->all() as $error)
                    showNotification('{{ $error }}', 'error');
                @endforeach
            @endif

            // Initialize progress
            updateProgress();
        });

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function focusFirstInput() {
            const currentStepElement = document.getElementById(`step${currentStep}`);
            if (!currentStepElement) return;
            const firstInput = currentStepElement.querySelector('input:not([readonly]):not([type="hidden"]):not([type="file"]):not([type="radio"]), select, textarea');
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

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Auto-save
        function saveFormData() {
            const formData = new FormData(document.getElementById('registrationForm'));
            const data = Object.fromEntries(formData);
            try { sessionStorage.setItem('deliverymanRegistrationData', JSON.stringify(data)); }
            catch (e) { window.formBackup = data; }
        }

        function loadFormData() {
            let savedData = null;
            try { savedData = JSON.parse(sessionStorage.getItem('deliverymanRegistrationData')); }
            catch (e) { savedData = window.formBackup; }

            if (savedData) {
                Object.keys(savedData).forEach(key => {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input && savedData[key]) {
                        if (input.type === 'radio') {
                            if (input.value === savedData[key]) {
                                input.checked = true;
                                const typeOption = input.closest('.type-option');
                                if (typeOption) typeOption.classList.add('selected');
                            }
                        } else {
                            input.value = savedData[key];
                        }
                    }
                });
                // Sync phone display
                if (savedData.phone) {
                    document.getElementById('phone_display').value = savedData.phone;
                }
            }
        }

        document.getElementById('registrationForm').addEventListener('input', saveFormData);
        document.getElementById('registrationForm').addEventListener('change', saveFormData);

        window.addEventListener('load', function() {
            loadFormData();
            focusFirstInput();
        });

        window.addEventListener('beforeunload', function(e) {
            if (currentStep > 1 && currentStep < totalSteps) {
                saveFormData();
            }
        });

        @if(isset($recaptcha) && $recaptcha['status'] == 1 && !empty($recaptcha['site_key']))
        window.onerror = function (message) {
            if (message.includes('recaptcha') || message.includes('grecaptcha')) {
                var errorMessage = 'reCAPTCHA verification failed. Please refresh the page and try again.';
                if (message.includes('Invalid site key')) {
                    errorMessage = 'Invalid reCAPTCHA configuration. Please contact support.';
                } else if (message.includes('not loaded in api.js')) {
                    errorMessage = 'reCAPTCHA could not be loaded. Please check your internet connection.';
                }
                showNotification(errorMessage, 'error');
                return true;
            }
            return false;
        };
        @endif

        @if(old('earning') !== null)
            document.addEventListener('DOMContentLoaded', function() {
                const earningValue = '{{ old('earning') }}';
                const type = earningValue === '1' ? 'freelancer' : 'salary';
                const option = document.getElementById(type).closest('.type-option');
                if (option) {
                    option.classList.add('selected');
                    document.getElementById(type).checked = true;
                }
            });
        @endif
    </script>
</body>
</html>
