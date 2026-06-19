<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgencyRequest;
use App\Service\AgencyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgencyController extends Controller
{
    private AgencyService $agencyService;

    public function __construct(AgencyService $agencyService)
    {
        $this->agencyService = $agencyService;
    }

    public function index(Request $request)
    {
        $owned = $request->boolean('owned');
        $payload = [
            'per_page' => $request->per_page,
            'owned' => $owned,
        ];
        return $this->agencyService->listAgency($payload);
    }

    public function validate(AgencyRequest $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Validation passed',
            'data' => $request->validated(),
        ]);
    }
}
