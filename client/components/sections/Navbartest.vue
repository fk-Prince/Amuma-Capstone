<template>
    <header :class="header">
        <NuxtLink to="/">
            <img
                :src="logoAmuma"
                alt="AMUMA logo"
                class="w-[170px] md:w-[250px] object-contain"
                :class="variant === 2 ? '' : 'h-[40px]'"
            />
        </NuxtLink>

        <div v-if="variant === 1" class="hidden lg:flex gap-12">
            <NuxtLink v-for="i in navItems" :key="i.to" :to="i.to">
                {{ i.label }}
            </NuxtLink>
        </div>

        <div v-if="variant === 1" class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <NuxtLink v-if="!user" to="/auth/signin">
                    <BaseButton class="px-[30px]">SIGN IN</BaseButton>
                </NuxtLink>

                <NuxtLink v-if="!user" to="/auth/signup">
                    <BaseButton
                        variant="secondary"
                        class="bg-accent px-[30px] border-muted-dark hover:bg-primary/20"
                        >Get Started
                    </BaseButton>
                </NuxtLink>

                <NavbarProfileDropdown v-if="user" :user="user" />

                <button class="lg:hidden" @click="mobileMenuOpen = true">
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
    </header>
</template>

<script setup lang="ts">
import logoAmuma from "~/assets/logo/logoAmuma.png";
import BaseButton from "../ui/BaseButton.vue";
import { ref } from "vue";
import { useAuthUser } from "~/composables/useAuthUser";
import { avatarSrc, userInitials } from "~/utils/user";
import DynamicSidebar from "./DynamicSidebar.vue";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";

const route = useRoute();
const user = useAuthUser();
const mobileMenuOpen = ref(false);

const hydrated = ref();
onMounted(() => {
    hydrated.value = true;
});

const initials = computed(() => userInitials(user));
const avatarUrl = computed(() => avatarSrc(initials.value));
const variant = computed(() => route.meta.navVariant ?? 1);

const props = withDefaults(
    defineProps<{
        navItems?: { label: string; to: string }[];
    }>(),
    {
        navItems: () => [
            { label: "Product", to: "/product" },
            { label: "Booking", to: "/booking" },
            { label: "Documentation", to: "/" },
            { label: "Company", to: "/" },
        ],
    },
);

const header = computed(() => {
    switch (variant.value) {
        case 1:
            return "fixed top-0 left-0 w-full z-[9999] flex bg-secondary text-white justify-between items-center px-6 h-[90px]";
        case 2:
            return "z-[9999] fixed top-[5%] left-[2%] bg-transparent text-white justify-between items-center px-6 h-[100px] items-center";
    }
});
</script>
