<template>
    <Transition name="modal">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                @click="close"
            />

            <div
                class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
            >
                <div
                    class="flex items-start justify-between border-b border-slate-100 px-5 py-4"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-slate-400"
                        >
                            Schedule Details
                        </p>

                        <h2 class="mt-1 text-lg font-semibold text-slate-800">
                            {{ schedule.schedule_code }}
                        </h2>
                    </div>

                    <span
                        v-if="!isEditing"
                        class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="statusTheme(schedule.status)"
                    >
                        {{ schedule.status }}
                    </span>
                </div>

                <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5">
                    <div
                        class="flex items-center gap-3 rounded-xl bg-slate-50 p-3"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary"
                        >
                            <svg
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 20c0-4 3.5-6 8-6s8 2 8 6" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-xs text-slate-400">Patient</p>
                            <p class="font-semibold text-slate-800">
                                {{ schedule.patient?.full_name }}
                            </p>
                        </div>
                    </div>

                    <div v-if="isEditing" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <BaseInput
                                :model-value="form.date"
                                label="Select Schedule Date"
                                mode="date"
                                :min="todayStr"
                                :error="errors.date"
                                required
                                @update:model-value="update('date', $event)"
                            />

                            <Combobox
                                :model-value="form.preferred_time"
                                :placeholder="displayTime"
                                label="Preferred Time"
                                :items="availableTimeSlots"
                                :error="errors.preferred_time"
                                required
                                @update:model-value="
                                    update('preferred_time', $event)
                                "
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <Combobox
                                v-model="form.status"
                                label="Status"
                                placeholder="Select status"
                                :items="statusItems"
                            />
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-3 gap-3">
                        <div class="rounded-xl border border-slate-100 p-3">
                            <p class="text-xs text-slate-400">Date</p>
                            <p class="mt-1 text-sm font-medium text-slate-700">
                                {{ schedule.scheduled_date }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-100 p-3">
                            <p class="text-xs text-slate-400">Time</p>
                            <p class="mt-1 text-sm font-medium text-slate-700">
                                {{ schedule.start_time }}
                                -
                                {{ schedule.end_time }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-100 p-3">
                            <p class="text-xs text-slate-400">Category</p>
                            <p class="mt-1 text-sm font-medium text-slate-700">
                                {{ schedule.category }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-semibold text-slate-800">
                            Services
                        </p>

                        <div class="space-y-2">
                            <div
                                v-for="service in schedule.services"
                                :key="service.schedule_services_id"
                                class="rounded-xl border border-slate-100 p-3"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-800"
                                        >
                                            {{ service.service_name }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-xs text-slate-400"
                                        >
                                            {{ service.type }}
                                            •
                                            {{ service.duration_minutes }} mins
                                        </p>
                                    </div>

                                    <span
                                        v-if="!isEditing"
                                        class="rounded-full px-2 py-1 text-[11px] font-medium"
                                        :class="
                                            service.assignees?.length
                                                ? 'bg-primary/10 text-primary'
                                                : 'bg-rose-100 text-rose-600'
                                        "
                                    >
                                        {{
                                            service.assignees?.length
                                                ? "Assigned"
                                                : "Needs Assignment"
                                        }}
                                    </span>
                                </div>

                                <div v-if="isEditing" class="mt-3">
                                    <div
                                        v-if="isFetchingEmployees"
                                        class="h-11 w-full animate-pulse rounded-lg bg-slate-100"
                                    />

                                    <Combobox
                                        v-else
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

                                <div v-else class="mt-3">
                                    <p
                                        class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
                                    >
                                        Assigned Employee<span
                                            v-if="service.assignees?.length > 1"
                                            >s</span
                                        >
                                    </p>

                                    <div
                                        v-if="service.assignees?.length"
                                        class="mt-3 space-y-2"
                                    >
                                        <div
                                            v-for="employee in service.assignees"
                                            :key="employee.employee_id"
                                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
                                        >
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <img
                                                    :src="employee.avatar"
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white"
                                                />

                                                <div>
                                                    <p
                                                        class="text-sm font-semibold text-slate-800"
                                                    >
                                                        {{ employee.full_name }}
                                                    </p>

                                                    <p
                                                        class="text-xs text-slate-500"
                                                    >
                                                        Assigned Employee
                                                    </p>
                                                </div>
                                            </div>

                                            <span
                                                class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700"
                                            >
                                                Assigned
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500"
                                    >
                                        No employee has been assigned yet.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3"
                >
                    <template v-if="isEditing">
                        <button
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                            @click="cancelEdit"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="rounded-lg flex gap-2 items-center bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="submitLoading"
                            @click="handleSchedule"
                        >
                            <LoaderCircle
                                v-if="submitLoading"
                                class="h-4 w-4 animate-spin"
                            />

                            <CalendarCheck2 v-else class="h-4 w-4" />

                            {{
                                submitLoading
                                    ? "Scheduling..."
                                    : "Schedule Service"
                            }}
                        </button>
                    </template>

                    <template v-else>
                        <button
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                            @click="close"
                        >
                            Close
                        </button>

                        <button
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
                            @click="startEdit"
                        >
                            Update
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { getLocalDateStr } from "~/utils/time";
import { generateAvailableAmPmTimes } from "~/utils/time-slot";
import { fullName } from "~/utils/user";
import type { Employee } from "~/types/employee";
import { CalendarCheck2, LoaderCircle } from "lucide-vue-next";

const props = defineProps<{
    open: boolean;
    schedule: any | null;
    submitLoading?: boolean;
    employees?: Employee[];
    isFetchingEmployees?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (
        e: "schedule",
        payload: {
            schedule_id: number;
            status: string;
            date: string;
            preferred_time: string;
            assignments: {
                employee_id: number;
                schedule_services_id: number;
            }[];
        },
    ): void;
    (e: "start-edit", schedule: any): void;
}>();

const isEditing = ref(false);
const todayStr = getLocalDateStr(new Date());

const form = ref({
    date: "",
    preferred_time: "",
    status: "",
});

const assignments = ref<Record<string, string>>({});

const errors = ref<Record<string, string>>({});

const statusItems = [
    { label: "Pending", value: "Pending" },
    { label: "Completed", value: "Completed" },
    { label: "Missed", value: "Missed" },
    { label: "Cancelled", value: "Cancelled" },
    { label: "Ongoing", value: "Ongoing" },
];

const employeeItems = computed(() => {
    return (props.employees ?? []).map((employee) => ({
        label: fullName(employee.first_name, "", employee.last_name),
        value: String(employee.employee_id),
    }));
});

const availableTimeSlots = computed(() =>
    generateAvailableAmPmTimes(form.value.date),
);

const displayTime = computed(() =>
    availableTimeSlots.value.length ? "Select time" : "No available time slots",
);

watch(
    () => props.schedule,
    (schedule) => {
        isEditing.value = false;
        assignments.value = {};
        if (!schedule) return;

        form.value = {
            date: schedule.scheduled_date ?? "",
            preferred_time: schedule.start_time ?? "",
            status: schedule.status ?? "pending",
        };

        schedule.services?.forEach((service: any) => {
            const assignedEmployee = service.assignees?.[0];
            if (assignedEmployee) {
                assignments.value[service.schedule_services_id] = String(
                    assignedEmployee.employee_id,
                );
            }
        });
    },
    { immediate: true },
);

function startEdit() {
    if (!props.schedule) return;

    form.value = {
        date: props.schedule.scheduled_date ?? "",
        preferred_time: props.schedule.start_time ?? "",
        status: props.schedule.status ?? "pending",
    };

    assignments.value = {};
    props.schedule.services?.forEach((service: any) => {
        const assignedEmployee = service.assignees?.[0];
        if (assignedEmployee) {
            assignments.value[service.schedule_services_id] = String(
                assignedEmployee.employee_id,
            );
        }
    });

    errors.value = {};
    isEditing.value = true;

    emit("start-edit", props.schedule);
}

function cancelEdit() {
    isEditing.value = false;
    errors.value = {};
}

function assign(serviceId: number, employeeId: string) {
    assignments.value = {
        ...assignments.value,
        [serviceId]: employeeId,
    };
}

function update(key: keyof typeof form.value, value: string) {
    form.value[key] = value;
    clearError(key);
}

function clearError(field: string) {
    delete errors.value[field];
}

function validate() {
    errors.value = {};

    if (!form.value.date) {
        errors.value.date = "Please select a schedule date.";
    }

    if (!form.value.preferred_time) {
        errors.value.preferred_time = "Please select a preferred time.";
    }

    return Object.keys(errors.value).length === 0;
}

function handleSchedule() {
    if (!props.schedule) return;
    if (!validate()) return;

    const formattedAssignments = Object.entries(assignments.value).map(
        ([schedule_services_id, employee_id]) => ({
            employee_id: Number(employee_id),
            schedule_services_id: Number(schedule_services_id),
        }),
    );

    emit("schedule", {
        schedule_id: props.schedule.schedule_id,
        status: form.value.status,
        date: form.value.date,
        preferred_time: form.value.preferred_time,
        assignments: formattedAssignments,
    });
}

function close() {
    isEditing.value = false;
    errors.value = {};
    emit("close");
}

function statusTheme(status?: string) {
    switch (status?.toLowerCase()) {
        case "completed":
            return "bg-primary/10 text-primary";

        case "missed":
            return "bg-rose-100 text-rose-600";

        default:
            return "bg-amber-100 text-amber-700";
    }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95) translateY(10px);
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}
</style>
