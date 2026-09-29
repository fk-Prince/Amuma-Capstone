<template>
    <BaseInput
        v-model="value"
        :label="label"
        :error="error"
        :placeholder="placeholder"
        :required="required"
    >
        <template #prefix>
            <span v-if="country" class="flex items-center gap-1.5 text-sm">
                <svg
                    viewBox="0 0 30 15"
                    class="h-3.5 w-7 shrink-0 rounded-[2px] ring-1 ring-black/10"
                    aria-label="Philippines"
                >
                    <rect width="30" height="7.5" fill="#0038A8" />
                    <rect y="7.5" width="30" height="7.5" fill="#CE1126" />
                    <path d="M0 0 L12.99 7.5 L0 15 Z" fill="#FFFFFF" />
                    <circle cx="4.33" cy="7.5" r="1.6" fill="#FCD116" />
                    <circle cx="1.3" cy="1.9" r="0.6" fill="#FCD116" />
                    <circle cx="1.3" cy="13.1" r="0.6" fill="#FCD116" />
                    <circle cx="10.6" cy="7.5" r="0.6" fill="#FCD116" />
                </svg>
                <span class="text-slate-500 dark:text-gray-400">{{
                    country.dial
                }}</span>
            </span>
        </template>
    </BaseInput>
</template>

<script setup lang="ts">
import { computed } from "vue";
import BaseInput from "./BaseInput.vue";

defineOptions({ name: "PhoneInput" });

const COUNTRIES = [{ code: "PH", dial: "+63" }];

const country = COUNTRIES[0];

const props = defineProps<{
    modelValue?: string;
    label?: string;
    error?: string;
    placeholder?: string;
    required?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
}>();

// The dial code is shown beside the field, so anything the user pastes that
// repeats it — +63, 63, or the local trunk 0 — is stripped back out.
function national(input: string) {
    const cleaned = input.replace(/[^\d+\s-]/g, "");

    return cleaned
        .replace(/^\+?63[\s-]?/, "")
        .replace(/^0(?=9)/, "")
        .trimStart();
}

const value = computed({
    get: () => props.modelValue,
    set: (val: string | number) =>
        emit("update:modelValue", national(String(val))),
});
</script>
