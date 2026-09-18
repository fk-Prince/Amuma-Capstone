<script setup lang="ts">
import logo from "~/assets/logo/logo.png";

withDefaults(
    defineProps<{
        iconClass?: string;
        textClass?: string;
        gapClass?: string;
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
            class="brand-shine font-extrabold leading-none tracking-wide"
            :class="textClass"
        >
            AMUMA
        </span>
    </span>
</template>

<style scoped>
.brand-shine {
    background-image: linear-gradient(
        100deg,
        #3182ed 0%,
        #3182ed 38%,
        #7fb1f8 50%,
        #3182ed 62%,
        #3182ed 100%
    );
    background-size: 250% 100%;
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    animation: brand-shine 4.5s linear infinite;
}

@keyframes brand-shine {
    from {
        background-position: 150% 0;
    }

    to {
        background-position: -150% 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .brand-shine {
        animation: none;
        background-image: none;
        color: #3182ed;
    }
}
</style>
