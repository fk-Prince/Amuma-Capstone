<?php

namespace App\Service\Geo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PhotonService
{
    private string $url = 'https://photon.komoot.io/api';

    private const PH_BBOX = '116.9,4.5,126.7,21.2';

    public function suggest(string $query, ?float $lat = null, ?float $lng = null, int $limit = 5): array
    {
        $query = trim($query);
        $bias = $lat !== null && $lng !== null ? round($lat, 2) . ':' . round($lng, 2) : 'none';
        $cacheKey = 'photon_suggest:' . md5(mb_strtolower($query) . "|{$bias}|{$limit}");

        return Cache::remember($cacheKey, now()->addDay(), function () use ($query, $lat, $lng, $limit) {
            $params = [
                'q' => $query,
                'limit' => $limit * 2,
                'lang' => 'en',
                'bbox' => self::PH_BBOX,
            ];

            if ($lat !== null && $lng !== null) {
                $params['lat'] = $lat;
                $params['lon'] = $lng;
            }

            $response = Http::withHeaders([
                'User-Agent' => config('app.name'),
            ])
                ->withOptions(['verify' => false])
                ->timeout(8)
                ->get($this->url, $params);

            if ($response->failed()) {
                throw new \Exception('Photon request failed: ' . $response->body(), 500);
            }

            return collect($response->json('features') ?? [])
                ->filter(fn($feature) => strtoupper($feature['properties']['countrycode'] ?? '') === 'PH')
                ->map(fn($feature) => $this->toSuggestion($feature))
                ->filter(fn($suggestion) => $suggestion['name'] !== '')
                ->unique(fn($suggestion) => $suggestion['name'] . '|' . $suggestion['detail'])
                ->take($limit)
                ->values()
                ->all();
        });
    }

    private function toSuggestion(array $feature): array
    {
        $props = $feature['properties'] ?? [];
        [$lng, $lat] = $feature['geometry']['coordinates'] ?? [0, 0];

        $street = trim(implode(' ', array_filter([
            $props['housenumber'] ?? null,
            $props['street'] ?? null,
        ])));
        $name = (string) ($props['name'] ?? $street);

        $detail = collect([
            $name !== $street ? $street : null,
            $props['district'] ?? $props['locality'] ?? null,
            $props['city'] ?? $props['county'] ?? null,
            $props['state'] ?? null,
        ])
            ->filter()
            ->reject(fn($part) => $part === $name)
            ->unique()
            ->implode(', ');

        return [
            'name' => $name,
            'detail' => $detail,
            'type' => $props['type'] ?? 'other',
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }
}
