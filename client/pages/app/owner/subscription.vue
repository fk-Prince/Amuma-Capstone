<template>
    <div>HELLO FROM Subscription OWner</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "AMUMA Subscription" });
</script>
