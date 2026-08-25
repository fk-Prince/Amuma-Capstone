<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import logoAmuma from "~/assets/logo/logoAmuma.png";
import logoWhite from "~/assets/logo/logoWhite.png";
import BaseButton from "../ui/BaseButton.vue";
import { useAuthUser } from "~/composables/useAuthUser";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";
import DynamicSidebar from "./DynamicSidebar.vue";

const user = useAuthUser();
const route = useRoute();
const hydrated = ref(false);
const mobileMenuOpen = ref(false);
const scrolled = ref(false);

const onScroll = () => {
    scrolled.value = window.scrollY > 8;
};

onMounted(() => {
    hydrated.value = true;
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
});

onUnmounted(() => {
    window.removeEventListener("scroll", onScroll);
});

const props = defineProps<{
    navList?: { label: string; to: string }[];
}>();

const variant = computed(() => route.meta.navVariant ?? 1);

const header = computed(() => {
    switch (variant.value) {
        case 1:
            return [
                "fixed top-0 left-0 z-50 w-full h-[90px] ",
                "md:px-[5%] lg:px-[10%]",
                "transition-all duration-300 ease-out",
                scrolled.value
                    ? "bg-white/90 backdrop-blur-md shadow-sm border-b border-muted-light"
                    : "bg-transparent border-b border-transparent",
            ]
                .filter(Boolean)
                .join(" ");

        case 2:
            return [
                "fixed top-4 left-1/2 -translate-x-1/2 z-50",
                "w-[92%] md:w-[80%] rounded-2xl h-[90px] ",
                "transition-all duration-300 ease-out",
                scrolled.value
                    ? "border border-primary-100 bg-light/90 backdrop-blur-xl shadow-lg shadow-primary-100/40"
                    : "border border-transparent bg-transparent shadow-none",
            ]
                .filter(Boolean)
                .join(" ");
        case 3:
            return [
                "fixed top-4 left-1/2 -translate-x-1/2 z-50",
                "w-[92%] md:w-[80%] h-[90px] rounded-2xl",
                "transition-all duration-300 ease-out",
                scrolled.value
                    ? "border border-primary-100 bg-light/90 backdrop-blur-xl shadow-lg shadow-primary-100/40"
                    : navTheme.value === "dark"
                      ? "border border-light/20 bg-light/10 "
                      : "border border-muted-light bg-light/90 ",
            ]
                .filter(Boolean)
                .join(" ");
        case 4:
            return [
                "relative w-full h-[70px] flex items-center",
                "md:px-[5%] lg:px-[8%]",
                "transition-all duration-300 ease-out bg-primary",
            ]
                .filter(Boolean)
                .join(" ");
        case 5:
            return [
                "fixed top-0 left-0 z-50 w-full  h-[90px] flex items-center justify-start",
                "transition-all duration-300 ease-out",
            ]
                .filter(Boolean)
                .join(" ");
    }
});

const isActive = (to: string) => {
    if (to === "/") return route.path === "/";
    return route.path === to || route.path.startsWith(`${to}/`);
};
const navTheme = computed(() => route.meta.navTheme ?? "light");

const navLinkClass = (to: string) => {
    if (isActive(to)) return "text-primary";

    if (scrolled.value) {
        return "text-secondary/80 hover:text-secondary";
    }

    return navTheme.value === "dark"
        ? "text-light/90 hover:text-light"
        : "text-secondary/80 hover:text-secondary";
};

const menuIconClass = computed(() => {
    if (scrolled.value) {
        return "text-secondary hover:bg-primary-50";
    }

    return navTheme.value === "dark"
        ? "text-light hover:bg-light/10"
        : "text-secondary hover:bg-primary-50";
});

const activeIndex = computed(() => {
    if (!props.navList) return -1;
    return props.navList.findIndex((item) => isActive(item.to));
});

const navRefs = ref<(HTMLElement | null)[]>([]);
const pillStyle = ref({
    width: "0px",
    opacity: "0",
    transform: "translateX(0px)",
});

const setNavRef = (el: any, index: number) => {
    navRefs.value[index] = el;
};

const updatePillPosition = () => {
    const el = navRefs.value[activeIndex.value];

    if (!el) {
        pillStyle.value = { ...pillStyle.value, opacity: "0" };
        return;
    }

    pillStyle.value = {
        width: `${el.offsetWidth}px`,
        opacity: "1",
        transform: `translateX(${el.offsetLeft}px)`,
    };
};

onMounted(() => {
    nextTick(updatePillPosition);
    window.addEventListener("resize", updatePillPosition);
});

onUnmounted(() => {
    window.removeEventListener("resize", updatePillPosition);
});

watch(
    () => [route.path, props.navList],
    () => nextTick(updatePillPosition),
    { deep: true },
);
</script>

<template>
    <header :class="header">
        <nav class="flex justify-between items-center w-full px-6 h-[90px]">
            <nav
                v-if="variant === 5"
                class="flex justify-start items-center w-full px-6 h-[90px]"
            >
                <NuxtLink to="/" class="shrink-0">
                    <img
                        :src="logoAmuma"
                        alt="AMUMA logo"
                        class="w-[180px] object-contain transition-all duration-300"
                    />
                </NuxtLink>
            </nav>
            <template
                v-if="
                    variant === 1 ||
                    variant === 2 ||
                    variant === 3 ||
                    variant === 4
                "
            >
                <div class="flex items-center gap-8">
                    <NuxtLink to="/" class="shrink-0">
                        <img
                            :src="variant === 4 ? logoWhite : logoAmuma"
                            alt="AMUMA logo"
                            class="w-[180px] object-contain transition-all duration-300"
                            :class="
                                !scrolled &&
                                navTheme === 'dark' &&
                                variant !== 1
                                    ? 'brightness-0 invert'
                                    : ''
                            "
                        />
                    </NuxtLink>

                    <div
                        v-if="variant === 1 || variant === 2 || variant === 3"
                        class="relative hidden lg:flex items-center gap-1"
                    >
                        <span
                            class="absolute inset-y-1 left-0 z-0 rounded-lg transition-all duration-300 ease-out"
                            :class="
                                scrolled || navTheme !== 'dark'
                                    ? 'bg-primary-50'
                                    : 'bg-light/15'
                            "
                            :style="pillStyle"
                        />
                        <NuxtLink
                            v-for="(i, index) in navList"
                            :key="i.to"
                            :ref="(el) => setNavRef(el, index)"
                            :to="i.to"
                            class="relative z-10 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-300"
                            :class="navLinkClass(i.to)"
                        >
                            {{ i.label }}
                        </NuxtLink>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <template v-if="!hydrated || !user">
                        <NuxtLink
                            :to="hydrated ? '/auth/signin' : undefined"
                            class="shrink-0"
                        >
                            <BaseButton
                                buttonClass="px-[18px] lg:px-[28px] h-11 rounded-xl whitespace-nowrap min-w-fit transition-all duration-300"
                                :class="
                                    variant === 1 && !scrolled
                                        ? 'bg-primary text-white border border-primary shadow-[0_6px_20px_rgba(37,99,235,0.25)] hover:bg-primary/90 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(37,99,235,0.35)]'
                                        : ''
                                "
                            >
                                SIGN IN
                            </BaseButton>
                        </NuxtLink>

                        <NuxtLink
                            :to="hydrated ? '/auth/signup' : undefined"
                            class="hidden sm:block shrink-0"
                        >
                            <BaseButton
                                variant="secondary"
                                class="h-11 rounded-xl border px-[20px] lg:px-[24px] whitespace-nowrap min-w-fit transition-all duration-300"
                                :class="
                                    variant === 1 && !scrolled
                                        ? 'border-white/20 bg-white/[0.06] text-white backdrop-blur-md hover:border-white/30 hover:bg-white/10 hover:-translate-y-0.5'
                                        : 'border-muted-dark bg-transparent hover:bg-primary/10'
                                "
                            >
                                <span>Get Started</span>

                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 16 16"
                                    fill="none"
                                    class="ml-1.5 transition-transform duration-300 group-hover:translate-x-0.5"
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

                    <NavbarProfileDropdown
                        v-else
                        :user="user"
                        :scrolled="scrolled"
                        :navTheme="navTheme"
                    />

                    <button
                        v-if="variant === 1 || variant === 2 || variant === 3"
                        class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg transition-colors duration-300"
                        :class="menuIconClass"
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
        </nav>

        <ClientOnly v-if="variant === 1 || variant === 2 || variant === 3">
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
