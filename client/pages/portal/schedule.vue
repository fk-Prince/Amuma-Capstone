<script setup lang="ts">
import {
    ref,
    computed,
    nextTick,
    onMounted,
    onBeforeUnmount,
    watch,
} from "vue";
import {
    Info,
    CalendarDays,
    CalendarRange,
    Building2,
    House,
    ChevronLeft,
    ChevronRight,
    HeartPulse,
    Stethoscope,
} from "lucide-vue-next";
import { patientAccessService } from "../../api/patient-access/PatientAccessService";
import HomecareADL from "~/components/sections/app/Patient/HomecareADL.vue";
import MedicalSchedule from "~/components/portal/MedicalSchedule.vue";
import EmptyState from "~/components/ui/EmptyState.vue";
import Pagination from "~/components/ui/Pagination.vue";
import type { ScheduleItem } from "~/types/schedule";

useHead({ title: "Schedule" });

definePageMeta({
    layout: "portal",
});

interface LovedOne {
    patient_id: number;
    uuid: string | null;
    name: string;
    branch_name: string | null;
    location_type: "facility" | "homecare" | "none";
    status: string;
}

interface ScheduleMeta {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

const PER_PAGE = 10;

const isLoading = ref(true);
const loadError = ref<string | null>(null);
const noPatients = ref(false);
const lovedOnes = ref<LovedOne[]>([]);
const selectedIndex = ref(0);

const { resolveIndex, syncQuery } = usePatientQuerySelection();

watch(selectedIndex, () =>
    syncQuery(lovedOnes.value[selectedIndex.value]?.uuid),
);

const lovedOnesScrollRef = ref<HTMLElement | null>(null);
const canScrollLovedOnesLeft = ref(false);
const canScrollLovedOnesRight = ref(false);

const activeScheduleType = ref<"adl" | "medical">("adl");
const scheduleLogs = ref<ScheduleItem[]>([]);
const scheduleMeta = ref<ScheduleMeta | null>(null);
const currentPage = ref(1);
const logsLoading = ref(false);

const SCHEDULE_TYPES = [
    {
        value: "adl",
        label: "Activities of Daily Living (ADL)",
        icon: HeartPulse,
    },
    { value: "medical", label: "Medical Service", icon: Stethoscope },
] as const;

const now = new Date();
const currentMonthKey = `${now.getFullYear()}-${String(
    now.getMonth() + 1,
).padStart(2, "0")}`;

const monthFilter = ref(currentMonthKey);

const lovedOne = computed<LovedOne | null>(
    () => lovedOnes.value[selectedIndex.value] ?? null,
);

const monthLabel = computed(() =>
    new Date(`${monthFilter.value}-01T00:00:00`).toLocaleDateString("en-US", {
        month: "long",
        year: "numeric",
    }),
);

const totalResults = computed(() => scheduleMeta.value?.total ?? 0);

const scheduleSummary = computed(() => {
    const label = totalResults.value === 1 ? "schedule" : "schedules";

    return monthFilter.value === currentMonthKey
        ? `${totalResults.value} ${label} this month`
        : `${totalResults.value} ${label} in ${monthLabel.value}`;
});

const selectedPatientInitials = computed(() => initials(lovedOne.value?.name));

const selectedPatientLocation = computed(() => {
    if (!lovedOne.value) {
        return "No active location";
    }

    if (lovedOne.value.location_type === "homecare") {
        return "Homecare";
    }

    if (lovedOne.value.location_type === "facility") {
        return lovedOne.value.branch_name || "Facility";
    }

    return "No active record";
});

function initials(name?: string | null) {
    if (!name) {
        return "?";
    }

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
            badge: "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20",
            dot: "bg-emerald-500",
        },
        pending: {
            badge: "bg-amber-50 text-amber-700 ring-1 ring-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20",
            dot: "bg-amber-500",
        },
        ongoing: {
            badge: "bg-blue-50 text-blue-700 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20",
            dot: "bg-blue-500",
        },
        homecare: {
            badge: "bg-primary-50 text-primary-700 ring-1 ring-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/20",
            dot: "bg-primary-500",
        },
        discharged: {
            badge: "bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
            dot: "bg-gray-400",
        },
        cancelled: {
            badge: "bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
            dot: "bg-gray-400",
        },
        "no active record": {
            badge: "bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
            dot: "bg-gray-400",
        },
    };

    return (
        map[status?.toLowerCase()] ?? {
            badge: "bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
            dot: "bg-gray-400",
        }
    );
}

function patientStatusKey(patient: LovedOne) {
    return patient.location_type === "homecare" ? "homecare" : patient.status;
}

function patientStatusLabel(patient: LovedOne) {
    return patient.location_type === "homecare"
        ? "Homecare"
        : patient.status || "Inactive";
}

function updateLovedOnesScrollState() {
    const el = lovedOnesScrollRef.value;

    if (!el) {
        canScrollLovedOnesLeft.value = false;
        canScrollLovedOnesRight.value = false;
        return;
    }

    canScrollLovedOnesLeft.value = el.scrollLeft > 4;
    canScrollLovedOnesRight.value =
        el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
}

function scrollLovedOnes(direction: 1 | -1) {
    lovedOnesScrollRef.value?.scrollBy({
        left: direction * 260,
        behavior: "smooth",
    });
}

function stepMonth(direction: 1 | -1) {
    const [year = 0, month = 1] = monthFilter.value.split("-").map(Number);
    const target = new Date(year, month - 1 + direction, 1);

    monthFilter.value = `${target.getFullYear()}-${String(
        target.getMonth() + 1,
    ).padStart(2, "0")}`;
}

async function loadScheduleLogs(page = 1) {
    const patient = lovedOne.value;

    if (!patient) {
        scheduleLogs.value = [];
        scheduleMeta.value = null;
        return;
    }

    logsLoading.value = true;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "schedule",
            type: activeScheduleType.value,
            patient_id: patient.patient_id,
            month: monthFilter.value,
            page,
            per_page: PER_PAGE,
        });

        scheduleLogs.value = Array.isArray(res?.data) ? res.data : [];
        scheduleMeta.value = res?.meta ?? null;
        currentPage.value = res?.meta?.current_page ?? page;
    } catch (err) {
        console.error("Error loading schedule history:", err);
        scheduleLogs.value = [];
        scheduleMeta.value = null;
    } finally {
        logsLoading.value = false;
    }
}

async function loadPatients() {
    isLoading.value = true;
    loadError.value = null;
    noPatients.value = false;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "profile",
        });

        const records: any[] = Array.isArray(res?.data) ? res.data : [];

        if (!records.length) {
            noPatients.value = true;
            return;
        }

        lovedOnes.value = records.map((item: any) => {
            const ctx = item?.location_context ?? {};

            const locationType: LovedOne["location_type"] =
                ctx?.type === "facility" || ctx?.type === "admission_fallback"
                    ? "facility"
                    : ctx?.type === "homecare"
                      ? "homecare"
                      : "none";

            return {
                patient_id: Number(item?.patient?.patient_id ?? 0),
                uuid: item?.patient?.uuid ?? null,
                name: item?.patient?.full_name || "Unnamed Resident",
                branch_name: item?.organization?.name ?? null,
                location_type: locationType,
                status: ctx?.status ?? "",
            };
        });

        selectedIndex.value = resolveIndex(lovedOnes.value);
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
        await loadScheduleLogs();
    }

    await nextTick();
    updateLovedOnesScrollState();
}

function updateAllScrollStates() {
    updateLovedOnesScrollState();
}

watch(selectedIndex, () => {
    currentPage.value = 1;
    loadScheduleLogs(1);
});

watch([activeScheduleType, monthFilter], () => {
    currentPage.value = 1;
    loadScheduleLogs(1);
});

onMounted(() => {
    initialLoad();
    window.addEventListener("resize", updateAllScrollStates);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", updateAllScrollStates);
});
</script>

<template>
    <div class="flex min-h-full flex-1 flex-col p-4">
        <div
            class="flex flex-1 flex-col overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-100 dark:bg-secondary dark:ring-white/10"
        >
            <template v-if="isLoading">
                <div class="flex flex-1 animate-pulse flex-col">
                    <div
                        class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-5 sm:px-7 sm:py-6 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-gray-100 dark:bg-white/10" />

                            <div class="space-y-2">
                                <div class="h-4 w-24 rounded bg-gray-100 dark:bg-white/10" />
                                <div class="h-3 w-52 rounded bg-gray-100 dark:bg-white/10" />
                            </div>
                        </div>

                        <div class="h-12 w-56 rounded-2xl bg-gray-100 dark:bg-white/10" />
                    </div>

                    <div
                        class="flex gap-3 overflow-hidden border-b border-gray-100 bg-gray-50/50 p-4 sm:px-7 dark:border-white/10 dark:bg-white/5"
                    >
                        <div
                            v-for="i in 3"
                            :key="i"
                            class="flex min-w-[230px] items-center gap-3 rounded-2xl bg-white p-3 dark:bg-secondary"
                        >
                            <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-white/10" />

                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-24 rounded bg-gray-100 dark:bg-white/10" />
                                <div class="h-2.5 w-20 rounded bg-gray-100 dark:bg-white/10" />
                                <div class="h-3 w-16 rounded-full bg-gray-100 dark:bg-white/10" />
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 px-5 py-5 sm:px-7 sm:py-6">
                        <div
                            class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
                        >
                            <div class="space-y-2">
                                <div class="h-4 w-36 rounded bg-gray-100 dark:bg-white/10" />
                                <div class="h-3 w-48 rounded bg-gray-100 dark:bg-white/10" />
                            </div>

                            <div
                                class="h-12 w-full rounded-2xl bg-gray-100 xl:w-[32rem] dark:bg-white/10"
                            />
                        </div>

                        <div class="mt-5 h-11 rounded-2xl bg-gray-50 dark:bg-white/5" />

                        <div class="mt-5 space-y-3">
                            <div
                                v-for="i in 3"
                                :key="i"
                                class="h-24 rounded-2xl bg-gray-50 dark:bg-white/5"
                            />
                        </div>
                    </div>

                    <div class="border-t border-gray-100 bg-blue-50/40 px-5 py-4 sm:px-7 dark:border-white/10 dark:bg-blue-500/10">
                        <div class="flex gap-3">
                            <div class="h-9 w-9 shrink-0 rounded-xl bg-blue-100 dark:bg-blue-500/15" />

                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-52 rounded bg-blue-100 dark:bg-blue-500/15" />
                                <div class="h-2.5 w-full max-w-lg rounded bg-blue-100/70 dark:bg-blue-500/15" />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else-if="noPatients">
                <div class="flex flex-1 items-center justify-center px-6 py-12">
                    <EmptyState
                        title="You currently have no patients"
                        cta-label="Book a Service"
                        cta-to="/booking/search"
                    />
                </div>
            </template>

            <template v-else-if="loadError">
                <div
                    class="flex flex-1 flex-col items-center justify-center px-6 py-14 text-center"
                >
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-300"
                    >
                        <Info class="h-5 w-5" />
                    </div>

                    <p class="mt-4 text-sm font-semibold text-gray-800 dark:text-white">
                        Unable to load schedule
                    </p>

                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        {{ loadError }}
                    </p>

                    <button
                        type="button"
                        class="mt-5 rounded-xl bg-primary-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-primary-600"
                        @click="initialLoad"
                    >
                        Try again
                    </button>
                </div>
            </template>

            <template v-else>
                <div
                    class="border-b border-gray-100 bg-gradient-to-r from-primary-50/70 via-white to-white px-5 py-5 sm:px-7 sm:py-6 dark:border-white/10 dark:from-primary-500/10 dark:via-secondary dark:to-secondary"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-500 text-white shadow-sm shadow-primary-500/30"
                            >
                                <CalendarDays class="h-5 w-5" />
                            </div>

                            <div>
                                <h1
                                    class="text-base font-semibold text-gray-900 dark:text-white"
                                >
                                    Schedule
                                </h1>

                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    Track every schedules for your loved one
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="lovedOne"
                            class="flex items-center gap-3 rounded-2xl bg-white/80 px-3 py-2 ring-1 ring-gray-100 backdrop-blur dark:ring-white/10"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-500 text-[11px] font-bold text-white"
                            >
                                {{ selectedPatientInitials }}
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="max-w-[180px] truncate text-xs font-semibold text-gray-800 dark:text-white"
                                >
                                    {{ lovedOne.name }}
                                </p>

                                <p
                                    class="mt-0.5 flex items-center gap-1 text-[11px] text-gray-400 dark:text-gray-500"
                                >
                                    <component
                                        :is="
                                            lovedOne.location_type ===
                                            'homecare'
                                                ? House
                                                : Building2
                                        "
                                        class="h-3 w-3 shrink-0"
                                    />
                                    <span class="truncate">
                                        {{ selectedPatientLocation }}
                                    </span>
                                </p>
                            </div>

                            <span
                                class="ml-1 inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-semibold capitalize"
                                :class="
                                    statusStyle(patientStatusKey(lovedOne))
                                        .badge
                                "
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="
                                        statusStyle(patientStatusKey(lovedOne))
                                            .dot
                                    "
                                />
                                {{ patientStatusLabel(lovedOne) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="lovedOnes.length > 1"
                    class="relative border-b border-gray-100 bg-gray-50/50 dark:border-white/10 dark:bg-white/5"
                >
                    <button
                        v-if="canScrollLovedOnesLeft"
                        type="button"
                        aria-label="Scroll left"
                        class="absolute left-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white text-gray-500 shadow-sm ring-1 ring-gray-100 transition hover:text-primary-600 dark:bg-secondary dark:text-gray-400 dark:ring-white/10 dark:hover:text-primary-300"
                        @click="scrollLovedOnes(-1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <div
                        ref="lovedOnesScrollRef"
                        class="flex gap-3 overflow-x-auto p-4 scrollbar-none sm:px-7"
                        @scroll="updateLovedOnesScrollState"
                    >
                        <button
                            v-for="(lo, idx) in lovedOnes"
                            :key="lo.patient_id"
                            type="button"
                            :aria-pressed="selectedIndex === idx"
                            class="group flex min-w-[230px] shrink-0 items-center gap-3 rounded-2xl p-3 text-left transition-all"
                            :class="
                                selectedIndex === idx
                                    ? 'bg-white shadow-sm ring-1 ring-primary-200 dark:bg-secondary dark:ring-primary-500/20'
                                    : 'ring-1 ring-transparent hover:bg-white/80 hover:ring-gray-100 dark:hover:ring-white/10'
                            "
                            @click="selectedIndex = idx"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[11px] font-bold transition"
                                :class="
                                    selectedIndex === idx
                                        ? 'bg-primary-500 text-white shadow-sm shadow-primary-500/30'
                                        : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200 dark:bg-white/10 dark:text-gray-400 dark:group-hover:bg-white/15'
                                "
                            >
                                {{ initials(lo.name) }}
                            </span>

                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-xs font-semibold"
                                    :class="
                                        selectedIndex === idx
                                            ? 'text-primary-700 dark:text-primary-300'
                                            : 'text-gray-800 dark:text-white'
                                    "
                                >
                                    {{ lo.name }}
                                </span>

                                <span
                                    v-if="lo.branch_name"
                                    class="mt-0.5 flex items-center gap-1 truncate text-[10px] text-gray-400 dark:text-gray-500"
                                >
                                    <Building2 class="h-3 w-3 shrink-0" />
                                    {{ lo.branch_name }}
                                </span>

                                <span
                                    class="mt-1.5 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[9px] font-semibold capitalize"
                                    :class="
                                        statusStyle(patientStatusKey(lo)).badge
                                    "
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            statusStyle(patientStatusKey(lo))
                                                .dot
                                        "
                                    />

                                    {{ patientStatusLabel(lo) }}
                                </span>
                            </span>
                        </button>
                    </div>

                    <button
                        v-if="canScrollLovedOnesRight"
                        type="button"
                        aria-label="Scroll right"
                        class="absolute right-2 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white text-gray-500 shadow-sm ring-1 ring-gray-100 transition hover:text-primary-600 dark:bg-secondary dark:text-gray-400 dark:ring-white/10 dark:hover:text-primary-300"
                        @click="scrollLovedOnes(1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex-1 px-5 py-5 sm:px-7 sm:py-6">
                    <div
                        class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
                    >
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                Patient Schedules
                            </p>

                            <div
                                class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-gray-400 dark:text-gray-500"
                            >
                                <span>{{ lovedOne?.name }}</span>

                                <span class="text-gray-300 dark:text-gray-500">•</span>

                                <span>{{ scheduleSummary }}</span>
                            </div>
                        </div>

                        <div
                            role="tablist"
                            aria-label="Schedule type"
                            class="grid grid-cols-2 gap-1 rounded-2xl bg-gray-100/80 p-1 xl:w-[32rem] dark:bg-white/10"
                        >
                            <button
                                v-for="type in SCHEDULE_TYPES"
                                :key="type.value"
                                type="button"
                                role="tab"
                                :aria-selected="
                                    activeScheduleType === type.value
                                "
                                class="group flex items-center justify-center gap-2 rounded-xl px-3 py-2 transition-all"
                                :class="
                                    activeScheduleType === type.value
                                        ? 'bg-white shadow-sm ring-1 ring-primary-100 dark:bg-secondary dark:ring-primary-500/20'
                                        : 'hover:bg-white/70'
                                "
                                @click="activeScheduleType = type.value"
                            >
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition"
                                    :class="
                                        activeScheduleType === type.value
                                            ? 'bg-primary-500 text-white shadow-sm shadow-primary-500/30'
                                            : 'bg-white text-gray-400 group-hover:text-primary-500 dark:bg-secondary dark:text-gray-500 dark:group-hover:text-primary-300'
                                    "
                                >
                                    <component
                                        :is="type.icon"
                                        class="h-3.5 w-3.5"
                                    />
                                </span>

                                <span
                                    class="truncate text-xs font-semibold"
                                    :class="
                                        activeScheduleType === type.value
                                            ? 'text-primary-700 dark:text-primary-300'
                                            : 'text-gray-600 dark:text-gray-400'
                                    "
                                >
                                    {{ type.label }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div
                        class="mt-5 flex items-center justify-between gap-2 rounded-2xl bg-gray-50/80 p-1.5 dark:bg-white/5"
                    >
                        <button
                            type="button"
                            aria-label="Previous month"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-gray-400 transition hover:bg-white hover:text-primary-600 hover:shadow-sm dark:text-gray-500 dark:hover:bg-secondary dark:hover:text-primary-300 dark:hover:bg-white/10"
                            @click="stepMonth(-1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>

                        <span
                            class="flex min-w-0 items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            <CalendarRange
                                class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500"
                            />
                            <span class="truncate">{{ monthLabel }}</span>
                        </span>

                        <button
                            type="button"
                            aria-label="Next month"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-gray-400 transition hover:bg-white hover:text-primary-600 hover:shadow-sm dark:text-gray-500 dark:hover:bg-secondary dark:hover:text-primary-300 dark:hover:bg-white/10"
                            @click="stepMonth(1)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="mt-5">
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

                        <Pagination
                            v-if="scheduleMeta && scheduleMeta.last_page > 1"
                            class="mt-5 overflow-x-auto"
                            :current-page="scheduleMeta.current_page"
                            :total-pages="scheduleMeta.last_page"
                            :total-items="scheduleMeta.total"
                            :items-per-page="scheduleMeta.per_page"
                            @change-page="loadScheduleLogs"
                        />
                    </div>
                </div>

                <div
                    v-if="activeScheduleType === 'adl'"
                    class="border-t border-blue-100 bg-blue-50/50 px-5 py-4 sm:px-7 dark:border-blue-500/20 dark:bg-blue-500/10"
                >
                    <div class="flex gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300"
                        >
                            <Info class="h-4 w-4" />
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-blue-900">
                                Attendance is tracked via QR check-in
                            </p>

                            <p
                                class="mt-1 text-[11px] leading-relaxed text-blue-700/80 dark:text-blue-300"
                            >
                                Caregivers scan a QR code when they arrive and
                                leave. The check-in and check-out times shown in
                                the schedule reflect the latest recorded visit.
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
