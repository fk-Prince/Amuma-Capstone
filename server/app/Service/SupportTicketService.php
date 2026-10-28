<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Events\SupportTicketUpdated;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\SupportTicketReplyResource;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\User;
use App\Repository\SupportTicketRepository;
use App\Service\External\SupabaseService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SupportTicketService
{
    private const MESSAGE_TYPE = 'Support';

    public function __construct(
        private SupportTicketRepository $supportTicketRepository,
        private NotificationService $notificationService,
    ) {}

    public function list(array $payload, User $user)
    {
        $scope = $this->resolveScope($payload, $user);

        $tickets = $this->supportTicketRepository->paginate(
            $payload,
            $scope,
            (int) ($payload['per_page'] ?? 15),
        );

        $counts = $this->supportTicketRepository->statusCounts($scope);

        return SupportTicketResource::collection($tickets)->additional([
            'status_counts' => collect(SupportTicket::STATUSES)
                ->mapWithKeys(fn(string $status) => [$status => (int) ($counts[$status] ?? 0)]),
        ]);
    }

    public function create(array $payload, User $user)
    {
        $scope = $this->resolveScope($payload, $user);

        if (!empty($scope['support'])) {
            throw new Exception('Platform admins cannot open support tickets.', 403);
        }

        $branch = $scope['branch'] ?? null;
        $attachment = $this->storeAttachment($payload['attachment'] ?? null);

        $ticket = DB::transaction(function () use ($payload, $user, $branch, $attachment) {
            $ticket = $this->supportTicketRepository->create([
                'branch_id' => $branch?->branch_id,
                'user_id' => $user->user_id,
                'category' => $payload['category'],
                'subject' => Str::ucfirst(trim($payload['subject'])),
                'status' => SupportTicket::STATUS_OPEN,
            ]);

            $this->supportTicketRepository->createReply($ticket, [
                'user_id' => $user->user_id,
                'message' => trim($payload['message']),
                'attachment' => $attachment,
            ]);

            return $ticket;
        });

        $ticket = $this->supportTicketRepository->findByUuid($ticket->uuid, $scope);

        $this->notifySupport(
            $ticket,
            $user,
            "New support ticket from {$this->requesterName($ticket)}: {$ticket->subject}",
        );

        return response()->json([
            'message' => 'Your ticket has been sent. AMUMA Support will reply here.',
            'data' => new SupportTicketResource($ticket),
        ], 201);
    }

    public function show(string $uuid, array $payload, User $user)
    {
        [, $ticket] = $this->resolveTicket($uuid, $payload, $user);

        return response()->json([
            'data' => new SupportTicketResource($ticket),
        ]);
    }

    public function reply(string $uuid, array $payload, User $user)
    {
        [$scope, $ticket] = $this->resolveTicket($uuid, $payload, $user);

        $fromSupport = !empty($scope['support']);
        $attachment = $this->storeAttachment($payload['attachment'] ?? null);

        $reply = DB::transaction(function () use ($ticket, $user, $payload, $attachment, $fromSupport) {
            $reply = $this->supportTicketRepository->createReply($ticket, [
                'user_id' => $user->user_id,
                'message' => trim($payload['message']),
                'attachment' => $attachment,
            ]);

            $this->supportTicketRepository->update($ticket, [
                'status' => $fromSupport ? SupportTicket::STATUS_ANSWERED : SupportTicket::STATUS_OPEN,
            ]);

            return $reply;
        });

        $this->supportTicketRepository->loadReplyAuthor($reply);

        $replyData = (new SupportTicketReplyResource($reply))->resolve();

        SupportTicketUpdated::safe(new SupportTicketUpdated($ticket->uuid, $ticket->status, $replyData));

        $fromSupport
            ? $this->notifyRequester(
                $ticket,
                $user,
                "AMUMA Support replied to your ticket: {$ticket->subject}",
            )
            : $this->notifySupport(
                $ticket,
                $user,
                "{$this->requesterName($ticket)} replied to a support ticket: {$ticket->subject}",
            );

        return response()->json([
            'message' => 'Reply sent.',
            'data' => [
                'status' => $ticket->status,
                'reply' => $replyData,
            ],
        ]);
    }

    public function updateStatus(string $uuid, array $payload, User $user)
    {
        [$scope, $ticket] = $this->resolveTicket($uuid, $payload, $user);

        $resolving = $payload['status'] === SupportTicket::STATUS_RESOLVED;

        if ($resolving === ($ticket->status === SupportTicket::STATUS_RESOLVED)) {
            throw new Exception($resolving ? 'This ticket is already resolved.' : 'This ticket is already open.', 422);
        }

        $this->supportTicketRepository->update($ticket, ['status' => $payload['status']]);

        SupportTicketUpdated::safe(new SupportTicketUpdated($ticket->uuid, $ticket->status));

        $message = $resolving
            ? "Support ticket \"{$ticket->subject}\" was marked as resolved."
            : "Support ticket \"{$ticket->subject}\" was reopened.";

        !empty($scope['support'])
            ? $this->notifyRequester($ticket, $user, $message)
            : $this->notifySupport($ticket, $user, $message);

        return response()->json([
            'message' => $resolving ? 'Ticket marked as resolved.' : 'Ticket reopened.',
            'data' => ['status' => $ticket->status],
        ]);
    }

    private function resolveScope(array $payload, User $user): array
    {
        if (!empty($payload['branch_uuid'])) {
            $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::ManageSubscription, PermissionAction::Read);

            return ['branch' => $branch, 'branch_id' => $branch->branch_id];
        }

        if ($user->isSystemOwner) {
            return ['support' => true];
        }

        return ['user_id' => $user->user_id];
    }

    private function resolveTicket(string $uuid, array $payload, User $user): array
    {
        $scope = $this->resolveScope($payload, $user);

        $ticket = $this->supportTicketRepository->findByUuid($uuid, $scope);

        if (!$ticket) {
            throw new Exception('Support ticket not found.', 404);
        }

        return [$scope, $ticket];
    }

    private function requesterName(SupportTicket $ticket): string
    {
        return $ticket->branch?->name ?? $ticket->submitter_name ?? $ticket->user?->email ?? 'a user';
    }

    private function storeAttachment(mixed $file): ?string
    {
        return $file instanceof UploadedFile ? SupabaseService::store($file)['url'] : null;
    }

    private function notifySupport(SupportTicket $ticket, User $actor, string $message): void
    {
        try {
            $this->notificationService->notifyPlatformAdmins(
                $ticket->branch,
                $message,
                self::MESSAGE_TYPE,
                $actor,
                null,
                (string) $ticket->uuid,
            );
        } catch (\Throwable $e) {
            Log::error('Support ticket admin notification failed: ' . $e->getMessage(), [
                'ticket_uuid' => $ticket->uuid,
            ]);
        }
    }

    private function notifyRequester(SupportTicket $ticket, User $actor, string $message): void
    {
        try {
            $ticket->isPublic()
                ? $this->notificationService->notifyUser(
                    $ticket->user,
                    self::MESSAGE_TYPE,
                    $message,
                    $actor,
                    (string) $ticket->uuid,
                )
                : $this->notificationService->notifyModuleStaff(
                    $ticket->branch,
                    ModuleEnum::ManageSubscription,
                    self::MESSAGE_TYPE,
                    $message,
                    $actor,
                    null,
                    (string) $ticket->uuid,
                );
        } catch (\Throwable $e) {
            Log::error('Support ticket notification failed: ' . $e->getMessage(), [
                'ticket_uuid' => $ticket->uuid,
            ]);
        }
    }
}
