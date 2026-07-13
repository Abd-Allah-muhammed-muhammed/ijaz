<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Payment\Actions\HandleCallbackAction;
use Modules\Payment\Models\Payment;

class TestingCheckoutController extends Controller
{
    public function __construct(
        private readonly HandleCallbackAction $callbackAction,
    ) {}

    /**
     * Show the fake hosted checkout page (UX only).
     */
    public function show(Payment $payment): View
    {
        abort_if($payment->driver !== 'testing', 404);
        abort_if(app()->isProduction(), 404);

        return view('payment::testing-checkout', compact('payment'));
    }

    /**
     * Handle the tester's button choice. This plays the role of the
     * gateway's WEBHOOK — it is the source of truth for status + events,
     * same as HandleRajhiWebhookAction does for Rajhi.
     */
    public function complete(Request $request, Payment $payment): RedirectResponse
    {
        abort_if($payment->driver !== 'testing', 404);
        abort_if(app()->isProduction(), 404);

        $status = $request->input('status', 'success');

        $this->callbackAction->handle($payment, ['status' => $status]);

        return redirect()->route('payment.redirect', [
            'driver' => 'testing',
            'payment' => $payment,
        ]);
    }
}
