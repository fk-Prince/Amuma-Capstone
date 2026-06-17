<template>
    <header :class="header">
        <nav class="flex justify-between items-center px-6 h-[90px]">
            <template v-if="variant === 1 || variant === 3">
                <NuxtLink to="/">
                    <img
                        :src="logoAmuma"
                        alt="AMUMA logo"
                        class="w-[170px] md:w-[250px] object-contain"
                    />
                </NuxtLink>
                <div v-if="variant === 1" class="hidden lg:flex gap-12">
                    <NuxtLink v-for="i in navItems" :key="i.to" :to="i.to">
                        {{ i.label }}
                    </NuxtLink>
                </div>

                <div class="flex items-center gap-3">
                    <div v-if="!hydrated" class="flex items-center gap-3">
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
                        <div class="flex items-center gap-2">
                            <NuxtLink v-if="!user" to="/auth/signin">
                                <BaseButton class="px-[30px]"
                                    >SIGN IN</BaseButton
                                >
                            </NuxtLink>

                            <NuxtLink v-if="!user" to="/auth/signup">
                                <BaseButton
                                    variant="secondary"
                                    class="bg-transparent px-[30px] border-muted-dark hover:bg-primary/20"
                                    >Get Started
                                    <svg
                                        width="20"
                                        height="16"
                                        viewBox="0 0 16 16"
                                        fill="none"
                                    >
                                        <path
                                            d="M3 8h10M9 4l4 4-4 4"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        /></svg
                                ></BaseButton>
                            </NuxtLink>
                        </div>

                        <NavbarProfileDropdown v-if="user" :user="user" />

                        <button
                            class="lg:hidden"
                            @click="mobileMenuOpen = true"
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
                </div>
            </template>

            <template v-else-if="variant === 2">
                <NuxtLink to="/">
                    <img
                        :src="logoAmuma"
                        alt="AMUMA logo"
                        class="w-[250px] object-contain"
                    />
                </NuxtLink>
            </template>
        </nav>

        <ClientOnly v-if="variant === 1">
            <DynamicSidebar
                :open="mobileMenuOpen"
                :logo="logoAmuma"
                :navItems="navItems"
                :user="user"
                :avatarSrc="avatarUrl"
                @close="mobileMenuOpen = false"
            />
        </ClientOnly>
    </header>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import BaseButton from "../ui/BaseButton.vue";

import logoAmuma from "~/assets/logo/logoAmuma.png";
import DynamicSidebar from "./DynamicSidebar.vue";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";

import { useAuthUser } from "~/composables/useAuthUser";
import { avatarSrc, userInitials } from "~/utils/user";

const route = useRoute();
const user = useAuthUser();

const hydrated = ref(false);
const mobileMenuOpen = ref(false);

onMounted(() => {
    hydrated.value = true;
});

const props = withDefaults(
    defineProps<{
        navItems?: { label: string; to: string }[];
    }>(),
    {
        navItems: () => [
            { label: "Product", to: "/product" },
            { label: "Booking", to: "/booking" },
            { label: "Documentation", to: "/" },
            { label: "Company", to: "/company" },
        ],
    },
);

const initials = computed(() => userInitials(user));
const avatarUrl = computed(() => avatarSrc(initials.value));
const variant = computed(() => route.meta.navVariant ?? 1);
const header = computed(() => {
    switch (variant.value) {
        case 1:
            return "w-full md:px-[5%] lg:px-[10%] bg-transparent h-[90px] border-1 border-b";

        case 2:
            return "absolute top-0 left-1/4 -translate-x-1/4 w-full h-[90px] bg-transparent z-[9999]";
        default:
            return "";
    }
});
</script>
