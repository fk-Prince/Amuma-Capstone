<template>
    <ClientOnly>
        <StaffSigninSection />
    </ClientOnly>
</template>

<script setup lang="ts">
import StaffSigninSection from "~/components/sections/auth/StaffSigninSection.vue";

definePageMeta({
    middleware: "auth-client",
    layout: "auth",
});

useHead({
    title: "Staff Sign in - AMUMA",
});
</script>