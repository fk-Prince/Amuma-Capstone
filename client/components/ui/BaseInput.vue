<template>
    <div class="flex flex-col gap-1.5 font-primary">
        <label v-if="label" class="text-sm font-semibold text-slate-700">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>

        <div
            class="flex items-center border-[1.5px] rounded-lg bg-white overflow-hidden transition"
            :class="[
                currentError
                    ? 'border-red-400 focus-within:ring-red-500/15'
                    : 'border-slate-200 focus-within:border-blue-500 focus-within:ring-blue-500/15',
                'focus-within:ring-2',
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
                :maxlength="textMax"
                :placeholder="placeholder"
                class="flex-1 min-w-0 px-3.5 py-2.5 text-sm text-slate-800 bg-transparent outline-none placeholder:text-slate-400"
                :class="[hasPrefix ? 'pl-2' : '', inputClass]"
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
import Search from "../icons/search.vue";

defineOptions({ name: "BaseInput" });

const props = defineProps({
    modelValue: { type: [String, Number], default: "" },
    label: { type: String, default: "" },
    placeholder: String,
    error: String,
    required: { type: Boolean, default: false },
    mode: { type: String, default: "text" },
    inputClass: { type: String, default: "" },
    isSearch: { type: Boolean, default: false },
    textMax: { type: Number, default: 255 },
});

const emit = defineEmits(["update:modelValue"]);

const currentError = ref(props.error);

watch(
    () => props.error,
    (val) => {
        currentError.value = val;
    },
);

const value = computed({
    get: () => props.modelValue,
    set: (val) => {
        emit("update:modelValue", val);

        if (currentError.value) {
            currentError.value = "";
        }
    },
});

const slots = useSlots();

const hasPrefix = computed(() => !!slots.prefix);
const hasSuffix = computed(() => !!slots.suffix);

const inputType = computed(() => {
    if (props.mode === "password") return "password";
    if (props.mode === "email") return "email";
    return "text";
});
</script>
