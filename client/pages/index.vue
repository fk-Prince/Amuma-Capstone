<script setup lang="ts">
import Landing from "~/components/sections/LandingPage/Landing.vue";
useHead({
    title: "AMUMA",
});
definePageMeta({
    layout: "default",
    navVariant: 1,
});
</script>

<template>
    <div class="min-h-screen w-full">
        <Landing />
    </div>
</template>
