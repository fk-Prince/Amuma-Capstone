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
                v-if="schedule"
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
                        class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                        :class="scheduleStatusTheme(schedule.status).badge"
                    >
                        {{ scheduleStatusLabel(schedule.status) }}
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

                    <div
                        v-if="isFacilitySchedule"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-3"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600"
                                >
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M3 21h18" />
                                        <path
                                            d="M5 21V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14"
                                        />
                                        <path d="M9 9h6" />
                                        <path d="M9 13h6" />
                                        <path d="M9 17h2" />
                                    </svg>
                                </div>

                                <div>
                                    <p
                                        class="text-sm font-semibold tracking-tight text-slate-800"
                                    >
                                        Facility Admission
                                    </p>

                                    <p class="text-xs text-slate-400">
                                        Current room assignment
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="
                                    schedule.patient?.is_admitted
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-amber-100 text-amber-700'
                                "
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="
                                        schedule.patient?.is_admitted
                                            ? 'bg-emerald-500'
                                            : 'bg-amber-500'
                                    "
                                />

                                {{
                                    schedule.patient?.is_admitted
                                        ? "Currently Admitted"
                                        : "Not Admitted"
                                }}
                            </span>
                        </div>

                        <div
                            v-if="
                                schedule.patient?.is_admitted &&
                                schedule.patient?.admission
                            "
                            class="p-4"
                        >
                            <div
                                class="mb-4 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3"
                            >
                                <div>
                                    <p
                                        class="text-[11px] font-medium uppercase tracking-wider text-emerald-600"
                                    >
                                        Assigned Accommodation
                                    </p>

                                    <p
                                        class="mt-0.5 text-sm font-semibold text-emerald-900"
                                    >
                                        {{
                                            schedule.patient.admission.bed?.room
                                                ?.room_type ?? "—"
                                        }}

                                        <span class="mx-1 text-emerald-300"
                                            >•</span
                                        >

                                        Room
                                        {{
                                            schedule.patient.admission.bed?.room
                                                ?.room_no ?? "—"
                                        }}

                                        <span class="mx-1 text-emerald-300"
                                            >•</span
                                        >

                                        Bed
                                        {{
                                            schedule.patient.admission.bed
                                                ?.bed_no ?? "—"
                                        }}

                                        <span class="mx-1 text-emerald-300"
                                            >•</span
                                        >

                                        {{
                                            schedule.patient.admission.bed?.room
                                                ?.floor ?? "—"
                                        }}
                                        Floor
                                    </p>
                                </div>

                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm"
                                >
                                    <svg
                                        width="17"
                                        height="17"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M3 7v11" />
                                        <path
                                            d="M21 18v-7a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v7"
                                        />
                                        <path d="M3 15h18" />
                                        <path d="M5 11h4" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex items-center gap-3 border-t border-slate-100 bg-amber-50/60 px-4 py-4"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600"
                            >
                                <svg
                                    width="17"
                                    height="17"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                    <path
                                        d="M10.3 3.8 2.9 17a2 2 0 0 0 1.75 3h14.7a2 2 0 0 0 1.75-3l-7.4-13.2a2 2 0 0 0-3.4 0Z"
                                    />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-800">
                                    Patient is not currently admitted
                                </p>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    No active facility admission or room
                                    assignment was found.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-sky-100 bg-sky-50 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-600"
                            >
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M12 21s-7-5.686-7-11a7 7 0 1 1 14 0c0 5.314-7 11-7 11z"
                                    />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-sky-600"
                                >
                                    Homecare Address
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-800"
                                >
                                    {{
                                        schedule.patient?.address ??
                                        "No address on file"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="isEditing" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <Combobox
                                v-model="form.status"
                                label="Status"
                                placeholder="Select status"
                                :items="statusItems"
                            />
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
                                <!-- -
                                {{ schedule.end_time }} -->
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
                                        v-if="service.assignees"
                                        class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
                                    >
                                        Assigned Employee
                                        <span
                                            v-if="service.assignees?.length > 1"
                                        >
                                            s
                                        </span>
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
                                                    v-if="employee.avatar"
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
                            class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
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
                            v-if="schedule?.status !== 'cancelled'"
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

    <ConfirmDialog
        :open="cancelConfirmOpen"
        title="Cancel this schedule?"
        message="This action cannot be undone."
        description="The payment will be refunded if there is any, and the invoice will be voided."
        confirm-label="Cancel Schedule"
        cancel-label="Keep Schedule"
        variant="danger"
        :loading="submitLoading"
        @confirm="confirmCancelSchedule"
        @cancel="cancelConfirmOpen = false"
    />
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import { getLocalDateStr } from "~/utils/time";
import { generateAvailableAmPmTimes } from "~/utils/time-slot";
import { fullName } from "~/utils/user";
import type { Employee } from "~/types/employee";
import type { ScheduleItem } from "~/types/schedule";
import { CalendarCheck2, LoaderCircle } from "lucide-vue-next";
import { useSchedule } from "~/composables/useSchedule";

const props = defineProps<{
    open: boolean;
    schedule: ScheduleItem | null;
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
                employee_id: number | null;
                schedule_services_id: number;
            }[];
        },
    ): void;

    (e: "start-edit", schedule: ScheduleItem): void;
}>();

const { scheduleStatusTheme, scheduleStatusLabel, statusItems } = useSchedule();

const isEditing = ref(false);

const todayStr = getLocalDateStr(new Date());

const form = ref({
    date: "",
    preferred_time: "",
    status: "",
});

const assignments = ref<Record<number, string>>({});

const errors = ref<Record<string, string>>({});

const isFacilitySchedule = computed(
    () => props.schedule?.category?.toLowerCase() === "facility",
);

const employeeItems = computed(() => [
    {
        label: "Unassigned",
        value: "",
    },
    ...(props.employees ?? []).map((employee) => ({
        label: fullName(employee.first_name, "", employee.last_name),
        value: String(employee.employee_id),
    })),
]);

const availableTimeSlots = computed(() =>
    generateAvailableAmPmTimes(form.value.date),
);

const displayTime = computed(() =>
    availableTimeSlots.value.length ? "Select time" : "No available time slots",
);

watch(
    () => props.schedule,
    (schedule) => {
        resetForm();

        if (!schedule) return;

        form.value = {
            date: schedule.scheduled_date ?? "",
            preferred_time: schedule.start_time ?? "",
            status: schedule.status ?? "Pending",
        };

        schedule.services?.forEach((service) => {
            const employee = service.assignees?.[0];

            if (employee) {
                assignments.value[service.schedule_services_id] = String(
                    employee.employee_id,
                );
            }
        });
    },
    {
        immediate: true,
    },
);

function resetForm() {
    isEditing.value = false;
    assignments.value = {};
    errors.value = {};
}

function startEdit() {
    if (!props.schedule) return;
    if (props.schedule.status === "cancelled") return;

    isEditing.value = true;

    form.value = {
        date: props.schedule.scheduled_date ?? "",
        preferred_time: props.schedule.start_time ?? "",
        status: props.schedule.status ?? "Pending",
    };

    assignments.value = {};

    props.schedule.services?.forEach((service) => {
        const employee = service.assignees?.[0];

        if (employee) {
            assignments.value[service.schedule_services_id] = String(
                employee.employee_id,
            );
        }
    });

    emit("start-edit", props.schedule);
}

function cancelEdit() {
    resetForm();
}

function assign(serviceId: number, employeeId: string) {
    const updated = { ...assignments.value };

    if (!employeeId) {
        delete updated[serviceId];
    } else {
        updated[serviceId] = employeeId;
    }

    assignments.value = updated;
}

function update(key: keyof typeof form.value, value: string) {
    form.value[key] = value;
    delete errors.value[key];
}

function validate() {
    errors.value = {};

    if (!form.value.date) {
        errors.value.date = "Please select a schedule date.";
    }

    if (!form.value.preferred_time) {
        errors.value.preferred_time = "Please select a preferred time.";
    }

    return !Object.keys(errors.value).length;
}

const cancelConfirmOpen = ref(false);

function buildSchedulePayload() {
    if (!props.schedule) return null;

    return {
        schedule_id: props.schedule.schedule_id,
        status: form.value.status,
        date: form.value.date,
        preferred_time: form.value.preferred_time,
        assignments: (props.schedule.services ?? []).map((service) => ({
            schedule_services_id: service.schedule_services_id,
            employee_id: assignments.value[service.schedule_services_id]
                ? Number(assignments.value[service.schedule_services_id])
                : null,
        })),
    };
}

function handleSchedule() {
    if (!props.schedule) return;

    if (!validate()) return;

    if (form.value.status === "cancelled") {
        cancelConfirmOpen.value = true;
        return;
    }

    const payload = buildSchedulePayload();

    if (payload) {
        emit("schedule", payload);
    }
}

function confirmCancelSchedule() {
    cancelConfirmOpen.value = false;

    const payload = buildSchedulePayload();

    if (payload) {
        emit("schedule", payload);
    }
}

function close() {
    resetForm();
    emit("close");
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
