<?php

namespace App\Actions\Dashboard;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AssembleDashboardChartDataAction
{
    /**
     * Zero-fill registration and revenue series across a continuous date period.
     *
     * @param  Collection<string, int>  $userRegistrations
     * @param  Collection<string, int>  $providerRegistrations
     * @param  Collection<string, float|int|string>  $revenueDaily
     * @return array{dates: list<string>, userRegistrations: list<int>, providerRegistrations: list<int>, revenue: list<float>}
     */
    public function handle(
        CarbonInterface $from,
        CarbonInterface $to,
        Collection $userRegistrations,
        Collection $providerRegistrations,
        Collection $revenueDaily,
    ): array {
        $chartData = [
            'dates' => [],
            'userRegistrations' => [],
            'providerRegistrations' => [],
            'revenue' => [],
        ];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $formattedDate = $date->format('Y-m-d');
            $chartData['dates'][] = $formattedDate;
            $chartData['userRegistrations'][] = $userRegistrations->get($formattedDate, 0);
            $chartData['providerRegistrations'][] = $providerRegistrations->get($formattedDate, 0);
            $chartData['revenue'][] = (float) $revenueDaily->get($formattedDate, 0);
        }

        return $chartData;
    }
}
