<template>
    <Transition name="fade">
        <Teleport to="body">
            <div
                v-if="open"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-primary-900/50 backdrop-blur-sm p-4"
                @click.self="emit('close')"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 dark:bg-secondary dark:ring-primary-500/20"
                >
                    <div
                        class="flex items-center justify-between border-b border-primary-100 px-6 py-4 dark:border-primary-500/20"
                    >
                        <div>
                            <h2
                                class="text-base font-semibold text-primary-900 dark:text-primary-300"
                            >
                                Period changes
                            </h2>

                            <p
                                class="mt-1 text-xs text-muted dark:text-gray-400"
                            >
                                {{ formatDate(periods[0]?.start_date) }}
                                →
                                {{
                                    formatDate(
                                        periods[periods.length - 1]?.end_date,
                                    )
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                            @click="emit('close')"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[70vh] space-y-2 overflow-y-auto p-6">
                        <div
                            v-for="period in periods"
                            :key="period.admission_period_id"
                            class="flex items-center justify-between gap-3 rounded-lg border px-3.5 py-2.5"
                            :class="
                                period.is_current
                                    ? 'border-primary/30 bg-primary/5 dark:border-primary-300/30 dark:bg-primary-300/10'
                                    : 'border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/5'
                            "
                        >
                            <div class="min-w-0">
                                <p
                                    class="text-sm font-semibold text-primary-950 dark:text-primary-300"
                                >
                                    {{ period.accommodation_type }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ formatDate(period.start_date) }}
                                    →
                                    {{ formatDate(period.end_date) }}
                                </p>
                            </div>

                            <span
                                class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                :class="
                                    period.is_current
                                        ? 'bg-primary/10 text-primary dark:bg-primary-300/15 dark:text-primary-300'
                                        : 'bg-slate-200 text-slate-600 dark:bg-white/15 dark:text-gray-400'
                                "
                            >
                                {{
                                    period.is_current
                                        ? "Current"
                                        : reasonLabel(period.reason)
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex justify-end border-t border-primary-100 bg-slate-50 px-6 py-4 dark:border-primary-500/20 dark:bg-white/5"
                    >
                        <button
                            type="button"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition hover:opacity-90"
                            @click="emit('close')"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </Transition>
</template>

<script setup lang="ts">
import type { DischargeChainPeriod } from "~/types/invoice";
import { formatDate } from "~/utils/time";

defineProps<{
    open: boolean;
    periods: DischargeChainPeriod[];
}>();

const emit = defineEmits<{
    close: [];
}>();

function reasonLabel(reason: string | null) {
    return (
        {
            admitted: "Admitted",
            extended: "Extended",
            room_change: "Room change",
            accommodation_change: "Accommodation change",
        }[reason ?? ""] ?? "Previous"
    );
}
</script>
