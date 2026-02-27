<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class VastController extends Controller
{
    private const MAX_WRAPPER_DEPTH = 5;

    public function fetch(Request $request)
    {
        $tag = $request->query('tag', 'instream');
        $platform = $request->query('platform', 'android');

        $settings = DB::table('tbl_settings')->first();

        $vastUrl = $this->selectVastUrl($settings, $tag, $platform);

        if (empty($vastUrl)) {
            return $this->emptyVast();
        }

        $vastUrl = $this->injectMobileParams($vastUrl);

        $xml = $this->fetchVast($vastUrl, 0);

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'no-cache, no-store');
    }

    private function selectVastUrl($settings, string $tag, string $platform): ?string
    {
        $isIos = $platform === 'ios';

        switch ($tag) {
            case 'infeed':
                return $isIos
                    ? ($settings->vast_feed_ad_tag_ios ?? null)
                    : ($settings->vast_feed_ad_tag_android ?? null);
            case 'midroll':
                return $isIos
                    ? ($settings->ima_midroll_ad_tag_ios ?? null)
                    : ($settings->ima_midroll_ad_tag_android ?? null);
            case 'postroll':
                return $isIos
                    ? ($settings->ima_postroll_ad_tag_ios ?? null)
                    : ($settings->ima_postroll_ad_tag_android ?? null);
            default:
                return $isIos
                    ? ($settings->ima_ad_tag_ios ?? null)
                    : ($settings->ima_ad_tag_android ?? null);
        }
    }

    private function fetchVast(string $url, int $depth): string
    {
        if ($depth >= self::MAX_WRAPPER_DEPTH) {
            Log::warning('[VAST] Max wrapper depth exceeded');
            return '<VAST version="4.0"></VAST>';
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Linux; Android 11; Generic) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
                    'Accept' => 'application/xml, text/xml, */*',
                    'X-Requested-With' => 'com.kick.entertainment',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('[VAST] HTTP ' . $response->status() . ' for VAST URL at depth=' . $depth);
                return '<VAST version="4.0"></VAST>';
            }

            $body = $response->body();

            if (empty(trim($body))) {
                return '<VAST version="4.0"></VAST>';
            }

            $wrapperUrl = $this->extractWrapperUrl($body);
            if ($wrapperUrl) {
                Log::info('[VAST] Following wrapper depth=' . $depth . ' -> ' . substr($wrapperUrl, 0, 80));
                return $this->fetchVast($wrapperUrl, $depth + 1);
            }

            return $body;
        } catch (\Exception $e) {
            Log::error('[VAST] Fetch error at depth=' . $depth . ': ' . $e->getMessage());
            return '<VAST version="4.0"></VAST>';
        }
    }

    private function extractWrapperUrl(string $xml): ?string
    {
        try {
            libxml_use_internal_errors(true);
            $parsed = new SimpleXMLElement($xml);
            $ad = $parsed->Ad ?? null;
            if (!$ad) return null;

            $wrapper = $ad->Wrapper ?? null;
            if (!$wrapper) return null;

            $uri = $wrapper->VASTAdTagURI ?? null;
            if ($uri) {
                return trim((string) $uri);
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function injectMobileParams(string $url): string
    {
        try {
            $parts = parse_url($url);
            $query = [];
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query);
            }

            // Always set a fresh correlator (required, prevents request deduplication)
            $query['correlator'] = (int)(microtime(true) * 1000);

            // Add app identifier for better ad targeting if not already set
            if (empty($query['app_name'])) {
                $query['app_name'] = 'com.kick.entertainment';
            }

            // Keep env as-is from the stored URL.
            // Use env=vp for Google's standard/test campaigns.
            // Use env=inapp in admin settings for mobile in-app campaigns.

            $scheme = $parts['scheme'] ?? 'https';
            $host = $parts['host'] ?? '';
            $path = $parts['path'] ?? '';

            return $scheme . '://' . $host . $path . '?' . http_build_query($query);
        } catch (\Exception $e) {
            return $url;
        }
    }

    private function emptyVast()
    {
        return response('<VAST version="4.0"></VAST>', 200)
            ->header('Content-Type', 'application/xml');
    }
}
