<template>
    <div class="min-h-screen w-full bg-white">
        <h1 class="text-2xl font-bold text-green-600">Payment Status</h1>

        <p class="mt-2 text-gray-600">Status: {{ status }}</p>
    </div>
</template>

<script setup lang="ts">
useHead({
    title: "Subscription Status",
});
const route = useRoute();
const status = computed(() => route.query.status || "unknown");
</script>
