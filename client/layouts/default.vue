<template>
    <div class="flex flex-col min-h-screen">
        <DefaultNavbar :navList="navList" />

        <main class="relative flex-1">
            <div
                class="pointer-events-none absolute inset-0 overflow-hidden print:hidden w-full h-full"
                aria-hidden="true"
            >
                <div
                    class="lg:flex hidden absolute -bottom-0 left-42 h-[520px] w-[520px] rounded-full bg-sky-300/25 blur-[140px]"
                ></div>

                <div
                    class="lg:flex hidden absolute -top-40 -left-42 h-[520px] w-[520px] rounded-full bg-sky-200/25 blur-[140px]"
                ></div>
                <div
                    class="lg:flex hidden absolute -top-40 left-1/2 -translate-x-1/2 h-[520px] w-[520px] rounded-full bg-sky-200/25 blur-[140px]"
                ></div>
                <div
                    class="lg:flex hidden absolute -top-90 -right-32 h-[520px] w-[520px] rounded-full bg-sky-200/25 blur-[150px]"
                ></div>
                <div
                    class="lg:flex hidden absolute top-1/3 left-1/2 h-[280px] w-[280px] -translate-x-1/2 rounded-full bg-cyan-200/20 blur-[100px]"
                ></div>
            </div>
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
