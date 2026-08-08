<template>
    <header
        class="h-[90px] w-full bg-white border-b border-primary-100/80 shadow-[0_0_40px_rgba(10,40,87,0.06)] flex items-center justify-between px-6 shrink-0"
    >
        <NuxtLink to="/" class="flex items-center pr-5">
            <img
                :src="logo"
                alt="AMUMA logo"
                class="w-[200px] object-contain"
            />
        </NuxtLink>

        <div class="flex gap-2 items-center">
            <div class="flex items-center gap-3">
                <div v-if="!isMounted" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full skeleton-shimmer" />
                    <div class="flex flex-col gap-2">
                        <div class="w-24 h-3 rounded-md skeleton-shimmer" />
                        <div class="w-14 h-3 rounded-md skeleton-shimmer" />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <Notification />
                    <NavbarProfileDropdown v-if="user" :user="user" />
                </div>
            </div>

            <button
                @click="$emit('open')"
                class="flex lg:hidden items-center justify-center w-10 h-10 rounded-lg text-primary-600 transition-colors duration-200 hover:bg-primary-50"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-6 h-6"
                >
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg>
            </button>
        </div>
    </header>
</template>

<script setup lang="ts">
import logo from "assets/logo/logoAmuma.png";
import { ref, onMounted } from "vue";
import Notification from "../ui/Notification.vue";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";

import { useAuthUser } from "~/composables/useAuthUser";

const user = useAuthUser();

defineEmits<{ open: [] }>();

const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});
</script>

<style scoped>
.skeleton-shimmer {
    background: linear-gradient(
        90deg,
        theme("colors.primary.50") 25%,
        theme("colors.primary.100") 50%,
        theme("colors.primary.50") 75%
    );
    background-size: 200% 100%;
    animation: shimmer 1.4s ease-in-out infinite;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>
