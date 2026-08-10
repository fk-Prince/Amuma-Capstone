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
                class="relative z-50 flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
            >
                <div
                    class="flex items-start justify-between border-b border-slate-100 px-6 py-5"
                >
                    <div>
                        <p
                            class="text-xs uppercase tracking-wide text-slate-400"
                        >
                            Assign Medical Staff
                        </p>

                        <h2 class="mt-1 text-lg font-semibold text-slate-800">
                            Activities of Daily Living (ADL) Booking
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ patientName }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            {{ formatDate(schedule?.scheduled_at) }}
                            •
                            {{ formatTime(schedule?.scheduled_at) }}
                        </p>
                    </div>

                    <button
                        type="button"
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
                            class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <p class="text-sm font-semibold text-slate-800">
                                Hours Booked
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{
                                    schedule?.total_hours
                                        ? `${formatDuration(Number(schedule.total_hours))} (${schedule.total_hours} hrs)`
                                        : "—"
                                }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Assigned Medical Staff
                            </p>
                            <button
                                type="button"
                                class="text-xs font-medium text-primary hover:underline"
                                @click="addAdlSlot"
                            >
                                + Add Staff
                            </button>
                        </div>

                        <div
                            v-for="(slotId, index) in adlEmployeeIds"
                            :key="index"
                            class="cursor-pointer rounded-xl border p-4 transition"
                            :class="
                                activeAdlSlot === index
                                    ? 'border-primary bg-primary/5 shadow-sm'
                                    : 'border-slate-200 bg-white hover:border-primary/30'
                            "
                            @click="activeAdlSlot = index"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <p class="text-sm font-semibold text-slate-800">
                                    Staff #{{ index + 1 }}
                                </p>

                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded-full px-2 py-1 text-[11px] font-semibold"
                                        :class="
                                            slotId
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-rose-100 text-rose-600'
                                        "
                                    >
                                        {{ slotId ? "Assigned" : "Unassigned" }}
                                    </span>

                                    <button
                                        v-if="adlEmployeeIds.length > 1"
                                        type="button"
                                        class="text-slate-400 hover:text-rose-500"
                                        @click.stop="removeAdlSlot(index)"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4" @click.stop>
                                <Combobox
                                    label="Assign Staff"
                                    placeholder="Select staff"
                                    search-bar
                                    :items="availableEmployeeItemsFor(slotId)"
                                    :model-value="slotId"
                                    @update:model-value="
                                        (value) =>
                                            assignAdl(index, String(value))
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
                                class="h-16 animate-pulse rounded-xl bg-white"
                            />
                        </div>

                        <div
                            v-else
                            class="space-y-2 overflow-y-auto max-h-[55vh]"
                        >
                            <div
                                v-if="!employeeData.length"
                                class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center"
                            >
                                <p class="text-sm text-slate-500">
                                    No employees available
                                </p>
                            </div>

                            <div
                                v-for="employee in employeeData"
                                :key="employee.employee_id"
                                class="rounded-xl border bg-white transition"
                                :class="[
                                    isSelected(employee.employee_id)
                                        ? 'border-primary bg-primary/5'
                                        : 'border-slate-200 hover:border-primary/40',
                                    employee.is_busy ||
                                    isTakenElsewhere(employee.employee_id)
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
                                        class="h-10 w-10 overflow-hidden rounded-full bg-[#EAF4F2] flex items-center justify-center shrink-0"
                                    >
                                        <img
                                            v-if="employee.avatar"
                                            :src="employee.avatar"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>

                                    <div class="flex-1 min-w-0">
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
                                            {{ employee.role_name }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="employee.is_busy"
                                        class="flex flex-col items-end gap-1"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                            ></span>
                                            Busy
                                        </span>

                                        <span
                                            class="flex items-center gap-1 text-[11px] font-medium text-amber-600 hover:underline"
                                            @click.stop="
                                                toggleConflicts(
                                                    employee.employee_id,
                                                )
                                            "
                                        >
                                            {{ employee.conflict_count ?? 0 }}
                                            {{
                                                employee.conflict_count === 1
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
                                        v-else-if="
                                            isTakenElsewhere(
                                                employee.employee_id,
                                            )
                                        "
                                        class="rounded-full bg-slate-200 px-2 py-1 text-xs text-slate-500"
                                    >
                                        Already assigned
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-700"
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
                                    class="space-y-2 border-t border-slate-100 bg-slate-50/50 p-3"
                                >
                                    <div
                                        v-for="(
                                            conflict, idx
                                        ) in employee.conflict_schedules"
                                        :key="idx"
                                        class="rounded-lg border border-amber-100 bg-white p-2.5 text-xs"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                class="font-semibold text-slate-700"
                                            >
                                                {{ conflict.schedule_code }}
                                            </span>
                                            <span
                                                class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                                :class="
                                                    conflict.category ===
                                                    'medical'
                                                        ? 'bg-sky-100 text-sky-700'
                                                        : 'bg-violet-100 text-violet-700'
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

                                        <p class="mt-1 text-slate-500">
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
                                                v-if="conflict.duration_minutes"
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
                                            class="mt-1 capitalize text-slate-400"
                                        >
                                            {{ conflict.status }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm text-slate-500 transition hover:bg-slate-100"
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
import { fullName } from "~/utils/user";
import { formatDate, formatTime, formatDuration } from "~/utils/time";
import Combobox from "~/components/ui/Combobox.vue";
import { employeeService } from "~/api/employee/EmployeeService";
import type { AuditRow } from "~/types/schedule";

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

const adlEmployeeIds = ref<string[]>([""]);
const activeAdlSlot = ref(0);

const patientName = computed(() => props.schedule?.patient_full_name ?? "");

const allAssignedIds = computed(() => {
    return adlEmployeeIds.value.filter((id): id is string => !!id);
});

function availableEmployeeItemsFor(currentValue: string) {
    const takenElsewhere = new Set(
        allAssignedIds.value.filter((id) => id !== currentValue),
    );

    const items = employeeData.value
        .filter((employee) => {
            const id = String(employee.employee_id);

            if (id === currentValue) return true;

            if (takenElsewhere.has(id)) return false;
            if (employee.is_busy) return false;

            return true;
        })
        .map((employee) => ({
            label:
                fullName(employee.first_name, "", employee.last_name) +
                (employee.is_busy ? " — Schedule conflict" : ""),
            value: String(employee.employee_id),
        }));

    return [{ label: "Unassigned", value: "" }, ...items];
}

function isTakenElsewhere(employeeId: string | number) {
    const id = String(employeeId);

    return adlEmployeeIds.value.some(
        (assignedId, index) =>
            assignedId === id && index !== activeAdlSlot.value,
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

    adlEmployeeIds.value = activeAssignments.length
        ? activeAssignments.map((a) => String(a.employee_id))
        : schedule.employee_id
          ? [String(schedule.employee_id)]
          : [""];

    activeAdlSlot.value = 0;
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
            adlEmployeeIds.value = [""];
            activeAdlSlot.value = 0;
            employeeData.value = [];
            expandedConflicts.value = new Set();
            return;
        }

        restoreSavedAssignments(schedule);
        expandedConflicts.value = new Set();

        fetchEmployees();
    },
    { immediate: true },
);

function addAdlSlot() {
    adlEmployeeIds.value.push("");
    activeAdlSlot.value = adlEmployeeIds.value.length - 1;
}

function removeAdlSlot(index: number) {
    if (adlEmployeeIds.value.length <= 1) return;
    adlEmployeeIds.value.splice(index, 1);
    if (activeAdlSlot.value >= adlEmployeeIds.value.length) {
        activeAdlSlot.value = adlEmployeeIds.value.length - 1;
    }
}

function assignAdl(index: number, employeeId: string) {
    adlEmployeeIds.value[index] = employeeId;
}

function isSelected(employeeId: string | number) {
    return adlEmployeeIds.value.includes(String(employeeId));
}

function handleEmployeeClick(employee: Employee) {
    if (employee.is_busy) return;
    if (isTakenElsewhere(employee.employee_id)) return;

    assignAdl(activeAdlSlot.value, String(employee.employee_id));
}

function confirm() {
    if (!props.schedule || props.isSaving) return;

    const scheduleServiceId = props.schedule.schedule_services_id ?? null;

    const assignedIds = adlEmployeeIds.value.filter((id) => id);

    const formattedAssignments = assignedIds.length
        ? assignedIds.map((employee_id) => {
              const employee = employeeData.value.find(
                  (e) => String(e.employee_id) === employee_id,
              );

              return {
                  employee_id: Number(employee_id),
                  schedule_services_id: scheduleServiceId,
                  avatar: employee?.avatar,
                  role_name: employee?.role_name,
                  employee_name: employee
                      ? fullName(employee.first_name, "", employee.last_name)
                      : "",
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
