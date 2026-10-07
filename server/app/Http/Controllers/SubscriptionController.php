<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\Subscription\BranchResubmitPurchaseRequest;
use App\Http\Requests\Subscription\BranchResubmitRequest;
use App\Http\Requests\Subscription\SubscriptionRequest;
use App\Http\Requests\Subscription\SubscriptionUniqueRequest;
use App\Service\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{

    public function __construct(private SubscriptionService $subscriptionService) {}

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

    public function newBranchFromCapacity(SubscriptionRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid, true);
        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            ModuleEnum::ManageSubscription,
            PermissionAction::Create
        );

        $data = $request->validated();

        $data['agency_id'] = $branch->agency_id;

        if ($request->hasFile('branch_image')) {
            $data['branch_image'] = $request->file('branch_image');
        }

        if ($request->hasFile('branch_document')) {
            $data['branch_document'] = $request->file('branch_document');
        }

        return $this->subscriptionService->createBranchWithinCapacity($data, $request->user());
    }

    public function newAdditionalBranch(SubscriptionRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid, true);
        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            ModuleEnum::ManageSubscription,
            PermissionAction::Create
        );

        $data = $request->validated();

        $data['agency_id'] = $branch->agency_id;

        if ($request->hasFile('branch_image')) {
            $data['branch_image'] = $request->file('branch_image');
        }

        if ($request->hasFile('branch_document')) {
            $data['branch_document'] = $request->file('branch_document');
        }

        return $this->subscriptionService->makeAdditionalBranch($data, $request->user());
    }

    public function resubmitBranch(BranchResubmitRequest $request)
    {
        return $this->subscriptionService->resubmitBranch(
            $this->resubmitPayload($request),
            $request->user()
        );
    }

    public function resubmitBranchWithPurchase(BranchResubmitPurchaseRequest $request)
    {
        return $this->subscriptionService->makeResubmitPurchase(
            $this->resubmitPayload($request),
            $request->user()
        );
    }

    private function resubmitPayload(BranchResubmitRequest $request): array
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            ModuleEnum::ManageSubscription,
            PermissionAction::Update
        );

        $data = $request->validated();
        $data['agency_id'] = $branch->agency_id;
        foreach (['branch_image', 'branch_document', 'agency_image', 'agency_id_front', 'agency_id_back', 'agency_document'] as $file) {
            $data[$file] = $request->file($file);
        }

        return $data;
    }

    public function validateSubscription(SubscriptionRequest $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Validation passed',
            'data' => $request->validated(),
        ]);
    }

    public function checkUnique(SubscriptionUniqueRequest $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Available',
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
        if ($request->filled('branch_uuid')) {
            $branch = BranchGuard::resolveBranch($request->branch_uuid);
            AuthGuard::requireModule(
                $request->user(),
                $branch->branch_id,
                ModuleEnum::ManageSubscription,
                PermissionAction::Read
            );
            BranchGuard::mergeRequest($request, $branch);
        }
        return $this->subscriptionService->subscriptionList($request->all());
    }

    public function renew(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id,  ModuleEnum::ManageSubscription,  PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        return $this->subscriptionService->makeRenewal($request->all(), $request->user());
    }

    public function applyUpgrade(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id,  ModuleEnum::ManageSubscription,  PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        return $this->subscriptionService->applyPendingPlan($request->all());
    }

    public function cancelPendingPlan(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id,  ModuleEnum::ManageSubscription,  PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        return $this->subscriptionService->cancelPendingPlan($request->all());
    }

    public function cancelTest(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id,  ModuleEnum::ManageSubscription,  PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        return $this->subscriptionService->cancelTest($request->all());
    }

    public function paymentInvoice(Request $request, string $reference)
    {
        $user = AuthGuard::requireUser($request->user());

        if ($user->isSystemOwner) {
            return $this->subscriptionService->paymentInvoice($reference);
        }

        $validated = $request->validate([
            'branch_uuid' => ['required', 'uuid'],
        ]);

        $branch = BranchGuard::resolveBranch($validated['branch_uuid']);

        AuthGuard::requireModule(
            $user,
            $branch->branch_id,
            ModuleEnum::BranchSettings,
            PermissionAction::Read
        );

        return $this->subscriptionService->paymentInvoice($reference, (int) $branch->agency_id);
    }

    public function action(Request $request)
    {
        if ($request->action === 'overview' || $request->action === 'overview_subscription') {
            return $this->subscriptionService->overview($request->all());
        } else if ($request->action === 'approve') {
            return $this->subscriptionService->approve($request->all());
        } else if ($request->action === 'reject') {
            return $this->subscriptionService->reject($request->all());
        } else if ($request->action === 'logs') {
            return $this->subscriptionService->verificationLogs($request->all());
        }
    }
}
