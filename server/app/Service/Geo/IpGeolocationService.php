<?php

namespace App\Service\Geo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpGeolocationService
{
    public function locate(?string $ip): ?array
    {
        if (!$ip || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        $response = Http::get("http://ip-api.com/json/{$ip}", [
            'fields' => 'status,lat,lon,city,country',
        ]);

        if ($response->failed()) {
            Log::warning('IP geolocation request failed', ['ip' => $ip]);
            return null;
        }

        $data = $response->json();

        if (($data['status'] ?? null) !== 'success') {
            return null;
        }

        return [
            'lat' => $data['lat'],
            'lng' => $data['lon'],
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
        ];
    }
}
