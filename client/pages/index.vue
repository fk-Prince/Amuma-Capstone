<script setup lang="ts">
import Landing from "~/components/sections/LandingPage/Landing.vue";
useHead({
    title: "AMUMA",
});
definePageMeta({
    navVariant: 1,
});
</script>

<template>
    <div class="min-h-screen w-full bg-primary/10 px-[10%]">
        <Landing />
    </div>
</template>
