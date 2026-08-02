<script setup lang="ts">
import { ref } from "vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";

function getLocalDateStr(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

const todayStr = getLocalDateStr(new Date());
const maxDateStr = getLocalDateStr(new Date(Date.now() + 7 * 86400000));

const period = ref(todayStr);

function handleApply(value: string) {
    console.log("Confirmed date:", value);
}
</script>

<template>
    <DatePickerField
        v-model="period"
        :min="todayStr"
        :max="maxDateStr"
        placeholder="Please select a period."
        @apply="handleApply"
    />
</template>
