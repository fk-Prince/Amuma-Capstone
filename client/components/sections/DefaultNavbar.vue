<template>
    <header :class="header">
        <nav class="flex justify-between items-center w-full px-6 h-[90px]">
            <template v-if="variant === 1 || variant === 2 || variant === 4">
                <div class="flex items-center gap-5">
                    <NuxtLink to="/">
                        <img
                            :src="logoAmuma"
                            alt="AMUMA logo"
                            class="w-[120px] md:w-[220px] object-contain"
                        />
                    </NuxtLink>
                    <div
                        v-if="variant === 1 || variant === 2"
                        class="hidden lg:flex gap-5"
                    >
                        <NuxtLink v-for="i in navList" :key="i.to" :to="i.to">
                            {{ i.label }}
                        </NuxtLink>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div v-if="!hydrated" class="flex items-center gap-3">
                        <NuxtLink to="/auth/signin">
                            <BaseButton class="px-[30px]">SIGN IN</BaseButton>
                        </NuxtLink>
                        <NuxtLink to="/auth/signup">
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
                                    />
                                </svg>
                            </BaseButton>
                        </NuxtLink>
                    </div>
                    <div v-else class="flex items-center gap-3">
                        <NuxtLink v-if="!user" to="/auth/signin">
                            <BaseButton class="px-[30px]">SIGN IN</BaseButton>
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
                                    />
                                </svg>
                            </BaseButton>
                        </NuxtLink>
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
                :avatarSrc="user?.avatar"
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
            return "w-full md:px-[5%] lg:px-[10%] bg-transparent h-[90px] border-1 border-b";
        case 2:
            return "fixed top-4 left-1/2 -translate-x-1/2 mx-auto w-[80%] rounded-2xl border border-blue-400/60 backdrop-blur-xl shadow-xl shadow-blue-100/50 z-50";
        case 3:
            return "absolute top-0 left-1/4 -translate-x-1/4 w-full h-[90px] bg-transparent z-[9999]";
        case 4:
            return "w-full md:px-[5%] lg:px-[10%] bg-transparent h-[90px] ";
        default:
            return "";
    }
});
</script>
