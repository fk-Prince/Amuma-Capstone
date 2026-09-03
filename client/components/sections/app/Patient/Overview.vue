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
    CalendarClock,
    Video,
    VideoOff,
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

// A VIP room is the only accommodation that comes with a camera; everyone else
// gets the schedule instead.
const isVipFacility = computed(() => {
    const accommodation =
        latestAdmission.value?.current_contract?.accommodation_type ??
        latestAdmission.value?.room?.room_type;

    return String(accommodation ?? "").toUpperCase() === "VIP";
});

const schedules = computed<any[]>(
    () => (props.patient as any)?.schedules ?? [],
);

function scheduleStatusClasses(status?: string) {
    switch (String(status ?? "").toLowerCase()) {
        case "ongoing":
            return "bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300";
        case "pending":
            return "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300";
        case "completed":
            return "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300";
        case "missed":
            return "bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300";
        default:
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
    }
}

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
        return "bg-[#E6F1FA] text-[#2563A6] dark:text-blue-300 dark:bg-blue-500/15";
    }

    if (value.includes("discharge")) {
        return "bg-[#FBE8E6] text-[#B3402F] dark:text-rose-300 dark:bg-rose-500/15";
    }

    if (value.includes("cancel")) {
        return "bg-[#F1F1F1] text-[#6B7280]";
    }

    return "bg-[#FDF3DE] text-[#966B1F] dark:text-amber-300 dark:bg-amber-500/15";
}

function cardClasses(status?: string) {
    return isAdmitted(status)
        ? "border-l-4 border-primary bg-primary-50 dark:bg-primary-500/10"
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
                            class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary dark:bg-primary-500/10"
                        >
                            {{ patient.gender }}
                        </span>

                        <span
                            v-if="patient.latest_admission"
                            class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary dark:bg-primary-500/10"
                        >
                            {{
                                patient.latest_admission?.status.toLowerCase() ===
                                "admitted"
                                    ? "Currently Admitted"
                                    : patient.latest_admission?.status
                            }}
                        </span>
                        <!-- <span
                            class="rounded-full bg-muted-light px-3 py-1 text-xs font-medium text-secondary dark:bg-white/10 dark:text-white"
                        >
                            {{ patient.blood_type || "No blood type on file" }}
                        </span> -->
                    </div>
                </div>
            </div>

            <div
                class="mt-6 grid gap-6 border-t border-muted-light pt-6 sm:grid-cols-3 dark:border-white/10"
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
                class="mt-6 grid gap-6 border-t border-muted-light pt-6 sm:grid-cols-4 dark:border-white/10"
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
                class="mt-6 grid gap-6 border-t border-muted-light pt-6 sm:grid-cols-3 dark:border-white/10"
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

            <div class="mt-6 border-t border-muted-light pt-6 dark:border-white/10">
                <p class="mb-2 text-xs text-muted dark:text-gray-400">Allergies</p>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="allergy in patient.allergies"
                        :key="allergy"
                        class="rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
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
                    class="flex items-center gap-1.5 rounded-lg border border-muted-light px-3 py-1.5 text-xs font-medium text-secondary transition-colors hover:border-primary/40 hover:text-primary-600 dark:hover:text-primary-300 dark:border-white/10 dark:text-white"
                    @click="goToAdmissionHistory"
                >
                    <History class="h-3.5 w-3.5" />
                    View Admission History
                </button>
            </div>

            <div class="mt-5">
                <div
                    class="rounded-xl p-4 transition hover:bg-primary-50/60 dark:hover:bg-primary-500/10"
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
                        class="mt-4 border-t border-muted-light pt-4 dark:border-white/10"
                    >
                        <p class="mb-2 text-xs font-semibold text-secondary dark:text-white">
                            Current Contract
                        </p>

                        <div class="space-y-2">
                            <div
                                class="rounded-lg bg-white px-3 py-2 border border-muted-light dark:bg-secondary dark:border-white/10"
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

        <section v-if="isVipFacility" class="rounded-2xl bg-white p-6 shadow-sm dark:bg-secondary">
            <div class="flex items-center gap-2">
                <Video class="h-4 w-4 text-primary" />
                <h3 class="font-semibold text-secondary dark:text-white">Room Camera</h3>
            </div>

            <p class="mt-1 text-xs text-muted dark:text-gray-400">
                VIP rooms include a live camera. Only the patient's authorised
                family and assigned staff may view it.
            </p>

            <div
                class="mt-4 flex aspect-video items-center justify-center rounded-xl bg-slate-900 text-center"
            >
                <div class="px-6">
                    <VideoOff class="mx-auto h-7 w-7 text-white/40" />

                    <p class="mt-2 text-sm font-medium text-white/80">
                        Camera feed unavailable
                    </p>

                    <p class="mt-1 text-xs text-white/50">
                        Room {{ latestAdmission?.room?.room_no || "—" }} · no
                        stream is connected to this room yet.
                    </p>
                </div>
            </div>
        </section>

        <section v-else class="rounded-2xl bg-white p-6 shadow-sm dark:bg-secondary">
            <div class="flex items-center gap-2">
                <CalendarClock class="h-4 w-4 text-primary" />
                <h3 class="font-semibold text-secondary dark:text-white">Recent Schedule</h3>
            </div>

            <p v-if="!schedules.length" class="mt-4 text-sm text-muted dark:text-gray-400">
                No recent schedule.
            </p>

            <ul v-else class="mt-4 space-y-2.5">
                <li
                    v-for="schedule in schedules"
                    :key="schedule.uuid"
                    class="flex items-start justify-between gap-3 rounded-xl border border-muted-light p-3.5 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-secondary dark:text-white">
                                {{ schedule.category || "Schedule" }}
                            </p>

                            <span
                                v-if="schedule.schedule_code"
                                class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-white/10 dark:text-gray-400"
                            >
                                {{ schedule.schedule_code }}
                            </span>
                        </div>

                        <p class="mt-0.5 text-xs text-muted dark:text-gray-400">
                            {{ formatDate(schedule.scheduled_at) }}
                        </p>

                        <p
                            v-if="schedule.address"
                            class="mt-0.5 truncate text-xs text-muted dark:text-gray-400"
                        >
                            {{ schedule.address }}
                        </p>
                    </div>

                    <span
                        class="shrink-0 rounded-full px-3 py-1 text-xs font-medium capitalize"
                        :class="scheduleStatusClasses(schedule.status)"
                    >
                        {{ schedule.status }}
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
