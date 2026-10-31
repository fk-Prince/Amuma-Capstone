<template>
    <CustomerSupport />
</template>

<script lang="ts" setup>
import CustomerSupport from "~/components/sections/resources/CustomerSupport.vue";

useHead({ title: "Customer Support - AMUMA" });
definePageMeta({
    layout: "default",
    middleware: "auth-client",
    navVariant: 3,
    navTheme: "light",
});
</script>
