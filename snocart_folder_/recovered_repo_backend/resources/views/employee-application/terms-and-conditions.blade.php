<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php($business_name = \App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value ?? 'Snocart')
    @php($favicon = \App\Models\BusinessSetting::where(['key'=>'icon'])->first())

    <title>{{ translate('messages.terms_and_conditions') }} | {{ $business_name }}</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="">
    <link rel="icon" type="image/x-icon" href="{{\App\CentralLogics\Helpers::get_full_url('business', $favicon?->value ?? '', $favicon?->storage[0]?->value ?? 'public','favicon')}}">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">

    <style>
        body {
            background: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .pdf-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            background: white;
        }
        .pdf-header {
            background: #D82E5E;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .pdf-header h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .pdf-viewer {
            flex: 1;
            overflow: hidden;
            background: #525659;
        }
        .pdf-viewer iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .pdf-viewer embed {
            width: 100%;
            height: 100%;
        }
        .btn-close-custom {
            background: white;
            color: #D82E5E;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-close-custom:hover {
            background: #f0f0f0;
            transform: translateY(-1px);
        }
        .no-pdf-message {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .no-pdf-message i {
            font-size: 64px;
            color: #D82E5E;
            margin-bottom: 20px;
        }
        .no-pdf-message h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .fallback-content {
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .fallback-content h4 {
            color: #D82E5E;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .fallback-content p {
            line-height: 1.8;
            color: #333;
        }
        .fallback-content ul {
            line-height: 2;
            color: #555;
        }
        .terms-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .btn-accept {
            background: #D82E5E;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            font-weight: 600;
            cursor: not-allowed;
            opacity: 0.5;
            transition: all 0.3s;
        }
        .btn-accept.enabled {
            cursor: pointer;
            opacity: 1;
        }
        .btn-accept.enabled:hover {
            background: #b5254c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(216, 46, 94, 0.3);
        }
        .scroll-indicator {
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .scroll-indicator.hidden {
            display: none;
        }
        .scroll-icon {
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        .pdf-viewer {
            padding-bottom: 70px; /* Space for footer */
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <div class="pdf-header">
            <h5>{{ translate('messages.employee_terms_and_conditions') }}</h5>
            <button class="btn-close-custom" onclick="closeWindow()">
                {{ translate('messages.close') }}
            </button>
        </div>

        <div class="pdf-viewer">
            <?php
                // Check if terms and conditions PDF exists
                $termsFile = public_path('assets/documents/employee-terms-and-conditions.pdf');
                $termsExists = file_exists($termsFile);
            ?>

            @if($termsExists)
                <!-- PDF Viewer -->
                <embed src="{{ asset('public/assets/documents/employee-terms-and-conditions.pdf') }}#toolbar=1&navpanes=1&scrollbar=1"
                       type="application/pdf"
                       width="100%"
                       height="100%">

                <!-- Fallback for browsers that don't support embed -->
                <noscript>
                    <div class="no-pdf-message">
                        <i class="tio-document"></i>
                        <h3>{{ translate('messages.unable_to_display_pdf') }}</h3>
                        <p>{{ translate('messages.download_pdf_to_view') }}</p>
                        <a href="{{ asset('public/assets/documents/employee-terms-and-conditions.pdf') }}"
                           class="btn btn-primary"
                           download>
                            {{ translate('messages.download_pdf') }}
                        </a>
                    </div>
                </noscript>
            @else
                <!-- Fallback Content if PDF doesn't exist -->
                <div style="overflow-y: auto; height: 100%; background: #f5f5f5;">
                    <div class="fallback-content">
                        <h2 style="color: #D82E5E; margin-bottom: 30px;">Employee Terms and Conditions</h2>

                        <h4>1. Employment Agreement</h4>
                        <p>By submitting your application, you agree to enter into an employment agreement with {{ $business_name }} upon approval of your application.</p>

                        <h4>2. Probation Period</h4>
                        <p>All new employees are subject to a probation period of 90 days from the date of joining. During this period, either party may terminate the employment with 7 days notice.</p>

                        <h4>3. Notice Period</h4>
                        <p>After successful completion of the probation period, a notice period of 30 days is required from either party for termination of employment.</p>

                        <h4>4. Background Verification</h4>
                        <p>All employees are subject to background verification including:</p>
                        <ul>
                            <li>Police verification (to be completed within 90 days)</li>
                            <li>Document verification (Aadhar, educational certificates)</li>
                            <li>Reference checks (family contact, previous employer)</li>
                        </ul>

                        <h4>5. Code of Conduct</h4>
                        <p>Employees must adhere to the company's code of conduct, including:</p>
                        <ul>
                            <li>Professional behavior at all times</li>
                            <li>Punctuality and regular attendance</li>
                            <li>Confidentiality of company information</li>
                            <li>Compliance with all policies and procedures</li>
                        </ul>

                        <h4>6. Compensation & Benefits</h4>
                        <p>Salary and benefits will be as per the offer letter issued upon approval of application. Payment will be made monthly via bank transfer.</p>

                        <h4>7. Working Hours</h4>
                        <p>Standard working hours are 9 hours per day, 6 days per week. Shift timings will be communicated by your supervisor.</p>

                        <h4>8. Leave Policy</h4>
                        <p>Employees are entitled to leave as per company policy. Leave must be applied for in advance and approved by the supervisor.</p>

                        <h4>9. Termination</h4>
                        <p>Employment may be terminated by the company with immediate effect in case of:</p>
                        <ul>
                            <li>Misconduct or violation of company policies</li>
                            <li>Criminal activity or fraud</li>
                            <li>Consistent poor performance</li>
                            <li>Unauthorized absence for more than 3 consecutive days</li>
                        </ul>

                        <h4>10. Data Privacy</h4>
                        <p>Your personal information will be used solely for employment purposes and will be handled in accordance with applicable data protection laws.</p>

                        <h4>11. Amendments</h4>
                        <p>The company reserves the right to amend these terms and conditions. Employees will be notified of any changes.</p>

                        <h4>12. Acceptance</h4>
                        <p>By checking the "I agree to terms and conditions" box on the registration form, you confirm that you have read, understood, and agree to these terms.</p>

                        <hr style="margin: 40px 0;">

                        <p style="text-align: center; color: #666; font-size: 14px;">
                            <strong>{{ $business_name }}</strong><br>
                            Last Updated: {{ date('F Y') }}<br>
                            <a href="javascript:window.close()" style="color: #D82E5E;">Close Window</a>
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer with Accept Button -->
        <div class="terms-footer">
            <div class="scroll-indicator" id="scrollIndicator">
                <span class="scroll-icon">⬇</span>
                <span>{{ translate('messages.scroll_to_bottom_to_accept') }}</span>
            </div>
            <button class="btn-accept" id="acceptBtn" onclick="acceptTerms()" disabled>
                {{ translate('messages.i_have_read_and_accept') }}
            </button>
        </div>
    </div>

    <script src="{{ asset('public/assets/admin/js/vendor.min.js') }}"></script>
    <script>
        let hasScrolledToBottom = false;

        // Function to check if user has scrolled to bottom
        function checkScrollPosition() {
            const pdfViewer = document.querySelector('.pdf-viewer');
            const fallbackContent = document.querySelector('.fallback-content');

            // For fallback HTML content
            if (fallbackContent) {
                const container = fallbackContent.parentElement;
                const scrollTop = container.scrollTop;
                const scrollHeight = container.scrollHeight;
                const clientHeight = container.clientHeight;

                // Check if scrolled to bottom (with 50px tolerance)
                if (scrollTop + clientHeight >= scrollHeight - 50) {
                    enableAcceptButton();
                }
            }
            // For PDF viewer - enable after 10 seconds of viewing
            else {
                // PDF viewers don't expose scroll events reliably
                // So we use a timer-based approach
                if (!hasScrolledToBottom) {
                    setTimeout(function() {
                        enableAcceptButton();
                    }, 10000); // 10 seconds
                    hasScrolledToBottom = true; // Only set timer once
                }
            }
        }

        // Enable accept button
        function enableAcceptButton() {
            const acceptBtn = document.getElementById('acceptBtn');
            const scrollIndicator = document.getElementById('scrollIndicator');

            if (acceptBtn && !acceptBtn.classList.contains('enabled')) {
                acceptBtn.classList.add('enabled');
                acceptBtn.disabled = false;
                acceptBtn.style.cursor = 'pointer';

                if (scrollIndicator) {
                    scrollIndicator.classList.add('hidden');
                }
            }
        }

        // Accept terms and close window
        function acceptTerms() {
            const acceptBtn = document.getElementById('acceptBtn');

            if (!acceptBtn.classList.contains('enabled')) {
                alert('{{ translate("messages.please_scroll_to_bottom_first") }}');
                return;
            }

            // Signal to parent window that terms were accepted
            if (window.opener) {
                window.opener.postMessage({ termsAccepted: true }, '*');
            }

            // Close the window
            window.close();

            // If window.close() doesn't work (some browsers block it)
            setTimeout(function() {
                if (!window.closed) {
                    window.location.href = 'about:blank';
                }
            }, 100);
        }

        // Close window without accepting
        function closeWindow() {
            if (confirm('{{ translate("messages.close_without_accepting") }}')) {
                window.close();

                // Fallback if window.close() doesn't work
                setTimeout(function() {
                    if (!window.closed) {
                        window.location.href = 'about:blank';
                    }
                }, 100);
            }
        }

        // Set up scroll listener
        document.addEventListener('DOMContentLoaded', function() {
            const fallbackContent = document.querySelector('.fallback-content');

            if (fallbackContent) {
                const container = fallbackContent.parentElement;
                container.addEventListener('scroll', checkScrollPosition);

                // Check initial position
                checkScrollPosition();
            } else {
                // For PDF viewer, start timer
                checkScrollPosition();
            }
        });
    </script>
</body>
</html>
