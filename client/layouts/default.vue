<template>
    <div class="flex flex-col min-h-screen">
        <DefaultNavbar :navList="navList" />

        <main class="flex-1">
            <slot />
        </main>

        <AppFooter v-if="footer" />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";

import DefaultNavbar from "~/components/sections/DefaultNavbar.vue";
import AppFooter from "~/components/sections/AppFooter.vue";
import { navList } from "~/config/publicMenu";

const route = useRoute();
const footer = computed(() => route.meta.footer ?? true);
</script>
