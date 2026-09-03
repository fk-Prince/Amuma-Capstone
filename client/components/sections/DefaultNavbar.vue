<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import logoAmuma from "~/assets/logo/logoAmuma.png";
import BaseButton from "../ui/BaseButton.vue";
import { useAuthUser } from "~/composables/useAuthUser";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";
import ThemeToggle from "../ui/ThemeToggle.vue";
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

// Same box the landing sections use, so the floating bar tracks the content
// column at every width instead of only where the two percentages happen to
// meet. Its px-10 below matches the sections' inner padding.
const CONTENT_BOX = "inset-x-0 mx-auto w-[94%] max-w-[1600px]";

const navInner = computed(() => {
    if (variant.value === 2 || variant.value === 3) return "px-10";
    // Percentage padding drifts against a capped container — it only lines up
    // at one viewport width — so variant 1 uses the same box as the sections.
    if (variant.value === 1 || variant.value === 4)
        return "mx-auto max-w-[100rem] px-6";
    // Sits over the auth pages' left panel, so it tracks that panel's padding.
    if (variant.value === 5) return "px-8 md:px-10 lg:px-20";

    return "px-6";
});

const header = computed(() => {
    switch (variant.value) {
        case 1:
            return [
                "fixed top-0 left-0 z-50 w-full h-[90px] ",
                "transition-colors duration-200 ease-out",
                scrolled.value
                    ? "bg-white dark:bg-secondary border-b border-muted-light dark:border-white/10"
                    : navTheme.value === "dark"
                      ? "bg-transparent border-b border-transparent"
                      : "bg-transparent border-b border-transparent dark:bg-secondary/95 dark:border-white/10 dark:backdrop-blur-md",
            ]
                .filter(Boolean)
                .join(" ");

        case 2:
            return [
                "fixed top-4 z-50",
                CONTENT_BOX,
                "rounded-xl h-[90px] ",
                "transition-colors duration-200 ease-out",
                scrolled.value
                    ? "border border-muted-light bg-light"
                    : navTheme.value === "dark"
                      ? "border border-transparent bg-transparent"
                      : "border border-transparent bg-transparent dark:border-white/10 dark:bg-secondary/95 dark:backdrop-blur-md",
            ]
                .filter(Boolean)
                .join(" ");
        case 3:
            return [
                "fixed top-4 z-50",
                CONTENT_BOX,
                "h-[90px] rounded-xl",
                "transition-colors duration-200 ease-out",
                scrolled.value
                    ? "border border-muted-light dark:border-white/10 bg-light dark:bg-secondary"
                    : navTheme.value === "dark"
                      ? "border border-light/20 bg-light/10 "
                      : "border border-muted-light dark:border-white/10 bg-light/90 dark:bg-secondary backdrop-blur-md",
            ]
                .filter(Boolean)
                .join(" ");
        case 4:
            return [
                "relative w-full h-[70px] flex items-center",
                "transition-all duration-300 ease-out bg-secondary",
            ]
                .filter(Boolean)
                .join(" ");
        case 5:
            return [
                "fixed top-0 left-0 z-50 w-full h-[90px] flex items-center",
                "transition-colors duration-200 ease-out",
                scrolled.value
                    ? "bg-white dark:bg-secondary border-b border-muted-light dark:border-white/10"
                    : "bg-transparent border-b border-transparent",
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

const isChromeSolid = computed(
    () => scrolled.value || navTheme.value !== "dark",
);

const navLinkClass = (to: string) => {
    if (isActive(to)) return "text-primary";

    if (scrolled.value) {
        return "text-secondary/80 hover:text-secondary dark:text-white/80 dark:hover:text-white";
    }

    if (navTheme.value === "dark") {
        return "text-light/90 hover:text-light";
    }

    return isChromeSolid.value
        ? "text-secondary/80 hover:text-secondary dark:text-white/80 dark:hover:text-white"
        : "text-secondary/80 hover:text-secondary";
};

const menuIconClass = computed(() => {
    if (scrolled.value) {
        return "text-secondary hover:bg-primary-50 dark:text-white dark:hover:bg-white/10";
    }

    if (navTheme.value === "dark") {
        return "text-light hover:bg-light/10";
    }

    return isChromeSolid.value
        ? "text-secondary hover:bg-primary-50 dark:text-white dark:hover:bg-white/10"
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
        <nav
            class="flex justify-between items-center w-full h-[90px]"
            :class="navInner"
        >
            <nav
                v-if="variant === 5"
                class="flex justify-between items-center w-full h-[90px]"
            >
                <NuxtLink to="/" class="shrink-0">
                    <img
                        :src="logoAmuma"
                        alt="AMUMA logo"
                        class="w-[180px] object-contain transition-all duration-300"
                    />
                </NuxtLink>

                <ClientOnly>
                    <ThemeToggle
                        class="text-slate-500 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-white/10"
                    />
                </ClientOnly>
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
                            :src="logoAmuma"
                            alt="AMUMA logo"
                            class="w-[180px] object-contain transition-all duration-300"
                        />
                    </NuxtLink>

                    <div
                        v-if="variant === 1 || variant === 2 || variant === 3"
                        class="relative hidden 2xl:flex items-center gap-1"
                    >
                        <span
                            class="absolute inset-y-1 left-0 z-0 rounded-lg transition-all duration-300 ease-out"
                            :class="
                                navTheme === 'dark' && !scrolled
                                    ? 'bg-light/15'
                                    : isChromeSolid
                                      ? 'bg-primary-50 dark:bg-primary-500/10'
                                      : 'bg-primary-50'
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
                        <ClientOnly>
                            <ThemeToggle
                                :class="
                                    !isChromeSolid
                                        ? 'text-white hover:bg-white/10'
                                        : 'text-muted-dark hover:bg-primary/10 dark:text-white dark:hover:bg-white/10'
                                "
                            />
                        </ClientOnly>

                        <NuxtLink
                            :to="hydrated ? '/auth/signin' : undefined"
                            class="hidden sm:block shrink-0"
                        >
                            <BaseButton
                                buttonClass="px-[18px] lg:px-[28px] h-11 rounded-lg whitespace-nowrap min-w-fit shadow-sm shadow-primary-500/25 transition-all duration-200 hover:shadow-md hover:shadow-primary-500/30 active:scale-[0.97]"
                                class="bg-primary text-white border border-primary hover:bg-primary-600"
                            >
                                Sign in
                            </BaseButton>
                        </NuxtLink>

                        <NuxtLink
                            :to="hydrated ? '/auth/signup' : undefined"
                            class="hidden sm:block shrink-0"
                        >
                            <BaseButton
                                variant="outline"
                                class="h-11 rounded-lg border-[1.5px] px-[20px] lg:px-[24px] whitespace-nowrap min-w-fit font-semibold transition-all duration-200 active:scale-[0.97]"
                                :class="
                                    !isChromeSolid
                                        ? 'border-white/30 bg-white/5 text-white hover:bg-white/15'
                                        : 'border-primary/30 text-primary bg-primary-50/60 hover:bg-primary-50 hover:border-primary/50 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/20'
                                "
                            >
                                Sign up
                            </BaseButton>
                        </NuxtLink>
                    </template>

                    <NavbarProfileDropdown
                        v-else
                        :user="user"
                        :scrolled="scrolled"
                        :navTheme="navTheme"
                        :theme-aware="isChromeSolid"
                    />

                    <button
                        v-if="variant === 1 || variant === 2 || variant === 3"
                        class="2xl:hidden w-9 h-9 flex items-center justify-center rounded-lg transition-colors duration-300"
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
                :desktop-breakpoint="1536"
                @close="mobileMenuOpen = false"
            />
        </ClientOnly>
    </header>
</template>
