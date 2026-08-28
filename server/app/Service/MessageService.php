<?php

namespace App\Service;

use App\Events\MessageSent;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\Message;
use App\Models\Patient;
use App\Models\PatientAccess;
use App\Models\ScheduleAssigned;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class MessageService
{
    private const UNRESTRICTED_ROLES = [
        'admission',
        'administrator',
        'branch_owner',
    ];

    public function clientConversations(Client $client): array
    {
        $conversations = Conversation::where('client_id', $client->client_id)
            ->with(['branch.location', 'patient', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->get();

        return $conversations
            ->map(fn($conversation) => $this->summary($conversation, 'client'))
            ->all();
    }


    public function branchConversations(array $payload, ?User $user = null): array
    {
        $search = trim((string) ($payload['search'] ?? ''));

        $conversations = Conversation::where('branch_id', $payload['branch_id'])
            ->where('type', Conversation::TYPE_FAMILY)
            ->when($user, function ($query) use ($user, $payload) {
                $query->whereIn(
                    'client_id',
                    $this->reachableClientIds($user, (int) $payload['branch_id'])
                );
            })
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . $search . '%';

                $query->where(function ($q) use ($term) {
                    $q->whereHas(
                        'client',
                        fn($c) => $c->whereRaw(
                            "concat(first_name, ' ', last_name) ilike ?",
                            [$term]
                        )
                    )->orWhereHas(
                        'patient',
                        fn($p) => $p->whereRaw(
                            "concat(first_name, ' ', last_name) ilike ?",
                            [$term]
                        )
                    );
                });
            })
            ->with(['client', 'patient', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->get();

        return $conversations
            ->map(fn($conversation) => $this->summary($conversation, 'staff'))
            ->all();
    }




    private function reachesEveryFamily(User $user, int $branchId): bool
    {
        $employeeId = $user->employee?->employee_id;

        if (!$employeeId) {
            return false;
        }

        return EmployeeBranch::where('branch_id', $branchId)
            ->where('employee_id', $employeeId)
            ->whereIn('role_name', self::UNRESTRICTED_ROLES)
            ->exists();
    }

    private function assignedPatientIds(User $user)
    {
        $employeeId = $user->employee?->employee_id;

        if (!$employeeId) {
            return collect();
        }

        return Patient::whereHas(
            'schedules.scheduleServices.assigned',
            fn($a) => $a->where('employee_id', $employeeId)
                ->where('is_active', true)
        )->pluck('patient_id');
    }


    private function reachableClientIds(User $user, int $branchId)
    {
        if ($this->reachesEveryFamily($user, $branchId)) {
            return PatientAccess::where('have_access', true)
                ->whereHas('patient', fn($p) => $p->where('branch_id', $branchId))
                ->pluck('client_id')
                ->unique();
        }

        return PatientAccess::where('have_access', true)
            ->whereIn('patient_id', $this->assignedPatientIds($user))
            ->pluck('client_id')
            ->unique();
    }


    private function familyPatientNames(Conversation $conversation): array
    {
        if (!$conversation->client_id) {
            return [];
        }

        return PatientAccess::where('client_id', $conversation->client_id)
            ->where('have_access', true)
            ->whereHas(
                'patient',
                fn($p) => $p->where('branch_id', $conversation->branch_id)
            )
            ->with('patient')
            ->get()
            ->map(fn($access) => trim(
                ($access->patient?->first_name ?? '') . ' ' .
                    ($access->patient?->last_name ?? '')
            ))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    private function staffContact(Conversation $conversation): array
    {
        $lastStaffMessage = $conversation->messages()
            ->where('sender_type', Message::SENDER_STAFF)
            ->orderByDesc('message_id')
            ->first();

        $employee = $lastStaffMessage
            ? Employee::where('user_id', $lastStaffMessage->sender_user_id)->first()
            : null;

        if (!$employee) {
            $employeeId = PatientAccess::where('client_id', $conversation->client_id)
                ->where('have_access', true)
                ->pluck('patient_id')
                ->pipe(fn($ids) => ScheduleAssigned::where('is_active', true)
                    ->whereHas(
                        'scheduleService.schedule',
                        fn($s) => $s->whereIn('patient_id', $ids)
                    )
                    ->value('employee_id'));

            $employee = $employeeId ? Employee::find($employeeId) : null;
        }

        if (!$employee) {
            return ['name' => null, 'avatar' => null, 'role' => null];
        }

        $role = EmployeeBranch::where('branch_id', $conversation->branch_id)
            ->where('employee_id', $employee->employee_id)
            ->value('role_name');

        return [
            'name' => trim(
                ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')
            ) ?: null,
            'avatar' => $employee->avatar,
            'role' => $this->roleLabel($role),
        ];
    }

    private function roleLabel(?string $role): ?string
    {
        if (!$role) {
            return null;
        }

        return match ($role) {
            'admission' => 'Frontdesk',
            'branch_owner' => 'Branch Owner',
            default => ucwords(str_replace('_', ' ', $role)),
        };
    }

    public function recipients(User $user, array $payload): array
    {
        $branchId = (int) $payload['branch_id'];

        $clientIds = $this->reachableClientIds($user, $branchId);

        if ($clientIds->isEmpty()) {
            return [];
        }

        $existing = Conversation::where('branch_id', $branchId)
            ->where('type', Conversation::TYPE_FAMILY)
            ->whereIn('client_id', $clientIds)
            ->get()
            ->keyBy('client_id');

        return Client::whereIn('client_id', $clientIds)
            ->get()
            ->map(function ($client) use ($existing, $branchId) {
                $patients = PatientAccess::where('client_id', $client->client_id)
                    ->where('have_access', true)
                    ->whereHas('patient', fn($p) => $p->where('branch_id', $branchId))
                    ->with('patient')
                    ->get()
                    ->map(fn($access) => trim(
                        ($access->patient?->first_name ?? '') . ' ' .
                            ($access->patient?->last_name ?? '')
                    ))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'client_id' => $client->client_id,
                    'client_name' => trim(
                        ($client->first_name ?? '') . ' ' . ($client->last_name ?? '')
                    ) ?: 'Family',
                    'avatar' => $client->avatar,
                    'patient_names' => $patients,
                    'patient_name' => $patients[0] ?? null,
                    'conversation_id' => $existing->get($client->client_id)?->conversation_id,
                ];
            })
            ->values()
            ->all();
    }


    public function openWith(User $user, array $payload): array
    {
        $branchId = (int) $payload['branch_id'];

        if (!$this->reachableClientIds($user, $branchId)->contains($payload['client_id'])) {
            throw new Exception('You are not assigned to this family.', 403);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'branch_id' => $branchId,
                'client_id' => $payload['client_id'],
            ],
            [
                'type' => Conversation::TYPE_FAMILY,
                'last_message_at' => now(),
            ]
        );

        return $this->thread($user, [
            'conversation_id' => $conversation->conversation_id,
        ]);
    }

    public function thread(User $user, array $payload): array
    {
        $conversation = Conversation::with(['branch', 'client', 'patient'])
            ->find($payload['conversation_id']);

        if (!$conversation) {
            throw new Exception('Conversation not found.', 404);
        }

        $audience = $this->authorize($user, $conversation);

        $this->markRead($conversation, $user);

        $messages = $conversation->messages()
            ->orderBy('message_id')
            ->get()
            ->map(fn($message) => [
                'message_id' => $message->message_id,
                'sender_type' => $message->sender_type,
                'sender_user_id' => $message->sender_user_id,
                // Both sides of a staff thread send as "staff", so ownership
                // is decided by who sent it, not which side they are on.
                'is_mine' => $message->sender_user_id === $user->user_id,
                'body' => $message->body,
                'created_at' => $message->created_at?->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
            ])
            ->all();

        return [
            'conversation' => $this->summary($conversation, $audience, $user),
            'messages' => $messages,
        ];
    }

    public function send(User $user, array $payload): array
    {
        $body = trim((string) $payload['body']);

        if ($body === '') {
            throw new Exception('Message cannot be empty.', 422);
        }

        return DB::transaction(function () use ($user, $payload, $body) {
            $conversation = isset($payload['conversation_id'])
                ? Conversation::with('branch')->find($payload['conversation_id'])
                : $this->resolveClientConversation($user, $payload);

            if (!$conversation) {
                throw new Exception('Conversation not found.', 404);
            }

            $audience = $this->authorize($user, $conversation);

            $message = $conversation->messages()->create([
                'sender_user_id' => $user->user_id,
                'sender_type' => $audience === 'staff'
                    ? Message::SENDER_STAFF
                    : Message::SENDER_CLIENT,
                'body' => $body,
            ]);

            $conversation->update(['last_message_at' => now()]);

            broadcast(new MessageSent(
                $message,
                $this->channelsFor($conversation)
            ));

            return [
                'conversation_id' => $conversation->conversation_id,
                'message' => [
                    'message_id' => $message->message_id,
                    'sender_type' => $message->sender_type,
                    'sender_user_id' => $message->sender_user_id,
                    'is_mine' => true,
                    'body' => $message->body,
                    'created_at' => $message->created_at?->toIso8601String(),
                    'read_at' => null,
                ],
            ];
        });
    }

    /**
     * A client writing for the first time has no thread yet, so one is opened
     * against the branch that actually cares for the patient they picked.
     */
    private function resolveClientConversation(User $user, array $payload): Conversation
    {
        $client = $user->client;

        if (!$client) {
            throw new Exception('Only family accounts can start a conversation.', 403);
        }

        $access = PatientAccess::where('client_id', $client->client_id)
            ->where('patient_id', $payload['patient_id'] ?? null)
            ->where('have_access', true)
            ->with('patient')
            ->first();

        if (!$access || !$access->patient) {
            throw new Exception('You do not have access to this patient.', 403);
        }

        $branch = Branch::find($access->patient->branch_id);

        if (!$branch) {
            throw new Exception('This patient is not assigned to a branch yet.', 422);
        }

        // One thread per branch: a family with several relatives at the same
        // branch talks to that branch once, not once per patient.
        return Conversation::firstOrCreate(
            [
                'branch_id' => $branch->branch_id,
                'client_id' => $client->client_id,
            ],
            [
                'type' => Conversation::TYPE_FAMILY,
                'last_message_at' => now(),
            ]
        );
    }

    /**
     * Returns which side of the thread the user is on, or throws. Staff pass
     * through their branch assignment; a client must still hold access to the
     * patient the thread is about.
     */
    private function authorize(User $user, Conversation $conversation): string
    {
        if ($conversation->isStaffThread()) {
            $employeeId = $user->employee?->employee_id;

            $isParticipant = $employeeId
                && in_array($employeeId, [
                    $conversation->employee_one_id,
                    $conversation->employee_two_id,
                ], true);

            if (!$isParticipant) {
                throw new Exception('You do not have access to this conversation.', 403);
            }

            return 'staff';
        }

        if ($user->client && $user->client->client_id === $conversation->client_id) {
            return 'client';
        }

        $isBranchStaff = $user->employee
            && $user->employee->employeeBranch()
            ->where('branch_id', $conversation->branch_id)
            ->exists();

        if ($isBranchStaff) {
            $reachable = $this->reachableClientIds(
                $user,
                (int) $conversation->branch_id
            );

            if (!$reachable->contains($conversation->client_id)) {
                throw new Exception(
                    'You are not assigned to this family.',
                    403
                );
            }

            return 'staff';
        }

        throw new Exception('You do not have access to this conversation.', 403);
    }

    private function markRead(Conversation $conversation, User $user): void
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_user_id', '!=', $user->user_id)
            ->update(['read_at' => now()]);
    }

    /**
     * Threads between two employees of the same branch. The pair is stored in
     * a fixed order so A->B and B->A resolve to the same row.
     */
    public function staffConversations(User $user, array $payload): array
    {
        $employeeId = $user->employee?->employee_id;

        if (!$employeeId) {
            return [];
        }

        return Conversation::where('branch_id', $payload['branch_id'])
            ->where('type', Conversation::TYPE_STAFF)
            ->where(function ($query) use ($employeeId) {
                $query->where('employee_one_id', $employeeId)
                    ->orWhere('employee_two_id', $employeeId);
            })
            ->with(['employeeOne', 'employeeTwo', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn($conversation) => $this->summary($conversation, 'staff', $user))
            ->all();
    }

    public function colleagues(User $user, array $payload): array
    {
        $employeeId = $user->employee?->employee_id;

        if (!$employeeId) {
            return [];
        }

        $existing = Conversation::where('branch_id', $payload['branch_id'])
            ->where('type', Conversation::TYPE_STAFF)
            ->where(function ($query) use ($employeeId) {
                $query->where('employee_one_id', $employeeId)
                    ->orWhere('employee_two_id', $employeeId);
            })
            ->get();

        return EmployeeBranch::where('branch_id', $payload['branch_id'])
            ->where('employee_id', '!=', $employeeId)
            ->with('employees')
            ->get()
            ->map(function ($employeeBranch) use ($existing, $employeeId) {
                [$one, $two] = $this->orderedPair(
                    $employeeId,
                    $employeeBranch->employee_id
                );

                $match = $existing->first(
                    fn($c) => $c->employee_one_id === $one
                        && $c->employee_two_id === $two
                );

                return [
                    'employee_id' => $employeeBranch->employee_id,
                    'name' => trim(
                        ($employeeBranch->employees?->first_name ?? '') . ' ' .
                            ($employeeBranch->employees?->last_name ?? '')
                    ) ?: 'Staff',
                    'avatar' => $employeeBranch->employees?->avatar,
                    'role_name' => $employeeBranch->role_name,
                    'conversation_id' => $match?->conversation_id,
                ];
            })
            ->values()
            ->all();
    }

    public function openWithStaff(User $user, array $payload): array
    {
        $employeeId = $user->employee?->employee_id;

        if (!$employeeId) {
            throw new Exception('Only staff can start this conversation.', 403);
        }

        $branch = Branch::where('uuid', $payload['branch_uuid'])->first()
            ?? Branch::find($payload['branch_id'] ?? null);

        if (!$branch) {
            throw new Exception('Branch not found.', 404);
        }

        $bothAtBranch = EmployeeBranch::where('branch_id', $branch->branch_id)
            ->whereIn('employee_id', [$employeeId, $payload['employee_id']])
            ->distinct()
            ->count('employee_id');

        if ($bothAtBranch < 2) {
            throw new Exception('That colleague is not part of this branch.', 403);
        }

        [$one, $two] = $this->orderedPair($employeeId, (int) $payload['employee_id']);

        $conversation = Conversation::firstOrCreate(
            [
                'branch_id' => $branch->branch_id,
                'type' => Conversation::TYPE_STAFF,
                'employee_one_id' => $one,
                'employee_two_id' => $two,
            ],
            ['last_message_at' => now()]
        );

        return $this->thread($user, [
            'conversation_id' => $conversation->conversation_id,
        ]);
    }

    private function orderedPair(int $a, int $b): array
    {
        return [min($a, $b), max($a, $b)];
    }

    private function channelsFor(Conversation $conversation): array
    {
        if ($conversation->isStaffThread()) {
            $conversation->loadMissing('employeeOne.users', 'employeeTwo.users');

            return collect([
                $conversation->employeeOne?->users?->uuid,
                $conversation->employeeTwo?->users?->uuid,
            ])
                ->filter()
                ->map(fn($uuid) => 'User.Messages.' . $uuid)
                ->values()
                ->all();
        }

        $conversation->loadMissing('branch', 'client.user');

        $channels = ['Branch.Messages.' . $conversation->branch?->uuid];

        if ($conversation->client?->user?->uuid) {
            $channels[] = 'Client.Messages.' . $conversation->client->user->uuid;
        }

        return $channels;
    }

    private function summary(Conversation $conversation, string $audience, ?User $user = null): array
    {
        $latest = $conversation->latestMessage;

        if ($conversation->isStaffThread()) {
            $employeeId = $user?->employee?->employee_id;

            $other = $conversation->employee_one_id === $employeeId
                ? $conversation->employeeTwo
                : $conversation->employeeOne;

            return [
                'conversation_id' => $conversation->conversation_id,
                'type' => Conversation::TYPE_STAFF,
                'branch' => [
                    'branch_id' => $conversation->branch_id,
                    'uuid' => $conversation->branch?->uuid,
                    'name' => $conversation->branch?->name,
                ],
                'client_name' => null,
                'staff_name' => trim(
                    ($other?->first_name ?? '') . ' ' . ($other?->last_name ?? '')
                ) ?: 'Staff',
                'avatar' => $other?->avatar,
                'patient_name' => null,
                'last_message' => $latest?->body,
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
                'unread_count' => $user
                    ? $conversation->unreadForUser($user->user_id)
                    : 0,
            ];
        }

        $staff = $this->staffContact($conversation);
        $patientNames = $this->familyPatientNames($conversation);

        return [
            'conversation_id' => $conversation->conversation_id,
            'type' => Conversation::TYPE_FAMILY,
            'branch' => [
                'branch_id' => $conversation->branch_id,
                'uuid' => $conversation->branch?->uuid,
                'name' => $conversation->branch?->name,
            ],
            'client_name' => trim(
                ($conversation->client?->first_name ?? '') . ' ' .
                    ($conversation->client?->last_name ?? '')
            ) ?: null,
            'staff_name' => $staff['name'],
            'staff_role' => $staff['role'],
            'avatar' => $conversation->client?->avatar,
            'staff_avatar' => $staff['avatar'],
            'patient_names' => $patientNames,
            'patient_name' => $patientNames[0] ?? null,
            'last_message' => $latest?->body,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'unread_count' => $user
                ? $conversation->unreadForUser($user->user_id)
                : $conversation->messages()
                ->whereNull('read_at')
                ->where(
                    'sender_type',
                    $audience === 'staff' ? 'client' : 'staff'
                )
                ->count(),
        ];
    }
}
