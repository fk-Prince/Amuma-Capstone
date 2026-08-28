<?php

namespace App\Service\Geo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NominatimService
{
    private string $url = 'https://nominatim.openstreetmap.org';

    private function client()
    {
        return Http::withHeaders([
            'User-Agent' => config('app.name') . ' prince.sestoso@gmail.com',
            'Accept-Language' => 'en',
        ])->withOptions([
            'verify' => false,
        ]);
    }

    public function reverse(float $lat, float $lon, int $zoom = 18)
    {
        $cacheKey = "reverse_geo:" . round($lat, 5) . ":" . round($lon, 5) . ":z{$zoom}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lon, $zoom) {

            $response = $this->client()->get("{$this->url}/reverse", [
                'format' => 'json',
                'lat' => $lat,
                'lon' => $lon,
                'zoom' => $zoom,
                'addressdetails' => 1,
            ]);

            if ($response->failed()) {
                throw new \Exception(
                    'Nominatim request failed: ' . $response->body(),
                    500
                );
            }

            return $response->json();
        });
    }


    public function geocodeAddress(array $payload)
    {
        $address = $payload['address'] ?? collect([
            $payload['street'] ?? null,
            $payload['city'] ?? null,
            $payload['province'] ?? null,
            $payload['country'] ?? null,
        ])
            ->filter()
            ->implode(', ');


        if (!$address) {
            return null;
        }


        $response = $this->client()->get("{$this->url}/search", [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ]);


        $data = $response->json();

        if (!empty($data[0])) {
            return [
                'lat' => $data[0]['lat'],
                'lng' => $data[0]['lon'],
            ];
        }


        return null;
    }


    public function getCityByCoords(float $lat, float $lon)
    {
        $cacheKey = "city_by_coords:" . round($lat, 5) . ":" . round($lon, 5);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lon) {
            $response = $this->client()->get("{$this->url}/reverse", [
                'lat' => $lat,
                'lon' => $lon,
                'format' => 'json',
            ]);

            if ($response->failed()) {
                Log::warning('Nominatim reverse failed', [
                    'lat' => $lat,
                    'lon' => $lon,
                ]);

                return null;
            }

            $data = $response->json();

            return $data['address']['city']
                ?? $data['address']['town']
                ?? $data['address']['village']
                ?? null;
        });
    }
}
