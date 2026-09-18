<template>
    <ClientOnly>
        <BaseDropdownMenu align="right" width="w-64">
            <template #trigger="{ toggle, open }">
                <button
                    @click="toggle"
                    class="flex flex-row-reverse items-center gap-2.5 px-2 py-1.5 rounded-xl transition-colors focus:outline-none md:flex-row"
                    :class="[
                        scrolled || navTheme !== 'dark'
                            ? 'hover:bg-primary-50 dark:hover:bg-primary-500/10'
                            : 'hover:bg-light/10',
                        themeAware ? 'dark:hover:bg-white/10' : '',
                    ]"
                >
                    <div class="relative">
                        <img
                            :src="user.avatar"
                            class="w-9 h-9 rounded-full border-2 border-white shadow-sm object-cover"
                            :class="themeAware ? 'dark:border-white/20' : ''"
                            alt="Profile"
                        />
                        <span
                            class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border-2 border-white rounded-full"
                            :class="themeAware ? 'dark:border-secondary' : ''"
                        />
                    </div>

                    <div
                        class="hidden md:flex flex-col items-start leading-tight"
                    >
                        <span
                            class="text-sm font-medium transition-colors duration-300"
                            :class="[
                                scrolled || navTheme !== 'dark'
                                    ? 'text-secondary/80'
                                    : 'text-light',
                                themeAware ? 'dark:text-white' : '',
                            ]"
                        >
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                        <span
                            v-if="roleLabel"
                            class="mt-0.5 rounded-full border px-1.5 py-0.5 text-[10px] font-semibold leading-none"
                            :class="roleClass"
                        >
                            {{ roleLabel }}
                        </span>

                        <span
                            v-else
                            class="text-xs transition-colors duration-300"
                            :class="[
                                scrolled || navTheme !== 'dark'
                                    ? 'text-muted dark:text-gray-400'
                                    : 'text-light/70',
                                themeAware ? 'dark:text-gray-400' : '',
                            ]"
                        >
                            View profile
                        </span>
                    </div>

                    <ChevronIcon
                        :isOpen="open"
                        class="block w-4 h-4 transition-colors duration-300"
                        :class="[
                            scrolled || navTheme !== 'dark'
                                ? 'text-muted dark:text-gray-400'
                                : 'text-light/80',
                            themeAware ? 'dark:text-white/60' : '',
                        ]"
                    />
                </button>
            </template>

            <template #default="{ close }">
                <div
                    class="bg-white dark:bg-secondary text-gray-800 dark:text-white rounded-2xl border border-gray-100 dark:border-white/10 shadow-lg dark:shadow-black/30 overflow-hidden transition-colors"
                >
                    <div
                        class="px-4 py-4 border-b border-gray-50 dark:border-white/10 flex items-center gap-3"
                    >
                        <div class="relative shrink-0">
                            <img
                                :src="user.avatar"
                                class="w-10 h-10 rounded-full border-2 border-white shadow-sm object-cover"
                                alt="Profile"
                            />
                            <span
                                class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border border-white rounded-full"
                            />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <p
                                class="text-[15px] font-medium text-gray-800 dark:text-white truncate"
                            >
                                {{ user.first_name }} {{ user.last_name }}
                            </p>
                            <p
                                class="text-xs text-gray-400 dark:text-gray-400 truncate"
                            >
                                {{ user.email }}
                            </p>

                            <span
                                v-if="roleLabel"
                                class="mt-1.5 w-fit rounded-full border px-2 py-0.5 text-[10px] font-semibold leading-none"
                                :class="roleClass"
                            >
                                {{ roleLabel }}
                            </span>
                        </div>
                    </div>

                    <p
                        class="mx-4 mt-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500"
                    >
                        Appearance
                    </p>

                    <div
                        class="mx-4 my-1 flex items-center justify-between rounded-full bg-gray-50 dark:bg-white/5 p-1"
                    >
                        <button
                            type="button"
                            class="flex-1 flex items-center justify-center gap-1.5 rounded-full py-1.5 text-xs font-medium transition-colors"
                            :class="
                                !isDark
                                    ? 'bg-white text-gray-800 shadow-sm dark:bg-secondary dark:text-white'
                                    : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400'
                            "
                            @click="setTheme(false)"
                        >
                            <Sun class="w-3.5 h-3.5" />
                            Light
                        </button>
                        <button
                            type="button"
                            class="flex-1 flex items-center justify-center gap-1.5 rounded-full py-1.5 text-xs font-medium transition-colors"
                            :class="
                                isDark
                                    ? 'bg-secondary text-white shadow-sm'
                                    : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400'
                            "
                            @click="setTheme(true)"
                        >
                            <Moon class="w-3.5 h-3.5" />
                            Dark
                        </button>
                    </div>

                    <div class="py-1">
                        <DropdownItem
                            v-for="item in visibleMenuItems"
                            :key="item.label"
                            :icon="item.icon"
                            :label="item.label"
                            :to="item.to"
                            @click="() => onMenuItem(item, close)"
                        />
                    </div>

                    <div
                        class="py-1 border-t border-gray-50 dark:border-white/10"
                    >
                        <button
                            type="button"
                            :disabled="loggingOut"
                            class="w-full flex items-center gap-3 px-4 py-3 text-sm text-rose-500 transition-colors hover:bg-rose-50 dark:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-50 dark:text-rose-300"
                            @click="
                                () => {
                                    logout();
                                    close();
                                }
                            "
                        >
                            <LoaderCircle
                                v-if="loggingOut"
                                class="w-[18px] h-[18px] animate-spin"
                            />
                            <LogOut v-else class="w-[18px] h-[18px]" />
                            Log out
                        </button>
                    </div>
                </div>
            </template>
        </BaseDropdownMenu>

        <AuthTransitionScreen v-if="loggingOut" />

        <AuthTransitionScreen
            v-else-if="navigating"
            title="Taking you to your dashboard"
            subtitle=""
        />
    </ClientOnly>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute } from "vue-router";
import { LogOut, LoaderCircle, Sun, Moon } from "lucide-vue-next";
import BaseDropdownMenu from "../ui/BaseDropdownMenu.vue";
import DropdownItem from "../ui/DropdownItem.vue";
import ChevronIcon from "../icons/dropdown.vue";
import AuthTransitionScreen from "./AuthTransitionScreen.vue";
import { authService } from "~/api/auth/AuthService.js";
import {
    handleMenuClick,
    profileMenuDropDownList,
} from "~/config/profileMenu.js";
import type { User } from "~/types/auth.js";
import { formatRole, roleMeta } from "~/utils/user";

const props = withDefaults(
    defineProps<{
        user: User;
        scrolled?: boolean;
        navTheme?: any;
        themeAware?: boolean;
        role?: string | null;
    }>(),
    {
        scrolled: true,
        navTheme: "light",
        themeAware: false,
        role: null,
    },
);

const roleLabel = computed(() => {
    const role = props.role?.trim();

    if (!role) return "";

    return roleMeta[role]?.label ?? formatRole(role);
});

const roleClass = computed(
    () =>
        roleMeta[props.role?.trim() ?? ""]?.class ||
        "bg-primary-50 text-primary-600 border-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:border-primary-500/20",
);

const loggingOut = ref(false);
const navigating = ref(false);

async function onMenuItem(item: any, close: () => void) {
    if (item.to) {
        close();
        return;
    }

    navigating.value = true;
    close();

    try {
        await handleMenuClick(item);
    } finally {
        navigating.value = false;
    }
}
const isDark = useIsDark();
const route = useRoute();

const profileFrom = computed(() => {
    if (route.path.startsWith("/app/branches/")) return "dashboard";
    if (route.path.startsWith("/app/owner/")) return "owner";
    if (route.path.startsWith("/portal/")) return "portal";
    return undefined;
});

const visibleMenuItems = computed(() =>
    profileMenuDropDownList
        .filter((item) => {
        if (
            item.types &&
            !item.types.some((type: string) => props.user[type as keyof User])
        ) {
            return false;
        }

        if (
            item.requires &&
            !item.requires.every((key: string) => props.user[key as keyof User])
        ) {
            return false;
        }

        if (
            item.requiresAny &&
            !item.requiresAny.some(
                (key: string) => props.user[key as keyof User],
            )
        ) {
            return false;
        }

        return true;
        })
        .map((item) => ({
            ...item,
            to:
                item.to === "/profile"
                    ? {
                          path: "/profile",
                          query: {
                              tab: "profile",
                              from: profileFrom.value,
                              branch:
                                  profileFrom.value === "dashboard"
                                      ? (route.params.uuid as string)
                                      : undefined,
                          },
                      }
                    : item.to,
        })),
);

const logout = async () => {
    loggingOut.value = true;

    try {
        await authService.logout();
    } catch (err: any) {
        console.error(err);
    } finally {
        // Clearing the user here unmounts this dropdown, and the transition
        // screen with it, exposing the page until the reload paints.
        localStorage.removeItem("auth");
        window.location.href = "/auth/signin";
    }
};
</script>
