<?php

namespace App\Http\Controllers;

use App\Service\Geo\GeoNamesService;
use App\Service\Geo\NominatimService;
use App\Service\Geo\OverpassService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NominatimController extends Controller
{
    private NominatimService $nominatimService;
    private OverpassService $overpassService;

    public function __construct(private GeoNamesService $geoNames, NominatimService $nominatimService, OverpassService $overpassService)
    {
        $this->nominatimService = $nominatimService;
        $this->overpassService = $overpassService;
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
