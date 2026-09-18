<template>
    <div class="min-h-screen w-full bg-white dark:bg-secondary">
        <Aboutus />
    </div>
</template>

<script setup lang="ts">
import Aboutus from "~/components/sections/company/Aboutus.vue";

useHead({ title: "Company - AMUMA" });

definePageMeta({
    layout: "default",
    navVariant: 3,
    navTheme: "light",
});
</script>
