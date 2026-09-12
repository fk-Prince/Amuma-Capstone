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
} from "lucide-vue-next";
import type { PatientRetrieve, Admission } from "~/types/patient";
import { formatDate } from "~/utils/time";
import { formatCurrency } from "~/utils/currency";
import { useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();

const props = defineProps<{
    patient: PatientRetrieve;
    isEdit?: boolean;
}>();

const admissions = ref<Admission[]>([...(props.patient.admissions ?? [])]);

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
        <section class="rounded-2xl bg-white p-6 shadow-sm dark:bg-secondary">
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

                    <h2 class="mt-1 text-xl font-semibold text-secondary dark:text-white">
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
                            class="rounded-full bg-muted-light px-3 py-1 text-xs font-medium text-secondary dark:text-white"
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
                        <p class="text-xs text-muted dark:text-gray-400">Birthday</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary dark:text-white">
                            {{ formatDate(patient.date_of_birth) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Phone class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">Contact</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary dark:text-white">
                            {{ patient.phone_number || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Globe2 class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">Citizenship</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary dark:text-white">
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
                        <p class="text-xs text-muted dark:text-gray-400">Height</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary dark:text-white">
                            {{ patient.height + " cm" || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Weight class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">Weight</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary dark:text-white">
                            {{ patient.weight + " kg" || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Pill class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">Recorded Medications</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary dark:text-white">
                            {{ patient.medications_count ?? 0 }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <HeartPulse class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">Recorded Vital Signs</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary dark:text-white">
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
                        <p class="text-xs text-muted dark:text-gray-400">Location</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary dark:text-white">
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
                        <p class="text-xs text-muted dark:text-gray-400">Blood Type</p>
                        <p class="mt-0.5 text-sm font-medium text-secondary dark:text-white">
                            {{ patient.blood_type || "No blood type on file" }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-muted-light pt-6">
                <p class="mb-2 text-xs text-muted dark:text-gray-400">Allergies</p>
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
                        class="text-sm text-muted dark:text-gray-400"
                    >
                        No known allergies
                    </span>
                </div>
            </div>
        </section>

        <section
            v-if="latestAdmission"
            class="rounded-2xl bg-white p-6 shadow-sm dark:bg-secondary"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <Building2 class="h-4 w-4 text-primary" />
                    <h3 class="font-semibold text-secondary dark:text-white">
                        Latest Admission
                    </h3>
                </div>

                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-lg border border-muted-light px-3 py-1.5 text-xs font-medium text-secondary transition-colors hover:border-primary/40 hover:text-primary-600 dark:text-white"
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
                                    class="text-sm font-semibold capitalize text-secondary dark:text-white"
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
                                    class="mt-0.5 text-xs text-muted dark:text-gray-400"
                                >
                                    at
                                    {{ formatDate(latestAdmission.end_date) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1">
                                <p class="mt-0.5 text-xs text-muted dark:text-gray-400">
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
                                    class="mt-0.5 text-xs text-muted dark:text-gray-400"
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
                                <p class="text-[11px] text-muted dark:text-gray-400">Floor</p>
                                <p class="text-sm font-medium text-secondary dark:text-white">
                                    {{ latestAdmission.room?.floor || "—" }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <DoorOpen class="h-3.5 w-3.5 text-primary" />
                            <div>
                                <p class="text-[11px] text-muted dark:text-gray-400">Room</p>
                                <p class="text-sm font-medium text-secondary dark:text-white">
                                    {{ latestAdmission.room?.room_no || "—" }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <BedDouble class="h-3.5 w-3.5 text-primary" />
                            <div>
                                <p class="text-[11px] text-muted dark:text-gray-400">Bed</p>
                                <p class="text-sm font-medium text-secondary dark:text-white">
                                    {{ latestAdmission.bed?.bed_no || "—" }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="latestAdmission.current_contract"
                        class="mt-4 border-t border-muted-light pt-4"
                    >
                        <p class="mb-2 text-xs font-semibold text-secondary dark:text-white">
                            Current Contract
                        </p>

                        <div class="space-y-2">
                            <div
                                class="rounded-lg bg-white px-3 py-2 border border-muted-light dark:bg-secondary"
                            >
                                <div class="flex justify-between">
                                    <span class="text-xs text-muted dark:text-gray-400">
                                        {{
                                            latestAdmission.current_contract
                                                ?.category || "—"
                                        }}
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-primary"
                                    >
                                        {{
                                            formatCurrency(
                                                latestAdmission
                                                    .current_contract?.price,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div class="mt-1 text-xs text-secondary dark:text-white">
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
