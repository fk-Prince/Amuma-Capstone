<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[49] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="close"
            />

            <div
                class="relative z-50 flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                        >
                            <UserRound class="h-5 w-5" />
                        </div>

                        <div class="min-w-0">
                            <p
                                class="text-[11px] font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Assign Employee
                            </p>

                            <h2
                                class="truncate text-lg font-semibold text-slate-800"
                            >
                                {{ schedule?.schedule_code }}
                            </h2>

                            <p class="truncate text-sm text-slate-500">
                                {{ schedule?.patient?.full_name }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                        @click="close"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>
                </div>

                <div
                    class="grid min-h-0 flex-1 overflow-hidden lg:grid-cols-[minmax(0,1fr)_420px]"
                >
                    <div class="min-h-0 space-y-3 overflow-y-auto bg-white p-6">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Services
                        </p>

                        <div
                            v-for="service in schedule?.services ?? []"
                            :key="service.schedule_services_id"
                            class="rounded-2xl border p-4 transition"
                            :class="
                                activeService === service.schedule_services_id
                                    ? 'border-primary/40 bg-primary/[0.04] ring-1 ring-primary/20'
                                    : 'border-slate-200 bg-white hover:border-primary/25'
                            "
                        >
                            <div
                                class="flex cursor-pointer items-start justify-between gap-3"
                                @click="
                                    activeService = service.schedule_services_id
                                "
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-slate-800"
                                    >
                                        {{
                                            service.service_name ??
                                            "Activity of Daily Living"
                                        }}
                                    </p>

                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-400"
                                    >
                                        <CalendarDays class="h-3.5 w-3.5" />
                                        <span>
                                            {{
                                                formatDate(
                                                    schedule?.scheduled_at,
                                                )
                                            }}
                                        </span>

                                        <span class="text-slate-300">•</span>

                                        <Clock class="h-3.5 w-3.5" />
                                        <span>
                                            {{
                                                service.duration_minutes ??
                                                schedule?.total_duration_minutes
                                            }}
                                            min
                                        </span>

                                        <span
                                            v-if="service.type"
                                            class="text-slate-300"
                                        >
                                            •
                                        </span>

                                        <span v-if="service.type">
                                            {{ requiredRoleLabelFor(service) }}
                                            only
                                        </span>
                                    </div>
                                </div>

                                <span
                                    class="flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                    :class="
                                        selectionFor(
                                            service.schedule_services_id,
                                        ).length
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-rose-50 text-rose-600'
                                    "
                                >
                                    <component
                                        :is="
                                            selectionFor(
                                                service.schedule_services_id,
                                            ).length
                                                ? CheckCircle2
                                                : AlertCircle
                                        "
                                        class="h-3.5 w-3.5"
                                    />

                                    {{
                                        selectionFor(
                                            service.schedule_services_id,
                                        ).length
                                            ? `${selectionFor(service.schedule_services_id).length} assigned`
                                            : "Needs Assign"
                                    }}
                                </span>
                            </div>

                            <!-- One note per assigned person, so a service
                                 staffed by several people records what each of
                                 them is there to do. -->
                            <div
                                v-if="
                                    selectionFor(service.schedule_services_id)
                                        .length
                                "
                                class="mt-4 space-y-3"
                            >
                                <div
                                    v-for="entry in selectionFor(
                                        service.schedule_services_id,
                                    )"
                                    :key="entry.employee_id"
                                    class="rounded-xl border border-slate-200 bg-white p-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary text-[11px] font-semibold text-white"
                                        >
                                            <img
                                                v-if="
                                                    employeeById(
                                                        entry.employee_id,
                                                    )?.avatar
                                                "
                                                :src="
                                                    employeeById(
                                                        entry.employee_id,
                                                    )!.avatar
                                                "
                                                class="h-full w-full object-cover"
                                            />

                                            <template v-else>
                                                {{
                                                    initialsFor(
                                                        entry.employee_id,
                                                    )
                                                }}
                                            </template>
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-semibold text-slate-800"
                                            >
                                                {{
                                                    nameFor(entry.employee_id)
                                                }}
                                            </p>

                                            <p
                                                class="truncate text-xs capitalize text-slate-400"
                                            >
                                                {{
                                                    employeeById(
                                                        entry.employee_id,
                                                    )?.role_name ?? "Staff"
                                                }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                            title="Remove"
                                            @click="
                                                toggleEmployee(
                                                    service.schedule_services_id,
                                                    entry.employee_id,
                                                )
                                            "
                                        >
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <div class="mt-3">
                                        <input
                                            v-model="entry.note"
                                            type="text"
                                            :maxlength="NOTE_MAX"
                                            placeholder="Note for this assignment"
                                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                        />

                                        <div
                                            class="mt-2 flex flex-wrap items-center gap-1.5"
                                        >
                                            <span
                                                class="text-[11px] text-slate-400"
                                            >
                                                Suggestions:
                                            </span>

                                            <button
                                                v-for="preset in NOTE_PRESETS"
                                                :key="preset"
                                                type="button"
                                                class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition"
                                                :class="
                                                    entry.note === preset
                                                        ? 'border-primary bg-primary text-white'
                                                        : 'border-slate-200 text-slate-500 hover:border-primary/40 hover:text-primary'
                                                "
                                                @click="
                                                    entry.note =
                                                        entry.note === preset
                                                            ? ''
                                                            : preset
                                                "
                                            >
                                                {{ preset }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p
                                v-else
                                class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-xs text-slate-500"
                            >
                                Pick staff from the list on the right to assign
                                them to this service.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex min-h-0 flex-col overflow-hidden border-t border-slate-100 bg-slate-50/70 lg:border-l lg:border-t-0"
                    >
                        <div class="shrink-0 space-y-3 px-5 pb-3 pt-5">
                            <div class="flex items-baseline justify-between">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Available
                                    {{ requiredRoleLabel || "Staff" }}
                                </p>

                                <span class="text-[11px] text-slate-400">
                                    {{ filteredEmployees.length }} shown
                                </span>
                            </div>

                            <!-- A plain search field rather than a combobox:
                                 assignment is multi-select, so the list has to
                                 stay open while several people are picked. -->
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                />

                                <input
                                    v-model="employeeSearch"
                                    type="text"
                                    placeholder="Search name"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                        </div>

                        <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-5 pb-5">
                            <div v-if="isFetching" class="space-y-2">
                                <div
                                    v-for="i in 4"
                                    :key="i"
                                    class="flex w-full animate-pulse items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3"
                                >
                                    <div
                                        class="h-11 w-11 rounded-full bg-slate-200"
                                    />

                                    <div class="flex-1 space-y-2">
                                        <div
                                            class="h-3.5 w-28 rounded bg-slate-200"
                                        />
                                        <div
                                            class="h-3 w-16 rounded bg-slate-200"
                                        />
                                    </div>

                                    <div
                                        class="h-5 w-16 rounded-full bg-slate-200"
                                    />
                                </div>
                            </div>

                            <template v-else>
                                <div
                                    v-if="!filteredEmployees.length"
                                    class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-center"
                                >
                                    <Users class="h-8 w-8 text-slate-300" />

                                    <p
                                        class="text-sm font-medium text-slate-500"
                                    >
                                        {{
                                            employeeSearch
                                                ? "No staff match that search"
                                                : `No ${(requiredRoleLabel || "staff").toLowerCase()} available`
                                        }}
                                    </p>

                                    <p class="text-xs text-slate-400">
                                        {{
                                            employeeSearch
                                                ? "Try a different name."
                                                : `This service can only be staffed by a ${(requiredRoleLabel || "staff member").toLowerCase()}, and none is assigned to this branch.`
                                        }}
                                    </p>
                                </div>

                                <button
                                    v-for="employee in filteredEmployees"
                                    :key="employee.employee_id"
                                    type="button"
                                    :disabled="
                                        employee.is_busy ||
                                        hasRoleMismatch(employee)
                                    "
                                    class="flex w-full items-start gap-3 rounded-2xl border bg-white p-3 text-left transition"
                                    :class="[
                                        isSelected(employee.employee_id)
                                            ? 'border-primary/50 bg-primary/[0.04] ring-1 ring-primary/20'
                                            : 'border-slate-200 hover:border-primary/30 hover:bg-slate-50',
                                        employee.is_busy ||
                                        hasRoleMismatch(employee)
                                            ? 'cursor-not-allowed opacity-60 hover:border-slate-200 hover:bg-white'
                                            : '',
                                    ]"
                                    @click="
                                        requestToggle(
                                            activeService,
                                            employee,
                                        )
                                    "
                                >
                                    <span
                                        class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full text-xs font-semibold"
                                        :class="
                                            isSelected(employee.employee_id)
                                                ? 'bg-primary text-white'
                                                : 'bg-primary/10 text-primary'
                                        "
                                    >
                                        <img
                                            v-if="employee.avatar"
                                            :src="employee.avatar"
                                            :alt="employeeName(employee)"
                                            class="h-full w-full object-cover"
                                        />

                                        <template v-else>
                                            {{
                                                initials(employeeName(employee))
                                            }}
                                        </template>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex items-center gap-1.5"
                                        >
                                            <p
                                                class="truncate text-sm font-semibold text-slate-700"
                                            >
                                                {{ employeeName(employee) }}
                                            </p>

                                            <Check
                                                v-if="
                                                    isSelected(
                                                        employee.employee_id,
                                                    )
                                                "
                                                class="h-3.5 w-3.5 shrink-0 text-primary"
                                            />
                                        </div>

                                        <p
                                            class="truncate text-xs capitalize text-slate-500"
                                        >
                                            {{ employee.role_name ?? "Staff" }}

                                            <span
                                                v-if="
                                                    employee.formatted_assignment_type
                                                "
                                                class="text-slate-400"
                                            >
                                                •
                                                {{
                                                    employee.formatted_assignment_type
                                                }}
                                            </span>
                                        </p>

                                        <div
                                            class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-slate-400"
                                        >
                                            <span
                                                v-if="employee.phone_number"
                                                class="flex items-center gap-1"
                                            >
                                                <Phone class="h-3 w-3" />
                                                {{ employee.phone_number }}
                                            </span>

                                            <span
                                                v-if="employee.email"
                                                class="flex min-w-0 items-center gap-1"
                                            >
                                                <Mail class="h-3 w-3 shrink-0" />
                                                <span class="truncate">
                                                    {{ employee.email }}
                                                </span>
                                            </span>
                                        </div>

                                        <p
                                            v-if="employee.conflict_count"
                                            class="mt-1 text-[11px] text-amber-600"
                                        >
                                            {{ employee.conflict_count }}
                                            overlapping schedule{{
                                                employee.conflict_count === 1
                                                    ? ""
                                                    : "s"
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        v-if="employee.is_busy"
                                        class="flex shrink-0 items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-700"
                                    >
                                        <TriangleAlert class="h-3.5 w-3.5" />
                                        Conflict
                                    </span>

                                    <span
                                        v-else-if="hasRoleMismatch(employee)"
                                        class="flex shrink-0 items-center gap-1 rounded-full bg-rose-50 px-2 py-1 text-[11px] font-medium text-rose-600"
                                    >
                                        <CircleHelp class="h-3.5 w-3.5" />
                                        {{ requiredRoleLabel }} only
                                    </span>

                                    <span
                                        v-else-if="!employee.is_assigned"
                                        class="flex shrink-0 items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-500"
                                    >
                                        <CircleHelp class="h-3.5 w-3.5" />
                                        Not specialized
                                    </span>

                                    <span
                                        v-else
                                        class="flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700"
                                    >
                                        <Check class="h-3.5 w-3.5" />
                                        Available
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-100 bg-white px-6 py-4"
                >
                    <p class="text-xs text-slate-400">
                        {{ assignedServiceCount }} of
                        {{ schedule?.services?.length ?? 0 }} service(s)
                        assigned
                        <span v-if="totalAssigneeCount">
                            · {{ totalAssigneeCount }} staff
                        </span>
                    </p>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100"
                            @click="close"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSaving"
                            @click="confirm"
                        >
                            <Loader2
                                v-if="isSaving"
                                class="h-4 w-4 animate-spin"
                            />
                            {{ isSaving ? "Assigning..." : "Assign" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { Employee } from "~/types/employee";
import type { ScheduleItem, ScheduleServiceItem } from "~/types/schedule";
import { fullName, initials } from "~/utils/user";
import { formatDate } from "~/utils/time";
import { useToast } from "~/composables/useToast";
import {
    X,
    UserRound,
    Users,
    CalendarDays,
    Clock,
    CheckCircle2,
    AlertCircle,
    TriangleAlert,
    CircleHelp,
    Check,
    Loader2,
    Search,
    Phone,
    Mail,
} from "lucide-vue-next";

interface AssignmentEntry {
    employee_id: number;
    note: string;
}

const props = defineProps<{
    open: boolean;
    schedule?: ScheduleItem | null;
    employees?: Employee[];
    isFetching?: boolean;
    isSaving?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (
        e: "confirm",
        payload: {
            schedule_id: number;
            assignments: {
                employee_id: number;
                schedule_services_id: number;
                note: string | null;
            }[];
        },
    ): void;
}>();

const NOTE_MAX = 255;

const NOTE_PRESETS = [
    "Primary",
    "Assistance",
    "Supervisor",
    "Observer",
    "Stand-by",
];

const { error: toastError } = useToast();

/** schedule_services_id → the people assigned to it, each with its own note. */
const assignments = ref<Record<number, AssignmentEntry[]>>({});
const activeService = ref<number | null>(null);
const employeeSearch = ref("");

function selectionFor(serviceId: number): AssignmentEntry[] {
    return assignments.value[serviceId] ?? [];
}

function employeeById(employeeId: number) {
    return (props.employees ?? []).find(
        (e) => Number(e.employee_id) === Number(employeeId),
    );
}

function employeeName(employee: Employee) {
    return (
        employee.full_name ||
        fullName(employee.first_name, "", employee.last_name)
    );
}

function nameFor(employeeId: number) {
    const employee = employeeById(employeeId);

    if (employee) return employeeName(employee);

    // The person may no longer be in the available list (role changed, moved
    // branch) but is still assigned, so fall back to the schedule payload.
    const assignee = props.schedule?.services
        ?.flatMap((s) => s.assignees ?? [])
        .find((a) => Number(a.employee_id) === Number(employeeId));

    return assignee?.full_name ?? "Unknown employee";
}

function initialsFor(employeeId: number) {
    return initials(nameFor(employeeId));
}

/**
 * Only the role the active service actually allows — the backend rejects
 * anything else outright, so listing them would just offer a choice that
 * fails on submit. Anyone already assigned stays visible so they can still be
 * removed.
 */
const filteredEmployees = computed(() => {
    const term = employeeSearch.value.trim().toLowerCase();
    const required = requiredRoleFor(activeService.value);

    return (props.employees ?? []).filter((employee) => {
        if (
            required &&
            (employee.role_name ?? "").toLowerCase() !== required &&
            !isSelected(employee.employee_id)
        ) {
            return false;
        }

        if (!term) return true;

        // Name only — matching on role or email surfaces people the typist
        // wasn't looking for.
        return employeeName(employee).toLowerCase().includes(term);
    });
});

const assignedServiceCount = computed(
    () =>
        Object.values(assignments.value).filter((list) => list.length).length,
);

const totalAssigneeCount = computed(() =>
    Object.values(assignments.value).reduce(
        (total, list) => total + list.length,
        0,
    ),
);

// A Medical service can only be staffed by a nurse, an ADL service only by a
// caregiver — the backend enforces this unconditionally, so the picker
// shouldn't offer a choice that would just fail on submit.
function serviceTypeFor(serviceId: number | null): string | null {
    if (!serviceId) return null;

    return (
        props.schedule?.services?.find(
            (s) => s.schedule_services_id === serviceId,
        )?.type ?? null
    );
}

function requiredRoleFor(serviceId: number | null): string | null {
    const type = serviceTypeFor(serviceId);

    if (type === "Medical") return "nurse";
    if (type === "ADL") return "caregiver";

    return null;
}

function requiredRoleLabelFor(service: ScheduleServiceItem) {
    const role = requiredRoleFor(service.schedule_services_id);

    return role ? role.charAt(0).toUpperCase() + role.slice(1) : "Staff";
}

const requiredRoleLabel = computed(() => {
    const role = requiredRoleFor(activeService.value);

    return role ? role.charAt(0).toUpperCase() + role.slice(1) : "";
});

function hasRoleMismatch(
    employee: Employee,
    serviceId: number | null = activeService.value,
) {
    const required = requiredRoleFor(serviceId);

    if (!required) return false;

    return (employee.role_name ?? "").toLowerCase() !== required;
}

function isSelected(employeeId: string | number) {
    if (!activeService.value) return false;

    return selectionFor(activeService.value).some(
        (entry) => Number(entry.employee_id) === Number(employeeId),
    );
}

function toggleEmployee(serviceId: number, employeeId: number) {
    const current = selectionFor(serviceId);

    const index = current.findIndex(
        (entry) => Number(entry.employee_id) === Number(employeeId),
    );

    const next =
        index === -1
            ? [...current, { employee_id: Number(employeeId), note: "" }]
            : current.filter((_, i) => i !== index);

    assignments.value = {
        ...assignments.value,
        [serviceId]: next,
    };
}

function requestToggle(serviceId: number | null, employee: Employee) {
    if (!serviceId) return;

    const employeeId = Number(employee.employee_id);

    // Deselecting is always allowed — the guards below only gate adding.
    if (isSelected(employeeId)) {
        toggleEmployee(serviceId, employeeId);
        return;
    }

    if (employee.is_busy) {
        toastError(
            `${employeeName(employee)} already has a conflicting schedule and cannot be assigned.`,
        );
        return;
    }

    const requiredRole = requiredRoleFor(serviceId);

    if (requiredRole && hasRoleMismatch(employee, serviceId)) {
        toastError(`Only a ${requiredRole} can be assigned to this service.`);
        return;
    }

    toggleEmployee(serviceId, employeeId);
}

function resetAssignmentsFromSchedule(schedule?: ScheduleItem | null) {
    assignments.value = {};
    employeeSearch.value = "";

    if (!schedule) {
        activeService.value = null;
        return;
    }

    activeService.value = schedule.services?.[0]?.schedule_services_id ?? null;

    schedule.services?.forEach((service) => {
        assignments.value[service.schedule_services_id] = (
            service.assignees ?? []
        )
            .filter((assignee) => assignee.is_active !== false)
            .map((assignee) => ({
                employee_id: Number(assignee.employee_id),
                note: assignee.note ?? "",
            }));
    });
}

watch(
    () => props.schedule,
    (schedule) => resetAssignmentsFromSchedule(schedule),
    { immediate: true },
);

function confirm() {
    if (!props.schedule || props.isSaving) return;

    const formatted = Object.entries(assignments.value).flatMap(
        ([serviceId, entries]) =>
            entries.map((entry) => ({
                employee_id: Number(entry.employee_id),
                schedule_services_id: Number(serviceId),
                note: entry.note.trim() || null,
            })),
    );

    emit("confirm", {
        schedule_id: props.schedule.schedule_id,
        assignments: formatted,
    });
}

function close() {
    resetAssignmentsFromSchedule(props.schedule);
    emit("close");
}
</script>
