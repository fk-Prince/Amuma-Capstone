<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\SupportTicketReplyResource;
use App\Http\Resources\SupportTicketResource;
use App\Mail\SupportReplyMailer;
use App\Models\Branch;
use App\Models\SupportTicket;
use App\Models\User;
use App\Repository\SupportTicketRepository;
use App\Service\External\SupabaseService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $branch = $this->resolveScope($payload, $user);

        $tickets = $this->supportTicketRepository->paginate(
            $payload,
            $branch?->branch_id,
            (int) ($payload['per_page'] ?? 15),
        );

        $counts = $this->supportTicketRepository->statusCounts($branch?->branch_id);

        return SupportTicketResource::collection($tickets)->additional([
            'status_counts' => collect(SupportTicket::STATUSES)
                ->mapWithKeys(fn(string $status) => [$status => (int) ($counts[$status] ?? 0)]),
        ]);
    }

    public function create(array $payload, User $user)
    {
        $branch = $this->resolveScope($payload, $user);
        $attachment = $this->storeAttachment($payload['attachment'] ?? null);

        $ticket = DB::transaction(function () use ($payload, $user, $branch, $attachment) {
            $ticket = $this->supportTicketRepository->create([
                'branch_id' => $branch->branch_id,
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

        $ticket = $this->supportTicketRepository->findByUuid($ticket->uuid, $branch->branch_id);

        $this->notifySupport(
            $ticket,
            $user,
            "New support ticket {$ticket->ticket_code} from {$branch->name}: {$ticket->subject}",
        );

        return response()->json([
            'message' => 'Your ticket has been sent. AMUMA Support will reply here.',
            'data' => new SupportTicketResource($ticket),
        ], 201);
    }

    public function createPublic(array $payload, ?User $user)
    {
        $attachment = $this->storeAttachment($payload['attachment'] ?? null);

        $ticket = DB::transaction(function () use ($payload, $user, $attachment) {
            $ticket = $this->supportTicketRepository->create([
                'branch_id' => null,
                'user_id' => $user?->user_id,
                'contact_name' => $user ? null : Str::title(trim($payload['name'])),
                'contact_email' => $user ? null : strtolower(trim($payload['email'])),
                'category' => $payload['category'],
                'subject' => Str::ucfirst(trim($payload['subject'])),
                'status' => SupportTicket::STATUS_OPEN,
            ]);

            $this->supportTicketRepository->createReply($ticket, [
                'user_id' => $user?->user_id,
                'message' => trim($payload['message']),
                'attachment' => $attachment,
            ]);

            return $ticket;
        });

        $ticket = $this->supportTicketRepository->findByUuid($ticket->uuid, null);

        $this->notifySupport(
            $ticket,
            $user,
            "New support request {$ticket->ticket_code} from {$ticket->submitter_name}: {$ticket->subject}",
        );

        return response()->json([
            'message' => "Your message has been sent. AMUMA Support will reply to {$ticket->submitter_email}.",
            'data' => ['ticket_code' => $ticket->ticket_code],
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
        [$branch, $ticket] = $this->resolveTicket($uuid, $payload, $user);

        $fromSupport = $branch === null;
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

        if ($fromSupport) {
            $this->notifyBranch(
                $ticket,
                $user,
                "AMUMA Support replied to your ticket {$ticket->ticket_code}: {$ticket->subject}",
            );
            $this->emailSubmitter($ticket, $reply->message);
        } else {
            $this->notifySupport(
                $ticket,
                $user,
                "{$ticket->branch?->name} replied to support ticket {$ticket->ticket_code}: {$ticket->subject}",
            );
        }

        return response()->json([
            'message' => 'Reply sent.',
            'data' => [
                'status' => $ticket->status,
                'reply' => new SupportTicketReplyResource($reply),
            ],
        ]);
    }

    public function updateStatus(string $uuid, array $payload, User $user)
    {
        [$branch, $ticket] = $this->resolveTicket($uuid, $payload, $user);

        $resolving = $payload['status'] === SupportTicket::STATUS_RESOLVED;

        if ($resolving === ($ticket->status === SupportTicket::STATUS_RESOLVED)) {
            throw new Exception($resolving ? 'This ticket is already resolved.' : 'This ticket is already open.', 422);
        }

        $this->supportTicketRepository->update($ticket, ['status' => $payload['status']]);

        $message = $resolving
            ? "Support ticket {$ticket->ticket_code} was marked as resolved."
            : "Support ticket {$ticket->ticket_code} was reopened.";

        $branch === null
            ? $this->notifyBranch($ticket, $user, $message)
            : $this->notifySupport($ticket, $user, $message);

        return response()->json([
            'message' => $resolving ? 'Ticket marked as resolved.' : 'Ticket reopened.',
            'data' => ['status' => $ticket->status],
        ]);
    }

    private function resolveScope(array $payload, User $user): ?Branch
    {
        if (empty($payload['branch_uuid'])) {
            AuthGuard::requireSystemOwner($user);

            return null;
        }

        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::ManageSubscription, PermissionAction::Read);

        return $branch;
    }

    private function resolveTicket(string $uuid, array $payload, User $user): array
    {
        $branch = $this->resolveScope($payload, $user);

        $ticket = $this->supportTicketRepository->findByUuid($uuid, $branch?->branch_id);

        if (!$ticket) {
            throw new Exception('Support ticket not found.', 404);
        }

        return [$branch, $ticket];
    }

    private function storeAttachment(mixed $file): ?string
    {
        return $file instanceof UploadedFile ? SupabaseService::store($file)['url'] : null;
    }

    private function notifySupport(SupportTicket $ticket, ?User $actor, string $message): void
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

    private function notifyBranch(SupportTicket $ticket, User $actor, string $message): void
    {
        if ($ticket->isPublic()) {
            return;
        }

        try {
            $this->notificationService->notifyModuleStaff(
                $ticket->branch,
                ModuleEnum::ManageSubscription,
                self::MESSAGE_TYPE,
                $message,
                $actor,
                null,
                (string) $ticket->uuid,
            );
        } catch (\Throwable $e) {
            Log::error('Support ticket branch notification failed: ' . $e->getMessage(), [
                'ticket_uuid' => $ticket->uuid,
            ]);
        }
    }

    private function emailSubmitter(SupportTicket $ticket, string $reply): void
    {
        $email = $ticket->submitter_email;

        if (!$email) {
            return;
        }

        $footnote = $ticket->isPublic()
            ? "Need more help? Send another message from Resources > Customer Support on the AMUMA website and mention {$ticket->ticket_code}."
            : 'You can reply to this ticket from Customer Support in your AMUMA dashboard.';

        try {
            Mail::to($email)->send(new SupportReplyMailer(
                $ticket->submitter_name ?: 'there',
                $ticket->ticket_code,
                $ticket->subject,
                $reply,
                $footnote,
            ));
        } catch (\Throwable $e) {
            Log::error('Support reply email failed: ' . $e->getMessage(), [
                'ticket_uuid' => $ticket->uuid,
                'email' => $email,
            ]);
        }
    }
}
