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
     * Public settings dump — only rows with is_public = true.
     *
     * New settings default to private; expose them deliberately from the
     * Dashboard "Visible in public API" toggle.
     */
    public function settings(): JsonResponse
    {
        return $this->successResponse($this->settingService->publicBag());
    }
}
