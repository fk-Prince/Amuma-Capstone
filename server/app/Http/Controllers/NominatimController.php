<?php

namespace App\Http\Controllers;

use App\Exceptions\ExternalServiceException;
use App\Service\Geo\GeoNamesService;
use App\Service\Geo\IpGeolocationService;
use App\Service\Geo\NominatimService;
use App\Service\Geo\OverpassService;
use Illuminate\Http\Request;

class NominatimController extends Controller
{

    public function __construct(
        private GeoNamesService $geoNames,
        private NominatimService $nominatimService,
        private OverpassService $overpassService,
        private IpGeolocationService $ipGeolocationService,
    ) {}

    public function ipLocate(Request $request)
    {
        $ip = $request->query('ip') ?: $request->ip();

        $data = $this->ipGeolocationService->locate($ip);

        return response()->json([
            'success' => (bool) $data,
            'data' => $data,
        ]);
    }

    public function searchLocation(Request $request)
    {
        return $this->geoNames->search($request->all());
    }
    public function geocode(Request $request)
    {
        $q = $request->query('q');
        if (!$q || !is_string($q)) {
            return response()->json([
                'lat' => null,
                'lng' => null,
            ]);
        }
        $result = $this->nominatimService->geocodeAddress([
            'address' => trim($q),
        ]);
        return response()->json([
            'lat' => $result['lat'] ?? null,
            'lng' => $result['lng'] ?? null,
        ]);
    }

    public function reverse(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        try {
            $data = $this->nominatimService->reverse(
                $request->lat,
                $request->lon
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => ExternalServiceException::THIRD_PARTY_ERROR,
            ], 502);
        }
    }

    public function nearest(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        try {
            $data = $this->overpassService->nearestStreet(
                $request->lat,
                $request->lon
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => ExternalServiceException::THIRD_PARTY_ERROR,
            ], 502);
        }
    }
}
