@extends('payment-views.layouts.master')

@section('content')
<div class="payment-container">
    <div class="payment-card">
        <div class="loading-section">
            <div class="spinner"></div>
            <h2>Redirecting to Payment...</h2>
            <p>Amount: <strong>{{ $data->currency_code ?? 'INR' }} {{ number_format($data->payment_amount, 2) }}</strong></p>
        </div>
        <button type="button" id="cancel-button" class="cancel-button" onclick="handleCancel()">
            Cancel Payment
        </button>
    </div>
</div>

<!-- Hidden Form for Razorpay Response -->
<form action="{{ route('razor-pay.payment', ['payment_id' => $data->id]) }}" id="razorpay-form" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature">
    <input type="hidden" name="payment_id" value="{{ $data->id }}">
</form>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const razorpayForm = document.getElementById('razorpay-form');

    const options = {
        key: "{{ $razorpay_key }}",
        amount: {{ (int) round($data->payment_amount * 100) }},
        currency: "{{ $data->currency_code ?? 'INR' }}",
        name: "{{ $business_name }}",
        description: "Payment for Order",
        image: "{{ $business_logo }}",
        order_id: "{{ $razorpay_order_id }}",
        handler: function(response) {
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
            document.getElementById('razorpay_signature').value = response.razorpay_signature;
            razorpayForm.submit();
        },
        prefill: {
            name: "{{ $payer->name ?? '' }}",
            email: "{{ $payer->email ?? '' }}",
            contact: "{{ $payer->phone ?? '' }}"
        },
        notes: {
            payment_id: "{{ $data->id }}"
        },
        theme: {
            color: "#D82E5E"
        },
        modal: {
            ondismiss: function() {
                // User closed popup - redirect to cancel
                window.location.href = '{{ route('razor-pay.cancel', ['payment_id' => $data->id]) }}';
            },
            escape: true,
            animation: true
        },
        // UPI configured for WebView - Intent + Collect + QR
        config: {
            display: {
                blocks: {
                    upi: {
                        name: "Pay using UPI",
                        instruments: [
                            {
                                method: "upi",
                                flows: ["intent", "collect", "qr"]
                            }
                        ]
                    },
                    other: {
                        name: "Other Payment Methods",
                        instruments: [
                            { method: "card" },
                            { method: "netbanking" },
                            { method: "wallet" }
                        ]
                    }
                },
                sequence: ["block.upi", "block.other"],
                preferences: {
                    show_default_blocks: false
                }
            }
        },
        // Enable external handler for WebView intent URLs
        external: {
            wallets: ['paytm', 'phonepe', 'gpay']
        }
    };

    const rzp = new Razorpay(options);

    rzp.on('payment.failed', function(response) {
        alert('Payment failed: ' + response.error.description);
        window.location.href = '{{ route('razor-pay.cancel', ['payment_id' => $data->id]) }}';
    });

    // Auto-open Razorpay - wait for checkout.js to fully initialize
    function openRazorpay(attempts) {
        if (typeof Razorpay !== 'undefined') {
            rzp.open();
        } else if (attempts > 0) {
            setTimeout(function() { openRazorpay(attempts - 1); }, 200);
        } else {
            alert('Payment gateway failed to load. Please check your internet connection and try again.');
        }
    }
    setTimeout(function() { openRazorpay(10); }, 300);
});

function handleCancel() {
    window.location.href = '{{ route('razor-pay.cancel', ['payment_id' => $data->id]) }}';
}
</script>

<style>
:root {
    --primary-clr: #D82E5E;
    --gradient: linear-gradient(135deg, #D82E5E, #ff6b8a);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--gradient);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.payment-container { width: 100%; max-width: 400px; }

.payment-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    padding: 40px 32px;
    text-align: center;
}

.loading-section { margin-bottom: 24px; }

.spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid var(--primary-clr);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin { to { transform: rotate(360deg); } }

h2 { color: #1a1a2e; font-size: 18px; margin-bottom: 8px; }
p { color: #6c757d; font-size: 14px; }
p strong { color: var(--primary-clr); }

.cancel-button {
    width: 100%;
    padding: 14px;
    background: transparent;
    color: #6c757d;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}

.cancel-button:hover {
    background: #f8f9fa;
    color: #dc3545;
    border-color: #dc3545;
}
</style>
@endsection
