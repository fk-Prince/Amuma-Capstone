<template>
    <div>HELLO FROM OWNER DASHBOARD</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "AMUMA Dashboard" });
</script>
