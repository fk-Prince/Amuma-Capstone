<script setup lang="ts">
import {
    Briefcase,
    Heart,
    Droplet,
    Calendar,
    Phone,
    Globe2,
    Ruler,
    Weight,
    Pill,
    HeartPulse,
    MapPin,
} from "lucide-vue-next";
import type { PatientRetrieve } from "~/types/patient";
import { formatDate } from "~/utils/time";
import PatientAvatar from "~/components/ui/PatientAvatar.vue";

defineProps<{
    patient: PatientRetrieve;
    isEdit?: boolean;
}>();

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
            <div class="flex flex-wrap items-start gap-x-12 gap-y-5">
                <div class="flex items-start gap-4">
                    <PatientAvatar
                        :src="patient.avatar"
                        :name="patient.full_name"
                        size-class="h-14 w-14 text-xl"
                        rounded-class="rounded-xl"
                    />

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
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-12 gap-y-4 sm:pt-6">
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
                class="mt-6 grid gap-6 border-t border-muted-light pt-6 sm:grid-cols-3 dark:border-white/10"
            >
                <div class="flex items-center gap-3">
                    <Briefcase class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">
                            Occupation
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium text-secondary dark:text-white"
                        >
                            {{ patient.occupation || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Heart class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">
                            Marital Status
                        </p>
                        <p
                            class="mt-0.5 text-sm font-medium capitalize text-secondary dark:text-white"
                        >
                            {{ patient.marital_status || "—" }}
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
                class="mt-6 grid gap-6 border-t border-muted-light pt-6 sm:grid-cols-3 dark:border-white/10"
            >
                <div class="flex items-center gap-3">
                    <MapPin class="h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">
                            Address
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
    </div>
</template>
