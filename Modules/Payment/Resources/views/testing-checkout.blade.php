<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Payment Checkout</title>
</head>
<body style="margin:0; background:#f8fafc;">
<div style="max-width: 420px; margin: 3rem auto; padding: 1.5rem; border: 1px solid #e5e7eb; border-radius: 16px; background:#fff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    <div style="text-align:center; margin-bottom:1.5rem;">
        <span style="background:#fef3c7; color:#92400e; padding:4px 12px; border-radius:999px; font-size:0.75rem; font-weight:700; letter-spacing:0.05em;">
            TEST MODE
        </span>
    </div>

    <div style="text-align:center; margin-bottom:2rem;">
        <div style="color:#6b7280; font-size:0.9rem;">Ijaz Test Gateway</div>
        <div style="font-size:2rem; font-weight:700; margin-top:0.5rem;">
            {{ number_format((float) $payment->amount, 2) }} SAR
        </div>
        <div style="color:#9ca3af; font-size:0.8rem; margin-top:0.5rem;">
            Payment #{{ $payment->id }}
        </div>
    </div>

    <form method="POST" action="{{ route('payment.testing.checkout.complete', $payment) }}" style="margin-bottom:0.75rem;">
        @csrf
        <input type="hidden" name="status" value="success">
        <button type="submit" style="width:100%; padding:0.9rem; background:#16a34a; color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:600; cursor:pointer;">
            Simulate Success
        </button>
    </form>

    <form method="POST" action="{{ route('payment.testing.checkout.complete', $payment) }}" style="margin-bottom:0.75rem;">
        @csrf
        <input type="hidden" name="status" value="failed">
        <button type="submit" style="width:100%; padding:0.9rem; background:#dc2626; color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:600; cursor:pointer;">
            Simulate Failure
        </button>
    </form>

    <form method="POST" action="{{ route('payment.testing.checkout.complete', $payment) }}">
        @csrf
        <input type="hidden" name="status" value="cancelled">
        <button type="submit" style="width:100%; padding:0.9rem; background:#fff; color:#374151; border:1px solid #d1d5db; border-radius:10px; font-size:1rem; font-weight:600; cursor:pointer;">
            Simulate Cancellation
        </button>
    </form>
</div>
</body>
</html>
