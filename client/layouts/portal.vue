<template>
    <div class="h-[100dvh] flex bg-[#EEF3FB] overflow-hidden">
        <PortalSidebar :open="isOpen" @close="isOpen = false" />

        <div class="flex-1 flex flex-col min-w-0 h-full">
            <PortalHeader @open="isOpen = true" />

            <main
                class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden relative"
            >
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden"
                    aria-hidden="true"
                >
                    <div
                        class="absolute top-10 left-8 w-72 h-72 rounded-full bg-brand-300/40 dark:bg-brand-500/25 blur-3xl"
                    />
                    <div
                        class="absolute -top-32 -right-32 w-[420px] h-[420px] rounded-full bg-brand-200/40 dark:bg-brand-500/15 blur-3xl"
                    />
                    <div
                        class="absolute -bottom-40 left-56 w-[380px] h-[380px] rounded-full bg-brand-100/50 dark:bg-brand-500/10 blur-3xl"
                    />
                    <div
                        class="absolute top-1/3 right-1/4 w-[260px] h-[260px] rounded-full bg-violet-200/30 dark:bg-violet-500/15 blur-3xl"
                    />
                    <div
                        class="absolute top-10 left-[40%] w-[220px] h-[220px] rounded-full bg-emerald-100/40 dark:bg-emerald-500/10 blur-3xl"
                    />
                    <div
                        class="absolute bottom-10 right-16 w-[300px] h-[300px] rounded-full bg-brand-100/40 dark:bg-brand-500/10 blur-3xl"
                    />
                </div>

                <div class="relative min-h-full flex flex-col">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import PortalSidebar from "~/components/sections/PortalSidebar.vue";
import PortalHeader from "~/components/sections/PortalHeader.vue";

const isOpen = ref(false);
</script>
