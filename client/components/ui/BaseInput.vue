<template>
    <div class="flex w-full flex-col gap-1.5 font-primary" :class="[className]">
        <label v-if="label" class="text-sm font-semibold text-slate-700 dark:text-gray-300">
            {{ label }}
            <span v-if="required" class="text-danger ml-0.5">*</span>
        </label>

        <div
            class="group flex items-center rounded-xl bg-white overflow-hidden transition-all duration-150 dark:bg-white/5"
            :class="[
                currentError
                    ? 'border-red-400 focus-within:ring-4 focus-within:ring-red-500/10'
                    : 'border-slate-200 focus-within:border-primary-500 focus-within:ring-4 focus-within:ring-primary-500/10 dark:border-white/10',
                disabled
                    ? 'bg-slate-100 dark:bg-white/[0.03]'
                    : readonly
                      ? 'bg-slate-50 dark:bg-white/[0.03]'
                      : 'bg-white dark:bg-white/5',
                size === 'lg' ? 'rounded-2xl' : 'rounded-xl',
                boxClass,
            ]"
        >
            <span
                v-if="hasPrefix"
                :class="[
                    'flex items-center text-slate-400 transition-colors duration-150 group-focus-within:text-primary-500 flex-shrink-0 dark:text-gray-500',
                    size === 'lg' ? 'pl-4' : 'pl-3.5',
                ]"
            >
                <slot name="prefix" />
            </span>

            <textarea
                v-if="props.mode === 'textarea'"
                v-model="value"
                :maxlength="textMax"
                :placeholder="placeholder"
                :rows="rows"
                :disabled="disabled"
                :readonly="readonly"
                :class="[
                    'flex-1 min-w-0 bg-transparent outline-none placeholder:text-slate-400 text-slate-800 dark:text-gray-100 dark:placeholder:text-gray-500',
                    size === 'lg'
                        ? 'px-4 py-3.5 text-base'
                        : 'px-3.5 py-2.5 text-sm',
                    allowResize ? 'resize-y' : 'resize-none',
                    readonly ? 'cursor-default' : '',
                    inputClass,
                ]"
                @blur="validateSelf"
            />
            <input
                v-else
                v-model="value"
                :type="inputType"
                :maxlength="inputType === 'number' ? undefined : textMax"
                :min="min || undefined"
                :max="max || undefined"
                :placeholder="placeholder"
                :class="[
                    'flex-1 min-w-0 bg-transparent outline-none placeholder:text-slate-400 text-slate-800 dark:text-gray-100 dark:placeholder:text-gray-500',
                    size === 'lg'
                        ? 'px-4 py-3.5 text-base'
                        : 'px-3.5 py-2.5 text-sm',
                    hasPrefix ? (size === 'lg' ? 'pl-2.5' : 'pl-2') : '',
                    readonly ? 'cursor-default' : '',
                    inputClass,
                ]"
                @blur="validateSelf"
                :disabled="disabled"
                :readonly="readonly"
            />

            <span
                v-if="hasSuffix || isSearch"
                :class="[
                    'flex flex-shrink-0 items-center',
                    isSearch ? (size === 'lg' ? 'pr-4' : 'pr-3') + ' text-slate-400 dark:text-gray-500' : '',
                ]"
            >
                <slot v-if="hasSuffix" name="suffix" />
                <Search v-else-if="isSearch" />
            </span>
        </div>

        <p v-if="currentError" class="flex items-center gap-1 text-xs text-red-500 mt-0.5">
            {{ currentError }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, useSlots } from "vue";
import { type ZodType } from "zod";

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
        default: "border-[1.5px]",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
    className: {
        type: String,
        default: "",
    },
    allowResize: {
        type: Boolean,
        default: false,
    },
    rows: {
        type: Number,
        default: 0,
    },
    size: {
        type: String,
        default: "md",
    },
});

const emit = defineEmits(["update:modelValue"]);

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
        case "time":
            return "time";
        case "datetime":
            return "datetime-local";
        default:
            return "text";
    }
});
</script>