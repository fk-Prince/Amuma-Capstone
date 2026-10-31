<script setup lang="ts">
import { computed } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import {
    SALES_STATUS_OPTIONS,
    type ReportPeriodMode,
    type SalesStatus,
} from "~/types/report";

const props = defineProps<{
    mode: ReportPeriodMode;
    anchor: Date;
    dateFrom: string;
    dateTo: string;
    status: SalesStatus;
    search: string;
}>();

const emit = defineEmits<{
    (e: "update:mode", value: ReportPeriodMode): void;
    (e: "update:anchor", value: Date): void;
    (e: "update:dateFrom", value: string): void;
    (e: "update:dateTo", value: string): void;
    (e: "update:status", value: SalesStatus): void;
    (e: "update:search", value: string): void;
}>();

const modeOptions: { label: string; value: ReportPeriodMode }[] = [
    { label: "Month", value: "month" },
    { label: "Quarter", value: "quarter" },
    { label: "Date range", value: "range" },
];

const anchorLabel = computed(() => {
    const year = props.anchor.getFullYear();

    if (props.mode === "quarter") {
        return `Q${Math.floor(props.anchor.getMonth() / 3) + 1} ${year}`;
    }

    return props.anchor.toLocaleDateString("en-US", {
        month: "long",
        year: "numeric",
    });
});

function shift(direction: number) {
    const step = props.mode === "quarter" ? 3 : 1;
    const next = new Date(props.anchor);

    next.setDate(1);
    next.setMonth(next.getMonth() + direction * step);

    emit("update:anchor", next);
}
</script>

<template>
    <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center">
        <div
            class="inline-flex w-fit rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-white/10 dark:bg-white/5"
        >
            <button
                v-for="option in modeOptions"
                :key="option.value"
                type="button"
                class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                :class="
                    mode === option.value
                        ? 'bg-white text-primary shadow-sm dark:bg-secondary dark:text-primary-300'
                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                "
                @click="emit('update:mode', option.value)"
            >
                {{ option.label }}
            </button>
        </div>

        <div
            v-if="mode !== 'range'"
            class="flex w-fit items-center rounded-xl border border-slate-200 bg-slate-50 px-1 py-1 dark:border-white/10 dark:bg-white/5"
        >
            <button
                type="button"
                aria-label="Previous period"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-white hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                @click="shift(-1)"
            >
                <ChevronLeft class="h-4 w-4" />
            </button>

            <span
                class="min-w-[120px] text-center text-sm font-medium text-slate-700 dark:text-gray-300"
            >
                {{ anchorLabel }}
            </span>

            <button
                type="button"
                aria-label="Next period"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-white hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                @click="shift(1)"
            >
                <ChevronRight class="h-4 w-4" />
            </button>
        </div>

        <div v-else class="flex items-center gap-2">
            <DatePickerField
                :model-value="dateFrom"
                class-name="w-full min-w-0 sm:w-[150px]"
                placeholder="From"
                :default-to-today="false"
                @update:model-value="emit('update:dateFrom', $event)"
            />
            <span class="shrink-0 text-xs text-slate-400 dark:text-gray-500">
                to
            </span>
            <DatePickerField
                :model-value="dateTo"
                class-name="w-full min-w-0 sm:w-[150px]"
                placeholder="To"
                :min="dateFrom"
                :default-to-today="false"
                @update:model-value="emit('update:dateTo', $event)"
            />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row lg:ml-auto">
            <Combobox
                :model-value="status"
                :items="SALES_STATUS_OPTIONS"
                placeholder="All statuses"
                class="w-full sm:w-44"
                input-class="px-4 py-2.5 rounded-lg"
                @update:model-value="emit('update:status', ($event ?? '') as SalesStatus)"
            />

            <BaseInput
                mode="text"
                :is-search="true"
                :model-value="search"
                placeholder="Invoice no. or patient"
                class-name="w-full sm:w-64"
                input-class="px-4 py-2.5 rounded-lg w-full"
                @update:model-value="emit('update:search', String($event))"
            />
        </div>
    </div>
</template>
