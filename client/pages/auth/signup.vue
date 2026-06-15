<script setup lang="ts">
import SignupSection from "~/components/sections/SignupSection.vue";
definePageMeta({
    middleware: "auth-client",
    navVariant: 2,
    footer: false,
});
useHead({
    title: "Sign Up - AMUMA",
});
</script>

<template>
    <ClientOnly>
        <SignupSection />
    </ClientOnly>
</template>
