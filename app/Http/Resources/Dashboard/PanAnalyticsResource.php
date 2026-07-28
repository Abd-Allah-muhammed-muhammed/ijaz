<?php

namespace App\Http\Resources\Dashboard;

use App\Actions\PanAnalytics\CategorizePanElementAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PanAnalyticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $impressions = $this->impressions ?: 1; // Avoid division by zero
        $engagementRate = round(($this->hovers / $impressions) * 100, 2);
        $clickRate = round(($this->clicks / $impressions) * 100, 2);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'impressions' => (int) $this->impressions,
            'hovers' => (int) $this->hovers,
            'clicks' => (int) $this->clicks,
            'engagement_rate' => $engagementRate,
            'click_rate' => $clickRate,
            'category' => app(CategorizePanElementAction::class)->handle($this->name),
        ];
    }
}
