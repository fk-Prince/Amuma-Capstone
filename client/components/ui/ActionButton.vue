<script setup>
import { watch, onBeforeUnmount } from "vue";
import { string } from "zod";

const props = defineProps({
    variant: {
        type: String,
        default: "outline",
        validator: (value) => ["primary", "outline", "danger"].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    tooltip: {
        type: String,
        default: "",
    },
    type: {
        type: String,
        default: "button",
    },
    loading: {
        type: Boolean,
        default: false,
    },
    extraClass: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["click"]);

const variantClasses = {
    primary: {
        enabled:
            "border-primary-200 bg-primary text-white hover:bg-primary-600",
        disabled: "border-gray-200 bg-gray-100 text-gray-400",
    },
    outline: {
        enabled: "border-primary-200 text-primary hover:bg-primary-50",
        disabled: "border-gray-200 text-gray-400",
    },
    danger: {
        enabled: "border-rose-200 text-rose-600 hover:bg-rose-50",
        disabled: "border-gray-200 text-gray-400",
    },
};

const isBlocked = computed(() => props.disabled || props.loading);

const handleClick = (event) => {
    if (!isBlocked.value) {
        emit("click", event);
    }
};

watch(
    () => props.loading,
    (isLoading) => {
        document.body.classList.toggle("cursor-wait", isLoading);
    },
);

onBeforeUnmount(() => {
    if (props.loading) {
        document.body.classList.remove("cursor-wait");
    }
});
</script>

<template>
    <div class="relative inline-block group">
        <button
            :type="type"
            :disabled="isBlocked"
            class="rounded-lg border px-4 py-2 text-sm font-medium transition"
            :class="[
                disabled
                    ? variantClasses[variant].disabled
                    : variantClasses[variant].enabled,
                loading
                    ? 'cursor-wait opacity-60'
                    : disabled && 'cursor-not-allowed opacity-60',
                extraClass,
            ]"
            @click="handleClick"
        >
            <slot />
        </button>

        <div
            v-if="disabled && tooltip"
            class="pointer-events-none absolute right-0 top-full z-50 mt-2 hidden w-max max-w-xs rounded-md bg-gray-900 px-3 py-2 text-[12px] text-white shadow-lg group-hover:block"
        >
            {{ tooltip }}
        </div>
    </div>
</template>

<!-- <script setup>
const props = defineProps({
    variant: {
        type: String,
        default: "outline",
        validator: (value) => ["primary", "outline", "danger"].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    tooltip: {
        type: String,
        default: "",
    },
    type: {
        type: String,
        default: "button",
    },
    loading: {
        type: Boolean,
        default: false,
    },
    extraClass: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["click"]);

const variantClasses = {
    primary: {
        enabled:
            "border-primary-200 bg-primary text-white hover:bg-primary-600",
        disabled: "border-gray-200 bg-gray-100 text-gray-400",
    },
    outline: {
        enabled: "border-primary-200 text-primary hover:bg-primary-50",
        disabled: "border-gray-200 text-gray-400",
    },
    danger: {
        enabled: "border-rose-200 text-rose-600 hover:bg-rose-50",
        disabled: "border-gray-200 text-gray-400",
    },
};

const isBlocked = computed(() => props.disabled || props.loading);

const handleClick = (event) => {
    if (!isBlocked.value) {
        emit("click", event);
    }
};
</script>

<template>
    <div class="relative inline-block group">
        <button
            :type="type"
            :disabled="isBlocked"
            class="rounded-lg border px-4 py-2 text-sm font-medium transition"
            :class="[
                disabled
                    ? variantClasses[variant].disabled
                    : variantClasses[variant].enabled,
                loading
                    ? 'cursor-wait opacity-60'
                    : disabled
                      ? 'cursor-not-allowed opacity-60'
                      : 'cursor-pointer',
                extraClass,
            ]"
            @click="handleClick"
        >
            <slot />
        </button>

        <div
            v-if="disabled && tooltip"
            class="pointer-events-none absolute right-0 top-full z-50 mt-2 hidden w-max max-w-xs rounded-md bg-gray-900 px-3 py-2 text-[12px] text-white shadow-lg group-hover:block"
        >
            {{ tooltip }}
        </div>
    </div>
</template> -->
