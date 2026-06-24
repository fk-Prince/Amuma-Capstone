<template>
    <div>HELLO FROM Bookings</div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Bookings" });
</script>
