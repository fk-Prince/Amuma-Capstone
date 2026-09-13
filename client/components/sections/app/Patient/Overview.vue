<script setup lang="ts">
import { computed } from "vue";

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
    CalendarClock,
} from "lucide-vue-next";
import type { PatientRetrieve } from "~/types/patient";
import { formatDate } from "~/utils/time";

const props = defineProps<{
    patient: PatientRetrieve;
    isEdit?: boolean;
}>();

const schedules = computed<any[]>(
    () => (props.patient as any)?.schedules ?? [],
);

// undefined rather than null, so binding it to href drops the attribute
// instead of tripping the type.
function mapsUrl(schedule: any): string | undefined {
    const lat = Number(schedule?.latitude);
    const lng = Number(schedule?.longitude);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return undefined;

    return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
}

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

                    <h2
                        class="mt-1 text-xl font-semibold text-secondary dark:text-white"
                    >
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
                            {{ patient.blood_type || "N/A" }}
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
                        <p class="text-xs text-muted dark:text-gray-400">
                            Birthday
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
                            {{ formatDate(patient.date_of_birth) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Phone class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">
                            Contact
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
                            {{ formatPhone(patient.phone_number) || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Globe2 class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">
                            Citizenship
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
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
                        <p class="text-xs text-muted dark:text-gray-400">
                            Height
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
                            {{ patient.height ? patient.height + " cm" : "N/A" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Weight class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">
                            Weight
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
                            {{ patient.weight ? patient.weight + " kg" : "N/A" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Pill class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">
                            Recorded Medications
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
                            {{ patient.medications_count ?? 0 }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <HeartPulse class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">
                            Recorded Vital Signs
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
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
                        <p class="text-xs text-muted dark:text-gray-400">
                            Location
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
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
                        <p class="text-xs text-muted dark:text-gray-400">
                            Blood Type
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
                            {{ patient.blood_type || "N/A" }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="mt-6 border-t border-muted-light pt-6 dark:border-white/10"
            >
                <p class="mb-2 text-xs text-muted dark:text-gray-400">
                    Allergies
                </p>
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

        <section class="rounded-2xl bg-white p-6 shadow-sm dark:bg-secondary">
            <div class="flex items-center gap-2">
                <CalendarClock class="h-4 w-4 text-primary" />
                <h3 class="font-semibold text-secondary dark:text-white">
                    Recent Schedule
                </h3>
            </div>

            <p
                v-if="!schedules.length"
                class="mt-4 text-sm text-muted dark:text-gray-400"
            >
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
                            <p
                                class="text-sm font-semibold text-secondary dark:text-white"
                            >
                                {{ schedule.category || "Schedule" }}
                            </p>

                            <span
                                v-if="schedule.schedule_code"
                                class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-white/10 dark:text-gray-400"
                            >
                                {{ schedule.schedule_code }}
                            </span>

                            <span
                                v-if="schedule.type"
                                class="rounded-md bg-primary-50 px-1.5 py-0.5 text-[10px] font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                {{
                                    schedule.type === "ADL"
                                        ? "Activities of Daily Living"
                                        : schedule.type
                                }}
                            </span>
                        </div>

                        <p class="mt-0.5 text-xs text-muted dark:text-gray-400">
                            {{ formatDate(schedule.scheduled_at) }}
                        </p>

                        <a
                            v-if="schedule.address && mapsUrl(schedule)"
                            :href="mapsUrl(schedule)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-0.5 flex items-center gap-1 truncate text-xs text-primary hover:underline dark:text-primary-300"
                        >
                            <MapPin class="h-3 w-3 shrink-0" />
                            {{ schedule.address }}
                        </a>

                        <p
                            v-else-if="schedule.address"
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
