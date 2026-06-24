<template>
    <div>HELLO FROM GENERAL DASHBOARD</div>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Dashboard" });

const route = useRoute();
</script>
