<template>
    <div class="h-screen flex flex-col bg-[#EEF3FB]">
        <OwnerHeader @open="isOpen = true" />

        <div class="flex flex-1 min-h-0">
            <OwnerSidebar :open="isOpen" :logo="logo" @close="isOpen = false" />

            <main class="flex-1 overflow-auto p-0 m-0 relative">
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden"
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
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import OwnerHeader from "~/components/sections/owner/OwnerHeader.vue";
import OwnerSidebar from "~/components/sections/owner/OwnerSidebar.vue";

const isOpen = ref(false);

const logo = "/logo.svg";
</script>
