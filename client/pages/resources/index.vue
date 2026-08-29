<template>
    <div class="min-h-screen w-full bg-white dark:bg-secondary">
        <Resources />
    </div>
</template>

<script lang="ts" setup>
import Resources from "~/components/sections/resources/Resources.vue";

useHead({ title: "Resources" });
definePageMeta({
    layout: "default",
    navVariant: 1,
    navTheme: "light",
});
</script>
