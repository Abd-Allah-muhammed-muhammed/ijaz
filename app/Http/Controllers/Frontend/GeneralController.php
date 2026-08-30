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

    public function privacyAndPolicies(): Response
    {
        return $this->renderCmsPageBySlug('policies-and-privacy');
    }

    public function privacyPolicy(): Response
    {
        return $this->renderCmsPageBySlug('privacy');
    }

    public function serviceProviderAuthorizationTermsAndConditions(): Response
    {
        return $this->renderCmsPageBySlug('service-provider-authorization');
    }

    public function howToUseAgency(): Response
    {
        return $this->renderCmsPageBySlug('how-to-use-agency');
    }

    public function realEstateMarketplaceTermsOfUse(): Response
    {
        return $this->renderCmsPageBySlug('real-estate-marketplace-terms');
    }

    /**
     * Reusable CMS page by slug (e.g. /pages/terms). Content is render-time wrapped HTML.
     */
    public function cmsPage(Page $page): Response
    {
        return $this->renderCmsPagePayload($this->pageService->catalogPayload($page));
    }

    public function switchLang($locale): RedirectResponse
    {
        $url = $this->switchLocaleAction->handle((string) $locale);

        if ($url === null) {
            return redirect()->back();
        }

        return redirect()->to($url);
    }

    private function renderCmsPageBySlug(string $slug): Response
    {
        return $this->renderCmsPagePayload($this->pageService->catalogPayloadBySlug($slug));
    }

    /**
     * @param  array{id: int, slug: string, title: string, content: string}  $page
     */
    private function renderCmsPagePayload(array $page): Response
    {
        return inertia('Frontend/CmsPage', [
            'page' => $page,
        ]);
    }
}
