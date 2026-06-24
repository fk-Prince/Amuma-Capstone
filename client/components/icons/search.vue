<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-5 h-5"
        :class="extraClass"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
        />
    </svg>
</template>

<script setup lang="ts">
defineProps<{
    extraClass?: string;
}>();
</script>
