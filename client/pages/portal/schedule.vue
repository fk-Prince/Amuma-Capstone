<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import {
    CheckCircle,
    Loader,
    Clock,
    Info,
    CalendarDays,
    MapPin,
} from "lucide-vue-next";
import { patientAccessService } from "../../api/patient-access/PatientAccessService";
import { useSchedule } from "~/composables/useSchedule";
import HomecareADL from "~/components/sections/app/Patient/HomecareADL.vue";
import MedicalSchedule from "~/components/portal/MedicalSchedule.vue";
import type {
    ScheduleItem,
    ScheduleServiceItem,
    ScheduleAssignee,
} from "~/types/schedule";

useHead({ title: "Schedule" });

definePageMeta({
    layout: "portal",
});

interface LovedOne {
    patient_id: number;
    name: string;
    branch_name: string | null;
    location_type: "facility" | "homecare" | "none";
    status: string;
    adl: ScheduleItem | null;
    medical: ScheduleItem | null;
}

const isLoading = ref(true);
const loadError = ref<string | null>(null);
const lovedOnes = ref<LovedOne[]>([]);
const selectedIndex = ref(0);

const activeScheduleType = ref<"adl" | "medical">("adl");
const scheduleLogs = ref<ScheduleItem[]>([]);
const logsLoading = ref(false);
const scheduleLoading = ref(false);

const lovedOne = computed<LovedOne | null>(
    () => lovedOnes.value[selectedIndex.value] ?? null,
);

const lovedOneSchedules = computed<ScheduleItem[]>(() =>
    [lovedOne.value?.adl, lovedOne.value?.medical].filter(
        (item): item is ScheduleItem => Boolean(item),
    ),
);

const { selectedDate, dateList, scheduleCoversDate } = useSchedule({
    get schedules() {
        return lovedOneSchedules.value;
    },
});

const completedCount = computed(
    () =>
        lovedOneSchedules.value.filter((s) => s.status === "completed").length,
);

const upcomingCount = computed(
    () =>
        lovedOneSchedules.value.filter((s) =>
            ["pending", "confirmed"].includes(s.status),
        ).length,
);

const ongoingCount = computed(
    () => lovedOneSchedules.value.filter((s) => s.status === "ongoing").length,
);

const selectedPatientInitials = computed(() => {
    const name = lovedOne.value?.name;

    if (!name) return "?";

    return name
        .split(" ")
        .filter(Boolean)
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
});

const selectedPatientLocation = computed(() => {
    if (!lovedOne.value) return "No active location";

    if (lovedOne.value.location_type === "homecare") {
        return "Homecare";
    }

    if (lovedOne.value.location_type === "facility") {
        return lovedOne.value.branch_name || "Facility";
    }

    return "No active record";
});

const dayChips = computed(() =>
    dateList.value.map((iso) => {
        const date = new Date(`${iso}T00:00:00`);

        return {
            iso,
            dayName: date.toLocaleDateString("en-US", {
                weekday: "short",
            }),
            dayNumber: date.getDate(),
            month: date.toLocaleDateString("en-US", {
                month: "short",
            }),
        };
    }),
);

const todaySchedules = computed(() =>
    lovedOneSchedules.value.filter((schedule) =>
        scheduleCoversDate(schedule, selectedDate.value),
    ),
);

function primaryAssignee(
    service: ScheduleServiceItem,
): ScheduleAssignee | null {
    return service.assignees?.[0] ?? null;
}

function initials(name?: string | null) {
    if (!name) return "?";

    return name
        .split(" ")
        .filter(Boolean)
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
}

function statusStyle(status: string) {
    const map: Record<string, { badge: string; dot: string }> = {
        admitted: {
            badge: "bg-emerald-50 text-emerald-700 border-emerald-100",
            dot: "bg-emerald-500",
        },
        pending: {
            badge: "bg-amber-50 text-amber-700 border-amber-100",
            dot: "bg-amber-500",
        },
        ongoing: {
            badge: "bg-blue-50 text-blue-700 border-blue-100",
            dot: "bg-blue-500",
        },
        homecare: {
            badge: "bg-brand-50 text-brand-700 border-brand-100",
            dot: "bg-brand-500",
        },
        discharged: {
            badge: "bg-gray-100 text-gray-600 border-gray-200",
            dot: "bg-gray-400",
        },
        cancelled: {
            badge: "bg-gray-100 text-gray-600 border-gray-200",
            dot: "bg-gray-400",
        },
        "no active record": {
            badge: "bg-gray-100 text-gray-600 border-gray-200",
            dot: "bg-gray-400",
        },
    };

    return (
        map[status?.toLowerCase()] ?? {
            badge: "bg-gray-100 text-gray-600 border-gray-200",
            dot: "bg-gray-400",
        }
    );
}

function mapScheduleRecord(item: any): ScheduleItem | null {
    if (!item) return null;

    return {
        schedule_id: Number(item.schedule_id ?? 0),
        schedule_code: item.schedule_code ?? "",
        status: item.status ?? "",
        category: item.category ?? null,
        scheduled_date: item.scheduled_date ?? null,
        scheduled_at: item.scheduled_at ?? null,
        start_time: item.start_time ?? null,
        end_time: item.end_time ?? null,
        total_hours: Number(item.total_hours ?? 0),
        total_duration_minutes: Number(item.total_duration_minutes ?? 0),
        services: Array.isArray(item.services) ? item.services : [],
        type: item.type,
    };
}

async function loadScheduleLogs() {
    if (!lovedOne.value) {
        scheduleLogs.value = [];
        return;
    }

    logsLoading.value = true;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "schedule",
            type: activeScheduleType.value,
            patient_id: lovedOne.value.patient_id,
        });

        scheduleLogs.value = Array.isArray(res?.data) ? res.data : [];
    } catch (err) {
        console.error("Error loading schedule history:", err);
        scheduleLogs.value = [];
    } finally {
        logsLoading.value = false;
    }
}

async function loadPatientSchedule() {
    const patient = lovedOne.value;

    if (!patient) return;

    scheduleLoading.value = true;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "schedule",
            patient_id: patient.patient_id,
        });

        const data = res?.data ?? {};

        const target = lovedOnes.value.find(
            (lo) => lo.patient_id === patient.patient_id,
        );

        if (target) {
            target.adl = mapScheduleRecord(data?.schedule?.adl);
            target.medical = mapScheduleRecord(data?.schedule?.medical);
        }

        if (!todaySchedules.value.length && dateList.value.length) {
            const upcoming =
                dateList.value.find((d) => d !== selectedDate.value) ??
                dateList.value[0];

            if (upcoming) {
                selectedDate.value = upcoming;
            }
        }
    } catch (err) {
        console.error("Error loading patient schedule:", err);
    } finally {
        scheduleLoading.value = false;
    }
}

watch(selectedIndex, async () => {
    await Promise.all([loadPatientSchedule(), loadScheduleLogs()]);
});

watch(activeScheduleType, loadScheduleLogs);

async function loadPatients() {
    isLoading.value = true;
    loadError.value = null;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "profile",
        });

        const records: any[] = Array.isArray(res?.data) ? res.data : [];

        if (records.length) {
            lovedOnes.value = records.map((item: any) => {
                const ctx = item?.location_context ?? {};

                const locationType: LovedOne["location_type"] =
                    ctx?.type === "facility" ||
                    ctx?.type === "admission_fallback"
                        ? "facility"
                        : ctx?.type === "homecare"
                          ? "homecare"
                          : "none";

                return {
                    patient_id: Number(item?.patient?.patient_id ?? 0),
                    name: item?.patient?.full_name || "Unnamed Resident",
                    branch_name: item?.organization?.name ?? null,
                    location_type: locationType,
                    status: ctx?.status ?? "",
                    adl: null,
                    medical: null,
                };
            });

            selectedIndex.value = 0;
        } else {
            loadError.value = "No patients found.";
        }
    } catch (err: any) {
        console.error("Error loading patients:", err);
        loadError.value = err?.message || "Failed to load patients.";
    } finally {
        isLoading.value = false;
    }
}

async function initialLoad() {
    await loadPatients();

    if (lovedOnes.value.length) {
        await Promise.all([loadPatientSchedule(), loadScheduleLogs()]);
    }
}

onMounted(initialLoad);
</script>

<template>
    <div class="space-y-5 p-4 sm:p-6 lg:p-8">
        <template v-if="isLoading">
            <div
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm animate-pulse"
            >
                <div class="h-4 w-28 rounded bg-gray-100 mb-5" />

                <div class="flex gap-3 overflow-hidden">
                    <div
                        v-for="i in 3"
                        :key="i"
                        class="w-[220px] shrink-0 rounded-2xl border border-gray-100 p-4"
                    >
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-full bg-gray-100" />

                            <div class="flex-1 space-y-2">
                                <div class="h-3.5 w-24 rounded bg-gray-100" />
                                <div class="h-3 w-16 rounded bg-gray-100" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm animate-pulse"
                >
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-gray-100" />

                        <div class="space-y-2">
                            <div class="h-3 w-20 rounded bg-gray-100" />
                            <div class="h-6 w-8 rounded bg-gray-100" />
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm animate-pulse"
            >
                <div class="flex items-center justify-between mb-5">
                    <div class="space-y-2">
                        <div class="h-4 w-32 rounded bg-gray-100" />
                        <div class="h-3 w-48 rounded bg-gray-100" />
                    </div>

                    <div class="h-9 w-56 rounded-xl bg-gray-100" />
                </div>

                <div class="flex gap-2 overflow-hidden">
                    <div
                        v-for="i in 7"
                        :key="i"
                        class="h-16 w-14 shrink-0 rounded-xl bg-gray-100"
                    />
                </div>
            </div>

            <div
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm animate-pulse"
            >
                <div class="h-4 w-32 rounded bg-gray-100 mb-5" />

                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="i in 6"
                        :key="i"
                        class="rounded-2xl border border-gray-100 p-4 space-y-4"
                    >
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-gray-100" />

                            <div class="flex-1 space-y-2">
                                <div class="h-3.5 w-28 rounded bg-gray-100" />
                                <div class="h-3 w-20 rounded bg-gray-100" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="h-3 w-full rounded bg-gray-100" />
                            <div class="h-3 w-4/5 rounded bg-gray-100" />
                        </div>

                        <div class="h-8 w-full rounded-xl bg-gray-100" />
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="loadError">
            <div
                class="rounded-2xl border border-gray-100 bg-white px-6 py-12 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-500"
                >
                    <Info class="h-5 w-5" />
                </div>

                <p class="mt-4 text-sm font-medium text-gray-800">
                    Unable to load schedule
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    {{ loadError }}
                </p>

                <button
                    type="button"
                    @click="initialLoad"
                    class="mt-5 rounded-xl bg-brand-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-600"
                >
                    Try again
                </button>
            </div>
        </template>

        <template v-else>
            <section
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            Loved Ones
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            Select a patient to view their schedule
                        </p>
                    </div>

                    <div
                        v-if="lovedOne"
                        class="flex items-center gap-2 text-xs text-gray-400"
                    >
                        <CalendarDays class="h-4 w-4" />
                        <span>
                            {{
                                todaySchedules.length
                                    ? "Schedule available"
                                    : "No schedule today"
                            }}
                        </span>
                    </div>
                </div>

                <div
                    v-if="lovedOnes.length"
                    class="flex gap-3 overflow-x-auto p-4 no-scrollbar"
                >
                    <button
                        v-for="(lo, idx) in lovedOnes"
                        :key="lo.patient_id"
                        type="button"
                        @click="selectedIndex = idx"
                        class="group flex min-w-[220px] shrink-0 items-center gap-3 rounded-2xl border p-3 text-left transition-all"
                        :class="
                            selectedIndex === idx
                                ? 'border-brand-200 bg-brand-50 shadow-sm'
                                : 'border-gray-100 bg-white hover:border-gray-200 hover:bg-gray-50'
                        "
                    >
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-4"
                            :class="
                                selectedIndex === idx
                                    ? 'bg-brand-500 text-white ring-brand-100'
                                    : 'bg-gray-100 text-gray-500 ring-gray-50'
                            "
                        >
                            {{ initials(lo.name) }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <span
                                class="block truncate text-xs font-semibold"
                                :class="
                                    selectedIndex === idx
                                        ? 'text-brand-700'
                                        : 'text-gray-800'
                                "
                            >
                                {{ lo.name }}
                            </span>

                            <span
                                v-if="lo.branch_name"
                                class="mt-0.5 flex items-center gap-1 truncate text-[11px] text-gray-400"
                            >
                                <MapPin class="h-3 w-3 shrink-0" />
                                {{ lo.branch_name }}
                            </span>

                            <span
                                class="mt-1.5 inline-flex capitalize items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10px] font-medium"
                                :class="
                                    statusStyle(
                                        lo.location_type === 'homecare'
                                            ? 'homecare'
                                            : lo.status,
                                    ).badge
                                "
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="
                                        statusStyle(
                                            lo.location_type === 'homecare'
                                                ? 'homecare'
                                                : lo.status,
                                        ).dot
                                    "
                                />
                                {{
                                    lo.location_type === "homecare"
                                        ? "Homecare"
                                        : lo.status || "Inactive"
                                }}
                            </span>
                        </span>
                    </button>
                </div>
            </section>

            <template v-if="scheduleLoading">
                <div
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-center gap-3 animate-pulse">
                        <div class="h-11 w-11 rounded-full bg-gray-100" />

                        <div class="space-y-2">
                            <div class="h-4 w-32 rounded bg-gray-100" />
                            <div class="h-3 w-24 rounded bg-gray-100" />
                        </div>
                    </div>

                    <div class="mt-5 flex gap-2 overflow-hidden">
                        <div
                            v-for="i in 7"
                            :key="i"
                            class="h-16 w-14 shrink-0 rounded-xl bg-gray-100 animate-pulse"
                        />
                    </div>
                </div>
            </template>

            <template v-else>
                <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div
                        class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                            >
                                <CheckCircle class="h-5 w-5" />
                            </span>

                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Completed
                                </p>

                                <p
                                    class="mt-0.5 text-2xl font-bold tracking-tight text-gray-900"
                                >
                                    {{ completedCount }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                            >
                                <Loader class="h-5 w-5" />
                            </span>

                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Ongoing
                                </p>

                                <p
                                    class="mt-0.5 text-2xl font-bold tracking-tight text-gray-900"
                                >
                                    {{ ongoingCount }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600"
                            >
                                <Clock class="h-5 w-5" />
                            </span>

                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Upcoming
                                </p>

                                <p
                                    class="mt-0.5 text-2xl font-bold tracking-tight text-gray-900"
                                >
                                    {{ upcomingCount }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
                >
                    <div
                        class="flex flex-col gap-4 border-b border-gray-100 p-5 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                            >
                                <CalendarDays class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Schedule
                                </p>

                                <p class="text-xs text-gray-400">
                                    {{ lovedOne?.name || "Selected patient" }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="inline-flex w-full rounded-xl bg-gray-50 p-1 lg:w-auto"
                        >
                            <button
                                type="button"
                                @click="activeScheduleType = 'adl'"
                                class="flex-1 rounded-lg px-4 py-2 text-xs font-semibold transition-all lg:flex-none"
                                :class="
                                    activeScheduleType === 'adl'
                                        ? 'bg-white text-brand-600 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-700'
                                "
                            >
                                ADL
                            </button>

                            <button
                                type="button"
                                @click="activeScheduleType = 'medical'"
                                class="flex-1 rounded-lg px-4 py-2 text-xs font-semibold transition-all lg:flex-none"
                                :class="
                                    activeScheduleType === 'medical'
                                        ? 'bg-white text-brand-600 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-700'
                                "
                            >
                                Medical
                            </button>
                        </div>
                    </div>

                    <div class="border-b border-gray-100 px-5 py-4">
                        <div
                            v-if="dayChips.length"
                            class="flex gap-2 overflow-x-auto pb-1 no-scrollbar"
                        >
                            <button
                                v-for="day in dayChips"
                                :key="day.iso"
                                type="button"
                                @click="selectedDate = day.iso"
                                class="flex w-14 shrink-0 flex-col items-center rounded-xl border px-2 py-2 transition-all"
                                :class="
                                    selectedDate === day.iso
                                        ? 'border-brand-500 bg-brand-500 text-white shadow-sm'
                                        : 'border-gray-100 bg-white text-gray-500 hover:border-gray-200 hover:bg-gray-50'
                                "
                            >
                                <span
                                    class="text-[10px] font-semibold uppercase"
                                >
                                    {{ day.dayName }}
                                </span>

                                <span class="mt-0.5 text-base font-bold">
                                    {{ day.dayNumber }}
                                </span>

                                <span
                                    class="text-[9px]"
                                    :class="
                                        selectedDate === day.iso
                                            ? 'text-white/70'
                                            : 'text-gray-400'
                                    "
                                >
                                    {{ day.month }}
                                </span>
                            </button>
                        </div>

                        <div
                            v-else
                            class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center"
                        >
                            <p class="text-xs font-medium text-gray-500">
                                No schedule dates available
                            </p>
                        </div>
                    </div>

                    <div class="p-5">
                        <div
                            v-if="activeScheduleType === 'adl'"
                            class="overflow-hidden"
                        >
                            <HomecareADL
                                :logs="scheduleLogs"
                                :loading="logsLoading"
                                :variant="3"
                                @refresh="loadScheduleLogs"
                            />
                        </div>

                        <MedicalSchedule
                            v-else
                            :logs="scheduleLogs"
                            :loading="logsLoading"
                        />
                    </div>
                </section>

                <section
                    v-if="activeScheduleType === 'adl'"
                    class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm"
                >
                    <div class="flex gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600"
                        >
                            <Info class="h-5 w-5" />
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-blue-900">
                                Attendance is tracked via QR check-in
                            </p>

                            <p
                                class="mt-1 text-xs leading-relaxed text-blue-700/80"
                            >
                                Caregivers scan a QR code when they arrive and
                                leave. The check-in and check-out times shown in
                                the schedule reflect the latest recorded visit.
                            </p>
                        </div>
                    </div>
                </section>
            </template>
        </template>
    </div>
</template>
