<template>
    <BaseInput v-model="value">
        <template #prefix>
            <span v-if="country" class="flex items-center gap-1.5 text-sm">
                <span class="text-base leading-none">{{ country.flag }}</span>
                <span class="text-slate-500">{{ country.dial }}</span>
            </span>
        </template>
    </BaseInput>
</template>

<script setup lang="ts">
import { computed } from "vue";
import BaseInput from "./BaseInput.vue";

defineOptions({ name: "PhoneInput" });

const COUNTRIES = [{ code: "PH", flag: "🇵🇭", dial: "+63" }];

const country = COUNTRIES[0];

const props = defineProps<{
    modelValue?: string | number;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: string | number): void;
}>();

const value = computed({
    get: () => props.modelValue,
    set: (val: string | number) => emit("update:modelValue", val),
});
</script>
