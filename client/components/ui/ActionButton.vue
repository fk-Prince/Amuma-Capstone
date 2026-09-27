<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from "vue";

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
            "border-primary-200 dark:border-primary-500/30 bg-primary text-white hover:bg-primary-600",
        disabled:
            "border-gray-200 dark:border-white/10 bg-gray-100 dark:bg-white/5 text-gray-400 dark:text-gray-500",
    },
    outline: {
        enabled:
            "border-primary-200 dark:border-primary-500/30 text-primary dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-500/10",
        disabled:
            "border-gray-200 dark:border-white/10 text-gray-400 dark:text-gray-500",
    },
    danger: {
        enabled:
            "border-rose-200 dark:border-rose-500/30 text-rose-600 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-500/10",
        disabled:
            "border-gray-200 dark:border-white/10 text-gray-400 dark:text-gray-500",
    },
};

const isBlocked = computed(() => props.disabled || props.loading);

const handleClick = (event) => {
    if (!isBlocked.value) {
        emit("click", event);
    }
};

const wrapper = ref(null);
const tip = ref(null);
const tipVisible = ref(false);
const tipStyle = ref({});

const showTip = async () => {
    if (!props.disabled || !props.tooltip) return;

    tipVisible.value = true;
    tipStyle.value = { visibility: "hidden" };
    window.addEventListener("scroll", hideTip, true);
    await nextTick();

    if (!wrapper.value || !tip.value) return;

    const margin = 8;
    const anchor = wrapper.value.getBoundingClientRect();
    const box = tip.value.getBoundingClientRect();

    const left = Math.min(
        Math.max(anchor.right - box.width, margin),
        window.innerWidth - box.width - margin,
    );
    const below = anchor.bottom + margin;
    const top =
        below + box.height > window.innerHeight - margin
            ? anchor.top - box.height - margin
            : below;

    tipStyle.value = { left: `${left}px`, top: `${top}px` };
};

const hideTip = () => {
    tipVisible.value = false;
    window.removeEventListener("scroll", hideTip, true);
};

watch(
    () => props.loading,
    (isLoading) => {
        document.body.classList.toggle("cursor-wait", isLoading);
    },
);

onBeforeUnmount(() => {
    hideTip();
    if (props.loading) {
        document.body.classList.remove("cursor-wait");
    }
});
</script>

<template>
    <div
        ref="wrapper"
        class="relative inline-block"
        @mouseenter="showTip"
        @mouseleave="hideTip"
    >
        <button
            :type="type"
            :disabled="isBlocked"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border px-3 sm:px-4 py-2 text-sm font-medium transition dark:border-white/10"
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

        <Teleport to="body">
            <div
                v-if="tipVisible && disabled && tooltip"
                ref="tip"
                class="pointer-events-none fixed z-[1000] w-max max-w-xs rounded-md bg-gray-900 px-3 py-2 text-[12px] text-white shadow-lg"
                :style="tipStyle"
            >
                {{ tooltip }}
            </div>
        </Teleport>
    </div>
</template>
