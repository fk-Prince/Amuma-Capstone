<template>
    <div
        class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:border-primary-200 hover:shadow-md dark:border-white/10 dark:bg-secondary dark:hover:border-primary-500/20"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div
                    class="h-12 w-12 shrink-0 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10"
                >
                    <img
                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(patient.full_name)}&background=random&color=fff`"
                        :alt="patient.full_name"
                        class="h-full w-full object-cover"
                    />
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900 truncate dark:text-white">
                        {{ patient.full_name }}
                    </p>

                    <p
                        class="flex items-center gap-1 text-xs text-slate-400 truncate dark:text-gray-500"
                    >
                        <MapPin class="h-3 w-3 shrink-0" />
                        {{ patient.location?.full_address ?? "—" }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <button
                    v-for="item in actionMenuItems"
                    :key="item.label"
                    type="button"
                    :title="item.label"
                    class="flex h-8 w-8 items-center justify-center rounded-md transition"
                    :class="item.class"
                    @click.stop="item.action(patient)"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div
            class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 border-t border-slate-100 pt-3 text-xs dark:border-white/10"
        >
            <div>
                <p class="text-slate-400 dark:text-gray-500">Gender</p>
                <p class="mt-0.5 font-medium text-slate-700 dark:text-gray-400">
                    {{ patient.gender ?? "—" }}
                </p>
            </div>

            <div>
                <p class="text-slate-400 dark:text-gray-500">Age</p>
                <p class="mt-0.5 font-medium text-slate-700 dark:text-gray-400">
                    {{ calculateAge(patient.date_of_birth) }}
                </p>
            </div>

            <div>
                <p class="text-slate-400 dark:text-gray-500">Citizen</p>
                <p class="mt-0.5 font-medium text-slate-700 dark:text-gray-400">
                    {{ patient.citizenship ?? "—" }}
                </p>
            </div>

            <div>
                <p class="text-slate-400 dark:text-gray-500">Care Type</p>
                <p class="mt-0.5 font-medium text-slate-700 truncate dark:text-gray-400">
                    {{ careType }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { MapPin } from "lucide-vue-next";
import { calculateAge } from "~/utils/user";
import type { PatientRetrieve } from "~/types/patient";

defineProps<{
    patient: PatientRetrieve;
    careType: string;
    actionMenuItems: {
        label: string;
        icon: any;
        class: string;
        action: (patient: PatientRetrieve) => void;
    }[];
}>();
</script>
