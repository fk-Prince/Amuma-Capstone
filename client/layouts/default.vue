<template>
    <div class="flex flex-col min-h-screen">
        <DefaultNavbar :navList="navList" />

        <main class="relative flex-1">
            <div
                class="pointer-events-none absolute inset-0 overflow-hidden print:hidden w-full h-full dark:opacity-30"
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

        <button
            type="button"
            class="fixed bottom-5 left-5 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-white text-gray-600 shadow-[0_10px_30px_-10px_rgba(15,23,42,0.35)] ring-1 ring-black/[0.06] transition-colors hover:text-primary-500 dark:bg-secondary-900 dark:text-gray-300 dark:ring-white/10 dark:hover:text-primary-400 print:hidden"
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="setTheme(!isDark)"
        >
            <Sun v-if="isDark" class="h-5 w-5" />
            <Moon v-else class="h-5 w-5" />
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { Sun, Moon } from "lucide-vue-next";

import DefaultNavbar from "~/components/sections/DefaultNavbar.vue";
import AppFooter from "~/components/sections/AppFooter.vue";
import { navList } from "~/config/publicMenu";

const route = useRoute();
const footer = computed(() => route.meta.footer ?? true);
const isDark = useIsDark();
</script>
