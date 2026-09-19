<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import logoIcon from "~/assets/logo/logo.png";
import BrandLogo from "../ui/BrandLogo.vue";
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

const CONTENT_BOX = "inset-x-0 mx-auto w-[88%] max-w-[1600px]";

const AUTH_BOX = "inset-x-0 mx-auto w-[94%] max-w-[1400px]";

const DARK_CHROME_SOLID = "dark:border-white/10 dark:bg-secondary";

const DARK_CHROME_RAISED = "dark:border-white/10 dark:bg-[#212A3E]";

const DARK_GLOW = "dark:shadow-[0_10px_40px_-12px_rgba(0,0,0,0.8)]";

const INDICATOR_BLEED = 8;

const navInner = computed(() => {
    if (variant.value === 2 || variant.value === 3) return "px-4 sm:px-10";
    if (variant.value === 1 || variant.value === 4)
        return "mx-auto max-w-[100rem] px-6";
    if (variant.value === 5 || variant.value === 6) return "px-6";
    if (variant.value === 7) return "mx-auto max-w-[100rem] px-6 sm:px-10";

    return "px-6";
});

const header = computed(() => {
    switch (variant.value) {
        case 1:
            return [
                "fixed top-0 left-0 z-50 w-full h-[90px] ",
                "transition-colors duration-200 ease-out",
                scrolled.value
                    ? `bg-white border-b border-muted-light ${DARK_CHROME_SOLID}`
                    : navTheme.value === "dark"
                      ? "bg-transparent border-b border-transparent"
                      : `bg-transparent border-b border-transparent ${DARK_CHROME_SOLID}`,
            ]
                .filter(Boolean)
                .join(" ");

        case 2:
            return [
                "fixed top-6 z-50",
                CONTENT_BOX,
                "rounded-[20px] h-[90px] ",
                "transition-colors duration-200 ease-out",
                scrolled.value
                    ? `border border-muted-light bg-light ${DARK_CHROME_SOLID} ${DARK_GLOW}`
                    : navTheme.value === "dark"
                      ? "border border-transparent bg-transparent"
                      : `border border-transparent bg-transparent ${DARK_CHROME_RAISED} ${DARK_GLOW}`,
            ]
                .filter(Boolean)
                .join(" ");
        case 3:
            return [
                "fixed top-6 z-50",
                CONTENT_BOX,
                "h-[90px] rounded-[20px]",
                "transition-colors duration-200 ease-out",
                scrolled.value
                    ? `border border-muted-light bg-light ${DARK_CHROME_SOLID} ${DARK_GLOW}`
                    : navTheme.value === "dark"
                      ? "border border-light/20 bg-light/10 "
                      : `border border-muted-light bg-light ${DARK_CHROME_RAISED} ${DARK_GLOW}`,
            ]
                .filter(Boolean)
                .join(" ");
        case 4:
            return [
                "relative w-full h-[70px] flex items-center",
                "transition-all duration-300 ease-out",
                `bg-white border-b border-muted-light ${DARK_CHROME_SOLID}`,
            ]
                .filter(Boolean)
                .join(" ");
        case 5:
            return [
                "fixed top-4 sm:top-6 z-50",
                AUTH_BOX,
                "h-[72px] sm:h-[90px] rounded-[20px] flex items-center",
                "border border-light/20 bg-light/10 backdrop-blur-sm",
                "shadow-[0_10px_40px_-12px_rgba(0,0,0,0.55)]",
            ].join(" ");
        case 6:
            return [
                "fixed top-4 sm:top-6 z-50",
                AUTH_BOX,
                "h-[72px] sm:h-[90px] rounded-[20px] flex items-center",
                "border border-light/20 bg-light/10 backdrop-blur-sm",
                "shadow-[0_10px_40px_-12px_rgba(0,0,0,0.55)]",
            ].join(" ");
        case 7:
            return [
                "fixed top-0 left-0 z-50 w-full h-[90px] flex items-center",
                `bg-white border-b border-muted-light ${DARK_CHROME_SOLID}`,
            ].join(" ");
    }
});

const authSwitch = computed(() =>
    route.path === "/auth/signup"
        ? { label: "Sign in", to: "/auth/signin" }
        : { label: "Sign up", to: "/auth/signup" },
);

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

const indicatorColor = computed(() =>
    navTheme.value === "dark" && !scrolled.value ? "bg-light" : "bg-primary",
);

const signInLinkClass = computed(() => {
    if (!isChromeSolid.value) return "text-light/80 hover:text-light";

    return "text-primary hover:text-primary-600 dark:text-primary-300 dark:hover:text-primary-200";
});

const dividerClass = computed(() =>
    !isChromeSolid.value ? "bg-light/20" : "bg-muted-light dark:bg-white/15",
);

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
    navRefs.value[index] = el?.$el ?? el;
};

const updatePillPosition = () => {
    const el = navRefs.value[activeIndex.value];

    if (!el?.offsetWidth) {
        pillStyle.value = { ...pillStyle.value, opacity: "0" };
        return;
    }

    const styles = window.getComputedStyle(el);
    const padLeft = parseFloat(styles.paddingLeft) || 0;
    const padRight = parseFloat(styles.paddingRight) || 0;

    pillStyle.value = {
        width: `${el.offsetWidth - padLeft - padRight + INDICATOR_BLEED * 2}px`,
        opacity: "1",
        transform: `translateX(${el.offsetLeft + padLeft - INDICATOR_BLEED}px)`,
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
            class="relative flex justify-between items-center w-full"
            :class="[navInner, variant === 5 || variant === 6 ? 'h-full' : 'h-[90px]']"
        >
            <nav
                v-if="variant === 5"
                class="flex h-full w-full items-center justify-between"
            >
                <NuxtLink to="/" class="shrink-0" aria-label="AMUMA home">
                    <BrandLogo />
                </NuxtLink>

                <div class="flex shrink-0 items-center gap-1 sm:gap-3">
                    <ClientOnly>
                        <ThemeToggle
                            class="text-light/70 hover:bg-light/10 hover:text-light"
                        />
                    </ClientOnly>

                    <NuxtLink
                        :to="authSwitch.to"
                        class="whitespace-nowrap text-sm font-semibold text-primary-300 transition-colors duration-200 hover:text-light"
                    >
                        {{ authSwitch.label }}
                    </NuxtLink>
                </div>
            </nav>
            <nav
                v-if="variant === 6"
                class="relative flex h-full w-full items-center justify-center"
            >
                <NuxtLink to="/" aria-label="AMUMA home">
                    <BrandLogo />
                </NuxtLink>
            </nav>
            <nav
                v-if="variant === 7"
                class="flex w-full items-center justify-between"
            >
                <NuxtLink to="/" class="shrink-0" aria-label="AMUMA home">
                    <BrandLogo />
                </NuxtLink>

                <NavbarProfileDropdown
                    v-if="hydrated && user"
                    :user="user"
                    :scrolled="scrolled"
                    :navTheme="navTheme"
                    :theme-aware="true"
                />
            </nav>

            <template
                v-if="
                    variant === 1 ||
                    variant === 2 ||
                    variant === 3 ||
                    variant === 4
                "
            >
                <div class="flex flex-1 items-center min-w-0">
                    <NuxtLink to="/" class="shrink-0" aria-label="AMUMA home">
                        <BrandLogo
                            icon-class="h-8 w-8 sm:h-10 sm:w-10"
                            text-class="text-lg sm:text-2xl"
                        />
                    </NuxtLink>
                </div>

                <div
                    v-if="variant === 1 || variant === 2 || variant === 3"
                    class="relative hidden shrink-0 items-center xl:flex"
                >
                    <span
                        class="absolute bottom-0 left-0 h-[3px] rounded-full transition-all duration-300 ease-out"
                        :class="indicatorColor"
                        :style="pillStyle"
                    />

                    <NuxtLink
                        v-for="(i, index) in navList"
                        :key="i.to"
                        :ref="(el) => setNavRef(el, index)"
                        :to="i.to"
                        class="group relative z-10 whitespace-nowrap py-2 text-sm font-medium transition-colors duration-300 px-5"
                        :class="navLinkClass(i.to)"
                    >
                        {{ i.label }}

                        <span
                            v-if="!isActive(i.to)"
                            class="pointer-events-none absolute inset-x-3 bottom-0 h-[3px] origin-center scale-x-0 rounded-full transition-transform duration-300 ease-out group-hover:scale-x-100"
                            :class="indicatorColor"
                        />
                    </NuxtLink>
                </div>

                <div class="flex flex-1 items-center justify-end gap-6">
                    <template v-if="!hydrated || !user">
                        <NuxtLink
                            :to="hydrated ? '/auth/signin' : undefined"
                            class="hidden shrink-0 whitespace-nowrap md:px-5 text-sm font-medium transition-colors duration-200 sm:block"
                            :class="signInLinkClass"
                        >
                            Sign in
                        </NuxtLink>

                        <span
                            class="hidden h-6 w-px shrink-0 sm:block"
                            :class="dividerClass"
                        />

                        <NuxtLink
                            :to="hydrated ? '/auth/signup' : undefined"
                            class="hidden sm:block shrink-0"
                        >
                            <BaseButton
                                buttonClass="md:px-9 h-[46px] rounded-xl whitespace-nowrap min-w-fit shadow-sm shadow-primary-500/25 transition-all duration-200 hover:shadow-md hover:shadow-primary-500/30 active:scale-[0.97]"
                                class="bg-primary text-white border border-primary hover:bg-primary-600"
                            >
                                Sign up
                            </BaseButton>
                        </NuxtLink>

                        <ClientOnly>
                            <ThemeToggle
                                :class="
                                    !isChromeSolid
                                        ? 'text-white hover:bg-white/10'
                                        : 'text-muted-dark hover:bg-primary/10 dark:text-white dark:hover:bg-white/10'
                                "
                            />
                        </ClientOnly>
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
                        class="xl:hidden w-9 h-9 flex items-center justify-center rounded-lg transition-colors duration-300"
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
                :logo="logoIcon"
                :authMenu="navList"
                :user="user"
                :desktop-breakpoint="1280"
                @close="mobileMenuOpen = false"
            />
        </ClientOnly>
    </header>
</template>
