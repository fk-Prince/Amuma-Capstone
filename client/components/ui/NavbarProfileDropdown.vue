<template>
    <ClientOnly>
        <BaseDropdownMenu align="right" width="w-56">
            <template #trigger="{ toggle, open }">
                <button
                    @click="toggle"
                    class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl transition-colors focus:outline-none"
                    :class="
                        scrolled || navTheme !== 'dark'
                            ? 'hover:bg-primary-50'
                            : 'hover:bg-light/10'
                    "
                >
                    <div class="relative">
                        <img
                            :src="user.avatar"
                            class="w-9 h-9 rounded-full border-2 border-white shadow-sm object-cover"
                            alt="Profile"
                        />
                        <span
                            class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border-2 border-white rounded-full"
                        />
                    </div>

                    <div
                        class="hidden md:flex flex-col items-start leading-tight"
                    >
                        <span
                            class="text-sm font-medium transition-colors duration-300"
                            :class="
                                scrolled || navTheme !== 'dark'
                                    ? 'text-secondary/80'
                                    : 'text-light'
                            "
                        >
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                        <span
                            class="text-xs transition-colors duration-300"
                            :class="
                                scrolled || navTheme !== 'dark'
                                    ? 'text-muted'
                                    : 'text-light/70'
                            "
                        >
                            View profile
                        </span>
                    </div>

                    <ChevronIcon
                        :isOpen="open"
                        class="block w-4 h-4 transition-colors duration-300"
                        :class="
                            scrolled || navTheme !== 'dark'
                                ? 'text-muted'
                                : 'text-light/80'
                        "
                    />
                </button>
            </template>

            <template #default="{ close }">
                <div
                    class="bg-white text-gray-800 rounded-2xl border border-gray-100 shadow-lg overflow-hidden dark:bg-[#131C2E] dark:text-gray-200 dark:border-white/10"
                >
                    <div
                        class="px-4 py-3 border-b border-gray-50 flex items-center gap-3 dark:border-white/10"
                    >
                        <div class="relative shrink-0">
                            <img
                                :src="user.avatar"
                                class="w-9 h-9 rounded-full border-2 border-white shadow-sm object-cover dark:border-[#131C2E]"
                                alt="Profile"
                            />
                            <span
                                class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border border-white rounded-full dark:border-[#131C2E]"
                            />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <p
                                class="text-sm font-medium text-gray-800 truncate dark:text-white"
                            >
                                {{ user.first_name }} {{ user.last_name }}
                            </p>
                            <p
                                class="text-xs text-gray-400 truncate dark:text-gray-400"
                            >
                                {{ roleLabel }}
                            </p>
                        </div>
                    </div>

                    <div class="px-4 py-3 border-b border-gray-50 dark:border-white/10">
                        <p
                            class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                        >
                            Appearance
                        </p>

                        <div
                            class="inline-flex w-full items-center gap-1 rounded-full bg-slate-100 p-1 dark:bg-white/10"
                        >
                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition"
                                :class="
                                    !isDark
                                        ? 'bg-white text-slate-900 shadow-sm'
                                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                                "
                                @click="setTheme(false)"
                            >
                                <Sun class="h-3.5 w-3.5" />
                                Light
                            </button>

                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition"
                                :class="
                                    isDark
                                        ? 'bg-secondary text-white shadow-sm'
                                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                                "
                                @click="setTheme(true)"
                            >
                                <Moon class="h-3.5 w-3.5" />
                                Dark
                            </button>
                        </div>
                    </div>

                    <div class="py-1">
                        <DropdownItem
                            v-for="item in visibleMenuItems"
                            :key="item.label"
                            :icon="item.icon"
                            :label="item.label"
                            @click="
                                async () => {
                                    await handleMenuClick(item);
                                    close();
                                }
                            "
                        />
                    </div>

                    <div class="py-1 border-t border-gray-50 dark:border-white/10">
                        <button
                            type="button"
                            :disabled="loggingOut"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-500 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-rose-500/10"
                            @click="
                                () => {
                                    logout();
                                    close();
                                }
                            "
                        >
                            <LoaderCircle
                                v-if="loggingOut"
                                class="w-4 h-4 animate-spin"
                            />
                            <LogOut v-else class="w-4 h-4" />
                            Log out
                        </button>
                    </div>
                </div>
            </template>
        </BaseDropdownMenu>
    </ClientOnly>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { LogOut, LoaderCircle, Sun, Moon } from "lucide-vue-next";
import BaseDropdownMenu from "../ui/BaseDropdownMenu.vue";
import DropdownItem from "../ui/DropdownItem.vue";
import ChevronIcon from "../icons/dropdown.vue";
import { useIsDark, setTheme } from "~/composables/useTheme";
import { useLogout } from "~/composables/useLogout";
import {
    handleMenuClick,
    profileMenuDropDownList,
} from "~/config/profileMenu.js";
import type { User } from "~/types/auth.js";

const props = withDefaults(
    defineProps<{
        user: User;
        scrolled?: boolean;
        navTheme?: any;
        themeAware?: boolean;
    }>(),
    {
        scrolled: true,
        navTheme: "light",
        themeAware: false,
    },
);

const { logout, loading: loggingOut } = useLogout();
const isDark = useIsDark();

const roleLabel = computed(() => {
    if (props.user.isSystemOwner) return "System Owner";
    if (props.user.isClient) return "Family Member";
    if (props.user.isEmployee) return "Staff";
    return props.user.email;
});

const visibleMenuItems = computed(() =>
    profileMenuDropDownList.filter((item) => {
        if (!item.types) return true;
        return item.types.some((type) => props.user[type as keyof User]);
    }),
);
</script>