<template>
    <div>HELLO FROM Staff</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Staff" });
</script>
