<script setup lang="ts">
import { ref, computed, watch } from "vue";

import {
    Droplet,
    Calendar,
    Phone,
    Globe2,
    Ruler,
    Weight,
    Pill,
    HeartPulse,
    MapPin,
    Building2,
    DoorOpen,
    BedDouble,
    History,
    Stethoscope,
} from "lucide-vue-next";
import type { PatientRetrieve, Admission } from "~/types/patient";
import { formatDate } from "~/utils/time";
import { useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();

const props = defineProps<{
    patient: PatientRetrieve;
    isEdit?: boolean;
}>();

const admissions = ref<Admission[]>([...(props.patient.admissions ?? [])]);

const assessments = computed(() => {
    const value = props.patient.initial_assessment;

    if (!value) return [];

    if (Array.isArray(value)) return value;

    if (typeof value === "object") return [value];

    return [];
});

const activeAssessmentIndex = ref(0);

watch(assessments, (list) => {
    if (!list.length) {
        activeAssessmentIndex.value = 0;
        return;
    }

    if (activeAssessmentIndex.value > list.length - 1) {
        activeAssessmentIndex.value = list.length - 1;
    }
});

const activeAssessment = computed(
    () => assessments.value[activeAssessmentIndex.value] ?? null,
);

watch(
    () => props.patient.admissions,
    (val) => {
        admissions.value = [...(val ?? [])];
    },
);

const latestAdmission = computed(() => {
    if (!admissions.value.length) return null;

    const sorted = [...admissions.value].sort((a, b) => {
        const aTime = a.admitted_at ? new Date(a.admitted_at).getTime() : 0;
        const bTime = b.admitted_at ? new Date(b.admitted_at).getTime() : 0;
        return bTime - aTime;
    });

    return sorted[0] ?? null;
});

function goToAdmissionHistory() {
    router.push(
        `/app/branches/${route.params.uuid}/admissions/${route.params.p_uuid}`,
    );
}

function isAdmitted(status?: string) {
    return (status ?? "").toLowerCase() === "admitted";
}

function isWaiting(status?: string) {
    return (status ?? "").toLowerCase() === "waiting";
}

function statusClasses(status?: string) {
    const value = (status ?? "").toLowerCase();

    if (value === "admitted") {
        return "bg-primary text-white";
    }

    if (value.includes("complete")) {
        return "bg-[#E6F1FA] text-[#2563A6]";
    }

    if (value.includes("discharge")) {
        return "bg-[#FBE8E6] text-[#B3402F]";
    }

    if (value.includes("cancel")) {
        return "bg-[#F1F1F1] text-[#6B7280]";
    }

    return "bg-[#FDF3DE] text-[#966B1F]";
}

function cardClasses(status?: string) {
    return isAdmitted(status)
        ? "border-l-4 border-primary bg-primary-50"
        : "bg-muted-light/40";
}
</script>

<template>
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary text-xl font-semibold text-white"
                >
                    {{ patient.first_name.charAt(0) }}
                </div>

                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-primary"
                    >
                        Patient Overview
                    </p>

                    <h2 class="mt-1 text-xl font-semibold text-secondary">
                        {{ patient.full_name }}
                    </h2>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary"
                        >
                            {{ patient.gender }}
                        </span>

                        <span
                            v-if="patient.latest_admission"
                            class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary"
                        >
                            {{
                                patient.latest_admission?.status.toLowerCase() ===
                                "admitted"
                                    ? "Currently Admitted"
                                    : patient.latest_admission?.status
                            }}
                        </span>
                        <!-- <span
                            class="rounded-full bg-muted-light px-3 py-1 text-xs font-medium text-secondary"
                        >
                            {{ patient.blood_type || "No blood type on file" }}
                        </span> -->
                    </div>
                </div>
            </div>

            <div
                class="mt-6 grid gap-6 border-t border-muted-light pt-6 sm:grid-cols-3"
            >
                <div class="flex items-center gap-3">
                    <Calendar class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Birthday</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary">
                            {{ formatDate(patient.date_of_birth) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Phone class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Contact</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary">
                            {{ patient.phone_number || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Globe2 class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Citizenship</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary">
                            {{ patient.citizenship || "—" }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="mt-6 grid gap-6 border-t border-muted-light pt-6 sm:grid-cols-4"
            >
                <div class="flex items-center gap-3">
                    <Ruler class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Height</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary">
                            {{ patient.height + " cm" || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Weight class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Weight</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary">
                            {{ patient.weight + " kg" || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Pill class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Recorded Medications</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary">
                            {{ patient.medications_count ?? 0 }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <HeartPulse class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Recorded Vital Signs</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary">
                            {{ patient.vitals_count ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
            <div
                class="mt-6 grid gap-6 border-t border-muted-light pt-6 sm:grid-cols-3"
            >
                <div class="flex items-center gap-3">
                    <MapPin class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Location</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary">
                            {{
                                patient.location?.full_address ||
                                "No address recorded."
                            }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Droplet class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted">Blood Type</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary">
                            {{ patient.blood_type || "No blood type on file" }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-muted-light pt-6">
                <p class="mb-2 text-xs text-muted">Allergies</p>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="allergy in patient.allergies"
                        :key="allergy"
                        class="rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-600"
                    >
                        {{ allergy }}
                    </span>

                    <span
                        v-if="!patient.allergies?.length"
                        class="text-sm text-muted"
                    >
                        No known allergies
                    </span>
                </div>
            </div>
        </section>

        <section
            v-if="assessments.length"
            class="rounded-2xl bg-white p-6 shadow-sm"
        >
            <div class="flex items-center gap-2">
                <Stethoscope class="h-4 w-4 text-primary" />
                <h3 class="font-semibold text-secondary">Initial Assessment</h3>
            </div>

            <div
                v-if="assessments.length > 1"
                class="mt-4 flex flex-wrap items-center gap-2"
            >
                <button
                    v-for="(assessment, index) in assessments"
                    :key="index"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                    :class="
                        activeAssessmentIndex === index
                            ? 'bg-primary text-white'
                            : 'bg-muted-light/60 text-secondary hover:bg-muted-light'
                    "
                    @click="activeAssessmentIndex = index"
                >
                    Assessment {{ index + 1 }}
                </button>
            </div>

            <div v-if="activeAssessment" class="mt-5 space-y-6">
                <div>
                    <h5
                        class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted"
                    >
                        Diagnosis
                    </h5>

                    <div class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-muted">Diagnosis</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.diagnosis || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Diagnosis Date</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{
                                    formatDate(activeAssessment.diagnosis_date)
                                }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <p class="text-xs text-muted">Diagnosis Notes</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.diagnosis_notes || "—" }}
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h5
                        class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted"
                    >
                        Vital Signs
                    </h5>

                    <div
                        class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm sm:grid-cols-3"
                    >
                        <div>
                            <p class="text-xs text-muted">Blood Pressure</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.blood_pressure || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Pulse Rate</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.pulse_rate || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Respiratory Rate</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.respiratory_rate || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Temperature</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.temperature || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Oxygen Saturation</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.oxygen_saturation || "—" }}
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h5
                        class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted"
                    >
                        Mental / Cognitive State
                    </h5>

                    <div class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-muted">Mental State</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.mental_state || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Mood</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.mood || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Memory Issues</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.memory_issues || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Communication</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.communication || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted">Speech</p>
                            <p class="mt-0.5 font-medium text-secondary">
                                {{ activeAssessment.speech || "—" }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="latestAdmission"
            class="rounded-2xl bg-white p-6 shadow-sm"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <Building2 class="h-4 w-4 text-primary" />
                    <h3 class="font-semibold text-secondary">
                        Latest Admission
                    </h3>
                </div>

                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-lg border border-muted-light px-3 py-1.5 text-xs font-medium text-secondary transition-colors hover:border-primary/40 hover:text-primary-600"
                    @click="goToAdmissionHistory"
                >
                    <History class="h-3.5 w-3.5" />
                    View Admission History
                </button>
            </div>

            <div class="mt-5">
                <div
                    class="rounded-xl p-4 transition hover:bg-primary-50/60"
                    :class="cardClasses(latestAdmission.status)"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <p
                                    class="text-sm font-semibold capitalize text-secondary"
                                >
                                    {{ latestAdmission.status }}
                                </p>

                                <p
                                    v-if="
                                        latestAdmission.status
                                            ?.toLowerCase()
                                            .includes('discharge') &&
                                        latestAdmission.end_date
                                    "
                                    class="mt-0.5 text-xs text-muted"
                                >
                                    at
                                    {{ formatDate(latestAdmission.end_date) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1">
                                <p class="mt-0.5 text-xs text-muted">
                                    {{
                                        isWaiting(latestAdmission.status)
                                            ? `Waiting for admission at ${formatDate(latestAdmission.admitted_at)}`
                                            : `Admitted at ${formatDate(latestAdmission.admitted_at)}`
                                    }}
                                </p>

                                <p
                                    v-if="
                                        isAdmitted(latestAdmission.status) &&
                                        latestAdmission.end_date
                                    "
                                    class="mt-0.5 text-xs text-muted"
                                >
                                    till
                                    {{ formatDate(latestAdmission.end_date) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                v-if="!isWaiting(latestAdmission.status)"
                                class="rounded-full px-3 py-1 text-xs font-medium capitalize"
                                :class="statusClasses(latestAdmission.status)"
                            >
                                {{ latestAdmission.status }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div class="flex items-center gap-2">
                            <Building2 class="h-3.5 w-3.5 text-primary" />
                            <div>
                                <p class="text-[11px] text-muted">Floor</p>
                                <p class="text-sm font-medium text-secondary">
                                    {{ latestAdmission.room?.floor || "—" }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <DoorOpen class="h-3.5 w-3.5 text-primary" />
                            <div>
                                <p class="text-[11px] text-muted">Room</p>
                                <p class="text-sm font-medium text-secondary">
                                    {{ latestAdmission.room?.room_no || "—" }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <BedDouble class="h-3.5 w-3.5 text-primary" />
                            <div>
                                <p class="text-[11px] text-muted">Bed</p>
                                <p class="text-sm font-medium text-secondary">
                                    {{ latestAdmission.bed?.bed_no || "—" }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="latestAdmission.current_contract"
                        class="mt-4 border-t border-muted-light pt-4"
                    >
                        <p class="mb-2 text-xs font-semibold text-secondary">
                            Current Contract
                        </p>

                        <div class="space-y-2">
                            <div
                                class="rounded-lg bg-white px-3 py-2 border border-muted-light"
                            >
                                <div class="flex justify-between">
                                    <span class="text-xs text-muted">
                                        {{
                                            latestAdmission.current_contract
                                                ?.category || "—"
                                        }}
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-primary"
                                    >
                                        ₱{{
                                            latestAdmission.current_contract
                                                ?.price
                                        }}
                                    </span>
                                </div>

                                <div class="mt-1 text-xs text-secondary">
                                    {{
                                        latestAdmission.current_contract
                                            ?.accommodation_type || "—"
                                    }}
                                    ·
                                    {{
                                        latestAdmission.current_contract
                                            ?.billing_cycle || "—"
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
