<?php

namespace Modules\Guarantor\Http\Resources\Api;

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
        return [
            'id' => $this->getKey(),
            'name' => $this->name ?? trim(($this->f_name ?? '').' '.($this->l_name ?? '')),
            'type' => $this->when(
                true,
                fn () => $this->resource instanceof User ? 'user' : 'provider'
            ),
            'image' => $this->image_url ?? null,
            'phone' => $this->phone,
        ];
    }
}
