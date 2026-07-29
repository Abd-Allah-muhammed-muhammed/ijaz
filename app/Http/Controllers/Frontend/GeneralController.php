<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Locale\SwitchLocaleAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class GeneralController extends Controller
{
    public function __construct(
        private readonly SwitchLocaleAction $switchLocaleAction,
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

    public function switchLang($locale): RedirectResponse
    {
        $url = $this->switchLocaleAction->handle((string) $locale);

        if ($url === null) {
            return redirect()->back();
        }

        return redirect()->to($url);
    }
}
