<template>
    <header :class="header">
        <nav class="flex justify-between items-center w-full px-6 h-[90px]">
            <template v-if="variant === 1 || variant === 2 || variant === 4">
                <div class="flex items-center gap-8">
                    <NuxtLink to="/" class="shrink-0">
                        <img
                            :src="logoAmuma"
                            alt="AMUMA logo"
                            class="w-[120px] md:w-[200px] object-contain"
                        />
                    </NuxtLink>

                    <div
                        v-if="variant === 1 || variant === 2"
                        class="hidden lg:flex items-center gap-1"
                    >
                        <NuxtLink
                            v-for="i in navList"
                            :key="i.to"
                            :to="i.to"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-secondary/80 hover:text-secondary hover:bg-primary-50 transition-colors"
                        >
                            {{ i.label }}
                        </NuxtLink>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <template v-if="!hydrated || !user">
                        <NuxtLink :to="hydrated ? '/auth/signin' : undefined">
                            <BaseButton
                                buttonClass="px-[15px] lg:px-[30px] whitespace-nowrap min-w-fit"
                            >
                                SIGN IN
                            </BaseButton>
                        </NuxtLink>

                        <NuxtLink
                            :to="hydrated ? '/auth/signup' : undefined"
                            class="hidden sm:block"
                        >
                            <BaseButton
                                variant="secondary"
                                class="bg-transparent px-[30px] border-muted-dark hover:bg-primary/10 whitespace-nowrap min-w-fit"
                            >
                                Get Started
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
                                    />
                                </svg>
                            </BaseButton>
                        </NuxtLink>
                    </template>

                    <NavbarProfileDropdown v-else :user="user" />

                    <button
                        v-if="variant === 1 || variant === 2"
                        class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg text-secondary hover:bg-primary-50 transition-colors"
                        aria-label="Open menu"
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
            </template>

            <template v-else-if="variant === 3">
                <NuxtLink to="/">
                    <img
                        :src="logoAmuma"
                        alt="AMUMA logo"
                        class="w-[250px] object-contain"
                    />
                </NuxtLink>
            </template>
        </nav>

        <ClientOnly v-if="variant === 1 || variant === 2">
            <DynamicSidebar
                :open="mobileMenuOpen"
                :logo="logoAmuma"
                :authMenu="navList"
                :user="user"
                @close="mobileMenuOpen = false"
            />
        </ClientOnly>
    </header>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import logoAmuma from "~/assets/logo/logoAmuma.png";
import BaseButton from "../ui/BaseButton.vue";
import { useAuthUser } from "~/composables/useAuthUser";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";
import DynamicSidebar from "./DynamicSidebar.vue";

const user = useAuthUser();
const route = useRoute();
const hydrated = ref(false);
const mobileMenuOpen = ref(false);

onMounted(() => {
    hydrated.value = true;
});

const props = defineProps<{
    navList?: { label: string; to: string }[];
}>();

const variant = computed(() => route.meta.navVariant ?? 1);

const header = computed(() => {
    switch (variant.value) {
        case 1:
            return "w-full md:px-[5%] lg:px-[10%] bg-transparent h-[90px] border-b border-muted-light";
        case 2:
            return "fixed top-4 left-1/2 -translate-x-1/2 mx-auto w-[92%] md:w-[80%] rounded-2xl border border-primary-100 bg-white/70 backdrop-blur-xl shadow-lg shadow-primary-100/40 z-50";
        case 3:
            return "absolute top-0 left-1/4 -translate-x-1/4 w-full h-[90px] bg-gradient-to-b from-black/40 to-transparent z-[9999]";
        case 4:
            return "w-full md:px-[5%] lg:px-[10%] bg-transparent h-[90px]";
        default:
            return "";
    }
});
</script>
