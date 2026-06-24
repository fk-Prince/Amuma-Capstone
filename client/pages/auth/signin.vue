<template>
    <ClientOnly>
        <SigninSection />
    </ClientOnly>
</template>

<script setup lang="ts">
import SigninSection from "~/components/sections/auth/SigninSection.vue";
definePageMeta({
    middleware: "auth-client",
    navVariant: 2,
    footer: false,
});

useHead({
    title: "Sign In - AMUMA",
});
</script>
