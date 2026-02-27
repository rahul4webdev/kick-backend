<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AnalyticsController extends Controller
{
    private function analyticsUrl(): string
    {
        return 'http://127.0.0.1:3001';
    }

    private function analyticsKey(): string
    {
        return 'kick_analytics_s3cur3_k3y_2026';
    }

    private function fetchFromAnalytics(string $endpoint, array $params = []): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['x-api-key' => $this->analyticsKey()])
                ->get($this->analyticsUrl() . $endpoint, $params);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (\Exception $e) {
            // Analytics service may be down
        }
        return [];
    }

    public function analyticsOverview()
    {
        return view('analytics');
    }

    public function fetchAnalyticsOverview(Request $request)
    {
        $period = $request->period ?? '30d';
        $data = $this->fetchFromAnalytics('/api/metrics/overview', ['period' => $period]);
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function fetchAnalyticsEngagement(Request $request)
    {
        $period = $request->period ?? '30d';
        $data = $this->fetchFromAnalytics('/api/metrics/engagement', [
            'period' => $period,
            'granularity' => $request->granularity ?? 'daily',
        ]);
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function fetchAnalyticsTopPosts(Request $request)
    {
        $period = $request->period ?? '30d';
        $data = $this->fetchFromAnalytics('/api/metrics/posts', [
            'period' => $period,
            'sort' => $request->sort ?? 'engagement',
            'limit' => $request->limit ?? 20,
        ]);
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function fetchAnalyticsTopUsers(Request $request)
    {
        $period = $request->period ?? '30d';
        $data = $this->fetchFromAnalytics('/api/metrics/users', [
            'period' => $period,
            'limit' => $request->limit ?? 20,
        ]);
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function fetchAnalyticsDevices(Request $request)
    {
        $period = $request->period ?? '30d';
        $data = $this->fetchFromAnalytics('/api/metrics/devices', ['period' => $period]);
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function fetchAnalyticsLocations(Request $request)
    {
        $period = $request->period ?? '30d';
        $data = $this->fetchFromAnalytics('/api/metrics/locations', ['period' => $period]);
        return response()->json(['status' => true, 'data' => $data]);
    }
}
