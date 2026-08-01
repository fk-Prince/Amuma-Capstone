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

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-3"
                enter-to-class="opacity-100 scale-100 translate-y-0"
            >
                <div
                    class="relative z-50 flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                >
                    <div
                        class="flex items-start justify-between border-b border-slate-100 px-6 py-5"
                    >
                        <div>
                            <p
                                class="text-xs uppercase tracking-wide text-slate-400"
                            >
                                Assign Employee
                            </p>

                            <h2
                                class="mt-1 text-lg font-semibold text-slate-800"
                            >
                                {{ schedule?.schedule_code }}
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                {{ schedule?.patient?.full_name }}
                            </p>
                        </div>

                        <button
                            class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                            @click="close"
                        >
                            ✕
                        </button>
                    </div>

                    <div
                        class="grid flex-1 overflow-hidden lg:grid-cols-[1fr_320px]"
                    >
                        <div class="overflow-y-auto p-6 space-y-4">
                            <div
                                v-for="service in schedule?.services ?? []"
                                :key="service.schedule_services_id"
                                class="rounded-xl border p-4 transition cursor-pointer"
                                :class="
                                    activeService ===
                                    service.schedule_services_id
                                        ? 'border-primary bg-primary/5 shadow-sm'
                                        : 'border-slate-200 bg-white hover:border-primary/30'
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
                                            {{ service.service_name }}
                                        </p>

                                        <div class="flex gap-2 items-center">
                                            <p
                                                class="mt-1 text-xs text-slate-400"
                                            >
                                                {{
                                                    formatDate(
                                                        schedule?.scheduled_at,
                                                    )
                                                }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-slate-400"
                                            >
                                                •
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-slate-400"
                                            >
                                                <!-- {{ service.duration_minutes }} -->
                                                {{
                                                    schedule?.total_duration_minutes ??
                                                    service.duration_minutes
                                                }}
                                                minutes
                                            </p>
                                        </div>
                                    </div>

                                    <span
                                        class="rounded-full px-2 py-1 text-[11px] font-semibold"
                                        :class="
                                            assignments[
                                                service.schedule_services_id
                                            ]
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-rose-100 text-rose-600'
                                        "
                                    >
                                        {{
                                            assignments[
                                                service.schedule_services_id
                                            ]
                                                ? "Assigned"
                                                : "Need Assign"
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
                                                assign(
                                                    service.schedule_services_id,
                                                    String(val),
                                                )
                                        "
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="border-t border-slate-100 bg-slate-50 p-5 lg:border-l lg:border-t-0"
                        >
                            <p
                                class="mb-4 text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Employees
                            </p>
                            <div v-if="isFetching" class="space-y-2">
                                <div
                                    v-for="i in 4"
                                    :key="i"
                                    class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 animate-pulse"
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

                            <div v-else class="space-y-2">
                                <div
                                    v-if="!employees || employees.length === 0"
                                    class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-8 w-8 text-slate-300"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>

                                    <p
                                        class="text-sm font-medium text-slate-500"
                                    >
                                        No nurse or caregiver available
                                    </p>

                                    <p class="text-xs text-slate-400">
                                        There are no employees assigned to this
                                        branch with a nurse or caregiver role.
                                    </p>
                                </div>
                                <button
                                    v-for="employee in employees"
                                    :key="employee.employee_id"
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-xl border bg-white p-3 text-left transition hover:border-primary"
                                    :class="[
                                        isSelected(employee.employee_id)
                                            ? 'border-primary bg-primary/5'
                                            : 'border-slate-200',
                                        employee.is_busy ||
                                        !employee.is_assigned
                                            ? 'opacity-70 hover:border-slate-200'
                                            : '',
                                    ]"
                                    @click="
                                        assign(
                                            activeService,
                                            String(employee.employee_id),
                                        )
                                    "
                                >
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary"
                                    >
                                        {{
                                            employee.first_name.charAt(0) +
                                            employee.last_name.charAt(0)
                                        }}
                                    </div>

                                    <div class="flex-1">
                                        <p
                                            class="text-sm font-semibold text-slate-700"
                                        >
                                            {{
                                                fullName(
                                                    employee.first_name,
                                                    "",
                                                    employee.last_name,
                                                )
                                            }}
                                        </p>

                                        <p class="text-xs text-slate-400">
                                            {{ employee.role_name ?? "Staff" }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="employee.is_busy"
                                        class="flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-3.5 w-3.5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.28 11.18c.75 1.334-.213 2.98-1.742 2.98H3.72c-1.53 0-2.493-1.646-1.743-2.98l6.28-11.18zM11 14a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V7a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Schedule conflict
                                    </div>

                                    <div
                                        v-else-if="!employee.is_assigned"
                                        class="flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-500"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-3.5 w-3.5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Not assigned
                                    </div>

                                    <div
                                        v-else
                                        class="flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-3.5 w-3.5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Available
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4"
                    >
                        <button
                            class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:bg-slate-100"
                            @click="close"
                        >
                            Cancel
                        </button>

                        <button
                            class="flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSaving"
                            @click="confirm"
                        >
                            <svg
                                v-if="isSaving"
                                class="h-4 w-4 animate-spin"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"
                                />
                            </svg>

                            {{ isSaving ? "Assigning..." : "Assign" }}
                        </button>
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

function close() {
    emit("close");
}
</script>
