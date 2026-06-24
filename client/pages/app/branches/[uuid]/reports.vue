<template>
    <div>HELLO FROM Reports</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Reports" });
</script>
