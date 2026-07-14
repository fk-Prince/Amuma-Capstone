<template>
    <div><Calendar /></div>
</template>

<script setup lang="ts">
import Calendar from "~/components/ui/Calendar.vue";
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Billing" });
</script>
