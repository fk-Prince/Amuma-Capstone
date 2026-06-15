<template>
    <div class="flex flex-col min-h-screen">
        <DefaultNavbar />

        <main class="flex-1">
            <slot />
        </main>
        <template v-if="footer">
            <AppFooter />
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import DefaultNavbar from "~/components/sections/DefaultNavbar.vue";
import AppFooter from "~/components/sections/AppFooter.vue";
import { useRoute } from "vue-router";

const route = useRoute();
const footer = computed(() => route.meta.footer ?? true);
</script>
