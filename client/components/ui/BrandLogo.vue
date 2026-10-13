<script setup lang="ts">
import logo from "~/assets/logo/logo.png";

withDefaults(
    defineProps<{
        iconClass?: string;
        textClass?: string;
        gapClass?: string;
        iconOnly?: boolean;
    }>(),
    {
        iconClass: "h-9 w-9 sm:h-10 sm:w-10",
        textClass: "text-xl sm:text-2xl",
        gapClass: "gap-2 sm:gap-2.5",
    },
);
</script>

<template>
    <span class="flex items-center" :class="gapClass">
        <img
            :src="logo"
            alt=""
            aria-hidden="true"
            class="shrink-0 object-contain transition-all duration-300"
            :class="iconClass"
        />

        <span
            v-if="!iconOnly"
            class="brand-shine font-extrabold leading-none tracking-wide"
            :class="textClass"
        >
            AMUMA
        </span>
    </span>
</template>
