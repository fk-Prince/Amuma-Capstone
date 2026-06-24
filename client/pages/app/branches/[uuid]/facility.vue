<template>
    <div>HELLO FROM Facility</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Facility" });
</script>
