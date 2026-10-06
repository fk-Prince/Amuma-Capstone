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
import { ChevronDown } from "lucide-vue-next";
import Notification from "../ui/Notification.vue";

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

type NavChild = { label: string; to: string; icon?: any; description?: string };

const props = defineProps<{
    navList?: {
        label: string;
        to: string;
        match?: string;
        icon?: any;
        children?: NavChild[];
    }[];
}>();

const openMenu = ref<string | null>(null);

const closeMenu = () => {
    openMenu.value = null;
};

const onDocumentPointerDown = (e: PointerEvent) => {
    if (!(e.target as HTMLElement)?.closest?.("[data-nav-dropdown]")) {
        closeMenu();
    }
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === "Escape") closeMenu();
};

onMounted(() => {
    document.addEventListener("pointerdown", onDocumentPointerDown);
    document.addEventListener("keydown", onKeydown);
});

onUnmounted(() => {
    document.removeEventListener("pointerdown", onDocumentPointerDown);
    document.removeEventListener("keydown", onKeydown);
});

const dropdownLinkClass = (to: string) =>
    route.path === to
        ? "bg-primary/10 dark:bg-primary/15"
        : "hover:bg-primary/5 dark:hover:bg-white/5";

const dropdownIconClass = (to: string) =>
    route.path === to
        ? "bg-primary text-white"
        : "bg-primary-50 text-primary group-hover/item:bg-primary group-hover/item:text-white dark:bg-white/10 dark:text-primary-300";

const dropdownTitleClass = (to: string) =>
    route.path === to
        ? "text-primary dark:text-primary-300"
        : "text-secondary dark:text-white";

const variant = computed(() => route.meta.navVariant ?? 1);

const CONTENT_BOX = "inset-x-0 mx-auto w-[88%] max-w-[1600px]";

const AUTH_BOX = "inset-x-0 mx-auto w-[94%] max-w-[1400px]";

const DARK_CHROME_SOLID =
    "dark:border-white/10 dark:bg-secondary/70 dark:backdrop-blur-xl";

const DARK_CHROME_RAISED =
    "dark:border-white/10 dark:bg-[#212A3E]/60 dark:backdrop-blur-xl";

const DARK_GLOW =
    "shadow-[0_10px_30px_-10px_rgba(15,23,42,0.15)] dark:shadow-[0_10px_40px_-12px_rgba(0,0,0,0.8)]";

const INDICATOR_BLEED = 8;

const navInner = computed(() => {
    if (variant.value === 2 || variant.value === 3 || variant.value === 7)
        return "px-4 sm:px-10";
    if (variant.value === 1 || variant.value === 4)
        return "mx-auto max-w-[100rem] px-6";
    if (variant.value === 5) return "px-6";
    if (variant.value === 6) return "mx-auto max-w-[100rem] px-6 sm:px-10";

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
        case 7:
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
                "relative mx-4 mt-4 sm:mx-6 sm:mt-6 h-[70px] flex items-center",
                "rounded-2xl shadow-md shadow-secondary/10",
                "transition-all duration-300 ease-out bg-secondary dark:bg-surface",
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
                "relative w-full h-[90px] flex items-center bg-white dark:bg-secondary",
            ]
                .filter(Boolean)
                .join(" ");
    }
});

const authSwitch = computed(() => {
    if (route.path === "/auth/signin") {
        return { label: "Sign up", to: "/auth/signup" };
    }
    if (route.path === "/auth/signup") {
        return { label: "Sign in", to: "/auth/signin" };
    }
    return null;
});

const isActive = (to: string) => {
    if (to === "/") return route.path === "/";
    return route.path === to || route.path.startsWith(`${to}/`);
};
const isDark = useIsDark();

const navTheme = computed(() =>
    route.meta.navThemeDarkOnly && !isDark.value
        ? "light"
        : (route.meta.navTheme ?? "light"),
);

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

const isItemActive = (item: { to: string; match?: string }) =>
    item.match ? route.path.startsWith(item.match) : isActive(item.to);

const activeIndex = computed(() => {
    if (!props.navList) return -1;
    return props.navList.findIndex((item) => isItemActive(item));
});

const itemLinkClass = (item: { to: string; match?: string }) =>
    isItemActive(item) ? "text-primary" : navLinkClass(item.to);

// Notifications exist for staff and owners, and for a client once they have a
// booking or a patient. A brand-new client has nothing to be notified about.
const showNotifications = computed(() => {
    const current = user.value;

    if (!current) return false;

    return (
        !!current.isEmployee ||
        !!current.isSystemOwner ||
        (!!current.isClient && (!!current.hasBooking || !!current.hasPatient))
    );
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

watch(() => route.path, closeMenu);
</script>

<template>
    <header :class="header">
        <nav
            class="relative flex justify-between items-center w-full"
            :class="[navInner, variant === 5 ? 'h-full' : 'h-[90px]']"
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
                        v-if="authSwitch"
                        :to="authSwitch.to"
                        class="whitespace-nowrap text-sm font-semibold text-primary-300 transition-colors duration-200 hover:text-light"
                    >
                        {{ authSwitch.label }}
                    </NuxtLink>
                </div>
            </nav>
            <nav v-if="variant === 7" class="flex h-full w-full items-center">
                <NuxtLink to="/" class="shrink-0" aria-label="AMUMA home">
                    <BrandLogo
                        icon-class="h-8 w-8 sm:h-10 sm:w-10"
                        text-class="text-lg sm:text-2xl"
                    />
                </NuxtLink>

                <div class="relative ml-8 hidden shrink-0 items-center xl:flex">
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
                        :class="itemLinkClass(i)"
                    >
                        {{ i.label }}
                        <span
                            v-if="!isItemActive(i)"
                            class="pointer-events-none absolute inset-x-3 bottom-0 h-[3px] origin-center scale-x-0 rounded-full transition-transform duration-300 ease-out group-hover:scale-x-100"
                            :class="indicatorColor"
                        />
                    </NuxtLink>
                </div>

                <div class="ml-auto flex items-center gap-6">
                    <template v-if="!hydrated || !user">
                        <NuxtLink
                            :to="hydrated ? '/auth/signin' : undefined"
                            class="hidden shrink-0 whitespace-nowrap text-sm font-medium transition-colors duration-200 sm:block"
                            :class="signInLinkClass"
                        >
                            Sign in
                        </NuxtLink>

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

                    <template v-else>
                        <Notification v-if="showNotifications" />

                        <NavbarProfileDropdown
                            :user="user"
                            :scrolled="scrolled"
                            :navTheme="navTheme"
                            :theme-aware="isChromeSolid"
                        />
                    </template>

                    <button
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
            </nav>
            <nav
                v-if="variant === 6"
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
                    class="relative hidden shrink-0 items-center lg:flex"
                >
                    <span
                        class="absolute bottom-0 left-0 h-[3px] rounded-full transition-all duration-300 ease-out"
                        :class="indicatorColor"
                        :style="pillStyle"
                    />

                    <template v-for="(i, index) in navList" :key="i.to">
                        <div
                            v-if="i.children?.length"
                            :ref="(el) => setNavRef(el, index)"
                            data-nav-dropdown
                            class="relative z-10 py-2 px-3 xl:px-5"
                            @mouseenter="openMenu = i.to"
                            @mouseleave="closeMenu"
                        >
                            <button
                                type="button"
                                class="group relative flex items-center gap-1 whitespace-nowrap text-sm font-medium transition-colors duration-300"
                                :class="navLinkClass(i.to)"
                                aria-haspopup="menu"
                                :aria-expanded="openMenu === i.to"
                                @click="openMenu = i.to"
                            >
                                {{ i.label }}

                                <ChevronDown
                                    class="h-3.5 w-3.5 transition-transform duration-200"
                                    :class="
                                        openMenu === i.to ? 'rotate-180' : ''
                                    "
                                />

                                <span
                                    v-if="!isActive(i.to)"
                                    class="pointer-events-none absolute inset-x-0 -bottom-2 h-[3px] origin-center scale-x-0 rounded-full transition-transform duration-300 ease-out group-hover:scale-x-100"
                                    :class="indicatorColor"
                                />
                            </button>

                            <Transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-100 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-1"
                            >
                                <div
                                    v-if="openMenu === i.to"
                                    class="absolute left-1/2 top-full -translate-x-1/2 pt-3"
                                >
                                    <div
                                        role="menu"
                                        class="flex w-[300px] flex-col gap-1 rounded-2xl border border-muted-light bg-white p-2 shadow-[0_16px_40px_-12px_rgba(15,23,42,0.25)] dark:border-white/10 dark:bg-secondary"
                                    >
                                        <NuxtLink
                                            v-for="c in i.children"
                                            :key="c.to"
                                            :to="c.to"
                                            role="menuitem"
                                            class="group/item flex items-start gap-3 rounded-xl p-3 transition-colors duration-200"
                                            :class="dropdownLinkClass(c.to)"
                                            @click="closeMenu"
                                        >
                                            <span
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors duration-200"
                                                :class="dropdownIconClass(c.to)"
                                            >
                                                <component
                                                    :is="c.icon"
                                                    v-if="c.icon"
                                                    class="h-4 w-4"
                                                />
                                            </span>

                                            <span class="flex min-w-0 flex-col">
                                                <span
                                                    class="text-sm font-semibold"
                                                    :class="
                                                        dropdownTitleClass(c.to)
                                                    "
                                                >
                                                    {{ c.label }}
                                                </span>
                                                <span
                                                    v-if="c.description"
                                                    class="mt-0.5 text-xs leading-5 text-muted dark:text-gray-400"
                                                >
                                                    {{ c.description }}
                                                </span>
                                            </span>
                                        </NuxtLink>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <NuxtLink
                            v-else
                            :ref="(el) => setNavRef(el, index)"
                            :to="i.to"
                            class="group relative z-10 whitespace-nowrap py-2 text-sm font-medium transition-colors duration-300 px-3 xl:px-5"
                            :class="navLinkClass(i.to)"
                        >
                            {{ i.label }}

                            <span
                                v-if="!isActive(i.to)"
                                class="pointer-events-none absolute inset-x-3 bottom-0 h-[3px] origin-center scale-x-0 rounded-full transition-transform duration-300 ease-out group-hover:scale-x-100"
                                :class="indicatorColor"
                            />
                        </NuxtLink>
                    </template>
                </div>

                <div
                    class="flex flex-1 items-center justify-end gap-4 xl:gap-6"
                >
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
                                buttonClass="md:px-6 xl:px-9 h-[46px] rounded-xl whitespace-nowrap min-w-fit shadow-sm shadow-primary-500/25 transition-all duration-200 hover:shadow-md hover:shadow-primary-500/30 active:scale-[0.97]"
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

                    <template v-else>
                        <Notification v-if="showNotifications" />

                        <NavbarProfileDropdown
                            :user="user"
                            :scrolled="scrolled"
                            :navTheme="navTheme"
                            :theme-aware="isChromeSolid"
                        />
                    </template>

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

        <ClientOnly
            v-if="
                variant === 1 || variant === 2 || variant === 3 || variant === 7
            "
        >
            <DynamicSidebar
                :open="mobileMenuOpen"
                :logo="logoIcon"
                :authMenu="navList"
                :user="user"
                :desktop-breakpoint="1024"
                @close="mobileMenuOpen = false"
            />
        </ClientOnly>
    </header>
</template>
