<template>
    <div>HELLO FROM Room & Beds</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Room & Beds" });
</script>
