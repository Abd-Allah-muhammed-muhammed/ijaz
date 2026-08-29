<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Locale\SwitchLocaleAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Modules\Cms\Models\Page;
use Modules\Cms\Services\PageService;

class GeneralController extends Controller
{
    public function __construct(
        private readonly SwitchLocaleAction $switchLocaleAction,
        private readonly PageService $pageService,
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

    /**
     * Reusable CMS page by slug (e.g. /pages/terms). Content is self-styled HTML from admin.
     */
    public function cmsPage(Page $page): Response
    {
        $page = $this->pageService->showForCatalog($page);

        return inertia('Frontend/CmsPage', [
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'content' => $page->content,
            ],
        ]);
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
