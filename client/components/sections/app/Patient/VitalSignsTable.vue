<script setup lang="ts">
import { computed, ref } from "vue";
import {
    Eye,
    Pencil,
    Trash2,
    ChevronLeft,
    ChevronRight,
    Plus,
} from "lucide-vue-next";
import type { Vital } from "~/types/medication";

const props = withDefaults(
    defineProps<{
        vitals?: Vital[];
        variant?: "table" | "preview";
    }>(),
    {
        vitals: () => [],
        variant: "table",
    },
);

const latestVital = computed(() => props.vitals[0] ?? null);

const months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

const today = new Date();

const currentMonth = ref(today.getMonth());
const currentYear = ref(today.getFullYear());

const currentMonthLabel = computed(() => {
    return `${months[currentMonth.value]} ${currentYear.value}`;
});

function shiftMonth(delta: number) {
    const date = new Date(currentYear.value, currentMonth.value + delta, 1);

    currentMonth.value = date.getMonth();
    currentYear.value = date.getFullYear();
}

const filteredVitals = computed(() => {
    return props.vitals.filter((vital) => {
        if (!vital.recordedDate) {
            return false;
        }

        const date = new Date(vital.recordedDate);

        return (
            date.getMonth() === currentMonth.value &&
            date.getFullYear() === currentYear.value
        );
    });
});

const emit = defineEmits<{
    (e: "add-vital"): void;
    (e: "edit-vital", vital: Vital): void;
}>();
</script>

<template>
    <div v-if="variant === 'preview'" class="space-y-3">
        <div
            v-if="!latestVital"
            class="py-8 text-center text-sm text-slate-400 dark:text-gray-500"
        >
            No vital signs recorded.
        </div>

        <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-gray-500">
                    Blood Pressure
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                    {{
                        latestVital.bloodPressureSystolic &&
                        latestVital.bloodPressureDiastolic
                            ? `${latestVital.bloodPressureSystolic}/${latestVital.bloodPressureDiastolic}`
                            : "—"
                    }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-gray-500">
                    Heart Rate
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                    {{ latestVital.heartRate || "—" }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-gray-500">
                    Oxygen
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                    {{
                        latestVital.oxygenSaturation
                            ? `${latestVital.oxygenSaturation}%`
                            : "—"
                    }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-gray-500">
                    Temperature
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                    {{
                        latestVital.temperature
                            ? `${latestVital.temperature}°F`
                            : "—"
                    }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-gray-500">
                    Resp. Rate
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                    {{ latestVital.respiratoryRate || "—" }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-gray-500">
                    Glucose
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                    {{ latestVital.bloodGlucose || "—" }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-gray-500">
                    Pain
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                    {{ latestVital.painLevel || "—" }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-gray-500">
                    Recorded
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">
                    {{ latestVital.recordedDate }}
                    {{ latestVital.recordedTime }}
                </p>
            </div>
        </div>
    </div>

    <div v-else class="space-y-4">
        <div class="flex items-center justify-between">
            <div
                class="flex items-center rounded-xl border border-slate-200 bg-white px-2 py-1.5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <button
                    type="button"
                    class="p-2 text-slate-400 hover:text-slate-700 dark:text-gray-500 dark:hover:text-gray-400"
                    @click="shiftMonth(-1)"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>

                <span
                    class="min-w-[130px] text-center text-sm font-medium text-slate-700 dark:text-gray-200"
                >
                    {{ currentMonthLabel }}
                </span>

                <button
                    type="button"
                    class="p-2 text-slate-400 hover:text-slate-700 dark:text-gray-500 dark:hover:text-gray-400"
                    @click="shiftMonth(1)"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>

            <button
                class="rounded-xl bg-primary flex items-center gap-3 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary/50"
                @click="emit('add-vital')"
            >
                <Plus class="h-4 w-4" />

                Add Vital
            </button>
        </div>

        <div
            class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1200px] text-left">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-white/10">
                            <th
                                class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Date
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Time
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Blood Pressure
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Heart Rate
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Resp. Rate
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Oxygen
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Temperature
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Glucose
                            </th>

                            <th
                                class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Pain
                            </th>

                            <th
                                class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-50 dark:divide-white/10">
                        <tr v-if="filteredVitals.length === 0">
                            <td
                                colspan="9"
                                class="py-16 text-center text-sm text-slate-400 dark:text-gray-500"
                            >
                                No vital signs recorded for this month.
                            </td>
                        </tr>

                        <tr
                            v-for="vital in filteredVitals"
                            :key="vital.id"
                            class="transition hover:bg-slate-50/60 dark:hover:bg-white/5"
                        >
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400">
                                {{ vital.recordedDate }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-gray-400">
                                {{ vital.recordedTime }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-gray-400">
                                {{
                                    vital.bloodPressureSystolic &&
                                    vital.bloodPressureDiastolic
                                        ? `${vital.bloodPressureSystolic}/${vital.bloodPressureDiastolic}`
                                        : "—"
                                }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-gray-400">
                                {{ vital.heartRate ?? "—" }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-gray-400">
                                {{ vital.respiratoryRate ?? "—" }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-gray-400">
                                {{
                                    vital.oxygenSaturation
                                        ? `${vital.oxygenSaturation}%`
                                        : "—"
                                }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-gray-400">
                                {{
                                    vital.temperature
                                        ? `${vital.temperature}°F`
                                        : "—"
                                }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-gray-400">
                                {{ vital.bloodGlucose ?? "—" }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-gray-400">
                                {{ vital.painLevel ?? "—" }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 dark:text-gray-500 dark:hover:bg-white/10"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>

                                    <button
                                        class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 dark:text-gray-500 dark:hover:bg-white/10"
                                        @click="emit('edit-vital', vital)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>

                                    <button
                                        class="rounded-md p-1.5 text-red-400 hover:bg-red-50"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
