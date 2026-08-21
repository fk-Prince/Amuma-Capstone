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

        if ($request->hasFile('branch_document')) {
            $data['branch_document'] = $request->file('branch_document');
        }
        if ($request->hasFile('agency_document')) {
            $data['agency_document'] = $request->file('agency_document');
        }
        if ($request->hasFile('agency_id_back')) {
            $data['agency_id_back'] = $request->file('agency_id_back');
        }
        if ($request->hasFile('agency_id_front')) {
            $data['agency_id_front'] = $request->file('agency_id_front');
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
    public function index(Request $request)
    {
        return $this->subscriptionService->subscriptionList($request->all());
    }

    public function action(Request $request)
    {
        if ($request->action === 'overview' || $request->action === 'overview_subscription') {
            return $this->subscriptionService->overview($request->all());
        } else if ($request->action === 'approve') {
            return $this->subscriptionService->approve($request->all());
        } else if ($request->action === 'reject') {
            return $this->subscriptionService->reject($request->all());
        }
    }
}
