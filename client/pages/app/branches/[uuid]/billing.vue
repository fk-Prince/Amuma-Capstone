<template>
    <div>HELLO FROM Billing</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Billing" });
</script>
