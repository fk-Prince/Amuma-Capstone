<template>
    <div>HELLO FROM ADMISSION</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Admission" });
</script>
