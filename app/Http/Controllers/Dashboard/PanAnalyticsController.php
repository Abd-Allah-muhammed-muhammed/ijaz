<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\PanAnalyticsResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PanAnalyticsController extends Controller implements HasMiddleware
{
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
        // Get search query
        $category = $request->input('category');

        // Query pan_analytics table
        $query = DB::table('pan_analytics')->select('*');

        // Get all analytics data for summary and categorization
        $allAnalytics = DB::table('pan_analytics')->get();

        // Calculate summary statistics
        $summary = [
            'total_impressions' => $allAnalytics->sum('impressions'),
            'total_hovers' => $allAnalytics->sum('hovers'),
            'total_clicks' => $allAnalytics->sum('clicks'),
        ];

        // Calculate overall engagement rate
        $summary['overall_engagement_rate'] = $summary['total_impressions'] > 0
          ? round((($summary['total_hovers'] + $summary['total_clicks']) / $summary['total_impressions']) * 100, 2)
          : 0;

        // Categorize all elements and count by category
        $categorizedData = $allAnalytics->map(function ($item) {
            return (object) array_merge((array) $item, [
                'category' => $this->categorizeElement($item->name),
            ]);
        });

        $categories = $categorizedData->groupBy('category')->map->count()->toArray();

        // Get top 10 elements by clicks for charts
        $topElements = PanAnalyticsResource::collection(
            $allAnalytics->sortByDesc('clicks')->take(10)->values()
        );

        // Apply category filter for table data
        if ($category && $category !== 'all') {
            $query->where(function ($q) use ($category) {
                if ($category === 'page') {
                    $q->where('name', 'like', '%-page')
                        ->orWhere('name', 'like', '%page%');
                } elseif ($category === 'button') {
                    $q->where('name', 'like', '%-btn')
                        ->orWhere('name', 'like', '%-button')
                        ->orWhere('name', 'like', '%button%');
                } elseif ($category === 'form') {
                    $q->where('name', 'like', '%step%')
                        ->orWhere('name', 'like', '%form%')
                        ->orWhere('name', 'like', '%input%')
                        ->orWhere('name', 'like', '%checkbox%')
                        ->orWhere('name', 'like', '%field%');
                }
            });
        }

        // Paginate results
        $analytics = $query->orderByDesc('clicks')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        // Transform paginated data through resource
        $analytics->getCollection()->transform(function ($item) {
            return (new PanAnalyticsResource($item))->resolve();
        });

        // Prepare funnel data
        $funnelData = [
            'impressions' => $summary['total_impressions'],
            'hovers' => $summary['total_hovers'],
            'clicks' => $summary['total_clicks'],
        ];

        return inertia('Dashboard/PanAnalytics/Index', [
            'analytics' => fn () => $analytics,
            'summary' => fn () => $summary,
            'categories' => fn () => $categories,
            'topElements' => fn () => $topElements,
            'funnelData' => fn () => $funnelData,
            'params' => fn () => $request->all() ?: [],
        ]);
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

    public function export(): StreamedResponse
    {
        $analytics = DB::table('pan_analytics')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pan-analytics-'.date('Y-m-d-His').'.csv"',
        ];

        return response()->stream(function () use ($analytics) {
            $handle = fopen('php://output', 'w');

            // Add CSV headers
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

            // Add data rows
            foreach ($analytics as $item) {
                $impressions = $item->impressions ?: 1;
                $engagementRate = round(($item->hovers / $impressions) * 100, 2);
                $clickRate = round(($item->clicks / $impressions) * 100, 2);

                fputcsv($handle, [
                    $item->id,
                    $item->name,
                    $this->categorizeElement($item->name),
                    $item->impressions,
                    $item->hovers,
                    $item->clicks,
                    $engagementRate,
                    $clickRate,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function clear(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            DB::table('pan_analytics')->truncate();
            DB::commit();

            return to_route('dashboard.pan-analytics.index')->with('success', trans('Pan analytics cleared successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return back()->with('error', trans('something went wrong'));
        }
    }
}
