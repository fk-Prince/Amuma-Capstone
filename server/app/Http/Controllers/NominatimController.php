<?php

namespace App\Http\Controllers;

use  App\Service\Utils\NominatimService;
use Illuminate\Http\Request;

class NominatimController extends Controller
{
    private NominatimService $nominatimService;

    public function __construct(NominatimService $nominatimService)
    {
        $this->nominatimService = $nominatimService;
    }

    public function geocode(Request $request)
    {
        $q = $request->query('q');

        if (!$q) {
            return response()->json(['lat' => null, 'lng' => null]);
        }

        $result = $this->nominatimService->geocodeAddress(['city' => $q]);

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
            $data = $this->nominatimService->nearestStreet(
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
