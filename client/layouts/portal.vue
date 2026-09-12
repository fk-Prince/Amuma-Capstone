<template>
    <div class="h-[100dvh] flex lg:gap-4 bg-[#EEF3FB] dark:bg-secondary-950 overflow-hidden">
        <PortalSidebar :open="isOpen" @close="isOpen = false" />

        <div class="flex-1 flex flex-col min-w-0 h-full">
            <PortalHeader @open="isOpen = true" />

            <main
                class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden relative"
            >
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden dark:opacity-30"
                    aria-hidden="true"
                >
                    <div
                        class="absolute top-10 left-8 w-72 h-72 rounded-full bg-primary-300/40 blur-3xl"
                    />
                    <div
                        class="absolute -top-32 -right-32 w-[420px] h-[420px] rounded-full bg-primary-200/40 blur-3xl"
                    />
                    <div
                        class="absolute -bottom-40 left-56 w-[380px] h-[380px] rounded-full bg-primary-100/50 blur-3xl"
                    />
                    <div
                        class="absolute top-1/3 right-1/4 w-[260px] h-[260px] rounded-full bg-violet-200/30 blur-3xl"
                    />
                    <div
                        class="absolute top-10 left-[40%] w-[220px] h-[220px] rounded-full bg-emerald-100/40 blur-3xl"
                    />
                    <div
                        class="absolute bottom-10 right-16 w-[300px] h-[300px] rounded-full bg-primary-100/40 blur-3xl"
                    />
                </div>

                <div class="relative min-h-full flex flex-col px-3 pb-3 sm:px-4 sm:pb-4 lg:pl-0 lg:pr-4 lg:pb-4 lg:pt-1">
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