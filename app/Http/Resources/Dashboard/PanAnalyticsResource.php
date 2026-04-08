<?php

namespace App\Http\Resources\Dashboard;

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
            'category' => $this->categorizeElement($this->name),
        ];
    }

    /**
     * Categorize element based on naming patterns
     */
    private function categorizeElement(string $name): string
    {
        // Check for page
        if (str_ends_with($name, '-page') || str_contains($name, 'page')) {
            return 'page';
        }

        // Check for button
        if (str_ends_with($name, '-btn') || str_ends_with($name, '-button') || str_contains($name, 'button')) {
            return 'button';
        }

        // Check for form/input elements
        if (str_contains($name, 'step') ||
            str_contains($name, 'form') ||
            str_contains($name, 'input') ||
            str_contains($name, 'checkbox') ||
            str_contains($name, 'field')) {
            return 'form';
        }

        return 'other';
    }
}
