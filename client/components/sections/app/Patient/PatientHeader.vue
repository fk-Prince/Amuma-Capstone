<script setup lang="ts">
import { calculateAge } from "~/utils/user";
import {
    Calendar,
    MapPin,
    Pencil,
    Printer,
    Send,
    Share2,
    UserRound,
} from "lucide-vue-next";

import type { PatientRetrieve } from "~/types/patient";
import { formatDate } from "~/utils/time";

defineProps<{
    patient: PatientRetrieve;
}>();

function fullName(
    firstName?: string | null,
    middleName?: string | null,
    lastName?: string | null,
) {
    return [firstName, middleName, lastName].filter(Boolean).join(" ");
}
</script>

<template>
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary text-xl font-semibold text-white"
                >
                    {{ patient.first_name.charAt(0) }}
                </div>

                <div>
                    <h1 class="text-lg font-semibold text-gray-900">
                        {{
                            fullName(
                                patient.first_name,
                                patient.middle_name,
                                patient.last_name,
                            )
                        }}
                    </h1>

                    <div
                        class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-500"
                    >
                        <span class="flex items-center gap-1.5">
                            <Calendar class="h-4 w-4 text-primary" />
                            <div>
                                <p class="text-xs text-muted">Date of birth</p>
                                {{ formatDate(patient.date_of_birth) }}
                            </div>
                        </span>

                        <span class="text-gray-300">•</span>

                        <span class="flex items-center gap-1.5">
                            <UserRound class="h-4 w-4 text-primary" />
                            <div>
                                <p class="text-xs text-muted">Age</p>
                                {{ calculateAge(patient.date_of_birth, false) }}
                            </div>
                        </span>

                        <span class="text-gray-300">•</span>

                        <span class="flex items-center gap-1.5">
                            <MapPin class="h-4 w-4 text-primary" />
                            <div>
                                <p class="text-xs text-muted">Address</p>
                                {{
                                    patient.location?.full_address ||
                                    "No address provided"
                                }}
                            </div>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="mt-5 flex justify-end gap-2 border-t border-gray-100 pt-4 text-sm text-gray-500"
        >
            <button
                class="flex items-center gap-2 rounded-lg px-3 py-2 transition hover:bg-primary-50 hover:text-primary"
            >
                <Share2 class="h-4 w-4" />
                Share
            </button>

            <button
                class="flex items-center gap-2 rounded-lg px-3 py-2 transition hover:bg-primary-50 hover:text-primary"
            >
                <Send class="h-4 w-4" />
                Send
            </button>

            <button
                class="flex items-center gap-2 rounded-lg px-3 py-2 transition hover:bg-primary-50 hover:text-primary"
            >
                <Printer class="h-4 w-4" />
                Print
            </button>

            <button
                class="flex items-center gap-2 rounded-lg px-3 py-2 transition hover:bg-primary-50 hover:text-primary"
            >
                <Pencil class="h-4 w-4" />
                Edit
            </button>
        </div>
    </div>
</template>
