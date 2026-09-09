<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Delete Customer Account</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
        }

        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .warning-box h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .warning-box h3::before {
            content: "⚠️";
            margin-right: 10px;
            font-size: 20px;
        }

        .warning-box ul {
            color: #856404;
            font-size: 14px;
            margin-left: 30px;
        }

        .warning-box li {
            margin: 5px 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .input-group {
            position: relative;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            margin-right: 8px;
            cursor: pointer;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .otp-section {
            display: none;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🗑️ Delete Customer Account</h1>
            <p>Permanently delete your customer account</p>
        </div>

        <div class="warning-box">
            <h3>Before you proceed:</h3>
            <ul>
                <li>All your data will be permanently deleted</li>
                <li>You must complete all ongoing orders first</li>
                <li>This action cannot be undone</li>
            </ul>
        </div>

        <div id="message" class="message"></div>

        <form id="deletionForm">
            <div id="step1">
                <div class="form-group">
                    <label>Verification Method</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="type" value="email" checked>
                            <span>Email</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="type" value="phone">
                            <span>Phone</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label id="identifierLabel">Email Address</label>
                    <div class="input-group">
                        <input type="text" id="identifier" name="identifier" placeholder="Enter your email" required>
                    </div>
                </div>

                <button type="button" id="sendOtpBtn">Send Verification Code</button>
            </div>

            <div id="step2" class="otp-section">
                <div class="form-group">
                    <label>Enter 6-Digit OTP</label>
                    <div class="input-group">
                        <input type="text" id="otp" name="otp" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                    </div>
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                        Check your email/phone for the verification code
                    </small>
                </div>

                <button type="submit" id="deleteBtn">Permanently Delete My Account</button>
                <button type="button" id="backBtn" style="margin-top: 10px; background: #6c757d;">Back</button>
            </div>
        </form>

        <div class="back-link">
            <a href="/">← Back to Home</a>
        </div>
    </div>

    <script>
        // CSRF Token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // DOM Elements
        const form = document.getElementById('deletionForm');
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const sendOtpBtn = document.getElementById('sendOtpBtn');
        const deleteBtn = document.getElementById('deleteBtn');
        const backBtn = document.getElementById('backBtn');
        const identifierInput = document.getElementById('identifier');
        const identifierLabel = document.getElementById('identifierLabel');
        const otpInput = document.getElementById('otp');
        const messageDiv = document.getElementById('message');
        const typeRadios = document.querySelectorAll('input[name="type"]');

        // Update label based on type
        typeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'email') {
                    identifierLabel.textContent = 'Email Address';
                    identifierInput.placeholder = 'Enter your email';
                    identifierInput.type = 'email';
                } else {
                    identifierLabel.textContent = 'Phone Number';
                    identifierInput.placeholder = 'Enter your phone number';
                    identifierInput.type = 'tel';
                }
            });
        });

        // Show message
        function showMessage(message, type) {
            messageDiv.textContent = message;
            messageDiv.className = 'message ' + type;
            messageDiv.style.display = 'block';
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }

        // Send OTP
        sendOtpBtn.addEventListener('click', async function() {
            const identifier = identifierInput.value;
            const type = document.querySelector('input[name="type"]:checked').value;

            if (!identifier) {
                showMessage('Please enter your ' + (type === 'email' ? 'email' : 'phone number'), 'error');
                return;
            }

            sendOtpBtn.disabled = true;
            sendOtpBtn.innerHTML = '<span class="loading"></span> Sending...';

            try {
                const response = await fetch('/account-deletion/customer/request-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ identifier, type })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message, 'success');
                    step1.style.display = 'none';
                    step2.style.display = 'block';
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                sendOtpBtn.disabled = false;
                sendOtpBtn.innerHTML = 'Send Verification Code';
            }
        });

        // Delete account
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const identifier = identifierInput.value;
            const type = document.querySelector('input[name="type"]:checked').value;
            const otp = otpInput.value;

            if (otp.length !== 6) {
                showMessage('Please enter a valid 6-digit OTP', 'error');
                return;
            }

            if (!confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.')) {
                return;
            }

            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<span class="loading"></span> Deleting...';

            try {
                const response = await fetch('/account-deletion/customer/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ identifier, type, otp })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message, 'success');
                    form.reset();
                    step2.style.display = 'none';
                    step1.style.display = 'block';

                    // Redirect to home after 3 seconds
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 3000);
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = 'Permanently Delete My Account';
            }
        });

        // Back button
        backBtn.addEventListener('click', function() {
            step2.style.display = 'none';
            step1.style.display = 'block';
            otpInput.value = '';
        });
    </script>
</body>
</html>
