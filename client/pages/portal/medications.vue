<script setup lang="ts">
import {
    ref,
    computed,
    watch,
    nextTick,
    onMounted,
    onBeforeUnmount,
} from "vue";
import {
    Info,
    Pill,
    HeartPulse,
    ClipboardList,
    UserRound,
    ChevronLeft,
    ChevronRight,
    ShieldCheck,
    Clock3,
    Activity,
    RefreshCw,
    AlertCircle,
} from "lucide-vue-next";
import MedicationTable from "~/components/sections/app/Patient/MedicationTable.vue";
import VitalSignsTable from "~/components/sections/app/Patient/VitalSignsTable.vue";
import EmptyState from "~/components/ui/EmptyState.vue";
import { patientAccessService } from "../../api/patient-access/PatientAccessService";
import type { Medication, Vital } from "~/types/medication";

useHead({ title: "Medications" });

definePageMeta({
    layout: "portal",
});

interface LovedOne {
    patient_id: number;
    uuid: string | null;
    name: string;
    medications: Medication[];
    vitals: Vital[];
}

function fallbackLovedOne(): LovedOne {
    return {
        patient_id: 0,
        uuid: null,
        name: "Unnamed Resident",
        medications: [],
        vitals: [],
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

const lovedOne = computed(
    () => lovedOnes.value[selectedIndex.value] ?? fallbackLovedOne(),
);

const medications = computed(() => lovedOne.value.medications);

const vitals = computed(() => lovedOne.value.vitals);

const activeMedicationCount = computed(() => medications.value.length);

const vitalCount = computed(() => vitals.value.length);

const patientCount = computed(() => lovedOnes.value.length);

function selectPatient(index: number) {
    selectedIndex.value = index;
}

const lovedOnesScrollRef = ref<HTMLElement | null>(null);
const canScrollLovedOnesLeft = ref(false);
const canScrollLovedOnesRight = ref(false);

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
        left: direction * 220,
        behavior: "smooth",
    });
}

function splitRecordsByCategory(rawRecords: any[] | null | undefined) {
    const records = rawRecords ?? [];

    const meds: Medication[] = records
        .filter((entry) => ["Medication", "PRN"].includes(entry.category))
        .map((entry) => ({
            ...entry,
            durationLabel: entry.duration ? `${entry.duration} Days` : "0 Days",
            schedules: entry.schedules ?? [],
        })) as Medication[];

    const vitalRecords: Vital[] = records
        .filter((entry) => entry.category === "Vital Signs")
        .map((entry) => ({ ...entry })) as Vital[];

    return {
        meds,
        vitalRecords,
    };
}

function mapPatientRecord(item: any): LovedOne {
    const patient = item?.patient ?? {};

    const { meds, vitalRecords } = splitRecordsByCategory(patient.medication);

    return {
        patient_id: patient.patient_id ?? 0,
        uuid: patient.uuid ?? null,
        name: patient.full_name || "Unnamed Resident",
        medications: meds,
        vitals: vitalRecords,
    };
}

async function loadPatientData() {
    isLoading.value = true;
    loadError.value = null;
    noPatients.value = false;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "medication",
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
        console.error("Error loading medications:", err);

        loadError.value = err?.message || "Failed to load medications.";
    } finally {
        isLoading.value = false;
    }

    await nextTick();
    updateLovedOnesScrollState();
}

onMounted(() => {
    loadPatientData();
    window.addEventListener("resize", updateLovedOnesScrollState);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", updateLovedOnesScrollState);
});
</script>

<template>
    <div class="min-h-full space-y-6 p-5">
        <div v-if="isLoading" class="space-y-5">
            <div
                class="animate-pulse rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="flex gap-3 overflow-hidden">
                    <div
                        v-for="item in 4"
                        :key="item"
                        class="h-10 w-32 shrink-0 rounded-xl bg-gray-100 dark:bg-white/10"
                    />
                </div>
            </div>

            <div
                class="animate-pulse rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="mb-5 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-gray-100 dark:bg-white/10" />

                    <div class="space-y-2">
                        <div class="h-4 w-36 rounded bg-gray-200 dark:bg-white/15" />
                        <div class="h-3 w-24 rounded bg-gray-100 dark:bg-white/10" />
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="h-10 rounded-xl bg-gray-50 dark:bg-white/5" />

                    <div
                        v-for="row in 5"
                        :key="row"
                        class="grid grid-cols-4 gap-4 border-b border-gray-50 px-4 py-4 dark:border-white/10"
                    >
                        <div class="h-3.5 w-32 rounded bg-gray-100 dark:bg-white/10" />
                        <div class="h-3.5 w-24 rounded bg-gray-100 dark:bg-white/10" />
                        <div class="h-3.5 w-28 rounded bg-gray-100 dark:bg-white/10" />
                        <div class="h-3.5 w-16 rounded bg-gray-100 dark:bg-white/10" />
                    </div>
                </div>
            </div>

            <div
                v-for="card in 2"
                :key="card"
                class="animate-pulse rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="mb-5 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-gray-100 dark:bg-white/10" />

                    <div class="space-y-2">
                        <div class="h-4 w-28 rounded bg-gray-200 dark:bg-white/15" />
                        <div class="h-3 w-20 rounded bg-gray-100 dark:bg-white/10" />
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="h-10 rounded-xl bg-gray-50 dark:bg-white/5" />

                    <div
                        v-for="row in 4"
                        :key="row"
                        class="h-12 rounded-xl bg-gray-50 dark:bg-white/5"
                    />
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
                    Unable to load care information
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
                v-if="lovedOnes.length"
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6 dark:border-white/10">
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                Select a loved one
                            </p>

                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                Choose who you want to view
                            </p>
                        </div>

                        <span
                            v-if="patientCount > 1"
                            class="text-xs font-medium text-gray-400 dark:text-gray-500"
                        >
                            {{ selectedIndex + 1 }} of
                            {{ patientCount }}
                        </span>
                    </div>
                </div>

                <div class="relative">
                    <button
                        v-if="canScrollLovedOnesLeft"
                        type="button"
                        aria-label="Scroll left"
                        class="absolute left-1 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-gray-100 bg-white text-gray-500 shadow-md transition hover:bg-gray-50 dark:border-white/10 dark:bg-secondary dark:text-gray-400 dark:hover:bg-white/5"
                        @click="scrollLovedOnes(-1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <div
                        ref="lovedOnesScrollRef"
                        class="flex gap-2 overflow-x-auto scroll-smooth px-5 py-4 scrollbar-none sm:px-6"
                        @scroll="updateLovedOnesScrollState"
                    >
                        <button
                            v-for="(lo, idx) in lovedOnes"
                            :key="lo.patient_id"
                            type="button"
                            @click="selectPatient(idx)"
                            class="group flex min-w-fit items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left transition-all"
                            :class="
                                selectedIndex === idx
                                    ? 'border-primary-500 bg-primary-50 shadow-sm dark:bg-primary-500/10'
                                    : 'border-gray-100 bg-gray-50 hover:border-gray-200 hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:border-white/10 dark:hover:bg-secondary'
                            "
                        >
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg"
                                :class="
                                    selectedIndex === idx
                                        ? 'bg-primary-500 text-white'
                                        : 'bg-white text-gray-400 ring-1 ring-gray-100 dark:bg-secondary dark:text-gray-500 dark:ring-white/10'
                                "
                            >
                                <UserRound class="h-4 w-4" />
                            </span>

                            <span
                                class="max-w-40 truncate text-xs font-semibold"
                                :class="
                                    selectedIndex === idx
                                        ? 'text-primary-700 dark:text-primary-300'
                                        : 'text-gray-600 dark:text-gray-400'
                                "
                            >
                                {{ lo.name }}
                            </span>

                            <ChevronRight
                                class="h-3.5 w-3.5 transition-transform"
                                :class="
                                    selectedIndex === idx
                                        ? 'text-primary-500 dark:text-primary-300'
                                        : 'text-gray-300 dark:text-gray-500'
                                "
                            />
                        </button>
                    </div>

                    <button
                        v-if="canScrollLovedOnesRight"
                        type="button"
                        aria-label="Scroll right"
                        class="absolute right-1 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-gray-100 bg-white text-gray-500 shadow-md transition hover:bg-gray-50 dark:border-white/10 dark:bg-secondary dark:text-gray-400 dark:hover:bg-white/5"
                        @click="scrollLovedOnes(1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="border-b border-gray-100 px-5 py-5 sm:px-6 dark:border-white/10">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                <UserRound class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                >
                                    Current patient
                                </p>

                                <h2
                                    class="mt-0.5 truncate text-base font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ lovedOne.name }}
                                </h2>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <div
                                class="rounded-xl bg-primary-50 px-3 py-2 text-center dark:bg-primary-500/10"
                            >
                                <p
                                    class="text-lg font-bold leading-none text-primary-700 dark:text-primary-300"
                                >
                                    {{ activeMedicationCount }}
                                </p>

                                <p
                                    class="mt-1 text-[10px] font-medium text-primary-600 dark:text-primary-300"
                                >
                                    Medications
                                </p>
                            </div>

                            <div
                                class="rounded-xl bg-emerald-50 px-3 py-2 text-center dark:bg-emerald-500/10"
                            >
                                <p
                                    class="text-lg font-bold leading-none text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ vitalCount }}
                                </p>

                                <p
                                    class="mt-1 text-[10px] font-medium text-emerald-600 dark:text-emerald-300"
                                >
                                    Vital Records
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="mb-4 flex items-center gap-2">
                        <Pill class="h-4 w-4 text-primary-600 dark:text-primary-300" />

                        <h3 class="text-sm font-semibold text-gray-800 dark:text-white">
                            Medication Schedule
                        </h3>
                    </div>

                    <MedicationTable
                        v-if="medications.length"
                        :medications="medications"
                        view="client"
                        disabled
                    />

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-10 text-center dark:border-white/10 dark:bg-white/5"
                    >
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-gray-400 shadow-sm ring-1 ring-gray-100 dark:bg-secondary dark:text-gray-500 dark:ring-white/10"
                        >
                            <Pill class="h-5 w-5" />
                        </div>

                        <p class="mt-4 text-sm font-semibold text-gray-600 dark:text-gray-400">
                            No medications recorded
                        </p>

                        <p
                            class="mx-auto mt-1 max-w-sm text-xs leading-5 text-gray-400 dark:text-gray-500"
                        >
                            Medication records will appear here once they have
                            been added to the patient's care plan.
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div class="border-b border-gray-100 px-5 py-5 sm:px-6 dark:border-white/10">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                            >
                                <HeartPulse class="h-5 w-5" />
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Vital Signs
                                </h3>

                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                    Recorded health measurements
                                </p>
                            </div>
                        </div>

                        <div
                            class="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 sm:flex dark:bg-emerald-500/10"
                        >
                            <Activity class="h-3 w-3 text-emerald-600 dark:text-emerald-300" />

                            <span
                                class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-300"
                            >
                                {{ vitalCount }} records
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <VitalSignsTable v-if="vitals.length" :vitals="vitals" />

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-10 text-center dark:border-white/10 dark:bg-white/5"
                    >
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-gray-400 shadow-sm ring-1 ring-gray-100 dark:bg-secondary dark:text-gray-500 dark:ring-white/10"
                        >
                            <HeartPulse class="h-5 w-5" />
                        </div>

                        <p class="mt-4 text-sm font-semibold text-gray-600 dark:text-gray-400">
                            No vital signs recorded
                        </p>

                        <p
                            class="mx-auto mt-1 max-w-sm text-xs leading-5 text-gray-400 dark:text-gray-500"
                        >
                            Vital sign records will appear here once they are
                            added.
                        </p>
                    </div>
                </div>
            </div>

            <!-- <div
                class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm dark:border-blue-500/20 dark:bg-secondary"
            >
                <div
                    class="border-b border-blue-50 bg-blue-50/50 px-5 py-4 sm:px-6 dark:border-blue-500/20 dark:bg-blue-500/10"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300"
                        >
                            <ShieldCheck class="h-5 w-5" />
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">
                                Important Care Reminders
                            </h3>

                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                General medication safety guidance
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div
                            class="flex items-start gap-3 rounded-xl bg-gray-50 p-3.5 dark:bg-white/5"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                <Clock3 class="h-4 w-4" />
                            </span>

                            <div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                    Follow the schedule
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-5 text-gray-500 dark:text-gray-400"
                                >
                                    Always take medications at their scheduled
                                    times.
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl bg-gray-50 p-3.5 dark:bg-white/5"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                            >
                                <ClipboardList class="h-4 w-4" />
                            </span>

                            <div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                    Do not skip doses
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-5 text-gray-500 dark:text-gray-400"
                                >
                                    Continue the prescribed schedule even when
                                    feeling better.
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl bg-gray-50 p-3.5 dark:bg-white/5"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                            >
                                <HeartPulse class="h-4 w-4" />
                            </span>

                            <div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                    Watch for side effects
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-5 text-gray-500 dark:text-gray-400"
                                >
                                    Report unusual reactions or side effects to
                                    the caregiver promptly.
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl bg-gray-50 p-3.5 dark:bg-white/5"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300"
                            >
                                <Info class="h-4 w-4" />
                            </span>

                            <div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                    Store properly
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-5 text-gray-500 dark:text-gray-400"
                                >
                                    Keep medications in a cool, dry, and
                                    appropriate storage area.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50/60 px-3.5 py-3 dark:border-blue-500/20 dark:bg-blue-500/10"
                    >
                        <Info class="mt-0.5 h-4 w-4 shrink-0 text-blue-600 dark:text-blue-300" />

                        <p class="text-[11px] leading-5 text-blue-700 dark:text-blue-300">
                            These reminders are general guidelines. Follow the
                            patient's prescribed care plan and instructions from
                            their healthcare provider.
                        </p>
                    </div>
                </div>
            </div> -->
        </template>
    </div>
</template>
