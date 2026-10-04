<template>
    <ClientOnly>
        <AgencySignupSection />
    </ClientOnly>
</template>

<script setup lang="ts">
import AgencySignupSection from "~/components/sections/auth/AgencySignupSection.vue";

definePageMeta({
    middleware: "auth-client",
    layout: "auth",
});

useHead({
    title: "Create your account - AMUMA",
});
</script>