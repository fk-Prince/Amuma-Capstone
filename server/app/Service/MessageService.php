<?php

namespace App\Service;

use App\Events\MessageSent;
use App\Models\Branch;
use App\Models\CaregiverShift;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\Message;
use App\Models\PatientAccess;
use App\Models\PatientAdmission;
use App\Models\ScheduleAssigned;
use App\Models\User;
use App\Service\External\SupabaseService;
use App\Utils\MaskUtil;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MessageService
{
    private const NOT_MESSAGEABLE_BY_FAMILY = [
        'branch_manager',
        'agency_owner',
        'accounting',
    ];

    private const MESSAGEABLE_STATUSES = [
        EmployeeBranch::STATUS_ACTIVE,
        EmployeeBranch::STATUS_ONLEAVE,
    ];

    private const UNRESTRICTED_ROLES = [
        'admission',
        'branch_manager',
        'agency_owner',
    ];

    public function clientConversations(Client $client)
    {
        $conversations = Conversation::where('client_id', $client->client_id)
            ->with(['branch.location', 'patient', 'latestMessage', 'employeeOne'])
            ->orderByDesc('last_message_at')
            ->get();

        $hintsByBranch = $conversations
            ->groupBy('branch_id')
            ->map(fn($group, $branchId) => $this->batchSummaryHints($group, (int) $branchId, true));

        return $conversations
            ->map(fn($conversation) => $this->summary(
                $conversation,
                'client',
                null,
                $hintsByBranch[$conversation->branch_id][$conversation->conversation_id] ?? []
            ))
            ->all();
    }


    public function clientContacts(Client $client)
    {
        $accesses = PatientAccess::where('client_id', $client->client_id)
            ->where('have_access', true)
            ->with('patient.branch')
            ->get()
            ->filter(fn($access) => $access->patient?->branch_id);

        $conversations = Conversation::where('client_id', $client->client_id)
            ->where('type', Conversation::TYPE_FAMILY)
            ->whereNotNull('employee_one_id')
            ->get()
            ->keyBy(fn($conversation) => $conversation->branch_id . ':' . $conversation->employee_one_id);

        return $accesses
            ->map(fn($access) => [
                'patient_id' => $access->patient_id,
                'patient_name' => trim(
                    ($access->patient->first_name ?? '') . ' ' .
                        ($access->patient->last_name ?? '')
                ) ?: 'Resident',
                'branch' => [
                    'branch_id' => $access->patient->branch_id,
                    'name' => $access->patient->branch?->name,
                ],
                'care_team' => $this->careTeam($access->patient, $conversations),
            ])
            ->values()
            ->all();
    }

    public function openContact(User $user, array $payload)
    {
        $client = $user->client;

        if (!$client) {
            throw new Exception('Only family accounts can start this conversation.', 403);
        }

        $access = PatientAccess::where('client_id', $client->client_id)
            ->where('patient_id', $payload['patient_id'])
            ->where('have_access', true)
            ->with('patient')
            ->first();

        if (!$access?->patient?->branch_id) {
            throw new Exception('You do not have access to this patient.', 403);
        }

        $branchId = $access->patient->branch_id;

        $role = EmployeeBranch::where('branch_id', $branchId)
            ->where('employee_id', $payload['employee_id'])
            ->whereIn('status', self::MESSAGEABLE_STATUSES)
            ->value('role_name');

        if (!$role) {
            throw new Exception('That staff member is not part of this branch.', 403);
        }

        if (in_array($role, self::NOT_MESSAGEABLE_BY_FAMILY, true)) {
            throw new Exception('This staff member can\'t be messaged from the portal.', 403);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'branch_id' => $branchId,
                'client_id' => $client->client_id,
                'employee_one_id' => $payload['employee_id'],
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

    private function careTeam(object $patient, mixed $conversations = null)
    {
        $assignedIds = ScheduleAssigned::where('is_active', true)
            ->whereHas(
                'scheduleService.schedule',
                fn($schedule) => $schedule->where('patient_id', $patient->patient_id)
            )
            ->pluck('employee_id')
            ->merge(
                CaregiverShift::where('is_active', true)
                    ->whereHas(
                        'admission',
                        fn($admission) => $admission
                            ->where('patient_id', $patient->patient_id)
                            ->where('status', PatientAdmission::STATUS_ADMITTED)
                    )
                    ->pluck('caregiver_id')
            )
            ->unique();

        return EmployeeBranch::where('branch_id', $patient->branch_id)
            ->whereNotIn('role_name', self::NOT_MESSAGEABLE_BY_FAMILY)
            ->whereIn('status', self::MESSAGEABLE_STATUSES)
            ->with('employees.users')
            ->get()
            ->map(fn($employeeBranch) => [
                'employee_id' => $employeeBranch->employee_id,
                'name' => trim(
                    ($employeeBranch->employees?->first_name ?? '') . ' ' .
                        ($employeeBranch->employees?->last_name ?? '')
                ) ?: 'Staff',
                'role' => $this->roleLabel($employeeBranch->role_name),
                'email' => MaskUtil::email($employeeBranch->employees?->users?->email),
                'phone' => $employeeBranch->employees?->phone_number,
                'avatar' => $employeeBranch->employees?->avatar,
                'assigned' => $assignedIds->contains($employeeBranch->employee_id),
                'conversation_id' => $conversations
                    ?->get($patient->branch_id . ':' . $employeeBranch->employee_id)
                    ?->conversation_id,
            ])
            ->sortByDesc('assigned')
            ->values()
            ->all();
    }

    public function branchConversations(array $payload, User $user)
    {
        $branchId = (int) $payload['branch_id'];
        $employeeId = $this->requireBranchStaff($user, $branchId);
        $search = trim((string) ($payload['search'] ?? ''));

        $conversations = Conversation::where('branch_id', $branchId)
            ->where('type', Conversation::TYPE_FAMILY)
            ->whereIn('client_id', $this->reachableClientIds($user, $branchId))
            ->where(
                fn($q) => $q->whereNull('employee_one_id')
                    ->orWhere('employee_one_id', $employeeId)
            )
            ->when($search !== '', function ($query) use ($search, $branchId) {
                $term = '%' . $search . '%';

                $query->where(function ($q) use ($term, $branchId) {
                    $q->whereHas(
                        'client',
                        fn($c) => $c->whereRaw(
                            "concat(first_name, ' ', last_name) ilike ?",
                            [$term]
                        )
                    )->orWhereIn(
                        'client_id',
                        PatientAccess::select('client_id')
                            ->where('have_access', true)
                            ->whereHas(
                                'patient',
                                fn($p) => $p->where('branch_id', $branchId)
                                    ->where(
                                        fn($name) => $name
                                            ->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term])
                                            ->orWhereRaw("concat_ws(' ', first_name, middle_name, last_name) ilike ?", [$term])
                                    )
                            )
                    );
                });
            })
            ->with(['client', 'patient', 'latestMessage', 'employeeOne'])
            ->orderByDesc('last_message_at')
            ->get();

        $hints = $this->batchSummaryHints($conversations, $branchId);

        return $conversations
            ->map(fn($conversation) => $this->summary(
                $conversation,
                'staff',
                null,
                $hints[$conversation->conversation_id] ?? []
            ))
            ->all();
    }




    private function isBranchStaff(?int $employeeId, int $branchId): bool
    {
        return $employeeId && EmployeeBranch::where('branch_id', $branchId)
            ->where('employee_id', $employeeId)
            ->whereIn('status', self::MESSAGEABLE_STATUSES)
            ->exists();
    }

    private function requireBranchStaff(User $user, int $branchId): int
    {
        $employeeId = $user->employee?->employee_id;

        if (!$this->isBranchStaff($employeeId, $branchId)) {
            throw new Exception('You are not part of this branch.', 403);
        }

        return $employeeId;
    }

    private function reachesEveryFamily(User $user, int $branchId)
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

        return $this->assignedPatientsByEmployee(collect([$employeeId]))
            ->get($employeeId, collect());
    }

    private function assignedPatientsByEmployee($employeeIds)
    {
        $employeeIds = collect($employeeIds)->filter()->unique()->values();

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        $scheduled = DB::table('schedule_assigned')
            ->join('schedule_services', 'schedule_services.schedule_services_id', '=', 'schedule_assigned.schedule_services_id')
            ->join('schedules', 'schedules.schedule_id', '=', 'schedule_services.schedule_id')
            ->whereIn('schedule_assigned.employee_id', $employeeIds)
            ->where('schedule_assigned.is_active', true)
            ->select('schedule_assigned.employee_id', 'schedules.patient_id');

        return DB::table('caregiver_shifts')
            ->join('patient_admissions', 'patient_admissions.patient_admission_id', '=', 'caregiver_shifts.admission_id')
            ->whereIn('caregiver_shifts.caregiver_id', $employeeIds)
            ->where('caregiver_shifts.is_active', true)
            ->where('patient_admissions.status', PatientAdmission::STATUS_ADMITTED)
            ->select('caregiver_shifts.caregiver_id as employee_id', 'patient_admissions.patient_id')
            ->union($scheduled)
            ->get()
            ->groupBy('employee_id')
            ->map(fn($rows) => $rows->pluck('patient_id')->unique()->values());
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
            ->whereHas('patient', fn($p) => $p->where('branch_id', $branchId))
            ->pluck('client_id')
            ->unique();
    }


    private function familyPatientNames(Conversation $conversation, $preloaded = null, $onlyPatientIds = null)
    {
        if (!$conversation->client_id) {
            return [];
        }

        $access = $preloaded ?? PatientAccess::where('client_id', $conversation->client_id)
            ->where('have_access', true)
            ->whereHas(
                'patient',
                fn($p) => $p->where('branch_id', $conversation->branch_id)
            )
            ->with('patient')
            ->get();

        return $access
            ->when(
                $onlyPatientIds !== null,
                fn($rows) => $rows->filter(fn($access) => $onlyPatientIds->contains($access->patient_id))
            )
            ->map(fn($access) => trim(
                ($access->patient?->first_name ?? '') . ' ' .
                    ($access->patient?->last_name ?? '')
            ))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    private function staffContact(Conversation $conversation, array $hints = [])
    {
        if (!$conversation->employee_one_id) {
            return ['name' => null, 'avatar' => null, 'role' => 'Branch team'];
        }

        $employee = $conversation->employeeOne
            ?? Employee::find($conversation->employee_one_id);

        $role = isset($hints['roles'])
            ? $hints['roles']->get($conversation->employee_one_id)
            : EmployeeBranch::where('branch_id', $conversation->branch_id)
            ->where('employee_id', $conversation->employee_one_id)
            ->value('role_name');

        return [
            'name' => trim(
                ($employee?->first_name ?? '') . ' ' . ($employee?->last_name ?? '')
            ) ?: null,
            'avatar' => $employee?->avatar,
            'role' => $this->roleLabel($role),
        ];
    }


    private function batchSummaryHints(mixed $conversations, int $branchId, bool $forClient = false)
    {
        $clientIds = $conversations->pluck('client_id')->filter()->unique()->values();

        $patientsByClient = PatientAccess::where('have_access', true)
            ->whereIn('client_id', $clientIds)
            ->whereHas('patient', fn($p) => $p->where('branch_id', $branchId))
            ->with('patient')
            ->get()
            ->groupBy('client_id');

        $employeeIds = $conversations->pluck('employee_one_id')->filter()->unique()->values();

        $roles = EmployeeBranch::where('branch_id', $branchId)
            ->whereIn('employee_id', $employeeIds)
            ->pluck('role_name', 'employee_id');

        $restricted = $forClient
            ? $employeeIds->reject(
                fn($id) => in_array($roles->get($id), self::UNRESTRICTED_ROLES, true)
            )
            : collect();

        $assigned = $this->assignedPatientsByEmployee($restricted);

        return $conversations->mapWithKeys(fn($conversation) => [
            $conversation->conversation_id => [
                'roles' => $roles,
                'patients' => $patientsByClient->get($conversation->client_id, collect()),
                'patient_ids' => $restricted->contains($conversation->employee_one_id)
                    ? $assigned->get($conversation->employee_one_id, collect())
                    : null,
            ],
        ]);
    }

    private function roleLabel(?string $role): ?string
    {
        if (!$role) {
            return null;
        }

        return match ($role) {
            'admission' => 'Admission Staff',
            default => ucwords(str_replace('_', ' ', $role)),
        };
    }

    public function recipients(User $user, array $payload)
    {
        $branchId = (int) $payload['branch_id'];
        $this->requireBranchStaff($user, $branchId);
        $search = trim((string) ($payload['search'] ?? ''));

        $clientIds = $this->reachableClientIds($user, $branchId);

        if ($clientIds->isEmpty()) {
            return [];
        }

        $existing = Conversation::where('branch_id', $branchId)
            ->where('type', Conversation::TYPE_FAMILY)
            ->whereIn('client_id', $clientIds)
            ->get()
            ->keyBy('client_id');

        $patientsByClient = PatientAccess::whereIn('client_id', $clientIds)
            ->where('have_access', true)
            ->whereHas('patient', fn($p) => $p->where('branch_id', $branchId))
            ->with('patient')
            ->get()
            ->groupBy('client_id');

        return Client::whereIn('client_id', $clientIds)
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . $search . '%';

                $query->where(function ($q) use ($term) {
                    $q->whereRaw(
                        "concat(first_name, ' ', last_name) ilike ?",
                        [$term]
                    )->orWhereHas(
                        'user',
                        fn($u) => $u->where('email', 'ilike', $term)
                    );
                });
            })
            ->get()
            ->map(function ($client) use ($existing, $patientsByClient) {
                $patients = $patientsByClient->get($client->client_id, collect())
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
                    'email' => $client->user?->email,
                    'avatar' => $client->avatar,
                    'patient_names' => $patients,
                    'patient_name' => $patients[0] ?? null,
                    'conversation_id' => $existing->get($client->client_id)?->conversation_id,
                ];
            })
            ->values()
            ->all();
    }


    public function openWith(User $user, array $payload)
    {
        $branchId = (int) $payload['branch_id'];
        $employeeId = $this->requireBranchStaff($user, $branchId);

        if (empty($payload['client_id'])) {
            $payload['client_id'] = Client::whereHas(
                'user',
                fn($query) => $query->where('uuid', $payload['client_uuid'] ?? null)
            )->value('client_id');

            if (!$payload['client_id']) {
                throw new Exception('Family member not found.', 404);
            }
        }

        if (!$this->reachableClientIds($user, $branchId)->contains($payload['client_id'])) {
            throw new Exception('You are not assigned to this family.', 403);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'branch_id' => $branchId,
                'client_id' => $payload['client_id'],
                'employee_one_id' => $employeeId,
            ],
            [
                'type' => Conversation::TYPE_FAMILY,
                'last_message_at' => now(),
            ]
        );

        return $this->thread($user, [
            'conversation_id' => $conversation->conversation_id,
        ], true);
    }

    public function thread(User $user, array $payload, bool $asStaff = false)
    {
        $conversation = Conversation::with(['branch', 'client', 'patient'])
            ->find($payload['conversation_id']);

        if (!$conversation) {
            throw new Exception('Conversation not found.', 404);
        }

        $audience = $this->authorize($user, $conversation, $asStaff);

        $this->markRead($conversation, $user);

        $messages = $conversation->messages()
            ->orderBy('message_id')
            ->get()
            ->map(fn($message) => $message->toChat($user->user_id))
            ->all();

        return [
            'conversation' => $this->summary($conversation, $audience, $user),
            'messages' => $messages,
        ];
    }

    public function send(User $user, array $payload, bool $asStaff = false)
    {
        $body = trim((string) ($payload['body'] ?? ''));
        $file = $payload['attachment'] ?? null;
        $file = $file instanceof UploadedFile ? $file : null;

        if ($body === '' && !$file) {
            throw new Exception('Message cannot be empty.', 422);
        }

        return DB::transaction(function () use ($user, $payload, $body, $file, $asStaff) {
            $conversation = isset($payload['conversation_id'])
                ? Conversation::with('branch')->find($payload['conversation_id'])
                : $this->resolveClientConversation($user, $payload);

            if (!$conversation) {
                throw new Exception('Conversation not found.', 404);
            }

            $audience = $this->authorize($user, $conversation, $asStaff);

            $message = $conversation->messages()->create([
                'sender_user_id' => $user->user_id,
                'sender_type' => $audience === 'staff'
                    ? Message::SENDER_STAFF
                    : Message::SENDER_CLIENT,
                'body' => $body !== '' ? $body : null,
                'attachment_url' => $file ? $this->uploadAttachment($file) : null,
                'attachment_name' => $file?->getClientOriginalName(),
            ]);

            $conversation->update(['last_message_at' => now()]);

            broadcast(new MessageSent(
                $message,
                $this->channelsFor($conversation),
                $conversation->branch?->uuid
            ));

            return [
                'conversation_id' => $conversation->conversation_id,
                'message' => $message->toChat($user->user_id),
            ];
        });
    }

    private function uploadAttachment(UploadedFile $file): string
    {
        try {
            return SupabaseService::store($file)['url'];
        } catch (\Throwable $e) {
            throw new Exception(
                'We couldn\'t upload the attachment. Please try again or use a different file.',
                422,
                $e
            );
        }
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

        return Conversation::firstOrCreate(
            [
                'branch_id' => $branch->branch_id,
                'client_id' => $client->client_id,
                'employee_one_id' => $payload['employee_id'] ?? null,
            ],
            [
                'type' => Conversation::TYPE_FAMILY,
                'last_message_at' => now(),
            ]
        );
    }

    private function authorize(User $user, Conversation $conversation, bool $asStaff = false): string
    {
        if ($conversation->isStaffThread()) {
            $employeeId = $user->employee?->employee_id;

            $isParticipant = $employeeId
                && in_array($employeeId, [
                    $conversation->employee_one_id,
                    $conversation->employee_two_id,
                ], true)
                && $this->isBranchStaff($employeeId, (int) $conversation->branch_id);

            if (!$isParticipant) {
                throw new Exception('You do not have access to this conversation.', 403);
            }

            return 'staff';
        }

        $tryClient = function () use ($user, $conversation): ?string {
            if ($user->client && $user->client->client_id === $conversation->client_id) {
                return 'client';
            }

            return null;
        };

        $tryStaff = function () use ($user, $conversation): ?string {
            if (!$this->isBranchStaff($user->employee?->employee_id, (int) $conversation->branch_id)) {
                return null;
            }

            if (
                $conversation->employee_one_id
                && $conversation->employee_one_id !== $user->employee->employee_id
            ) {
                throw new Exception(
                    'This conversation is with another member of staff.',
                    403
                );
            }

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
        };

        $result = $asStaff
            ? ($tryStaff() ?? $tryClient())
            : ($tryClient() ?? $tryStaff());

        if ($result) {
            return $result;
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


    public function staffConversations(User $user, array $payload)
    {
        $employeeId = $this->requireBranchStaff($user, (int) $payload['branch_id']);

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

    public function colleagues(User $user, array $payload)
    {
        $employeeId = $this->requireBranchStaff($user, (int) $payload['branch_id']);

        $search = trim((string) ($payload['search'] ?? ''));

        $existing = Conversation::where('branch_id', $payload['branch_id'])
            ->where('type', Conversation::TYPE_STAFF)
            ->where(function ($query) use ($employeeId) {
                $query->where('employee_one_id', $employeeId)
                    ->orWhere('employee_two_id', $employeeId);
            })
            ->get();

        return EmployeeBranch::where('branch_id', $payload['branch_id'])
            ->where('employee_id', '!=', $employeeId)
            ->whereIn('status', self::MESSAGEABLE_STATUSES)
            ->with('employees.users')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . $search . '%';

                $query->whereHas('employees', function ($e) use ($term) {
                    $e->whereRaw(
                        "concat(first_name, ' ', last_name) ilike ?",
                        [$term]
                    )->orWhereHas(
                        'users',
                        fn($u) => $u->where('email', 'ilike', $term)
                    );
                });
            })
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
                    'email' => $employeeBranch->employees?->users?->email,
                    'avatar' => $employeeBranch->employees?->avatar,
                    'role_name' => $employeeBranch->role_name,
                    'conversation_id' => $match?->conversation_id,
                ];
            })
            ->values()
            ->all();
    }

    public function openWithStaff(User $user, array $payload)
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

        $this->requireBranchStaff($user, $branch->branch_id);

        if (
            (int) $payload['employee_id'] === $employeeId
            || !$this->isBranchStaff((int) $payload['employee_id'], $branch->branch_id)
        ) {
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

    private function orderedPair(int $a, int $b)
    {
        return [min($a, $b), max($a, $b)];
    }

    private function channelsFor(Conversation $conversation)
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

        $conversation->loadMissing('client.user', 'employeeOne.users');

        $staffUuids = $conversation->employee_one_id
            ? collect([$conversation->employeeOne?->users?->uuid])
            : $this->familyTeamUserUuids($conversation);

        return $staffUuids
            ->filter()
            ->unique()
            ->map(fn($uuid) => 'User.Messages.' . $uuid)
            ->when(
                $conversation->client?->user?->uuid,
                fn($channels, $uuid) => $channels->push('Client.Messages.' . $uuid)
            )
            ->values()
            ->all();
    }

    private function familyTeamUserUuids(Conversation $conversation)
    {
        $patientIds = PatientAccess::where('client_id', $conversation->client_id)
            ->where('have_access', true)
            ->whereHas('patient', fn($p) => $p->where('branch_id', $conversation->branch_id))
            ->pluck('patient_id');

        $staff = EmployeeBranch::where('branch_id', $conversation->branch_id)
            ->whereIn('status', self::MESSAGEABLE_STATUSES)
            ->with('employees.users')
            ->get();

        $isUnrestricted = fn($employeeBranch) => in_array(
            $employeeBranch->role_name,
            self::UNRESTRICTED_ROLES,
            true
        );

        $assigned = $this->assignedPatientsByEmployee(
            $staff->reject($isUnrestricted)->pluck('employee_id')
        );

        return $staff
            ->filter(
                fn($employeeBranch) => $isUnrestricted($employeeBranch)
                    || $assigned->get($employeeBranch->employee_id, collect())
                    ->intersect($patientIds)
                    ->isNotEmpty()
            )
            ->map(fn($employeeBranch) => $employeeBranch->employees?->users?->uuid);
    }

    private function summary(Conversation $conversation, string $audience, ?User $user = null, array $hints = [])
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
                'last_message' => $latest?->preview(),
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
                'unread_count' => $user
                    ? $conversation->unreadForUser($user->user_id)
                    : 0,
            ];
        }

        if (!$hints) {
            $hints = $this->batchSummaryHints(
                collect([$conversation]),
                (int) $conversation->branch_id,
                $audience === 'client'
            )[$conversation->conversation_id];
        }

        $staff = $this->staffContact($conversation, $hints);
        $patientNames = $this->familyPatientNames(
            $conversation,
            $hints['patients'] ?? null,
            $hints['patient_ids'] ?? null
        );

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
            'last_message' => $latest?->preview(),
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
