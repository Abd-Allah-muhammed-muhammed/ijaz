<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Payment\Actions\HandleCallbackAction;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use RuntimeException;

class GeneralController extends Controller
{
    public function __construct(
        private readonly HandleCallbackAction $handleCallbackAction,
    ) {}

    public function index()
    {
        return inertia('Frontend/LandingPage', []);
    }

    public function aboutUs()
    {
        return inertia('Frontend/AboutUs', []);
    }

    public function ourServices()
    {
        return inertia('Frontend/OurServices', []);
    }

    public function ourService()
    {
        return inertia('Frontend/Service', []);
    }

    public function customerReviews()
    {
        return inertia('Frontend/CustomerReviews', []);
    }

    public function privacyAndPolicies()
    {
        return inertia('Frontend/PrivacyAndPolicies', []);
    }

    public function privacyPolicy()
    {
        return inertia('Frontend/PrivacyPolicy', []);
    }

    public function serviceProviderAuthorizationTermsAndConditions()
    {
        return inertia('Frontend/ServiceProviderAuthorizationTermsAndConditions', []);
    }

    public function howToUseAgency()
    {
        return inertia('Frontend/HowToUseAgency', []);
    }

    public function realEstateMarketplaceTermsOfUse()
    {
        return inertia('Frontend/RealEstateMarketplaceTermsOfUse', []);
    }

    public function paymentTest(Payment $payment)
    {
        return view('payment::payment.mock', compact('payment'));
    }

    public function paymentTestSubmit(Request $request, Payment $payment)
    {
        $status = $request->input('status');

        return redirect()->route('payment.test.callback', [
            'payment' => $payment->id,
            'payment_id' => uniqid('test-transaction-id-', true),
            'status' => $status,
        ]);

    }

    public function paymentTestCallback(Request $request, Payment $payment): RedirectResponse
    {
        $this->handleCallbackAction->handle($payment, $request->all());

        $payment->refresh();

        return match ($payment->status) {
            PaymentStatusEnum::Accepted => redirect()->route('payment.test.success', ['payment' => $payment->id]),
            PaymentStatusEnum::Rejected, PaymentStatusEnum::Canceled => redirect()->route('payment.test.failed', ['payment' => $payment->id]),
            default => throw new RuntimeException('Unsupported payment status: '.$payment->status->value, 500),
        };
    }

    public function paymentTestSuccess()
    {
        return view('payment::success');
    }

    public function paymentTestFailed()
    {
        return view('payment::failed');
    }

    // Example in a controller
    public function switchLang($locale)
    {
        if (in_array($locale, array_keys(config('laravellocalization.supportedLocales')))) {
            LaravelLocalization::setLocale($locale);

            return redirect()->to(LaravelLocalization::getLocalizedURL($locale, url()->previous()));
        }

        return redirect()->back();
    }
}
