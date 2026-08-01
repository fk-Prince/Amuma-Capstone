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
                            {{
                                isAdl
                                    ? "Activities of Daily Living (ADL) Booking"
                                    : "New Booking"
                            }}
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ patientName }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            {{ formatDate(booking?.booking_data.service.date) }}
                            •
                            {{
                                formatTime(
                                    booking?.booking_data.service.prefered_time,
                                )
                            }}
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
                        <template v-if="isAdl">
                            <div
                                class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                            >
                                <p class="text-sm font-semibold text-slate-800">
                                    Hours Booked
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{
                                        booking?.booking_data?.service
                                            ?.time_span
                                            ? `${formatDuration(Number(booking.booking_data.service.time_span))} (${booking.booking_data.service.time_span} hrs)`
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
                                    <p
                                        class="text-sm font-semibold text-slate-800"
                                    >
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
                                            {{
                                                slotId
                                                    ? "Assigned"
                                                    : "Need Assign"
                                            }}
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
                                        :items="
                                            availableEmployeeItemsFor(slotId)
                                        "
                                        :model-value="slotId"
                                        @update:model-value="
                                            (value) =>
                                                assignAdl(index, String(value))
                                        "
                                    />
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div
                                v-for="service in booking?.booking_data.service
                                    .services ?? []"
                                :key="service.service_id"
                                class="cursor-pointer rounded-xl border p-4 transition"
                                :class="
                                    activeService === service.service_id
                                        ? 'border-primary bg-primary/5 shadow-sm'
                                        : 'border-slate-200 bg-white hover:border-primary/30'
                                "
                                @click="activeService = service.service_id"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-800"
                                        >
                                            {{ service.service_name }}
                                        </p>
                                        <div
                                            class="mt-2 flex gap-2 text-xs text-slate-400"
                                        >
                                            <span>{{
                                                formatCurrency(service.price)
                                            }}</span>
                                        </div>
                                    </div>
                                    <span
                                        class="rounded-full px-2 py-1 text-[11px] font-semibold"
                                        :class="
                                            assignments[service.service_id]
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-rose-100 text-rose-600'
                                        "
                                    >
                                        {{
                                            assignments[service.service_id]
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
                                        :items="
                                            availableEmployeeItemsFor(
                                                assignments[
                                                    service.service_id
                                                ] ?? '',
                                            )
                                        "
                                        :model-value="
                                            assignments[service.service_id] ??
                                            ''
                                        "
                                        @update:model-value="
                                            (value) =>
                                                assign(
                                                    service.service_id,
                                                    String(value),
                                                )
                                        "
                                    />
                                </div>
                            </div>
                        </template>
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

                            <button
                                v-for="employee in employeeData"
                                :key="employee.employee_id"
                                type="button"
                                class="flex w-full items-center gap-3 rounded-xl border bg-white p-3 text-left transition"
                                :class="[
                                    isSelected(employee.employee_id)
                                        ? 'border-primary bg-primary/5'
                                        : 'border-slate-200 hover:border-primary/40',
                                    employee.is_busy ||
                                    isTakenElsewhere(employee.employee_id)
                                        ? 'opacity-60'
                                        : '',
                                ]"
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
                                    <p class="truncate text-xs text-slate-400">
                                        {{ employee.role_name }}
                                    </p>
                                </div>

                                <span
                                    v-if="employee.is_busy"
                                    class="rounded-full bg-amber-100 px-2 py-1 text-xs text-amber-700"
                                >
                                    Busy
                                </span>
                                <span
                                    v-else-if="
                                        isTakenElsewhere(employee.employee_id)
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
import { formatCurrency } from "~/utils/currency";
import { formatDate, formatTime, formatDuration } from "~/utils/time";
import Combobox from "~/components/ui/Combobox.vue";
import { scheduleService } from "~/api/schedule/ScheduleService";
import type { HomecareBooking } from "~/types/booking";

interface SavedAssignment {
    employee_id: number;
    service_id: number | null;
    employee_name: string;
    role_name?: string;
    avatar?: string;
}

interface BookingData {
    booking_id: number;
    reference_id: string;

    booking_data: {
        service: HomecareBooking;
        patient: {
            first_name: string;
            middle_name: string | null;
            last_name: string;
        };
    };

    assignments?: SavedAssignment[];
}

const props = defineProps<{
    open: boolean;
    booking?: BookingData | null;
    branchUuid: string;
    isSaving?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (
        e: "confirm",
        payload: {
            booking: BookingData;
            assignments: SavedAssignment[];
        },
    ): void;
}>();

const employeeData = ref<Employee[]>([]);
const isFetching = ref(false);

const assignments = ref<Record<number, string>>({});
const activeService = ref<number | null>(null);

const adlEmployeeIds = ref<string[]>([""]);
const activeAdlSlot = ref(0);

const isAdl = computed(
    () => props.booking?.booking_data?.service?.type === "ADL",
);

const patientName = computed(() => {
    const patient = props.booking?.booking_data?.patient;
    if (!patient) return "";
    return fullName(
        patient.first_name,
        patient.middle_name ?? "",
        patient.last_name,
    );
});

const allAssignedIds = computed(() => {
    return [
        ...Object.values(assignments.value),
        ...adlEmployeeIds.value,
    ].filter((id): id is string => !!id);
});

const employeeItems = computed(() => {
    return employeeData.value.map((employee) => ({
        label:
            fullName(employee.first_name, "", employee.last_name) +
            (employee.is_busy ? " — Schedule conflict" : ""),
        value: String(employee.employee_id),
    }));
});

function availableEmployeeItemsFor(currentValue: string) {
    const takenElsewhere = new Set(
        allAssignedIds.value.filter((id) => id !== currentValue),
    );

    return employeeData.value
        .filter((employee) => !takenElsewhere.has(String(employee.employee_id)))
        .map((employee) => ({
            label:
                fullName(employee.first_name, "", employee.last_name) +
                (employee.is_busy ? " — Schedule conflict" : ""),
            value: String(employee.employee_id),
        }));
}

function isTakenElsewhere(employeeId: string | number) {
    const id = String(employeeId);

    if (isAdl.value) {
        return adlEmployeeIds.value.some(
            (assignedId, index) =>
                assignedId === id && index !== activeAdlSlot.value,
        );
    }

    return Object.entries(assignments.value).some(
        ([serviceId, assignedId]) =>
            assignedId === id && Number(serviceId) !== activeService.value,
    );
}

async function fetchEmployees() {
    if (!props.booking) return;

    try {
        isFetching.value = true;

        const service = props.booking.booking_data.service;

        const payload =
            service.type === "ADL"
                ? {
                      service_ids: [] as number[],
                      date: service.date,
                      time: service.prefered_time,
                      time_span_hours: service.time_span ?? null,
                      branch_uuid: props.branchUuid,
                  }
                : {
                      service_ids:
                          service.services?.map((s) => s.service_id) ?? [],
                      date: service.date,
                      time: service.prefered_time,
                      branch_uuid: props.branchUuid,
                  };

        const response = await scheduleService.action({
            type: "available_employee",
            ...payload,
        });

        employeeData.value = response.data ?? [];
    } catch (error) {
        console.error(error);
        employeeData.value = [];
    } finally {
        isFetching.value = false;
    }
}

function restoreSavedAssignments(booking: BookingData) {
    const saved = booking.assignments ?? [];

    if (booking.booking_data.service.type === "ADL") {
        const ids = saved
            .filter((a) => a.employee_id != null)
            .map((a) => String(a.employee_id));

        adlEmployeeIds.value = ids.length ? ids : [""];
        activeAdlSlot.value = 0;
        assignments.value = {};
    } else {
        const restored: Record<number, string> = {};
        for (const a of saved) {
            if (a.service_id != null) {
                restored[a.service_id] = String(a.employee_id);
            }
        }
        assignments.value = restored;
        adlEmployeeIds.value = [""];
        activeAdlSlot.value = 0;
    }
}

watch(
    () => props.booking,
    (booking) => {
        if (!booking) {
            assignments.value = {};
            adlEmployeeIds.value = [""];
            activeAdlSlot.value = 0;
            activeService.value = null;
            employeeData.value = [];
            return;
        }

        restoreSavedAssignments(booking);

        activeService.value =
            booking.booking_data.service.services?.[0]?.service_id ?? null;

        fetchEmployees();
    },
    { immediate: true },
);

function assign(serviceId: number | null, employeeId: string) {
    if (!serviceId) return;
    assignments.value = { ...assignments.value, [serviceId]: employeeId };
}

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
    const inMedical = Object.values(assignments.value).includes(
        String(employeeId),
    );
    const inAdl = adlEmployeeIds.value.includes(String(employeeId));
    return inMedical || inAdl;
}

function handleEmployeeClick(employee: Employee) {
    if (employee.is_busy) return;
    if (isTakenElsewhere(employee.employee_id)) return;

    const employeeId = String(employee.employee_id);

    if (isAdl.value) {
        assignAdl(activeAdlSlot.value, employeeId);
    } else {
        assign(activeService.value, employeeId);
    }
}

function confirm() {
    if (!props.booking || props.isSaving) return;

    const formattedAssignments = isAdl.value
        ? adlEmployeeIds.value
              .filter((id) => id)
              .map((employee_id) => {
                  const employee = employeeData.value.find(
                      (e) => String(e.employee_id) === employee_id,
                  );

                  return {
                      employee_id: Number(employee_id),
                      service_id: null,
                      avatar: employee?.avatar,
                      role_name: employee?.role_name,
                      employee_name: employee
                          ? fullName(
                                employee.first_name,
                                "",
                                employee.last_name,
                            )
                          : "",
                  };
              })
        : Object.entries(assignments.value).map(([service_id, employee_id]) => {
              const employee = employeeData.value.find(
                  (e) => String(e.employee_id) === employee_id,
              );
              return {
                  service_id: Number(service_id),
                  employee_id: Number(employee_id),
                  avatar: employee?.avatar,
                  role_name: employee?.role_name,

                  employee_name: employee
                      ? fullName(employee.first_name, "", employee.last_name)
                      : "",
              };
          });

    emit("confirm", {
        booking: props.booking,
        assignments: formattedAssignments,
    });
}
function close() {
    emit("close");
}
</script>
