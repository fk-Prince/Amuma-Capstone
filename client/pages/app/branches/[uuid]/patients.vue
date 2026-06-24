<template>
    <div>HELLO FROM Patients</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Patients" });
</script>
