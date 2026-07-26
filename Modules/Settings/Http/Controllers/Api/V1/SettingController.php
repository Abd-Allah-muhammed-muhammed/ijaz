<?php

namespace Modules\Settings\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Settings\Services\SettingService;

#[Group('Catalog')]
class SettingController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    /**
     * @unauthenticated
     *
     * Public settings dump — allowlisted only.
     *
     * Historically this returned app('settings')->toArray() (every key). That is
     * unsafe once admin-only keys land in the same table. SettingService::publicBag()
     * filters to config('settings.public_keys'), which currently mirrors the full
     * historical seed set (non-breaking). New sensitive keys stay private until
     * deliberately added to that allowlist.
     */
    public function settings(): JsonResponse
    {
        return $this->successResponse($this->settingService->publicBag());
    }
}
