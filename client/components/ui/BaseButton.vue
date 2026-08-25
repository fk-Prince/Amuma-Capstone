<script setup lang="ts">
import { computed } from "vue";
import { LoaderCircle } from "lucide-vue-next";

defineOptions({ name: "BaseButton" });

const props = defineProps({
    type: {
        type: String as () => "button" | "submit" | "reset",
        default: "button",
    },
    variant: { type: String, default: "primary" },
    size: { type: String, default: "md" },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    full: { type: Boolean, default: false },
    buttonClass: { type: String, default: "" },
});

const baseClass =
    "font-semibold rounded-xl transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed";

const variantClass = computed(() => {
    switch (props.variant) {
        case "secondary":
            return "bg-gray-100 text-gray-800 hover:bg-gray-200 border border-gray-200";
        case "danger":
            return "bg-red-600 text-white hover:bg-red-700";
        default:
            return "bg-blue-600 text-white hover:bg-blue-700";
    }
});

const sizeClass = computed(() => {
    switch (props.size) {
        case "sm":
            return "py-2 px-3 text-sm";
        case "lg":
            return "py-3.5 px-6 text-base";
        default:
            return "py-3 px-4 text-base";
    }
});

const widthClass = computed(() => (props.full ? "w-full" : ""));
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[baseClass, variantClass, sizeClass, widthClass, buttonClass]"
    >
        <LoaderCircle v-if="loading" class="h-4 w-4 shrink-0 animate-spin" />
        <slot />
    </button>
</template>
