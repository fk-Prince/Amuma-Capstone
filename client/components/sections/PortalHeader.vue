<template>
    <header
        class="h-[90px] w-full bg-white border-b border-gray-200 flex items-center justify-between px-6 shrink-0"
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
                    <div
                        class="w-9 h-9 bg-gray-200 rounded-full animate-pulse"
                    />
                    <div class="flex flex-col gap-2">
                        <div
                            class="w-24 h-3 bg-gray-200 rounded animate-pulse"
                        />
                        <div
                            class="w-14 h-3 bg-gray-200 rounded animate-pulse"
                        />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <Notification />
                    <NavbarProfileDropdown v-if="user" :user="user" />
                </div>
            </div>

            <button
                @click="$emit('open')"
                class="flex lg:hidden items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100"
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
