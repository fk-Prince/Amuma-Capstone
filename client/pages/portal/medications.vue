<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import {
    Info,
    Pill,
    HeartPulse,
    ClipboardList,
    UserRound,
    ChevronRight,
    ShieldCheck,
    Clock3,
    Activity,
    RefreshCw,
    AlertCircle,
} from "lucide-vue-next";
import MedicationTable from "~/components/sections/app/Patient/MedicationTable.vue";
import VitalSignsTable from "~/components/sections/app/Patient/VitalSignsTable.vue";
import { patientAccessService } from "../../api/patient-access/PatientAccessService";
import type { Medication, Vital } from "~/types/medication";

useHead({ title: "Medications" });

definePageMeta({
    layout: "portal",
});

interface LovedOne {
    patient_id: number;
    name: string;
    medications: Medication[];
    vitals: Vital[];
}

function fallbackLovedOne(): LovedOne {
    return {
        patient_id: 0,
        name: "Unnamed Resident",
        medications: [],
        vitals: [],
    };
}

const isLoading = ref(true);
const loadError = ref<string | null>(null);

const lovedOnes = ref<LovedOne[]>([]);
const selectedIndex = ref(0);

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
        name: patient.full_name || "Unnamed Resident",
        medications: meds,
        vitals: vitalRecords,
    };
}

async function loadPatientData() {
    isLoading.value = true;
    loadError.value = null;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "medication",
        });

        const records: any[] = Array.isArray(res?.data) ? res.data : [];

        if (records.length) {
            lovedOnes.value = records.map(mapPatientRecord);
            selectedIndex.value = 0;
        } else {
            lovedOnes.value = [];
            loadError.value = "No patient data returned.";
        }
    } catch (err: any) {
        console.error("Error loading medications:", err);

        loadError.value = err?.message || "Failed to load medications.";
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
        <div
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-600 to-brand-700 px-6 py-7 text-white shadow-sm sm:px-8"
        >
            <div
                class="absolute -right-10 -top-16 h-40 w-40 rounded-full bg-white/10"
            />

            <div
                class="absolute -bottom-24 right-24 h-52 w-52 rounded-full bg-white/5"
            />

            <div class="relative flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20"
                >
                    <Pill class="h-6 w-6" />
                </div>

                <div>
                    <h1
                        class="text-xl font-semibold tracking-tight sm:text-2xl"
                    >
                        Medications & Care
                    </h1>

                    <p class="mt-1 max-w-xl text-sm leading-6 text-white/75">
                        View medication schedules, vital signs, and important
                        care instructions for your loved one.
                    </p>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="space-y-5">
            <div
                class="animate-pulse rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div class="flex gap-3 overflow-hidden">
                    <div
                        v-for="item in 4"
                        :key="item"
                        class="h-10 w-32 shrink-0 rounded-xl bg-gray-100"
                    />
                </div>
            </div>

            <div
                class="animate-pulse rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div class="mb-5 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-gray-100" />

                    <div class="space-y-2">
                        <div class="h-4 w-36 rounded bg-gray-200" />
                        <div class="h-3 w-24 rounded bg-gray-100" />
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="h-10 rounded-xl bg-gray-50" />

                    <div
                        v-for="row in 5"
                        :key="row"
                        class="grid grid-cols-4 gap-4 border-b border-gray-50 px-4 py-4"
                    >
                        <div class="h-3.5 w-32 rounded bg-gray-100" />
                        <div class="h-3.5 w-24 rounded bg-gray-100" />
                        <div class="h-3.5 w-28 rounded bg-gray-100" />
                        <div class="h-3.5 w-16 rounded bg-gray-100" />
                    </div>
                </div>
            </div>

            <div
                v-for="card in 2"
                :key="card"
                class="animate-pulse rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div class="mb-5 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-gray-100" />

                    <div class="space-y-2">
                        <div class="h-4 w-28 rounded bg-gray-200" />
                        <div class="h-3 w-20 rounded bg-gray-100" />
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="h-10 rounded-xl bg-gray-50" />

                    <div
                        v-for="row in 4"
                        :key="row"
                        class="h-12 rounded-xl bg-gray-50"
                    />
                </div>
            </div>
        </div>

        <div
            v-else-if="loadError"
            class="overflow-hidden rounded-3xl border border-rose-100 bg-white shadow-sm"
        >
            <div class="flex flex-col items-center px-6 py-14 text-center">
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-500"
                >
                    <AlertCircle class="h-7 w-7" />
                </div>

                <h2 class="mt-4 text-sm font-semibold text-gray-900">
                    Unable to load care information
                </h2>

                <p class="mt-1 max-w-sm text-sm leading-6 text-gray-500">
                    {{ loadError }}
                </p>

                <button
                    type="button"
                    @click="loadPatientData"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                >
                    <RefreshCw class="h-4 w-4" />
                    Try again
                </button>
            </div>
        </div>

        <template v-else>
            <div
                v-if="lovedOnes.length"
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
            >
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                Select a loved one
                            </p>

                            <p class="mt-0.5 text-xs text-gray-400">
                                Choose who you want to view
                            </p>
                        </div>

                        <span
                            v-if="patientCount > 1"
                            class="text-xs font-medium text-gray-400"
                        >
                            {{ selectedIndex + 1 }} of
                            {{ patientCount }}
                        </span>
                    </div>
                </div>

                <div
                    class="flex gap-2 overflow-x-auto px-5 py-4 no-scrollbar sm:px-6"
                >
                    <button
                        v-for="(lo, idx) in lovedOnes"
                        :key="lo.patient_id"
                        type="button"
                        @click="selectPatient(idx)"
                        class="group flex min-w-fit items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left transition-all"
                        :class="
                            selectedIndex === idx
                                ? 'border-brand-500 bg-brand-50 shadow-sm'
                                : 'border-gray-100 bg-gray-50 hover:border-gray-200 hover:bg-white'
                        "
                    >
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            :class="
                                selectedIndex === idx
                                    ? 'bg-brand-500 text-white'
                                    : 'bg-white text-gray-400 ring-1 ring-gray-100'
                            "
                        >
                            <UserRound class="h-4 w-4" />
                        </span>

                        <span
                            class="max-w-40 truncate text-xs font-semibold"
                            :class="
                                selectedIndex === idx
                                    ? 'text-brand-700'
                                    : 'text-gray-600'
                            "
                        >
                            {{ lo.name }}
                        </span>

                        <ChevronRight
                            class="h-3.5 w-3.5 transition-transform"
                            :class="
                                selectedIndex === idx
                                    ? 'text-brand-500'
                                    : 'text-gray-300'
                            "
                        />
                    </button>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
            >
                <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                            >
                                <UserRound class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-xs font-medium uppercase tracking-wider text-gray-400"
                                >
                                    Current patient
                                </p>

                                <h2
                                    class="mt-0.5 truncate text-base font-semibold text-gray-900"
                                >
                                    {{ lovedOne.name }}
                                </h2>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <div
                                class="rounded-xl bg-brand-50 px-3 py-2 text-center"
                            >
                                <p
                                    class="text-lg font-bold leading-none text-brand-700"
                                >
                                    {{ activeMedicationCount }}
                                </p>

                                <p
                                    class="mt-1 text-[10px] font-medium text-brand-600"
                                >
                                    Medications
                                </p>
                            </div>

                            <div
                                class="rounded-xl bg-emerald-50 px-3 py-2 text-center"
                            >
                                <p
                                    class="text-lg font-bold leading-none text-emerald-700"
                                >
                                    {{ vitalCount }}
                                </p>

                                <p
                                    class="mt-1 text-[10px] font-medium text-emerald-600"
                                >
                                    Vital Records
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="mb-4 flex items-center gap-2">
                        <Pill class="h-4 w-4 text-brand-600" />

                        <h3 class="text-sm font-semibold text-gray-800">
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
                        class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-10 text-center"
                    >
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-gray-400 shadow-sm ring-1 ring-gray-100"
                        >
                            <Pill class="h-5 w-5" />
                        </div>

                        <p class="mt-4 text-sm font-semibold text-gray-600">
                            No medications recorded
                        </p>

                        <p
                            class="mx-auto mt-1 max-w-sm text-xs leading-5 text-gray-400"
                        >
                            Medication records will appear here once they have
                            been added to the patient's care plan.
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
            >
                <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                            >
                                <HeartPulse class="h-5 w-5" />
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Vital Signs
                                </h3>

                                <p class="mt-0.5 text-xs text-gray-400">
                                    Recorded health measurements
                                </p>
                            </div>
                        </div>

                        <div
                            class="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 sm:flex"
                        >
                            <Activity class="h-3 w-3 text-emerald-600" />

                            <span
                                class="text-[10px] font-semibold text-emerald-700"
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
                        class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-10 text-center"
                    >
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-gray-400 shadow-sm ring-1 ring-gray-100"
                        >
                            <HeartPulse class="h-5 w-5" />
                        </div>

                        <p class="mt-4 text-sm font-semibold text-gray-600">
                            No vital signs recorded
                        </p>

                        <p
                            class="mx-auto mt-1 max-w-sm text-xs leading-5 text-gray-400"
                        >
                            Vital sign records will appear here once they are
                            added.
                        </p>
                    </div>
                </div>
            </div>

            <!-- <div
                class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm"
            >
                <div
                    class="border-b border-blue-50 bg-blue-50/50 px-5 py-4 sm:px-6"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600"
                        >
                            <ShieldCheck class="h-5 w-5" />
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">
                                Important Care Reminders
                            </h3>

                            <p class="mt-0.5 text-xs text-gray-400">
                                General medication safety guidance
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div
                            class="flex items-start gap-3 rounded-xl bg-gray-50 p-3.5"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"
                            >
                                <Clock3 class="h-4 w-4" />
                            </span>

                            <div>
                                <p class="text-xs font-semibold text-gray-700">
                                    Follow the schedule
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-5 text-gray-500"
                                >
                                    Always take medications at their scheduled
                                    times.
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl bg-gray-50 p-3.5"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600"
                            >
                                <ClipboardList class="h-4 w-4" />
                            </span>

                            <div>
                                <p class="text-xs font-semibold text-gray-700">
                                    Do not skip doses
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-5 text-gray-500"
                                >
                                    Continue the prescribed schedule even when
                                    feeling better.
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl bg-gray-50 p-3.5"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600"
                            >
                                <HeartPulse class="h-4 w-4" />
                            </span>

                            <div>
                                <p class="text-xs font-semibold text-gray-700">
                                    Watch for side effects
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-5 text-gray-500"
                                >
                                    Report unusual reactions or side effects to
                                    the caregiver promptly.
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl bg-gray-50 p-3.5"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600"
                            >
                                <Info class="h-4 w-4" />
                            </span>

                            <div>
                                <p class="text-xs font-semibold text-gray-700">
                                    Store properly
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-5 text-gray-500"
                                >
                                    Keep medications in a cool, dry, and
                                    appropriate storage area.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50/60 px-3.5 py-3"
                    >
                        <Info class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />

                        <p class="text-[11px] leading-5 text-blue-700">
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
