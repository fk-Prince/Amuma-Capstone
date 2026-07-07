<?php

namespace App\Service\Utils;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NominatimService
{
    public function __construct() {}

    public function reverse(float $lat, float $lon, int $zoom = 18)
    {
        $cacheKey = "reverse_geo:" . round($lat, 5) . ":" . round($lon, 5) . ":z{$zoom}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lon, $zoom) {
            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders([
                'User-Agent' => config('app.name') . ' prince.sestoso@gmail.com',
                'Accept-Language' => 'en',
            ])->get('https://nominatim.openstreetmap.org/reverse', [
                'format'         => 'json',
                'lat'            => $lat,
                'lon'            => $lon,
                'zoom'           => $zoom,
                'addressdetails' => 1,
            ]);

            if ($response->failed()) {
                throw new \Exception('Nominatim request failed: ' . $response->body(), 500);
            }

            return $response->json();
        });
    }

    public function nearestStreet(float $lat, float $lon)
    {
        $cacheKey = "nearest_street:" . round($lat, 5) . ":" . round($lon, 5);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lon) {
            $query = <<<OVERPASS
        [out:json][timeout:10];
        way(around:200,{$lat},{$lon})[highway][name];
        out geom 1;
        OVERPASS;

            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders([
                'User-Agent' => 'YourAppName/1.0 (contact@email.com)',
            ])->post('https://overpass-api.de/api/interpreter', [
                'data' => $query,
            ]);

            if ($response->failed()) {
                throw new \Exception('Overpass request failed: ' . $response->body(), 500);
            }

            $elements = $response->json()['elements'] ?? [];

            $streetName = collect($elements)
                ->filter(fn($el) => !empty($el['tags']['name']))
                ->first()['tags']['name'] ?? null;

            return response()->json([
                'success' => true,
                'street' => $streetName,
            ]);
        });
    }

    public function geocodeAddress(array $payload)
    {
        $address = $payload['address'] ?? collect([
            $payload['street'] ?? null,
            $payload['city'] ?? null,
            $payload['province'] ?? null,
            $payload['country'] ?? null,
        ])->filter()->implode(', ');

        if (!$address) {
            return null;
        }

        $response = Http::withOptions([
            'verify' => false,
        ])->withHeaders([
            'User-Agent' => 'YourAppName/1.0 (contact@email.com)',
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ]);


        $data = $response->json();
        if (!empty($data) && isset($data[0])) {
            return [
                'lat' => $data[0]['lat'] ?? null,
                'lng' => $data[0]['lon'] ?? null,
            ];
        }

        $fallback = collect([
            $payload['city'] ?? null,
            $payload['province'] ?? null,
            $payload['country'] ?? null,
        ])->filter()->implode(', ');

        if (!$fallback || $fallback === $address) {
            return null;
        }


        $response2 = $response =   Http::withOptions([
            'verify' => false,
        ])->withHeaders([
            'User-Agent' => 'YourAppName/1.0 (contact@email.com)'
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $fallback,
            'format' => 'json',
            'limit' => 1,
        ]);


        $data2 = $response2->json();

        if (!empty($data2) && isset($data2[0])) {
            return [
                'lat' => $data2[0]['lat'] ?? null,
                'lng' => $data2[0]['lon'] ?? null,
            ];
        }

        return null;
    }

    public function getCityByCords(float $lat, float $long)
    {
        $response =   Http::withOptions([
            'verify' => false,
        ])->withHeaders([
            'User-Agent' => 'YourAppName/1.0 (contact@email.com)',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat'    => $lat,
            'lon'    => $long,
            'format' => 'json',
        ]);


        if ($response->failed()) {
            Log::warning('Nominatim reverse geocode failed', [
                'lat'    => $lat,
                'long'   => $long,
                'status' => $response->status(),
            ]);
            return null;
        }

        $data = $response->json();

        return $data['address']['city'] ?? null;
    }
}
