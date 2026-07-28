<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\PanAnalytics\PanAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PanAnalyticsController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PanAnalyticsService $panAnalyticsService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show panAnalytics', only: ['index']),
            new Middleware('permission:export panAnalytics', only: ['export']),
            new Middleware('permission:delete panAnalytics', only: ['clear']),
        ];
    }

    public function index(Request $request)
    {
        $payload = $this->panAnalyticsService->indexPayload(
            $request->input('category'),
            $request->integer('per_page', 10),
        );

        return inertia('Dashboard/PanAnalytics/Index', [
            'analytics' => fn () => $payload['analytics'],
            'summary' => fn () => $payload['summary'],
            'categories' => fn () => $payload['categories'],
            'topElements' => fn () => $payload['topElements'],
            'funnelData' => fn () => $payload['funnelData'],
            'params' => fn () => $request->all() ?: [],
        ]);
    }

    public function export(): StreamedResponse
    {
        $rows = $this->panAnalyticsService->exportRows();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pan-analytics-'.date('Y-m-d-His').'.csv"',
        ];

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Element Name',
                'Category',
                'Impressions',
                'Hovers',
                'Clicks',
                'Engagement Rate (%)',
                'Click Rate (%)',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'],
                    $row['name'],
                    $row['category'],
                    $row['impressions'],
                    $row['hovers'],
                    $row['clicks'],
                    $row['engagement_rate'],
                    $row['click_rate'],
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function clear(): RedirectResponse
    {
        try {
            $this->panAnalyticsService->clear();

            return to_route('dashboard.pan-analytics.index')->with('success', trans('Pan analytics cleared successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', trans('something went wrong'));
        }
    }
}
