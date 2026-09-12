<script setup lang="ts">
import { computed } from "vue";
import { LoaderCircle } from "lucide-vue-next";
import { useSound } from "~/composables/useSound";

defineOptions({ name: "BaseButton", inheritAttrs: false });

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
    // Set to false on buttons that fire very frequently (pagination, table
    // row actions, filters) so the click sound doesn't get repetitive.
    sound: { type: Boolean, default: true },
});

const { playClick, playClickDanger } = useSound();

const baseClass =
    "font-semibold rounded-xl transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40 focus-visible:ring-offset-2";

const variantClass = computed(() => {
    switch (props.variant) {
        case "secondary":
            return "bg-gray-100 text-gray-800 hover:bg-gray-200 border border-gray-200 dark:bg-white/10 dark:text-gray-200 dark:hover:bg-white/20 dark:border-white/10";
        case "outline":
            return "bg-transparent";
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

function handleClick(event: MouseEvent) {
    if (!props.disabled && !props.loading && props.sound) {
        props.variant === "danger" ? playClickDanger() : playClick();
    }
}
</script>

<template>
    <button
        v-bind="$attrs"
        :type="type"
        :disabled="disabled || loading"
        :class="[baseClass, variantClass, sizeClass, widthClass, buttonClass]"
        @click="handleClick"
    >
        <LoaderCircle v-if="loading" class="h-4 w-4 shrink-0 animate-spin" />
        <slot />
    </button>
</template>     