<?php

namespace Modules\Guarantor\Http\Resources\Api;

use App\Http\Resources\Api\V1\ProviderResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuarantorParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return match ($this->resource::class) {
            User::class => UserResource::make($this->resource)->toArray($request),
            Provider::class => ProviderResource::make($this->resource)->toArray($request),
            default => [],
        };
    }
}
