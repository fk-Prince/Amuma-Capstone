<script setup lang="ts">
import SignupSection from "~/components/sections/auth/SignupSection.vue";
definePageMeta({
    middleware: "auth-client",
    navVariant: 5,
    footer: false,
});
useHead({
    title: "Sign up - AMUMA",
});
</script>

<template>
    <ClientOnly>
        <SignupSection />
    </ClientOnly>
</template>
