<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Enums\RoleEnum;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\InquiryResource;
use App\Mail\InquiryReplyMailer;
use App\Models\Inquiry;
use App\Models\User;
use App\Repository\InquiryRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InquiryService
{
    public function __construct(
        private InquiryRepository $inquiryRepository,
        private NotificationService $notificationService,
    ) {}

    public function createInquiry(string $branchUuid, array $payload, ?User $user)
    {
        $branch = BranchGuard::resolveBranch($branchUuid);

        $inquiry = $this->inquiryRepository->create([
            'branch_id' => $branch->branch_id,
            'user_id' => $user?->user_id,
            'first_name' => Str::title($payload['first_name']),
            'last_name' => Str::title($payload['last_name']),
            'email' => $payload['email'],
            'phone_number' => $payload['phone_number'],
            'city' => $payload['city'] ?? null,
            'message' => $payload['message'],
            'status' => Inquiry::STATUS_NEW,
        ]);

        try {
            $this->notificationService->notifyBranchStaff(
                $branch,
                "New inquiry from {$inquiry->first_name} {$inquiry->last_name} is waiting for a response.",
                'Inquiry',
                $user,
                null,
                (string) $inquiry->uuid,
                [RoleEnum::Admission->value],
            );
        } catch (\Throwable $e) {
            Log::error('Inquiry notification failed: ' . $e->getMessage(), [
                'inquiry_uuid' => $inquiry->uuid,
            ]);
        }

        return response()->json([
            'message' => 'Your inquiry has been sent. The provider will reply to you through email.',
            'data' => ['uuid' => $inquiry->uuid],
        ], 201);
    }

    public function listInquiries(array $payload, User $user)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Inquiries, PermissionAction::Read);

        $inquiries = $this->inquiryRepository->paginateForBranch(
            $branch->branch_id,
            $payload,
            (int) ($payload['per_page'] ?? 10),
        );

        $counts = $this->inquiryRepository->statusCounts($branch->branch_id);

        return InquiryResource::collection($inquiries)->additional([
            'status_counts' => [
                Inquiry::STATUS_NEW => (int) ($counts[Inquiry::STATUS_NEW] ?? 0),
                Inquiry::STATUS_READ => (int) ($counts[Inquiry::STATUS_READ] ?? 0),
                Inquiry::STATUS_RESOLVED => (int) ($counts[Inquiry::STATUS_RESOLVED] ?? 0),
            ],
        ]);
    }

    public function updateStatus(string $uuid, array $payload, User $user)
    {
        [, $inquiry] = $this->resolveForUpdate($uuid, $payload['branch_uuid'], $user);

        $inquiry = $this->inquiryRepository->update($inquiry, [
            'status' => $payload['status'],
        ]);

        return response()->json([
            'message' => 'Inquiry updated.',
            'data' => new InquiryResource($inquiry),
        ]);
    }

    public function reply(string $uuid, array $payload, User $user)
    {
        [$branch, $inquiry] = $this->resolveForUpdate($uuid, $payload['branch_uuid'], $user);

        try {
            Mail::to($inquiry->email)->send(new InquiryReplyMailer(
                trim("{$inquiry->first_name} {$inquiry->last_name}"),
                (string) $branch->name,
                $branch->email,
                $branch->contact_number,
                (string) $inquiry->message,
                $payload['reply'],
            ));
        } catch (\Throwable $e) {
            Log::error('Inquiry reply email failed: ' . $e->getMessage(), [
                'inquiry_uuid' => $inquiry->uuid,
                'email' => $inquiry->email,
            ]);

            throw new Exception('The reply could not be emailed. Please try again.', 502);
        }

        $inquiry = $this->inquiryRepository->update($inquiry, [
            'reply' => $payload['reply'],
            'replied_at' => now(),
            'replied_by' => $user->user_id,
            'status' => Inquiry::STATUS_RESOLVED,
        ]);

        return response()->json([
            'message' => "Reply sent to {$inquiry->email}.",
            'data' => new InquiryResource($inquiry),
        ]);
    }

    private function resolveForUpdate(string $uuid, string $branchUuid, User $user): array
    {
        $branch = BranchGuard::resolveBranch($branchUuid);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Inquiries, PermissionAction::Update);

        $inquiry = $this->inquiryRepository->findForBranch($uuid, $branch->branch_id);

        if (!$inquiry) {
            throw new Exception('Inquiry not found.', 404);
        }

        return [$branch, $inquiry];
    }
}
