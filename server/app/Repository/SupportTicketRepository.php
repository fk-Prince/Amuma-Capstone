<?php

namespace App\Repository;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketRepository
{
    private const SUMMARY_RELATIONS = [
        'branch:branch_id,uuid,name,agency_id',
        'branch.agencies:agency_id,name',
        'user:user_id,uuid,email',
        'user.employee:employee_id,user_id,first_name,last_name',
        'user.client:client_id,user_id,first_name,last_name',
    ];

    public function create(array $payload): SupportTicket
    {
        return SupportTicket::create($payload);
    }

    public function createReply(SupportTicket $ticket, array $payload): SupportTicketReply
    {
        return $ticket->replies()->create($payload);
    }

    public function paginate(array $filters, array $scope, int $perPage)
    {
        return $this->scoped(SupportTicket::query(), $scope)
            ->select([
                'support_ticket_id',
                'uuid',
                'branch_id',
                'user_id',
                'category',
                'subject',
                'status',
                'created_at',
            ])
            ->with(self::SUMMARY_RELATIONS)
            ->withMax('replies as last_reply_at', 'created_at')
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn($query, $category) => $query->where('category', $category))
            ->when($filters['source'] ?? null, fn($query, $source) => $source === 'public'
                ? $query->whereNull('branch_id')
                : $query->whereNotNull('branch_id'))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $search = trim($search);
                $term = '%' . $search . '%';
                $code = preg_match('/^SUP-?0*(\d+)$/i', $search, $match) ? (int) $match[1] : null;

                $query->where(function ($inner) use ($term, $code) {
                    $inner->where('subject', 'ilike', $term)
                        ->orWhereHas('user', fn($user) => $user->where('email', 'ilike', $term))
                        ->orWhereHas('branch', fn($branch) => $branch->where('name', 'ilike', $term))
                        ->orWhereHas('branch.agencies', fn($agency) => $agency->where('name', 'ilike', $term))
                        ->when($code, fn($byCode) => $byCode->orWhere('support_ticket_id', $code));
                });
            })
            ->orderByDesc('last_reply_at')
            ->orderByDesc('support_ticket_id')
            ->paginate($perPage);
    }

    public function statusCounts(array $scope)
    {
        return $this->scoped(SupportTicket::query(), $scope)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    public function findByUuid(string $uuid, array $scope): ?SupportTicket
    {
        return $this->scoped(SupportTicket::query(), $scope)
            ->where('uuid', $uuid)
            ->with([
                ...self::SUMMARY_RELATIONS,
                'replies' => fn($query) => $query
                    ->orderBy('created_at')
                    ->orderBy('support_ticket_reply_id'),
                ...$this->authorRelations('replies.user'),
            ])
            ->first();
    }

    public function loadReplyAuthor(SupportTicketReply $reply): SupportTicketReply
    {
        return $reply->load($this->authorRelations('user'));
    }

    public function update(SupportTicket $ticket, array $payload): SupportTicket
    {
        $ticket->update($payload);

        return $ticket;
    }

    private function scoped(Builder $query, array $scope): Builder
    {
        return $query
            ->when($scope['branch_id'] ?? null, fn($inner, $branchId) => $inner->where('branch_id', $branchId))
            ->when($scope['user_id'] ?? null, fn($inner, $userId) => $inner
                ->whereNull('branch_id')
                ->where('user_id', $userId));
    }

    private function authorRelations(string $path): array
    {
        return [
            "{$path}:user_id,email",
            "{$path}.employee:employee_id,user_id,first_name,last_name",
            "{$path}.client:client_id,user_id,first_name,last_name",
            "{$path}.systemOwner:platform_admin_id,user_id,first_name,last_name",
        ];
    }
}
