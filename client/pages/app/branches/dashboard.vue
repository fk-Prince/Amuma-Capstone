<template>
    <div>HELLO FROM OWNER BRANCH</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Branch Dashboard" });
</script>
