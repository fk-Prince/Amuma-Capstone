<template>
    <div class="h-[100dvh] flex bg-[#EEF3FB] dark:bg-[#0b0f1a] overflow-hidden">
        <OwnerSidebar :open="isOpen" @close="isOpen = false" />

        <div class="flex-1 flex flex-col min-w-0 h-full">
            <OwnerHeader @open="isOpen = true" />

            <main
                class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden relative"
            >
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden"
                    aria-hidden="true"
                >
                    <div
                        class="absolute top-10 left-8 w-72 h-72 rounded-full bg-primary-300/40 dark:bg-primary-500/25 blur-3xl"
                    />
                    <div
                        class="absolute -top-32 -right-32 w-[420px] h-[420px] rounded-full bg-primary-200/40 dark:bg-primary-500/15 blur-3xl"
                    />
                    <div
                        class="absolute -bottom-40 left-56 w-[380px] h-[380px] rounded-full bg-primary-100/50 dark:bg-primary-500/10 blur-3xl"
                    />
                    <div
                        class="absolute top-1/3 right-1/4 w-[260px] h-[260px] rounded-full bg-accent-200/30 dark:bg-accent-500/15 blur-3xl"
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
import OwnerHeader from "~/components/sections/owner/OwnerHeader.vue";
import OwnerSidebar from "~/components/sections/owner/OwnerSidebar.vue";

const isOpen = ref(false);
</script>
