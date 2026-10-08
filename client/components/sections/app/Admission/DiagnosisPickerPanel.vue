<template>
    <div class="min-h-0">
        <div
            class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-5 dark:border-white/10"
        >
            <div class="min-w-0">
                <h4 class="text-sm font-semibold">Patient diagnoses</h4>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                    Pick one to connect it to this charge.
                </p>
            </div>

            <button
                type="button"
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                aria-label="Close diagnosis list"
                @click="$emit('close')"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <p
            v-if="!diagnoses.length"
            class="px-5 py-10 text-center text-sm text-slate-400 dark:text-gray-500"
        >
            No diagnoses on record for this patient.
        </p>

        <ul
            v-else
            class="flex-1 divide-y divide-slate-100 overflow-y-auto dark:divide-white/10"
        >
            <li v-for="item in diagnoses" :key="item.uuid">
                <button
                    type="button"
                    class="flex w-full items-start justify-between gap-3 px-5 py-3.5 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                    :class="
                        selectedUuid === item.uuid
                            ? 'bg-primary/5'
                            : 'hover:bg-slate-50 dark:hover:bg-white/5'
                    "
                    :disabled="takenIds.has(item.uuid)"
                    @click="$emit('select', item.uuid)"
                >
                    <div class="min-w-0">
                        <p
                            class="text-sm font-medium text-slate-800 break-words dark:text-white"
                        >
                            {{ item.diagnosis }}
                        </p>
                        <p
                            v-if="item.diagnosis_date"
                            class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            {{ formatDate(item.diagnosis_date) }}
                        </p>
                    </div>

                    <span
                        v-if="selectedUuid === item.uuid"
                        class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-primary"
                    >
                        Selected
                    </span>
                    <span
                        v-else-if="item.paid"
                        class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        Paid
                    </span>
                    <span
                        v-else-if="item.charged"
                        class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        Charged
                    </span>
                    <span
                        v-else-if="takenIds.has(item.uuid)"
                        class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                    >
                        On another charge
                    </span>
                </button>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { X } from "lucide-vue-next";
import { formatDate } from "~/utils/time";

defineProps<{
    diagnoses: {
        uuid: string;
        diagnosis: string;
        diagnosis_date: string | null;
        charged: boolean;
        paid: boolean;
    }[];
    selectedUuid: string;
    takenIds: Set<string>;
}>();

defineEmits<{
    (e: "select", uuid: string): void;
    (e: "close"): void;
}>();
</script>
