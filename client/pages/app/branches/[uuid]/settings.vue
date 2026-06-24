<template>
    <div>HELLO FROM Settings</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Settings" });
</script>
