<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Provider
 * @mixin User
 */
class GuaranteeRequestUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return match (get_class($this->resource)) {
            User::class => UserResource::make($this)->toArray($request),
            Provider::class => ProviderResource::make($this)->toArray($request),
            default => [],
        };
    }
}
