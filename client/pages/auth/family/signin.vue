<template>
    <ClientOnly>
        <SigninSection />
    </ClientOnly>
</template>

<script setup lang="ts">
import SigninSection from "~/components/sections/auth/SigninSection.vue";

definePageMeta({
    middleware: "auth-client",
    layout: "auth",
});

useHead({
    title: "Family Sign in - AMUMA",
});
</script>