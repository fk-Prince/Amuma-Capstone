<script setup lang="ts">
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
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

const moduleItems = [
    { label: "All modules", value: "" },
    ...AUDIT_MODULE_OPTIONS,
];

const actionItems = [
    { label: "All actions", value: "" },
    ...AUDIT_ACTION_OPTIONS,
];
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
                placeholder="User name, email or details"
                input-class="px-4 py-2.5 rounded-lg w-full"
            />
        </div>

        <Combobox
            :modelValue="module"
            :items="moduleItems"
            placeholder="All modules"
            class="w-full sm:w-48"
            input-class="px-4 py-2.5 rounded-lg"
            @update:modelValue="$emit('update:module', $event ?? '')"
        />

        <Combobox
            :modelValue="action"
            :items="actionItems"
            placeholder="All actions"
            class="w-full sm:w-44"
            input-class="px-4 py-2.5 rounded-lg"
            @update:modelValue="$emit('update:action', $event ?? '')"
        />

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
