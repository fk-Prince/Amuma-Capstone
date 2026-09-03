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
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-black/40 backdrop-blur-sm"
                @click="close"
            />

            <div
                class="relative z-50 flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
            >
                <div
                    class="flex items-start justify-between border-b border-slate-100 px-6 py-5 dark:border-white/10"
                >
                    <div>
                        <p
                            class="text-xs uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Assign Caregiver Staff
                        </p>

                        <h2 class="mt-1 text-lg font-semibold text-slate-800 dark:text-white">
                            Activities of Daily Living (ADL) Booking
                        </h2>

                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                            {{ patientName }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">
                            {{ formatDate(schedule?.scheduled_at) }}
                            •
                            {{ formatTime(schedule?.scheduled_at) }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:text-gray-500 dark:hover:bg-white/10"
                        @click="close"
                    >
                        ✕
                    </button>
                </div>

                <div
                    class="grid flex-1 overflow-hidden lg:grid-cols-[65%_35%]"
                >
                    <div class="overflow-y-auto p-6 space-y-4">
                        <div
                            class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <p class="text-sm font-semibold text-slate-800 dark:text-white">
                                Hours Booked
                            </p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                                {{
                                    schedule?.total_hours
                                        ? `${formatDuration(Number(schedule.total_hours))} (${schedule.total_hours} hrs)`
                                        : "—"
                                }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Assigned Caregiver
                            </p>

                            <span
                                class="flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="
                                    assignments.length
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                        : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300'
                                "
                            >
                                {{
                                    assignments.length
                                        ? `${assignments.length} assigned`
                                        : "Needs Assign"
                                }}
                            </span>
                        </div>

                        <div v-if="assignments.length" class="space-y-3">
                            <div
                                v-for="(entry, entryIndex) in assignments"
                                :key="entry.employee_id"
                                class="rounded-xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-secondary"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-bold text-primary"
                                        >
                                            {{ entryIndex + 1 }}
                                        </span>

                                        <p
                                            class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                        >
                                            Caregiver
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                                        >
                                            Assigned
                                        </span>

                                        <button
                                            type="button"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                                            title="Remove"
                                            @click="
                                                toggleEmployee(
                                                    entry.employee_id,
                                                )
                                            "
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center gap-3">
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
                                                initialsFor(entry.employee_id)
                                            }}
                                        </template>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                        >
                                            {{ nameFor(entry.employee_id) }}
                                        </p>

                                        <p
                                            class="truncate text-xs capitalize text-slate-400 dark:text-gray-500"
                                        >
                                            {{
                                                employeeById(entry.employee_id)
                                                    ?.role_name ?? "Caregiver"
                                            }}
                                        </p>

                                        <div
                                            class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                                        >
                                            <span
                                                v-if="
                                                    employeeById(
                                                        entry.employee_id,
                                                    )?.phone_number
                                                "
                                                class="flex items-center gap-1"
                                            >
                                                {{
                                                    employeeById(
                                                        entry.employee_id,
                                                    )?.phone_number
                                                }}
                                            </span>

                                            <span
                                                v-if="
                                                    employeeById(
                                                        entry.employee_id,
                                                    )?.email
                                                "
                                                class="flex min-w-0 items-center gap-1"
                                            >
                                                <span class="truncate">
                                                    {{
                                                        employeeById(
                                                            entry.employee_id,
                                                        )?.email
                                                    }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <input
                                        v-model="entry.note"
                                        type="text"
                                        :maxlength="NOTE_MAX"
                                        placeholder="Note for this assignment"
                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:text-gray-400"
                                    />

                                    <div
                                        class="mt-2 flex flex-wrap items-center gap-1.5"
                                    >
                                        <span
                                            class="text-[11px] text-slate-400 dark:text-gray-500"
                                        >
                                            Suggestions:
                                        </span>

                                        <button
                                            v-for="preset in NOTE_PRESETS"
                                            :key="preset"
                                            type="button"
                                            class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition dark:border-white/10"
                                            :class="
                                                entry.note === preset
                                                    ? 'border-primary bg-primary text-white'
                                                    : 'border-slate-200 text-slate-500 hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-400'
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
                            class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-xs text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
                        >
                            Pick staff from the list on the right to assign
                            them to this booking.
                        </p>
                    </div>

                    <div
                        class="flex flex-col overflow-hidden border-t border-slate-100 bg-slate-50 lg:border-l lg:border-t-0 dark:border-white/10 dark:bg-white/5"
                    >
                        <div class="shrink-0 space-y-3 p-5 pb-3">
                            <div class="flex items-baseline justify-between">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                >
                                    Available Caregivers
                                </p>

                                <span class="text-[11px] text-slate-400 dark:text-gray-500">
                                    {{ filteredEmployees.length }} shown
                                </span>
                            </div>

                            <div class="relative">
                                <input
                                    v-model="employeeSearch"
                                    type="text"
                                    placeholder="Search name"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-700 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:bg-secondary dark:text-gray-400"
                                />
                            </div>
                        </div>

                        <div
                            class="min-h-0 flex-1 space-y-2 overflow-y-auto px-5 pb-5"
                        >
                            <div v-if="isFetching" class="space-y-2">
                                <div
                                    v-for="i in 4"
                                    :key="i"
                                    class="h-16 animate-pulse rounded-xl bg-white dark:bg-secondary"
                                />
                            </div>

                            <div v-else class="space-y-2">
                                <div
                                    v-if="!filteredEmployees.length"
                                    class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center dark:border-white/10 dark:bg-secondary"
                                >
                                    <p class="text-sm text-slate-500 dark:text-gray-400">
                                        {{
                                            employeeSearch
                                                ? "No caregivers match that search"
                                                : "No caregivers available"
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-for="employee in filteredEmployees"
                                    :key="employee.employee_id"
                                    class="rounded-xl border bg-white transition dark:bg-secondary dark:border-white/10"
                                    :class="[
                                        isSelected(employee.employee_id)
                                            ? 'border-primary bg-primary/5'
                                            : 'border-slate-200 hover:border-primary/40 dark:border-white/10',
                                        employee.is_busy ||
                                        !isCaregiver(employee) ||
                                        isAssignmentTypeMismatch(employee)
                                            ? 'opacity-60'
                                            : '',
                                    ]"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-3 p-3 text-left"
                                        @click="handleEmployeeClick(employee)"
                                    >
                                        <div
                                            class="h-10 w-10 overflow-hidden rounded-full bg-[#EAF4F2] flex items-center justify-center shrink-0 dark:bg-accent-500/15"
                                        >
                                            <img
                                                v-if="employee.avatar"
                                                :src="employee.avatar"
                                                class="h-full w-full object-cover"
                                            />

                                            <template v-else>
                                                {{
                                                    initials(
                                                        fullName(
                                                            employee.first_name,
                                                            "",
                                                            employee.last_name,
                                                        ),
                                                    )
                                                }}
                                            </template>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <p
                                                    class="truncate text-sm font-semibold text-slate-700 dark:text-gray-400"
                                                >
                                                    {{
                                                        fullName(
                                                            employee.first_name,
                                                            "",
                                                            employee.last_name,
                                                        )
                                                    }}
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
                                                class="truncate text-xs text-slate-400 dark:text-gray-500"
                                            >
                                                {{ employee.role_name }}
                                            </p>

                                            <p
                                                v-if="
                                                    employee.phone_number ||
                                                    employee.email
                                                "
                                                class="mt-0.5 flex flex-wrap items-center gap-x-2 text-[11px] text-slate-400 dark:text-gray-500"
                                            >
                                                <span
                                                    v-if="
                                                        employee.phone_number
                                                    "
                                                >
                                                    {{ employee.phone_number }}
                                                </span>

                                                <span
                                                    v-if="employee.email"
                                                    class="truncate"
                                                >
                                                    {{ employee.email }}
                                                </span>
                                            </p>
                                        </div>

                                        <div
                                            v-if="employee.is_busy"
                                            class="flex flex-col items-end gap-1"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                                ></span>
                                                Busy
                                            </span>

                                            <span
                                                class="flex items-center gap-1 text-[11px] font-medium text-amber-600 hover:underline dark:text-amber-300"
                                                @click.stop="
                                                    toggleConflicts(
                                                        employee.employee_id,
                                                    )
                                                "
                                            >
                                                {{
                                                    employee.conflict_count ??
                                                    0
                                                }}
                                                {{
                                                    employee.conflict_count ===
                                                    1
                                                        ? "schedule conflict"
                                                        : "schedule conflicts"
                                                }}
                                                <span
                                                    class="transition-transform"
                                                    :class="
                                                        expandedConflicts.has(
                                                            employee.employee_id,
                                                        )
                                                            ? 'rotate-180'
                                                            : ''
                                                    "
                                                >
                                                    ▾
                                                </span>
                                            </span>
                                        </div>
                                        <span
                                            v-else-if="!isCaregiver(employee)"
                                            class="rounded-full bg-rose-100 px-2 py-1 text-xs text-rose-600 dark:bg-rose-500/15 dark:text-rose-300"
                                        >
                                            Caregiver only
                                        </span>
                                        <span
                                            v-else-if="
                                                isAssignmentTypeMismatch(
                                                    employee,
                                                )
                                            "
                                            class="rounded-full bg-rose-100 px-2 py-1 text-xs text-rose-600 dark:bg-rose-500/15 dark:text-rose-300"
                                        >
                                            {{
                                                assignmentTypeLabel(employee)
                                            }}
                                        </span>
                                        <span
                                            v-else-if="
                                                isSelected(
                                                    employee.employee_id,
                                                )
                                            "
                                            class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                                        >
                                            Assigned
                                        </span>
                                        <span
                                            v-else
                                            class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                                        >
                                            Available
                                        </span>
                                    </button>

                                    <div
                                        v-if="
                                            employee.is_busy &&
                                            expandedConflicts.has(
                                                employee.employee_id,
                                            ) &&
                                            employee.conflict_schedules?.length
                                        "
                                        class="space-y-2 border-t border-slate-100 bg-slate-50/50 p-3 dark:border-white/10 dark:bg-white/5"
                                    >
                                        <div
                                            v-for="(
                                                conflict, idx
                                            ) in employee.conflict_schedules"
                                            :key="idx"
                                            class="rounded-lg border border-amber-100 bg-white p-2.5 text-xs dark:border-amber-500/20 dark:bg-secondary"
                                        >
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <span
                                                    class="font-semibold text-slate-700 dark:text-gray-400"
                                                >
                                                    {{ conflict.schedule_code }}
                                                </span>
                                                <span
                                                    class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                                    :class="
                                                        conflict.category ===
                                                        'medical'
                                                            ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'
                                                            : 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300'
                                                    "
                                                >
                                                    {{
                                                        conflict.category ===
                                                        "medical"
                                                            ? "Medical"
                                                            : "ADL"
                                                    }}
                                                </span>
                                            </div>

                                            <p class="mt-1 text-slate-500 dark:text-gray-400">
                                                {{
                                                    formatDate(
                                                        conflict.scheduled_at,
                                                    )
                                                }}
                                                •
                                                {{
                                                    formatTime(
                                                        conflict.scheduled_at,
                                                    )
                                                }}
                                                <span
                                                    v-if="
                                                        conflict.duration_minutes
                                                    "
                                                >
                                                    ({{
                                                        formatDuration(
                                                            conflict.duration_minutes /
                                                                60,
                                                        )
                                                    }})
                                                </span>
                                            </p>

                                            <p
                                                class="mt-1 capitalize text-slate-400 dark:text-gray-500"
                                            >
                                                {{ conflict.status }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm text-slate-500 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10"
                        @click="close"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="isSaving"
                        @click="confirm"
                    >
                        {{ isSaving ? "Assigning..." : "Assign" }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import type { Employee } from "~/types/employee";
import { fullName, initials } from "~/utils/user";
import { formatDate, formatTime, formatDuration } from "~/utils/time";
import { Check } from "lucide-vue-next";
import { employeeService } from "~/api/employee/EmployeeService";
import { useToast } from "~/composables/useToast";
import type { AuditRow } from "~/types/schedule";

const NOTE_MAX = 255;

const NOTE_PRESETS = ["AM Shift", "PM Shift", "Full Shift"];

interface AssignmentEntry {
    employee_id: string;
    note: string;
}

const props = defineProps<{
    open: boolean;
    schedule?: AuditRow;
    branchUuid: string;
    isSaving?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (
        e: "confirm",
        payload: {
            schedule_service_id: number | null;
            assignments: any[];
        },
    ): void;
}>();

const employeeData = ref<Employee[]>([]);
const isFetching = ref(false);
const employeeSearch = ref("");
const { error: toastError } = useToast();

function isCaregiver(employee: Employee) {
    return (employee.role_name ?? "").toLowerCase() === "caregiver";
}

// A homecare visit can only be staffed by an online (homecare) caregiver, a
// facility booking only by an in-house one — someone set up for both is
// eligible either way.
function requiredAssignmentType(): "online" | "facility" {
    return props.schedule?.category === "Facility" ? "facility" : "online";
}

function isAssignmentTypeMismatch(employee: Employee): boolean {
    const type = employee.assignment_type;

    if (!type || type === "both") return false;

    return type !== requiredAssignmentType();
}

function assignmentTypeLabel(employee: Employee): string {
    return employee.assignment_type === "facility"
        ? "Facility only"
        : "Homecare only";
}

/** The caregivers currently assigned to this ADL booking, each with a note. */
const assignments = ref<AssignmentEntry[]>([]);

const patientName = computed(() => props.schedule?.patient_full_name ?? "");

const filteredEmployees = computed(() => {
    const term = employeeSearch.value.trim().toLowerCase();

    if (!term) return employeeData.value;

    return employeeData.value.filter((employee) =>
        fullName(employee.first_name, "", employee.last_name)
            .toLowerCase()
            .includes(term),
    );
});

function employeeById(employeeId: string | number) {
    return employeeData.value.find(
        (e) => Number(e.employee_id) === Number(employeeId),
    );
}

function nameFor(employeeId: string | number) {
    const employee = employeeById(employeeId);

    if (employee) return fullName(employee.first_name, "", employee.last_name);

    const assignee = props.schedule?.assignees?.find(
        (a) => Number(a.employee_id) === Number(employeeId),
    );

    return assignee?.full_name ?? "Unknown employee";
}

function initialsFor(employeeId: string | number) {
    return initials(nameFor(employeeId));
}

function isSelected(employeeId: string | number) {
    return assignments.value.some(
        (entry) => Number(entry.employee_id) === Number(employeeId),
    );
}

async function fetchEmployees() {
    if (!props.schedule) return;

    try {
        isFetching.value = true;

        const response = await employeeService.list({
            schedule_id: props.schedule.schedule_id,
            branch_uuid: props.branchUuid,
            type: "schedule",
        });

        employeeData.value = response.data ?? [];
    } catch (error) {
        console.error(error);
        employeeData.value = [];
    } finally {
        isFetching.value = false;
    }
}

function restoreSavedAssignments(schedule: AuditRow) {
    const activeAssignments = (schedule.assigned ?? []).filter(
        (a) => a.is_active,
    );

    const ids = activeAssignments.length
        ? activeAssignments.map((a) => String(a.employee_id))
        : schedule.employee_id
          ? [String(schedule.employee_id)]
          : [];

    assignments.value = ids.map((id) => {
        const match = schedule.assignees?.find(
            (a) => String(a.employee_id) === id,
        );

        const note = match
            ? (match.note ?? "")
            : id === String(schedule.employee_id)
              ? (schedule.note ?? "")
              : "";

        return { employee_id: id, note };
    });
}

const expandedConflicts = ref<Set<string | number>>(new Set());

function toggleConflicts(employeeId: string | number) {
    if (expandedConflicts.value.has(employeeId)) {
        expandedConflicts.value.delete(employeeId);
    } else {
        expandedConflicts.value.add(employeeId);
    }
    expandedConflicts.value = new Set(expandedConflicts.value);
}

watch(
    () => props.schedule,
    (schedule) => {
        if (!schedule) {
            assignments.value = [];
            employeeData.value = [];
            employeeSearch.value = "";
            expandedConflicts.value = new Set();
            return;
        }

        restoreSavedAssignments(schedule);
        employeeSearch.value = "";
        expandedConflicts.value = new Set();

        fetchEmployees();
    },
    { immediate: true },
);

// A caregiver mid-visit (clocked in, not yet out) can't be pulled off the
// booking — that would orphan their open clock-in session.
function isEmployeeClockedIn(employeeId: string | number | null | undefined) {
    if (!employeeId) return false;

    const id = Number(employeeId);

    return (props.schedule?.online_logs ?? []).some(
        (log) =>
            Number(log.employee_id) === id &&
            log.in_timestamp &&
            !log.out_timestamp,
    );
}

function toggleEmployee(employeeId: string) {
    const index = assignments.value.findIndex(
        (entry) => entry.employee_id === employeeId,
    );

    if (index === -1) {
        assignments.value = [...assignments.value, { employee_id: employeeId, note: "" }];
        return;
    }

    if (isEmployeeClockedIn(employeeId)) {
        toastError(
            "This caregiver is currently clocked in and can't be unassigned.",
        );
        return;
    }

    assignments.value = assignments.value.filter((_, i) => i !== index);
}

function handleEmployeeClick(employee: Employee) {
    if (isSelected(employee.employee_id)) {
        toggleEmployee(String(employee.employee_id));
        return;
    }

    if (employee.is_busy) return;

    if (!isCaregiver(employee)) {
        toastError(
            `${fullName(employee.first_name, "", employee.last_name)} is not a caregiver and cannot be assigned to this ADL booking.`,
        );
        return;
    }

    if (isAssignmentTypeMismatch(employee)) {
        toastError(
            `${fullName(employee.first_name, "", employee.last_name)} is ${assignmentTypeLabel(employee).toLowerCase()} and cannot be assigned to this ${requiredAssignmentType() === "facility" ? "facility" : "homecare"} booking.`,
        );
        return;
    }

    toggleEmployee(String(employee.employee_id));
}

function confirm() {
    if (!props.schedule || props.isSaving) return;

    const scheduleServiceId = props.schedule.schedule_services_id ?? null;

    const formattedAssignments = assignments.value.length
        ? assignments.value.map((entry) => {
              const employee = employeeById(entry.employee_id);

              return {
                  employee_id: Number(entry.employee_id),
                  schedule_services_id: scheduleServiceId,
                  avatar: employee?.avatar,
                  role_name: employee?.role_name,
                  employee_name: employee
                      ? fullName(employee.first_name, "", employee.last_name)
                      : "",
                  note: entry.note.trim() || null,
              };
          })
        : [
              {
                  employee_id: null,
                  schedule_services_id: scheduleServiceId,
              },
          ];

    emit("confirm", {
        schedule_service_id: scheduleServiceId,
        assignments: formattedAssignments,
    });
}

function close() {
    emit("close");
}
</script>
