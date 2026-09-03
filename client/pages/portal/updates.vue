<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import {
    ChevronLeft,
    ChevronRight,
    CalendarDays,
    MapPin,
    UserRound,
    Activity,
    Clock3,
    HeartPulse,
    Building2,
    ClipboardList,
    AlertCircle,
    RefreshCw,
} from "lucide-vue-next";
import Icon from "./Icon.vue";
import Pagination from "~/components/ui/Pagination.vue";
import EmptyState from "~/components/ui/EmptyState.vue";
import { patientAccessService } from "../../api/patient-access/PatientAccessService";
import type { PatientActivity } from "~/types/patient-activity";

useHead({ title: "Updates" });

definePageMeta({
    layout: "portal",
});

interface LovedOne {
    patient_id: number;
    uuid: string | null;
    name: string;
    photo: string;
    branchName: string;
    locationName: string;
    status: string;
    locationType: "facility" | "homecare" | "none";
    activities: PatientActivity[];
}

const PLACEHOLDER_PHOTO = "https://placehold.co/200x200?text=Patient";

function fallbackLovedOne(): LovedOne {
    return {
        patient_id: 0,
        uuid: null,
        name: "Unnamed Resident",
        photo: PLACEHOLDER_PHOTO,
        branchName: "N/A",
        locationName: "N/A",
        status: "Inactive",
        locationType: "none",
        activities: [],
    };
}

const isLoading = ref(true);
const loadError = ref<string | null>(null);
const noPatients = ref(false);
const lovedOnes = ref<LovedOne[]>([]);
const selectedIndex = ref(0);

const { resolveIndex, syncQuery } = usePatientQuerySelection();

watch(selectedIndex, () =>
    syncQuery(lovedOnes.value[selectedIndex.value]?.uuid),
);
const itemsPerPage = 15;
const currentPage = ref(1);

const lovedOne = computed(
    () => lovedOnes.value[selectedIndex.value] ?? fallbackLovedOne(),
);

const totalItems = computed(() => lovedOne.value.activities.length);

const totalPages = computed(() =>
    Math.max(1, Math.ceil(totalItems.value / itemsPerPage)),
);

const pagedActivities = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;

    return lovedOne.value.activities.slice(start, start + itemsPerPage);
});

function prevLovedOne() {
    if (!lovedOnes.value.length) return;

    selectedIndex.value =
        (selectedIndex.value - 1 + lovedOnes.value.length) %
        lovedOnes.value.length;
}

function nextLovedOne() {
    if (!lovedOnes.value.length) return;

    selectedIndex.value = (selectedIndex.value + 1) % lovedOnes.value.length;
}

watch(selectedIndex, () => {
    currentPage.value = 1;
});

function onChangePage(page: number) {
    currentPage.value = page;
}

function statusConfig(status: string) {
    const map: Record<
        string,
        {
            label: string;
            class: string;
        }
    > = {
        admitted: {
            label: "Admitted",
            class: "bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20",
        },
        pending: {
            label: "Pending",
            class: "bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20",
        },
        ongoing: {
            label: "Ongoing",
            class: "bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20",
        },
        homecare: {
            label: "Homecare",
            class: "bg-primary-50 text-primary-700 ring-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/20",
        },
        discharged: {
            label: "Discharged",
            class: "bg-gray-100 text-gray-600 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
        },
        cancelled: {
            label: "Cancelled",
            class: "bg-gray-100 text-gray-600 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
        },
        "no active record": {
            label: "Inactive",
            class: "bg-gray-100 text-gray-600 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
        },
        inactive: {
            label: "Inactive",
            class: "bg-gray-100 text-gray-600 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
        },
    };

    return (
        map[status?.toLowerCase()] ?? {
            label: status || "Unknown",
            class: "bg-gray-100 text-gray-600 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
        }
    );
}

function deriveStatusLabel(ctx: any): string {
    if (ctx?.type === "facility" || ctx?.type === "admission_fallback") {
        return (ctx?.status ?? "Unknown")
            .toString()
            .replace(/_/g, " ")
            .replace(/\b\w/g, (c: string) => c.toUpperCase());
    }

    if (ctx?.type === "homecare") {
        return "Homecare";
    }

    return "Inactive";
}

function deriveLocationType(ctx: any): "facility" | "homecare" | "none" {
    const type = ctx?.type?.toString().toLowerCase();

    if (type === "facility" || type === "admission_fallback") {
        return "facility";
    }

    if (type === "homecare") {
        return "homecare";
    }

    return "none";
}
const defaultTypeStyle = {
    icon: "users",
    bg: "bg-amber-50 dark:bg-amber-500/10",
    text: "text-amber-600 dark:text-amber-300",
    label: "Activity",
};

const typeStyles: Record<
    string,
    {
        icon: string;
        bg: string;
        text: string;
        label: string;
    }
> = {
    appointment: {
        icon: "calendar-clock",
        bg: "bg-violet-50 dark:bg-violet-500/10",
        text: "text-violet-600 dark:text-violet-300",
        label: "Appointment",
    },
    therapy: {
        icon: "activity",
        bg: "bg-emerald-50 dark:bg-emerald-500/10",
        text: "text-emerald-600 dark:text-emerald-300",
        label: "Therapy",
    },
    meal: {
        icon: "utensils",
        bg: "bg-primary-50 dark:bg-primary-500/10",
        text: "text-primary-600 dark:text-primary-300",
        label: "Meal",
    },
    activity: defaultTypeStyle,
};

function styleFor(type: string) {
    return typeStyles[type] ?? defaultTypeStyle;
}

function isSameDay(a: Date, b: Date) {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

function dateGroupLabel(date: Date) {
    const today = new Date();
    const tomorrow = new Date(today);

    tomorrow.setDate(today.getDate() + 1);

    const formatted = date.toLocaleDateString("en-US", {
        month: "long",
        day: "numeric",
        year: "numeric",
    });

    if (isSameDay(date, today)) {
        return `Today · ${formatted}`;
    }

    if (isSameDay(date, tomorrow)) {
        return `Tomorrow · ${formatted}`;
    }

    return formatted;
}

const activityGroups = computed(() => {
    const groups: {
        key: string;
        label: string;
        items: PatientActivity[];
    }[] = [];

    for (const item of pagedActivities.value) {
        if (!item.occurredAt) continue;

        const date = new Date(item.occurredAt);

        if (Number.isNaN(date.getTime())) continue;

        const key = date.toDateString();
        let group = groups.find((g) => g.key === key);

        if (!group) {
            group = {
                key,
                label: dateGroupLabel(date),
                items: [],
            };

            groups.push(group);
        }

        group.items.push(item);
    }

    return groups;
});

function formatTime(value: string) {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "";

    return date.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
    });
}

function mapPatientRecord(item: any): LovedOne {
    const patient = item?.patient ?? {};
    const org = item?.organization ?? {};
    const ctx = item?.location_context ?? {};

    return {
        patient_id: patient.patient_id ?? 0,
        uuid: patient.uuid ?? null,
        name: patient.full_name || "Unnamed Resident",
        photo: patient.avatar || PLACEHOLDER_PHOTO,
        branchName: org.name || "N/A",
        locationName: org.full_address || "N/A",
        status: deriveStatusLabel(ctx),
        locationType: deriveLocationType(ctx),
        activities: Array.isArray(item?.activities) ? item.activities : [],
    };
}

async function loadPatientData() {
    isLoading.value = true;
    loadError.value = null;
    noPatients.value = false;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "profile,activity",
        });

        const records: any[] = Array.isArray(res?.data) ? res.data : [];

        if (records.length) {
            lovedOnes.value = records.map(mapPatientRecord);
            selectedIndex.value = resolveIndex(lovedOnes.value);
        } else {
            lovedOnes.value = [];
            noPatients.value = true;
        }
    } catch (err: any) {
        console.error("Error loading updates:", err);

        loadError.value = err?.message || "Failed to load updates.";
    } finally {
        isLoading.value = false;
    }
}

onMounted(() => {
    loadPatientData();
});
</script>

<template>
    <div class="min-h-full space-y-6 p-5">
        <div v-if="isLoading" class="space-y-5">
            <div
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="animate-pulse">
                    <div class="flex items-center gap-4">
                        <div
                            class="h-16 w-16 shrink-0 rounded-2xl bg-gray-200 dark:bg-white/15"
                        />

                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-40 rounded bg-gray-200 dark:bg-white/15" />
                            <div class="h-3 w-28 rounded bg-gray-100 dark:bg-white/10" />
                            <div class="h-5 w-20 rounded-full bg-gray-100 dark:bg-white/10" />
                        </div>
                    </div>

                    <div class="mt-5 h-12 rounded-xl bg-gray-50 dark:bg-white/5" />
                </div>
            </div>

            <div
                v-for="i in 3"
                :key="i"
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="animate-pulse">
                    <div class="mb-5 h-3 w-32 rounded bg-gray-200 dark:bg-white/15" />

                    <div class="space-y-4">
                        <div
                            v-for="row in 3"
                            :key="row"
                            class="flex items-start gap-4"
                        >
                            <div
                                class="h-4 w-14 shrink-0 rounded bg-gray-100 dark:bg-white/10"
                            />

                            <div
                                class="h-10 w-10 shrink-0 rounded-xl bg-gray-100 dark:bg-white/10"
                            />

                            <div class="flex-1 space-y-2">
                                <div class="h-3.5 w-48 rounded bg-gray-200 dark:bg-white/15" />

                                <div class="h-3 w-32 rounded bg-gray-100 dark:bg-white/10" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <EmptyState
            v-else-if="noPatients"
            title="You currently have no patients"
            cta-label="Book a Service"
            cta-to="/booking/search"
        />

        <div
            v-else-if="loadError"
            class="overflow-hidden rounded-3xl border border-rose-100 bg-white shadow-sm dark:border-rose-500/20 dark:bg-secondary"
        >
            <div class="flex flex-col items-center px-6 py-14 text-center">
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    <AlertCircle class="h-7 w-7" />
                </div>

                <h2 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
                    Unable to load updates
                </h2>

                <p class="mt-1 max-w-sm text-sm leading-6 text-gray-500 dark:text-gray-400">
                    {{ loadError }}
                </p>

                <button
                    type="button"
                    @click="loadPatientData"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    <RefreshCw class="h-4 w-4" />
                    Try again
                </button>
            </div>
        </div>

        <template v-else>
            <div
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="p-5 sm:p-6">
                    <div
                        class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-4">
                            <div class="relative shrink-0">
                                <img
                                    :src="lovedOne.photo"
                                    :alt="lovedOne.name"
                                    class="h-16 w-16 rounded-2xl object-cover object-top ring-4 ring-gray-50 dark:ring-white/10"
                                />

                                <span
                                    class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white"
                                    :class="
                                        lovedOne.locationType === 'facility'
                                            ? 'bg-violet-500'
                                            : lovedOne.locationType ===
                                                'homecare'
                                              ? 'bg-primary-500'
                                              : 'bg-gray-400'
                                    "
                                >
                                    <Building2
                                        v-if="
                                            lovedOne.locationType === 'facility'
                                        "
                                        class="h-2.5 w-2.5 text-white"
                                    />

                                    <HeartPulse
                                        v-else-if="
                                            lovedOne.locationType === 'homecare'
                                        "
                                        class="h-2.5 w-2.5 text-white"
                                    />

                                    <UserRound
                                        v-else
                                        class="h-2.5 w-2.5 text-white"
                                    />
                                </span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2
                                        class="truncate text-base font-semibold text-gray-900 sm:text-lg dark:text-white"
                                    >
                                        {{ lovedOne.name }}
                                    </h2>

                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            statusConfig(lovedOne.status).class
                                        "
                                    >
                                        {{
                                            statusConfig(lovedOne.status).label
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    <span
                                        v-if="lovedOne.branchName !== 'N/A'"
                                        class="inline-flex items-center gap-1"
                                    >
                                        <MapPin
                                            class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500"
                                        />
                                        {{ lovedOne.branchName }}
                                    </span>

                                    <span
                                        v-if="
                                            lovedOne.locationName !== 'N/A' &&
                                            lovedOne.locationName !==
                                                lovedOne.branchName
                                        "
                                        class="hidden text-gray-300 sm:inline dark:text-gray-500"
                                    >
                                        •
                                    </span>

                                    <span
                                        v-if="
                                            lovedOne.locationName !== 'N/A' &&
                                            lovedOne.locationName !==
                                                lovedOne.branchName
                                        "
                                        class="truncate"
                                    >
                                        {{ lovedOne.locationName }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="lovedOnes.length > 1"
                            class="flex items-center justify-between gap-3 sm:justify-end"
                        >
                            <span class="text-xs font-medium text-gray-400 dark:text-gray-500">
                                {{ selectedIndex + 1 }} of
                                {{ lovedOnes.length }}
                            </span>

                            <div
                                class="flex items-center rounded-xl border border-gray-100 bg-gray-50 p-1 dark:border-white/10 dark:bg-white/5"
                            >
                                <button
                                    type="button"
                                    aria-label="Previous loved one"
                                    @click="prevLovedOne"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-white hover:text-gray-700 hover:shadow-sm dark:text-gray-500 dark:hover:bg-secondary dark:hover:text-gray-400 dark:hover:bg-white/10"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </button>

                                <button
                                    type="button"
                                    aria-label="Next loved one"
                                    @click="nextLovedOne"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-white hover:text-gray-700 hover:shadow-sm dark:text-gray-500 dark:hover:bg-secondary dark:hover:text-gray-400 dark:hover:bg-white/10"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-5 grid grid-cols-2 gap-3 border-t border-gray-100 pt-5 sm:grid-cols-3 dark:border-white/10"
                    >
                        <div class="rounded-xl bg-gray-50 px-3.5 py-3 dark:bg-white/5">
                            <div
                                class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                            >
                                <Activity class="h-3.5 w-3.5" />
                                Updates
                            </div>

                            <p
                                class="mt-1.5 text-sm font-semibold text-gray-800 dark:text-white"
                            >
                                {{ totalItems }}
                                {{ totalItems === 1 ? "update" : "updates" }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-gray-50 px-3.5 py-3 dark:bg-white/5">
                            <div
                                class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                            >
                                <CalendarDays class="h-3.5 w-3.5" />
                                Care Type
                            </div>

                            <p
                                class="mt-1.5 text-sm font-semibold text-gray-800 dark:text-white"
                            >
                                {{
                                    lovedOne.locationType === "facility"
                                        ? "Facility Care"
                                        : lovedOne.locationType === "homecare"
                                          ? "Homecare"
                                          : "No Active Care"
                                }}
                            </p>
                        </div>

                        <div
                            class="col-span-2 rounded-xl bg-gray-50 px-3.5 py-3 sm:col-span-1 dark:bg-white/5"
                        >
                            <div
                                class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                            >
                                <MapPin class="h-3.5 w-3.5" />
                                Location
                            </div>

                            <p
                                class="mt-1.5 truncate text-sm font-semibold text-gray-800 dark:text-white"
                            >
                                {{
                                    lovedOne.branchName !== "N/A"
                                        ? lovedOne.branchName
                                        : "Not assigned"
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="!activityGroups.length"
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="flex flex-col items-center px-6 py-14 text-center">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-50 text-gray-400 dark:bg-white/5 dark:text-gray-500"
                    >
                        <Activity class="h-7 w-7" />
                    </div>

                    <h2 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
                        No updates yet
                    </h2>

                    <p class="mt-1 max-w-sm text-sm leading-6 text-gray-500 dark:text-gray-400">
                        Activities and updates logged by the care team will
                        appear here.
                    </p>
                </div>
            </div>

            <div
                v-for="group in activityGroups"
                :key="group.key"
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-6 dark:border-white/10"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                        >
                            <CalendarDays class="h-4 w-4" />
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ group.label }}
                            </h3>

                            <p class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">
                                {{ group.items.length }}
                                {{
                                    group.items.length === 1
                                        ? "activity"
                                        : "activities"
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-gray-50 dark:divide-white/10">
                    <div
                        v-for="item in group.items"
                        :key="item.id"
                        class="group flex items-start gap-3 px-5 py-4 transition-colors hover:bg-gray-50/70 sm:gap-4 sm:px-6 dark:hover:bg-white/5"
                    >
                        <div class="w-14 shrink-0 pt-2 text-right sm:w-16">
                            <span
                                class="text-[11px] font-semibold text-gray-400 dark:text-gray-500"
                            >
                                {{ formatTime(item.occurredAt) }}
                            </span>
                        </div>

                        <div
                            class="relative flex shrink-0 items-center justify-center"
                        >
                            <span
                                class="flex h-10 w-10 items-center justify-center rounded-xl ring-1 ring-inset ring-black/[0.03]"
                                :class="[
                                    styleFor(item.type).bg,
                                    styleFor(item.type).text,
                                ]"
                            >
                                <Icon
                                    :name="styleFor(item.type).icon"
                                    class="h-4.5 w-4.5"
                                />
                            </span>
                        </div>

                        <div class="min-w-0 flex-1 pt-0.5">
                            <div
                                class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-3"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-semibold text-gray-800 dark:text-white"
                                    >
                                        {{ item.title }}
                                    </p>

                                    <p
                                        v-if="item.subtitle"
                                        class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                                    >
                                        {{ item.subtitle }}
                                    </p>
                                </div>

                                <span
                                    class="w-fit shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold"
                                    :class="[
                                        styleFor(item.type).bg,
                                        styleFor(item.type).text,
                                    ]"
                                >
                                    {{ styleFor(item.type).label }}
                                </span>
                            </div>

                            <p
                                v-if="item.description"
                                class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400"
                            >
                                {{ item.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="totalItems > itemsPerPage"
                class="rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <Pagination
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :total-items="totalItems"
                    :items-per-page="itemsPerPage"
                    @change-page="onChangePage"
                />
            </div>
        </template>
    </div>
</template>
