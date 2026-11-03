<template>
    <div
        class="flex h-full flex-col rounded-2xl border border-slate-100 bg-white p-4 shadow-sm transition hover:border-primary-200 hover:shadow-md sm:p-5 dark:border-white/10 dark:bg-secondary dark:hover:border-primary-500/20"
    >
        <div class="flex items-start gap-3">
            <PatientAvatar
                :src="patient.avatar"
                :name="patient.full_name"
                size-class="h-12 w-12 text-base"
                class="shrink-0"
            />

            <div class="min-w-0 flex-1">
                <p
                    class="break-words text-sm font-semibold leading-snug text-slate-900 dark:text-white"
                >
                    {{ patient.full_name }}
                </p>

                <p
                    class="mt-1 flex items-start gap-1 text-xs text-slate-400 dark:text-gray-500"
                    :title="patient.location?.full_address ?? undefined"
                >
                    <MapPin class="mt-0.5 h-3 w-3 shrink-0" />
                    <span class="line-clamp-2 break-words">
                        {{ patient.location?.full_address ?? "—" }}
                    </span>
                </p>
            </div>
        </div>

        <div
            class="mt-3 flex flex-wrap items-center gap-1 border-t border-slate-100 pt-3 dark:border-white/10"
        >
            <button
                v-for="item in actionMenuItems"
                :key="item.label"
                type="button"
                :title="item.label"
                :aria-label="item.label"
                class="flex h-9 w-9 items-center justify-center rounded-md transition sm:h-8 sm:w-8"
                :class="item.class"
                @click.stop="item.action(patient)"
            >
                <component :is="item.icon" class="h-4 w-4" />
            </button>
        </div>

        <div
            class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 border-t border-slate-100 pt-3 text-xs dark:border-white/10"
        >
            <div class="min-w-0">
                <p class="text-slate-400 dark:text-gray-500">Gender</p>
                <p class="mt-0.5 font-medium text-slate-700 dark:text-gray-400">
                    {{ patient.gender ?? "—" }}
                </p>
            </div>

            <div class="min-w-0">
                <p class="text-slate-400 dark:text-gray-500">Age</p>
                <p class="mt-0.5 break-words font-medium text-slate-700 dark:text-gray-400">
                    {{ calculateAge(patient.date_of_birth) }}
                </p>
            </div>

            <div class="min-w-0">
                <p class="text-slate-400 dark:text-gray-500">Citizen</p>
                <p class="mt-0.5 break-words font-medium text-slate-700 dark:text-gray-400">
                    {{ patient.citizenship ?? "—" }}
                </p>
            </div>

            <div class="min-w-0">
                <p class="text-slate-400 dark:text-gray-500">Care Type</p>
                <p class="mt-0.5 break-words font-medium text-slate-700 dark:text-gray-400">
                    {{ careType }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { MapPin } from "lucide-vue-next";
import { calculateAge } from "~/utils/user";
import PatientAvatar from "~/components/ui/PatientAvatar.vue";
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
