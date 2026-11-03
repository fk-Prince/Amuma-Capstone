<template>
    <BaseInput
        v-model="value"
        kind="phone"
        :label="label"
        :error="error"
        :placeholder="placeholder"
        :required="required"
    >
        <template #prefix>
            <span v-if="country" class="flex items-center gap-1.5 text-sm">
                <img :src="PH_FLAG" alt="Philippines" :class="FLAG_ICON_CLASS" />
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
import { FLAG_ICON_CLASS, PH_FLAG } from "~/utils/flags";

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
// repeats it — +63, 63, or the local trunk 0 — is stripped back out, and only
// the 10 digits after +63 are kept (the 11-digit 09XX… form, without the 0).
function national(input: string) {
    const cleaned = input.replace(/[^\d+\s-]/g, "");

    const local = cleaned
        .replace(/^\+?63[\s-]?/, "")
        .replace(/^0(?=9)/, "")
        .trimStart();

    let digits = 0;
    let out = "";
    for (const char of local) {
        if (/\d/.test(char)) {
            if (digits === 10) break;
            digits++;
        }
        out += char;
    }
    return out;
}

const value = computed({
    get: () => props.modelValue,
    set: (val: string | number) =>
        emit("update:modelValue", national(String(val))),
});
</script>
