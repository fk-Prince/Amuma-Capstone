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

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-3"
                enter-to-class="opacity-100 scale-100 translate-y-0"
            >
                <div
                    class="relative z-50 flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5"
                    >
                        <div class="flex items-center gap-3 min-w-0">
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
                        class="grid flex-1 overflow-hidden lg:grid-cols-[1fr_320px]"
                    >
                        <div class="overflow-y-auto p-6 space-y-3 bg-white">
                            <p
                                class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Services
                            </p>

                            <div
                                v-for="service in schedule?.services ?? []"
                                :key="service.schedule_services_id"
                                class="group rounded-2xl border p-4 transition cursor-pointer"
                                :class="
                                    activeService ===
                                    service.schedule_services_id
                                        ? 'border-primary/40 bg-primary/[0.04] ring-1 ring-primary/20'
                                        : 'border-slate-200 bg-white hover:border-primary/25 hover:bg-slate-50/60'
                                "
                                @click="
                                    activeService = service.schedule_services_id
                                "
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-slate-800"
                                        >
                                            {{
                                                service.service_name ??
                                                "Homecare Service"
                                            }}
                                        </p>

                                        <div
                                            class="mt-1 flex items-center gap-1.5 text-xs text-slate-400"
                                        >
                                            <CalendarDays class="h-3.5 w-3.5" />
                                            <span>{{
                                                formatDate(
                                                    schedule?.scheduled_at,
                                                )
                                            }}</span>

                                            <span class="text-slate-300"
                                                >•</span
                                            >

                                            <Clock class="h-3.5 w-3.5" />
                                            <span>
                                                {{
                                                    schedule?.total_duration_minutes ??
                                                    service.duration_minutes
                                                }}
                                                min
                                            </span>
                                        </div>
                                    </div>

                                    <span
                                        class="flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                        :class="
                                            assignments[
                                                service.schedule_services_id
                                            ]
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-rose-50 text-rose-600'
                                        "
                                    >
                                        <component
                                            :is="
                                                assignments[
                                                    service.schedule_services_id
                                                ]
                                                    ? CheckCircle2
                                                    : AlertCircle
                                            "
                                            class="h-3.5 w-3.5"
                                        />
                                        {{
                                            assignments[
                                                service.schedule_services_id
                                            ]
                                                ? "Assigned"
                                                : "Needs Assign"
                                        }}
                                    </span>
                                </div>

                                <div class="mt-4">
                                    <Combobox
                                        label="Assigned Employee"
                                        placeholder="Select employee"
                                        search-bar
                                        :items="employeeItems"
                                        :model-value="
                                            assignments[
                                                service.schedule_services_id
                                            ] ?? ''
                                        "
                                        @update:model-value="
                                            (val) =>
                                                requestAssign(
                                                    service.schedule_services_id,
                                                    String(val),
                                                )
                                        "
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col overflow-hidden border-t border-slate-100 bg-slate-50/70 lg:border-l lg:border-t-0"
                        >
                            <div class="px-5 pb-3 pt-5">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Available Staff
                                </p>
                            </div>

                            <div
                                class="flex-1 overflow-y-auto px-5 pb-5 space-y-2"
                            >
                                <div v-if="isFetching" class="space-y-2">
                                    <div
                                        v-for="i in 4"
                                        :key="i"
                                        class="flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 animate-pulse"
                                    >
                                        <div
                                            class="h-9 w-9 rounded-full bg-slate-200"
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
                                        v-if="
                                            !employees || employees.length === 0
                                        "
                                        class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-center"
                                    >
                                        <Users class="h-8 w-8 text-slate-300" />

                                        <p
                                            class="text-sm font-medium text-slate-500"
                                        >
                                            No nurse or caregiver available
                                        </p>

                                        <p class="text-xs text-slate-400">
                                            There are no employees assigned to
                                            this branch with a nurse or
                                            caregiver role.
                                        </p>
                                    </div>

                                    <button
                                        v-for="employee in employees"
                                        :key="employee.employee_id"
                                        type="button"
                                        :disabled="
                                            employee.is_busy ||
                                            hasRoleMismatch(employee)
                                        "
                                        class="flex w-full items-center gap-3 rounded-2xl border bg-white p-3 text-left transition"
                                        :class="[
                                            isSelected(employee.employee_id)
                                                ? 'border-primary/50 bg-primary/[0.04] ring-1 ring-primary/20'
                                                : 'border-slate-200 hover:border-primary/30 hover:bg-slate-50',
                                            employee.is_busy ||
                                            hasRoleMismatch(employee)
                                                ? 'opacity-60 cursor-not-allowed hover:border-slate-200 hover:bg-white'
                                                : !employee.is_assigned
                                                  ? 'opacity-60'
                                                  : '',
                                        ]"
                                        @click="
                                            requestAssign(
                                                activeService,
                                                String(employee.employee_id),
                                            )
                                        "
                                    >
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                                            :class="
                                                isSelected(employee.employee_id)
                                                    ? 'bg-primary text-white'
                                                    : 'bg-primary/10 text-primary'
                                            "
                                        >
                                            {{
                                                employee.first_name.charAt(0) +
                                                employee.last_name.charAt(0)
                                            }}
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-semibold text-slate-700"
                                            >
                                                {{
                                                    fullName(
                                                        employee.first_name,
                                                        "",
                                                        employee.last_name,
                                                    )
                                                }}
                                            </p>

                                            <p
                                                class="truncate text-xs text-slate-400"
                                            >
                                                {{
                                                    employee.role_name ??
                                                    "Staff"
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            v-if="employee.is_busy"
                                            class="flex shrink-0 items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-700"
                                        >
                                            <TriangleAlert
                                                class="h-3.5 w-3.5"
                                            />
                                            Conflict
                                        </div>

                                        <div
                                            v-else-if="hasRoleMismatch(employee)"
                                            class="flex shrink-0 items-center gap-1 rounded-full bg-rose-50 px-2 py-1 text-[11px] font-medium text-rose-600"
                                        >
                                            <CircleHelp class="h-3.5 w-3.5" />
                                            {{ requiredRoleLabel }} only
                                        </div>

                                        <div
                                            v-else-if="!employee.is_assigned"
                                            class="flex shrink-0 items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-500"
                                        >
                                            <CircleHelp class="h-3.5 w-3.5" />
                                            Not specialized
                                        </div>

                                        <div
                                            v-else
                                            class="flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700"
                                        >
                                            <Check class="h-3.5 w-3.5" />
                                            Available
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 border-t border-slate-100 bg-white px-6 py-4"
                    >
                        <p class="text-xs text-slate-400">
                            {{ Object.keys(assignments).length }} of
                            {{ schedule?.services?.length ?? 0 }} service(s)
                            assigned
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
            </Transition>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import type { Employee } from "~/types/employee";
import type { ScheduleItem } from "~/types/schedule";
import { fullName } from "~/utils/user";
import { formatDate } from "~/utils/time";
import Combobox from "~/components/ui/Combobox.vue";
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
} from "lucide-vue-next";

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
            }[];
        },
    ): void;
}>();

const assignments = ref<Record<string, string>>({});
const employeeItems = computed(() => {
    return (props.employees ?? []).map((employee) => ({
        label:
            fullName(employee.first_name, "", employee.last_name) +
            (employee.is_busy ? " — Schedule conflict" : ""),
        value: String(employee.employee_id),
    }));
});
const activeService = ref<number | null>(null);
const { error: toastError } = useToast();

// A Medical schedule service can only be staffed by a nurse, an ADL
// service only by a caregiver — matches the hard rule the backend
// enforces unconditionally, so the picker shouldn't offer a choice that
// would just fail on submit.
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

watch(
    () => props.schedule,
    (schedule) => {
        assignments.value = {};

        if (!schedule) {
            activeService.value = null;
            return;
        }

        activeService.value =
            schedule.services?.[0]?.schedule_services_id ?? null;

        schedule.services?.forEach((service: any) => {
            const assignedEmployee = service.assignees?.[0];

            if (assignedEmployee) {
                assignments.value[service.schedule_services_id] = String(
                    assignedEmployee.employee_id,
                );
            }
        });
    },
    {
        immediate: true,
    },
);

function assign(serviceId: number | null, employeeId: string) {
    if (!serviceId) return;

    assignments.value = {
        ...assignments.value,
        [serviceId]: employeeId,
    };
}

function requestAssign(serviceId: number | null, employeeId: string) {
    if (!serviceId || !employeeId) return;

    const employee = props.employees?.find(
        (e) => String(e.employee_id) === employeeId,
    );

    // An employee with a scheduling conflict is shown in the list so the
    // conflict is visible, but can never actually be assigned — the
    // backend enforces this unconditionally, so there's no "assign
    // anyway" path here either.
    if (employee?.is_busy) {
        toastError(
            `${fullName(employee.first_name, "", employee.last_name)} already has a conflicting schedule and cannot be assigned.`,
        );
        return;
    }

    const requiredRole = requiredRoleFor(serviceId);

    if (employee && requiredRole && hasRoleMismatch(employee, serviceId)) {
        toastError(
            `Only a ${requiredRole} can be assigned to this service.`,
        );
        return;
    }

    assign(serviceId, employeeId);
}

function isSelected(employeeId: string | number) {
    return Object.values(assignments.value).includes(String(employeeId));
}

function confirm() {
    if (!props.schedule || props.isSaving) return;

    const formattedAssignments = Object.entries(assignments.value).map(
        ([schedule_services_id, employee_id]) => ({
            employee_id: Number(employee_id),
            schedule_services_id: Number(schedule_services_id),
        }),
    );

    emit("confirm", {
        schedule_id: props.schedule.schedule_id,
        assignments: formattedAssignments,
    });
}
function resetAssignmentsFromSchedule(schedule?: ScheduleItem | null) {
    assignments.value = {};

    if (!schedule) {
        activeService.value = null;
        return;
    }

    activeService.value = schedule.services?.[0]?.schedule_services_id ?? null;

    schedule.services?.forEach((service: any) => {
        const assignedEmployee = service.assignees?.[0];

        if (assignedEmployee) {
            assignments.value[service.schedule_services_id] = String(
                assignedEmployee.employee_id,
            );
        }
    });
}

watch(
    () => props.schedule,
    (schedule) => {
        resetAssignmentsFromSchedule(schedule);
    },
    {
        immediate: true,
    },
);

function close() {
    resetAssignmentsFromSchedule(props.schedule);
    emit("close");
}
</script>
