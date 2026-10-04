<template>
    <ClientOnly>
        <PortalSelectSection />
    </ClientOnly>
</template>

<script setup lang="ts">
import PortalSelectSection from "~/components/sections/auth/PortalSelectSection.vue";

definePageMeta({
    middleware: "auth-client",
    navVariant: 5,
    footer: false,
});

useHead({
    title: "Choose your portal - AMUMA",
});
</script>s