<template>
    <div>HELLO FROM Schedules</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Schedules" });
</script>
