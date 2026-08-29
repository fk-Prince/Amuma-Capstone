<template>
    <ClientOnly>
        <template #fallback>
            <div />
        </template>

        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open && !isDesktop"
                    class="fixed inset-0 z-[60] bg-primary-900/50 backdrop-blur-sm"
                    @click="$emit('close')"
                />
            </Transition>

            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-250 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <aside
                    v-if="open && !isDesktop"
                    class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-secondary shadow-[0_0_40px_rgba(10,40,87,0.15)] z-[70] flex flex-col lg:hidden"
                >
                    <div
                        class="flex items-center justify-between px-5 h-[72px] border-b border-primary-100/80 dark:border-white/10"
                    >
                        <!-- <NuxtLink to="/" class="flex items-center gap-2.5">
                            <img
                                :src="logo"
                                alt="AMUMA"
                                class="w-9 h-9 rounded-lg object-contain shrink-0"
                            />
                            <p
                                class="font-extrabold text-primary-500 text-xl tracking-wide [text-shadow:0_4px_8px_rgb(49_130_237_/_35%)]"
                            >
                                AMUMA
                            </p>
                        </NuxtLink> -->

                        <button
                            @click="$emit('close')"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-primary-500 hover:bg-primary-50 hover:text-primary-700 transition-colors duration-200"
                        >
                            <X class="w-[18px] h-[18px]" />
                        </button>
                    </div>

                    <nav class="scroll-left flex-1 overflow-y-auto">
                        <div
                            class="scroll-left-inner flex flex-col px-3 py-4 gap-1"
                        >
                            <ClientOnly>
                                <template #fallback>
                                    <NavSkeleton :count="7" />
                                </template>

                                <template
                                    v-for="(item, index) in navItems"
                                    :key="`${item.id || item.to}-${index}`"
                                >
                                    <div
                                        v-if="item.divider"
                                        class="h-px bg-primary-100 my-2.5 mx-2"
                                    />
                                    <NuxtLink
                                        v-else
                                        :to="item.to"
                                        :class="navClass(item.to)"
                                        @click="$emit('close')"
                                    >
                                        <span :class="iconWrapClass(item.to)">
                                            <component
                                                v-if="item.icon"
                                                :is="item.icon"
                                                class="w-4 h-4"
                                            />
                                        </span>
                                        <span class="truncate">{{
                                            item.label
                                        }}</span>
                                        <ChevronRight
                                            v-if="isActive(item.to)"
                                            class="w-3.5 h-3.5 ml-auto opacity-70"
                                        />
                                    </NuxtLink>
                                </template>
                            </ClientOnly>
                        </div>
                    </nav>

                    <div class="border-t border-primary-100/80 px-3 pb-5 pt-3 dark:border-white/10">
                        <ClientOnly>
                            <template #fallback>
                                <UserSkeleton />
                            </template>

                            <template v-if="user">
                                <div
                                    class="flex items-center gap-2.5 mb-3 px-2"
                                >
                                    <div class="relative shrink-0">
                                        <div
                                            class="w-9 h-9 rounded-full overflow-hidden bg-primary flex items-center justify-center text-white text-xs font-semibold ring-2 ring-primary-100"
                                        >
                                            <img
                                                v-if="user.avatar"
                                                :src="user.avatar"
                                                alt="Profile"
                                                class="w-full h-full object-cover"
                                            />
                                        </div>
                                        <span
                                            class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 border-2 border-white dark:border-secondary rounded-full"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate font-semibold text-[14px] text-primary-900 dark:text-white"
                                        >
                                            {{ user.first_name }}
                                            {{ user.last_name }}
                                        </p>
                                        <p
                                            class="truncate text-[12px] text-muted dark:text-gray-400"
                                        >
                                            {{ user.email }}
                                        </p>
                                        <p
                                            v-if="activeRoleName"
                                            class="inline-block w-full text-center text-[11px] px-2 py-0.5 rounded-full font-semibold border mt-1"
                                            :class="
                                                roleMeta[activeRoleName]
                                                    ?.class ||
                                                'bg-primary-50 text-primary-600 border-primary-200'
                                            "
                                        >
                                            {{
                                                roleMeta[activeRoleName]
                                                    ?.label ||
                                                formatRole(activeRoleName)
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="logout"
                                    :disabled="loadingLogout"
                                    class="w-full flex items-center gap-2 text-red-500 hover:bg-red-50 px-3 py-2 rounded-lg font-medium text-[13px] transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg
                                        v-if="loadingLogout"
                                        class="w-4 h-4 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />

                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                        />
                                    </svg>

                                    <LogOut v-else class="w-4 h-4" />

                                    {{
                                        loadingLogout
                                            ? "Logging out..."
                                            : "Log out"
                                    }}
                                </button>
                            </template>

                            <template v-else>
                                <div class="flex flex-col gap-1.5 px-2">
                                    <NuxtLink
                                        to="/auth/signin"
                                        class="w-full text-center bg-primary hover:bg-primary-600 text-white font-semibold text-[13px] py-2 rounded-lg transition-colors duration-200"
                                    >
                                        Sign in
                                    </NuxtLink>
                                    <NuxtLink
                                        to="/auth/signup"
                                        class="w-full text-center bg-primary-50 hover:bg-primary-100 text-primary-700 font-semibold text-[13px] py-2 rounded-lg transition-colors duration-200"
                                    >
                                        Sign up
                                    </NuxtLink>
                                </div>
                            </template>
                        </ClientOnly>
                    </div>
                </aside>
            </Transition>
        </Teleport>
    </ClientOnly>

    <aside
        v-if="variant === 2 && isDesktop"
        :class="[
            'hidden lg:flex h-full relative bg-white shadow-[0_0_40px_rgba(10,40,87,0.08)] flex-col shrink-0 transition-[width] duration-300 ease-in-out border-r border-primary-100/70',
            desktopCollapsed ? 'w-[76px]' : 'w-60',
        ]"
    >
        <!-- <NuxtLink
            to="/"
            :class="[
                'flex items-center gap-2.5 shrink-0 pt-4 pb-3',
                desktopCollapsed ? 'justify-center px-0' : 'px-[19px]',
            ]"
        >
            <img
                :src="logo"
                alt="AMUMA"
                class="w-9 h-9 rounded-lg object-contain shrink-0"
            />
            <div
                v-if="!desktopCollapsed"
                class="whitespace-nowrap leading-tight"
            >
                <p
                    class="font-extrabold text-primary-500 text-2xl tracking-wide [text-shadow:0_4px_8px_rgb(49_130_237_/_35%)]"
                >
                    AMUMA
                </p>
            </div>
        </NuxtLink> -->

        <button
            @click="desktopCollapsed = !desktopCollapsed"
            class="absolute -right-3 top-7 z-10 bg-white border border-primary-200 shadow-md w-7 h-7 flex items-center justify-center rounded-full text-primary-600 hover:bg-primary hover:text-white hover:border-primary transition-colors duration-200"
        >
            <component
                :is="desktopCollapsed ? ChevronRight : ChevronLeft"
                class="w-4 h-4"
            />
        </button>

        <div class="scroll-left flex-1 overflow-y-auto overflow-x-hidden py-4">
            <div class="scroll-left-inner flex flex-col px-2.5 gap-1">
                <ClientOnly>
                    <template #fallback>
                        <NavSkeleton :count="9" :collapsed="desktopCollapsed" />
                    </template>

                    <template
                        v-for="(item, index) in navItems"
                        :key="`${item.id || item.to}-${index}`"
                    >
                        <div
                            v-if="item.divider"
                            class="h-px bg-primary-100 my-2.5 mx-2"
                        />
                        <NuxtLink
                            v-else
                            :to="item.to"
                            :class="navClass(item.to, true)"
                            :title="desktopCollapsed ? item.label : undefined"
                        >
                            <span :class="iconWrapClass(item.to)">
                                <component
                                    v-if="item.icon"
                                    :is="item.icon"
                                    class="w-4 h-4"
                                />
                            </span>
                            <span v-if="!desktopCollapsed" class="truncate">{{
                                item.label
                            }}</span>
                        </NuxtLink>
                    </template>
                </ClientOnly>
            </div>
        </div>

        <div class="border-t border-primary-100/80 px-2.5 pb-5 pt-3">
            <ClientOnly>
                <template #fallback>
                    <UserSkeleton :collapsed="desktopCollapsed" />
                </template>

                <template v-if="user">
                    <div
                        :class="[
                            'flex items-center gap-2.5 mb-3',
                            desktopCollapsed ? 'justify-center px-0' : 'px-1',
                        ]"
                    >
                        <div class="relative shrink-0">
                            <div
                                class="w-9 h-9 rounded-full overflow-hidden bg-primary flex items-center justify-center text-white text-xs font-semibold ring-2 ring-primary-100"
                            >
                                <img
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    alt="Profile"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <span
                                class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 border-2 border-white rounded-full"
                            />
                        </div>

                        <div v-if="!desktopCollapsed" class="min-w-0">
                            <p
                                class="truncate font-semibold text-[14px] text-primary-900"
                            >
                                {{ user.first_name }} {{ user.last_name }}
                            </p>
                            <p class="truncate text-[12px] text-muted">
                                {{ user.email }}
                            </p>
                            <p
                                v-if="activeRoleName"
                                class="inline-block w-full text-center text-[11px] px-2 py-0.5 rounded-full font-semibold border mt-1"
                                :class="
                                    roleMeta[activeRoleName]?.class ||
                                    'bg-primary-50 text-primary-600 border-primary-200'
                                "
                            >
                                {{
                                    roleMeta[activeRoleName]?.label ||
                                    formatRole(activeRoleName)
                                }}
                            </p>
                        </div>
                    </div>

                    <button
                        @click="logout"
                        :disabled="loadingLogout"
                        class="w-full flex items-center gap-2 text-red-500 hover:bg-red-50 px-3 py-2 rounded-lg font-medium text-[13px] transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="desktopCollapsed ? 'justify-center px-0' : ''"
                        :title="desktopCollapsed ? 'Log out' : undefined"
                    >
                        <svg
                            v-if="loadingLogout"
                            class="w-4 h-4 animate-spin shrink-0"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />
                        </svg>

                        <LogOut v-else class="w-4 h-4 shrink-0" />

                        <span v-if="!desktopCollapsed" class="truncate">
                            {{ loadingLogout ? "Logging out..." : "Log out" }}
                        </span>
                    </button>
                </template>

                <template v-else>
                    <div class="flex flex-col gap-1.5">
                        <NuxtLink
                            to="/auth/signin"
                            class="w-full text-center bg-primary hover:bg-primary-600 text-white font-semibold text-[13px] py-2 rounded-lg transition-colors duration-200"
                        >
                            {{ desktopCollapsed ? "→" : "Sign in" }}
                        </NuxtLink>
                        <NuxtLink
                            v-if="!desktopCollapsed"
                            to="/auth/signup"
                            class="w-full text-center bg-primary-50 hover:bg-primary-100 text-primary-700 font-semibold text-[13px] py-2 rounded-lg transition-colors duration-200"
                        >
                            Sign up
                        </NuxtLink>
                    </div>
                </template>
            </ClientOnly>
        </div>
    </aside>
</template>

<script setup lang="ts">
import {
    computed,
    ref,
    onMounted,
    onBeforeUnmount,
    h,
    defineComponent,
} from "vue";
import { useRoute } from "vue-router";
import { authService } from "~/api/auth/AuthService";
import { useToast } from "~/composables/useToast";
import { resetAuth } from "~/composables/useAuthUser";
import { ChevronLeft, ChevronRight, LogOut, X } from "lucide-vue-next";
import { useBranchStore } from "~/stores/branch";
import { formatRole, roleMeta } from "~/utils/user";

const props = withDefaults(
    defineProps<{
        open: boolean;
        logo: string;
        authMenu?: Array<{
            id?: string;
            label: string;
            to: string;
            icon?: any;
            divider?: boolean;
        }>;
        user?: any | null;
        variant?: 1 | 2;
    }>(),
    {
        variant: 1,
    },
);

const emit = defineEmits<{
    close: [];
    logout: [];
}>();

const branchStore = useBranchStore();
const route = useRoute();
const { success, error } = useToast();

const desktopCollapsed = ref(false);
const navItems = computed(() => props.authMenu ?? []);
const activeRoleName = computed(
    () => branchStore.activeBranch?.role_name ?? null,
);
const isDesktop = ref(false);

const initials = computed(() => {
    const first = props.user?.first_name?.[0] ?? "";
    const last = props.user?.last_name?.[0] ?? "";
    return `${first}${last}`.toUpperCase() || "U";
});

const checkScreen = () => {
    isDesktop.value = window.innerWidth >= 1024;
    if (isDesktop.value) {
        emit("close");
    } else {
        desktopCollapsed.value = false;
    }
};

onMounted(() => {
    checkScreen();
    window.addEventListener("resize", checkScreen);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", checkScreen);
});

function isActive(to: string) {
    return route.path === to || route.path.startsWith(to + "/");
}

function navClass(to: string, desktop = false) {
    return [
        "relative px-3 py-2 rounded-lg transition-all duration-200 flex gap-2.5 items-center font-medium text-[13px]",
        desktop && desktopCollapsed.value ? "justify-center px-0" : "",
        isActive(to)
            ? "bg-primary text-white shadow-md shadow-primary-500/30"
            : "text-primary-700 hover:bg-primary-50 dark:text-white/80 dark:hover:bg-white/10",
    ];
}

function iconWrapClass(to: string) {
    return [
        "w-7 h-7 rounded-md flex items-center justify-center shrink-0 transition-colors duration-200",
        isActive(to)
            ? "bg-white/20 text-white"
            : "bg-primary-50 text-primary-500 dark:bg-white/10 dark:text-white/70",
    ];
}

const NavSkeleton = defineComponent({
    props: {
        count: { type: Number, default: 8 },
        collapsed: { type: Boolean, default: false },
    },
    setup(p) {
        return () =>
            h(
                "div",
                { class: "flex flex-col gap-1.5" },
                Array.from({ length: p.count }, (_, i) =>
                    h(
                        "div",
                        {
                            key: i,
                            class: [
                                "flex items-center gap-2.5 px-3 py-2 rounded-lg",
                                p.collapsed ? "justify-center px-0" : "",
                            ],
                        },
                        [
                            h("div", {
                                class: "w-7 h-7 rounded-md shrink-0 skeleton-shimmer",
                            }),
                            !p.collapsed
                                ? h("div", {
                                      class: "h-3 rounded-md skeleton-shimmer",
                                      style: {
                                          width: `${50 + ((i * 17) % 40)}%`,
                                      },
                                  })
                                : null,
                        ],
                    ),
                ),
            );
    },
});

const UserSkeleton = defineComponent({
    props: {
        collapsed: { type: Boolean, default: false },
    },
    setup(p) {
        return () =>
            h("div", [
                h(
                    "div",
                    {
                        class: [
                            "flex items-center gap-2.5 mb-3",
                            p.collapsed ? "justify-center" : "px-1",
                        ],
                    },
                    [
                        h("div", {
                            class: "w-9 h-9 rounded-full shrink-0 skeleton-shimmer",
                        }),
                        !p.collapsed
                            ? h(
                                  "div",
                                  { class: "flex-1 space-y-1.5 min-w-0" },
                                  [
                                      h("div", {
                                          class: "h-3 w-3/5 rounded-md skeleton-shimmer",
                                      }),
                                      h("div", {
                                          class: "h-2.5 w-4/5 rounded-md skeleton-shimmer",
                                      }),
                                      h("div", {
                                          class: "h-3.5 w-14 rounded-full skeleton-shimmer",
                                      }),
                                  ],
                              )
                            : null,
                    ],
                ),
                h("div", {
                    class: [
                        "rounded-lg skeleton-shimmer",
                        p.collapsed ? "w-9 h-9 mx-auto" : "w-full h-9",
                    ],
                }),
            ]);
    },
});

const loadingLogout = ref(false);
const logout = async () => {
    loadingLogout.value = true;

    // The server call is best-effort — if it fails (expired session,
    // network blip, stale CSRF token) the user must still be logged out
    // locally and sent to sign-in, never left stranded on the page they
    // clicked "Log out" from.
    try {
        const res = await authService.logout();
        success(res.message ?? "Logged out successfully.");
    } catch (err: any) {
        console.error(err);
    } finally {
        resetAuth();
        loadingLogout.value = false;
        // A hard redirect (not navigateTo) — the SPA router briefly kept
        // rendering the still-mounted dashboard layout mid-transition,
        // flashing /app/branches before landing on sign-in. A full page
        // load never runs that stale layout at all.
        window.location.href = "/auth/signin";
    }
};
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

.scroll-left {
    direction: rtl;
    scrollbar-width: thin;
    scrollbar-color: theme("colors.primary.300") transparent;
}

.scroll-left-inner {
    direction: ltr;
}

.scroll-left::-webkit-scrollbar {
    width: 5px;
}

.scroll-left::-webkit-scrollbar-track {
    background: transparent;
}

.scroll-left::-webkit-scrollbar-thumb {
    background-color: theme("colors.primary.300");
    border-radius: 999px;
}

.scroll-left::-webkit-scrollbar-thumb:hover {
    background-color: theme("colors.primary.500");
}
</style>
