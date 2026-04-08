<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Payment\Order\AddProviderTransaction;
use App\Actions\Payment\Order\AddUserTransaction;
use App\Actions\Payment\Order\NotifyProviderForOrder;
use App\Actions\Payment\Order\NotifyUserForOrder;
use App\Actions\Payment\Order\ProcessOrder;
use App\Actions\Payment\Test\UpdatePaymentStatus;
use App\Enums\Payment\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\OrderOffer;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Pipeline;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use RuntimeException;

class GeneralController extends Controller
{
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
        return view('payment.mock', compact('payment'));
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

        return match ($payment->product_type) {
            OrderOffer::class => $this->offerRequestPayment($request, $payment),
            default => throw new RuntimeException('Unknown product type: '.$payment->product_type),
        };

    }

    public function offerRequestPayment(Request $request, Payment $payment): RedirectResponse
    {
        $payment = Pipeline::send($payment)
            ->withinTransaction()
            ->through([
                new UpdatePaymentStatus($request),
                ProcessOrder::class,
                AddProviderTransaction::class,
                AddUserTransaction::class,
                NotifyProviderForOrder::class,
                NotifyUserForOrder::class,
            ])
            ->thenReturn();

        return match ($payment->status) {
            PaymentStatusEnum::Accepted => redirect()->route('payment.test.success', ['payment' => $payment->id]),
            PaymentStatusEnum::Rejected, PaymentStatusEnum::Canceled->value => redirect()->route('payment.test.failed', ['payment' => $payment->id]),
            default => throw new RuntimeException('Unsupported payment status: '.$payment->status, 500),
        };
    }

    public function paymentTestSuccess()
    {
        return view('payment.success');
    }

    public function paymentTestFailed()
    {
        return view('payment.failed');
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

    public function help()
    {
        return inertia('Frontend/Help', []);
    }

    public function submitMessage(Request $request) {}
}
