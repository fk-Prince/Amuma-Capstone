<template>
    <div>HELLO FROM MANAGE BRANCHES</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Manage Branches" });
</script>
