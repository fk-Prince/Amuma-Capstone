<?php

namespace App\Service\Geo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoNamesService
{

    // public function search(Request $request)
    // {
    //     $search = trim($request->input('search', ''));

    //     if (strlen($search) < 2) {
    //         return response()->json([
    //             'data' => [],
    //         ]);
    //     }

    //     $key = 'geonames:search:' . md5(strtolower($search));

    //     $locations = Cache::remember(
    //         $key,
    //         now()->addHours(24),
    //         function () use ($search) {
    //             $response = Http::withOptions([
    //                 'verify' => false,
    //             ])->get('https://secure.geonames.org/searchJSON', [
    //                 'q' => $search,
    //                 'maxRows' => 10,
    //                 'username' => config('services.geonames.username'),
    //                 'featureClass' => 'P',
    //                 'style' => 'FULL',
    //             ]);

    //             if (!$response->successful()) {
    //                 return [];
    //             }

    //             return collect($response->json('geonames', []))
    //                 ->map(fn($location) => [
    //                     'id' => $location['geonameId'],
    //                     'name' => $location['name'],
    //                     'city' => $location['name'] ?? null,
    //                     'province' => $location['adminName1'] ?? null,
    //                     'country' => $location['countryName'] ?? null,
    //                     'country_code' => $location['countryCode'] ?? null,
    //                     'latitude' => $location['lat'] ?? null,
    //                     'longitude' => $location['lng'] ?? null,
    //                 ])
    //                 ->values()
    //                 ->all();
    //         }
    //     );

    //     return response()->json([
    //         'data' => $locations,
    //     ]);
    // }
    public function search(array $request)
    {
        $search = trim($request['search'] ?? '');

        $page = max(
            1,
            (int) ($request['page'] ?? 1)
        );

        $perPage = min(
            10,
            max(1, (int) ($request['per_page'] ?? 10))
        );

        if (strlen($search) < 2) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ]);
        }

        $cacheKey = 'geonames:search:' . md5(
            strtolower($search) . ":page:{$page}:per_page:{$perPage}"
        );

        $result = Cache::remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($search, $page, $perPage) {
                $startRow = (($page - 1) * $perPage) + 1;

                $response = Http::withOptions([
                    'verify' => false,
                ])->get(
                    'https://secure.geonames.org/searchJSON',
                    [
                        'q' => $search,
                        'maxRows' => 10,
                        'startRow' => $startRow,
                        'username' => config('services.geonames.username'),
                        'featureClass' => 'S',
                        'country' => 'PH',
                        'style' => 'FULL',
                    ]
                );

                if (!$response->successful()) {
                    return [
                        'data' => [],
                        'total' => 0,
                    ];
                }

                $json = $response->json();

                $locations = collect(
                    $json['geonames'] ?? []
                )
                    ->map(function ($location) {
                        return [
                            'id' => $location['geonameId'] ?? null,
                            'name' => $location['name'] ?? null,
                            'street' => $location['street'] ?? null,
                            'province' => $location['adminName1'] ?? null,
                            'country' => $location['countryName'] ?? null,
                            'country_code' => $location['countryCode'] ?? null,
                            'latitude' => $location['lat'] ?? null,
                            'longitude' => $location['lng'] ?? null,
                        ];
                    })
                    ->filter(fn($location) => $location['id'])
                    ->values()
                    ->all();

                return [
                    'data' => $locations,
                    'total' => (int) ($json['totalResultsCount'] ?? 0),
                ];
            }
        );

        $total = $result['total'];

        $lastPage = $total > 0
            ? (int) ceil($total / $perPage)
            : 1;

        return response()->json([
            'data' => $result['data'],
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }
    // public function search(array $request)
    // {
    //     $search = trim($request['search'] ?? '');
    //     $page = max((int) ($request['page'] ?? 1), 1);
    //     $perPage = min((int) ($request['per_page'] ?? 10), 10);

    //     if (strlen($search) < 2) {
    //         return response()->json([
    //             'data' => [],
    //             'current_page' => $page,
    //             'last_page' => 1,
    //             'per_page' => $perPage,
    //             'total' => 0,
    //         ]);
    //     }

    //     $startRow = ($page - 1) * $perPage;

    //     $cacheKey = 'geonames:search:' . md5(
    //         strtolower($search) . ":{$page}:{$perPage}"
    //     );

    //     $locations = Cache::remember(
    //         $cacheKey,
    //         now()->addHours(24),
    //         function () use ($search, $startRow, $perPage) {
    //             $response = Http::withOptions([
    //                 'verify' => false,
    //             ])->get(
    //                 'https://secure.geonames.org/searchJSON',
    //                 [
    //                     'q' => $search,
    //                     'maxRows' => $perPage,
    //                     'startRow' => $startRow,
    //                     'username' => config('services.geonames.username'),
    //                     'country' => 'PH',
    //                     'style' => 'FULL',
    //                 ]
    //             );

    //             if (!$response->successful()) {
    //                 return [
    //                     'data' => [],
    //                     'total' => 0,
    //                 ];
    //             }

    //             $json = $response->json();

    //             $locations = collect($json['geonames'] ?? [])
    //                 ->map(function ($location) {
    //                     return [
    //                         'id' => $location['geonameId'] ?? null,
    //                         'name' => $location['name'] ?? null,
    //                         'street' => $location['street'] ?? null,
    //                         'province' => $location['adminName1'] ?? null,
    //                         'district' => $location['adminName2'] ?? null,
    //                         'city' => $location['adminName3'] ?? null,
    //                         'country' => $location['countryName'] ?? null,
    //                         'country_code' => $location['countryCode'] ?? null,
    //                         'latitude' => $location['lat'] ?? null,
    //                         'longitude' => $location['lng'] ?? null,
    //                         'feature_class' => $location['fcl'] ?? null,
    //                         'feature_code' => $location['fcode'] ?? null,
    //                     ];
    //                 })
    //                 ->filter(fn($location) => $location['id'])
    //                 ->values()
    //                 ->all();

    //             return [
    //                 'data' => $locations,
    //                 'total' => (int) ($json['totalResultsCount'] ?? count($locations)),
    //             ];
    //         }
    //     );

    //     $total = $locations['total'];

    //     return response()->json([
    //         'data' => $locations['data'],
    //         'current_page' => $page,
    //         'last_page' => max((int) ceil($total / $perPage), 1),
    //         'per_page' => $perPage,
    //         'total' => $total,
    //     ]);
    // }

}
