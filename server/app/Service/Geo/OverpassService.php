<?php

namespace App\Service\Geo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OverpassService
{
    private string $url = 'https://overpass-api.de/api/interpreter';


    public function nearestStreet(float $lat, float $lon)
    {
        $cacheKey = "nearest_street:" . round($lat, 5) . ":" . round($lon, 5);
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lon) {

            $query = <<<OVERPASS
            [out:json][timeout:10];
            way(around:200,{$lat},{$lon})[highway][name];
            out geom 1;
            OVERPASS;


            $response = Http::withHeaders([
                'User-Agent' => config('app.name'),
            ])
                ->post($this->url, [
                    'data' => $query,
                ]);


            if ($response->failed()) {
                throw new \Exception('Overpass request failed: ' . $response->body(), 500);
            }

            $elements = $response->json()['elements'] ?? [];

            return collect($elements)
                ->filter(fn($el) => !empty($el['tags']['name']))
                ->first()['tags']['name'] ?? null;
        });
    }
}
