<script setup lang="ts">
import BaseInput from "~/components/ui/BaseInput.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import {
    AUDIT_ACTION_OPTIONS,
    AUDIT_MODULE_OPTIONS,
} from "~/types/audit-log";

defineProps<{
    search: string;
    module: string;
    action: string;
    dateFrom: string;
    dateTo: string;
}>();

defineEmits<{
    (e: "update:search", value: string): void;
    (e: "update:module", value: string): void;
    (e: "update:action", value: string): void;
    (e: "update:dateFrom", value: string): void;
    (e: "update:dateTo", value: string): void;
}>();

const selectClass =
    "w-full sm:w-auto rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-primary dark:border-white/10 dark:bg-secondary dark:text-gray-200";
</script>

<template>
    <div
        class="flex flex-col sm:flex-row sm:flex-wrap gap-3 items-stretch sm:items-center flex-1"
    >
        <div class="relative w-full min-w-0 sm:flex-1 sm:max-w-xs">
            <BaseInput
                mode="text"
                :is-search="true"
                :modelValue="search"
                @update:modelValue="$emit('update:search', $event)"
                placeholder="Description or user email"
                input-class="px-4 py-2.5 rounded-lg w-full"
            />
        </div>

        <select
            :value="module"
            :class="selectClass"
            aria-label="Filter by module"
            @change="
                $emit(
                    'update:module',
                    ($event.target as HTMLSelectElement).value,
                )
            "
        >
            <option value="">All modules</option>
            <option
                v-for="option in AUDIT_MODULE_OPTIONS"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>

        <select
            :value="action"
            :class="selectClass"
            aria-label="Filter by action"
            @change="
                $emit(
                    'update:action',
                    ($event.target as HTMLSelectElement).value,
                )
            "
        >
            <option value="">All actions</option>
            <option
                v-for="option in AUDIT_ACTION_OPTIONS"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>

        <div class="flex items-center gap-2">
            <DatePickerField
                :modelValue="dateFrom"
                class-name="w-full min-w-0 sm:w-[150px]"
                placeholder="From"
                :default-to-today="false"
                @update:modelValue="$emit('update:dateFrom', $event)"
            />
            <span class="shrink-0 text-xs text-slate-400 dark:text-gray-500"
                >to</span
            >
            <DatePickerField
                :modelValue="dateTo"
                class-name="w-full min-w-0 sm:w-[150px]"
                placeholder="To"
                :default-to-today="false"
                @update:modelValue="$emit('update:dateTo', $event)"
            />
        </div>
    </div>
</template>
