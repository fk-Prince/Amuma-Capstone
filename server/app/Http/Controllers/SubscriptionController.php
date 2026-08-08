<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionRequest;
use App\Service\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    private SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function newSubscription(SubscriptionRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('branch_image')) {
            $data['branch_image'] = $request->file('branch_image');
        }
        if ($request->hasFile('agency_image')) {
            $data['agency_image'] = $request->file('branch_image');
        }
        return $this->subscriptionService->makeSubscription($data, $request->user());
    }

    public function validateSubscription(SubscriptionRequest $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Validation passed',
            'data' => $request->validated(),
        ]);
    }

    public function subscriptionWebhook(Request $request)
    {
        return $this->subscriptionService->subscriptionWebhook($request);
    }


    public function retrieveSubscriptionDetail(Request $request)
    {
        return $this->subscriptionService->createSubscription($request->user(), $request->all());
    }
}
