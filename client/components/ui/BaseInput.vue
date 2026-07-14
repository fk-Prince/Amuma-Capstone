<template>
    <div class="flex flex-col gap-1.5 font-primary">
        <label v-if="label" class="text-sm font-semibold text-slate-700">
            {{ label }}
            <span v-if="required" class="text-danger ml-0.5">*</span>
        </label>

        <div
            class="flex items-center rounded-lg bg-white overflow-hidden transition"
            :class="[
                currentError
                    ? 'border-red-400 focus-within:ring-red-500/15'
                    : 'border-slate-200 focus-within:border-blue-500 focus-within:ring-blue-500/15',
                disabled ? 'bg-slate-100' : 'bg-white',
                boxClass,
            ]"
        >
            <span
                v-if="hasPrefix"
                class="flex items-center pl-3.5 text-slate-400 flex-shrink-0"
            >
                <slot name="prefix" />
            </span>

            <input
                v-model="value"
                :type="inputType"
                :maxlength="inputType === 'number' ? undefined : textMax"
                :min="min || undefined"
                :max="max || undefined"
                :placeholder="placeholder"
                class="flex-1 min-w-0 px-3.5 py-2.5 text-sm text-slate-800 bg-transparent outline-none placeholder:text-slate-400"
                :class="[hasPrefix ? 'pl-2' : '', inputClass]"
                @blur="validateSelf"
                :disabled="disabled"
            />
            <span
                v-if="hasSuffix || isSearch"
                class="flex items-center flex-shrink-0 pr-3"
            >
                <slot v-if="hasSuffix" name="suffix" />
                <Search v-else-if="isSearch" />
            </span>
        </div>

        <p v-if="currentError" class="text-xs text-red-500 mt-0.5">
            {{ currentError }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, useSlots } from "vue";
import type { ZodType } from "zod";

import Search from "../icons/search.vue";

defineOptions({ name: "BaseInput" });

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    label: {
        type: String,
        default: "",
    },
    placeholder: String,
    error: String,
    schema: {
        type: Object as () => ZodType | undefined,
        default: undefined,
    },
    required: {
        type: Boolean,
        default: false,
    },
    mode: {
        type: String,
        default: "text",
    },
    inputClass: {
        type: String,
        default: "",
    },
    isSearch: {
        type: Boolean,
        default: false,
    },
    textMax: {
        type: Number,
        default: 255,
    },
    min: {
        type: String,
        default: "",
    },
    max: {
        type: String,
        default: "",
    },
    boxClass: {
        type: String,
        default: "border-[1.5px] focus-within:ring-2",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue"]);

// externally-supplied error (e.g. from a parent's full-schema safeParse on submit) takes precedence
const externalError = ref(props.error);
const localError = ref("");

const currentError = computed(() => externalError.value || localError.value);

watch(
    () => props.error,
    (val) => {
        externalError.value = val;
        if (val) localError.value = "";
    },
);

function validateSelf() {
    if (!props.schema) return;

    const result = props.schema.safeParse(props.modelValue);
    localError.value = result.success
        ? ""
        : (result.error.issues[0]?.message ?? "Invalid value");
}

const value = computed({
    get: () => props.modelValue,
    set: (val) => {
        emit("update:modelValue", val);
        externalError.value = "";
        if (localError.value) {
            validateSelf();
        }
    },
});

const slots = useSlots();

const hasPrefix = computed(() => !!slots.prefix);
const hasSuffix = computed(() => !!slots.suffix);

const inputType = computed(() => {
    switch (props.mode) {
        case "password":
            return "password";
        case "email":
            return "email";
        case "number":
            return "number";
        case "date":
            return "date";
        default:
            return "text";
    }
});
</script>
