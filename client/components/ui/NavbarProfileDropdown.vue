<template>
    <ClientOnly>
        <BaseDropdownMenu align="right" width="w-56">
            <template #trigger="{ toggle, open }">
                <button
                    @click="toggle"
                    class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl transition-colors focus:outline-none"
                    :class="[
                        scrolled || navTheme !== 'dark'
                            ? 'hover:bg-primary-50'
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
                            class="text-xs transition-colors duration-300"
                            :class="[
                                scrolled || navTheme !== 'dark'
                                    ? 'text-muted'
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
                                ? 'text-muted'
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
                        class="px-4 py-3 border-b border-gray-50 dark:border-white/10 flex items-center gap-3"
                    >
                        <div class="relative shrink-0">
                            <img
                                :src="user.avatar"
                                class="w-9 h-9 rounded-full border-2 border-white shadow-sm object-cover"
                                alt="Profile"
                            />
                            <span
                                class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border border-white rounded-full"
                            />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <p
                                class="text-sm font-medium text-gray-800 dark:text-white truncate"
                            >
                                {{ user.first_name }} {{ user.last_name }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-400 truncate">
                                {{ user.email }}
                            </p>
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

                    <div
                        class="mx-4 my-1 flex items-center justify-between rounded-full bg-gray-50 dark:bg-white/5 p-1"
                    >
                        <button
                            type="button"
                            class="flex-1 flex items-center justify-center gap-1.5 rounded-full py-1.5 text-xs font-medium transition-colors"
                            :class="
                                !isDark
                                    ? 'bg-white text-gray-800 shadow-sm'
                                    : 'text-gray-400 hover:text-gray-600'
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
                                    : 'text-gray-400 hover:text-gray-600'
                            "
                            @click="setTheme(true)"
                        >
                            <Moon class="w-3.5 h-3.5" />
                            Dark
                        </button>
                    </div>

                    <div class="py-1 border-t border-gray-50 dark:border-white/10">
                        <button
                            type="button"
                            :disabled="loggingOut"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-500 transition-colors hover:bg-rose-50 dark:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-50"
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
import { computed, onMounted, ref } from "vue";
import { LogOut, LoaderCircle, Sun, Moon } from "lucide-vue-next";
import BaseDropdownMenu from "../ui/BaseDropdownMenu.vue";
import DropdownItem from "../ui/DropdownItem.vue";
import ChevronIcon from "../icons/dropdown.vue";
import { authService } from "~/api/auth/AuthService.js";
import { resetAuth } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
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

const { success, error } = useToast();
const loggingOut = ref(false);
const isDark = ref(false);

const THEME_KEY = "theme";

const setTheme = (dark: boolean) => {
    isDark.value = dark;
    document.documentElement.classList.toggle("dark", dark);
    localStorage.setItem(THEME_KEY, dark ? "dark" : "light");
};

onMounted(() => {
    const stored = localStorage.getItem(THEME_KEY);

    setTheme(stored ? stored === "dark" : true);
});

const visibleMenuItems = computed(() =>
    profileMenuDropDownList.filter((item) => {
        if (!item.types) return true;
        return item.types.some((type) => props.user[type as keyof User]);
    }),
);

const logout = async () => {
    loggingOut.value = true;

    try {
        const res = await authService.logout();
        success(res.message ?? "Logged out successfully.");
    } catch (err: any) {
        console.error(err);
    } finally {
        resetAuth();
        loggingOut.value = false;
        window.location.href = "/auth/signin";
    }
};
</script>
