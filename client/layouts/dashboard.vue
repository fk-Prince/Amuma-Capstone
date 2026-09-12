﻿<template>
    <div
        v-if="open"
        class="fixed inset-0 z-30 bg-gray-900/40 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
        @click="emit('close')"
    />

    <aside
        class="group fixed inset-y-3 z-40 flex w-64 shrink-0 flex-col overflow-hidden rounded-3xl bg-white dark:bg-secondary-900 shadow-[0_20px_45px_-18px_rgba(15,23,42,0.18)] ring-1 ring-black/[0.04] dark:ring-white/[0.06] transition-[transform,width] duration-200 ease-in-out lg:static lg:inset-y-0 lg:my-4 lg:h-[calc(100dvh-2rem)] lg:w-[84px] lg:translate-x-0 lg:hover:w-64"
        :class="[
            sidebarPosition === 'right'
                ? 'right-3 lg:right-auto lg:order-last'
                : 'left-3 lg:left-auto',
            open
                ? 'translate-x-0'
                : sidebarPosition === 'right'
                  ? 'translate-x-[120%] lg:translate-x-0'
                  : '-translate-x-[120%] lg:translate-x-0',
        ]"
    >
        <div
            class="flex shrink-0 items-center justify-between px-[19px] pt-4 pb-3"
        >
            <NuxtLink
                :to="homeLink"
                class="flex items-center gap-2.5"
                @click="emit('close')"
            >
                <img
                    :src="logo"
                    alt="AMUMA"
                    class="w-9 h-9 rounded-lg object-contain shrink-0"
                />
                <p
                    class="font-extrabold text-primary-500 text-2xl tracking-wide leading-tight whitespace-nowrap [text-shadow:0_4px_8px_rgb(49_130_237_/_35%)] transition-opacity duration-150 delay-75 lg:opacity-0 lg:group-hover:opacity-100"
                >
                    AMUMA
                </p>
            </NuxtLink>

            <button
                type="button"
                class="rounded-lg p-1.5 text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-gray-600 dark:hover:text-gray-300 lg:hidden"
                aria-label="Close navigation"
                @click="emit('close')"
            >
                <X class="h-5 w-5" />
            </button>
        </div>

        <nav
            class="sidebar-scroll flex-1 px-3.5 space-y-1.5 mt-5 overflow-y-auto overflow-x-hidden"
        >
            <template v-for="(section, i) in groupedMenus" :key="i">
                <p
                    v-if="section.label"
                    class="px-[13px] text-[10px] font-semibold uppercase tracking-wider text-gray-300 dark:text-gray-500 whitespace-nowrap transition-opacity duration-150 delay-75 lg:opacity-0 lg:group-hover:opacity-100"
                    :class="i === 0 ? 'pb-1' : 'pt-3 pb-1'"
                >
                    {{ section.label }}
                </p>

                <NuxtLink
                    v-for="item in section.items"
                    :key="item.to"
                    :to="item.to"
                    class="w-full flex items-center gap-3 lg:justify-center lg:gap-0 lg:px-0 lg:group-hover:justify-start lg:group-hover:gap-3 lg:group-hover:px-[13px] px-[13px] py-3 rounded-xl text-sm font-medium transition-colors"
                    :class="
                        isActive(item.to)
                            ? 'bg-primary-500 text-white shadow-sm'
                            : 'text-gray-400 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-gray-600 dark:hover:text-gray-200'
                    "
                    @click="emit('close')"
                >
                    <component
                        :is="item.icon"
                        v-if="item.icon"
                        class="w-[18px] h-[18px] shrink-0"
                    />
                    <span
                        class="flex-1 lg:flex-none lg:w-0 lg:group-hover:flex-1 lg:group-hover:w-auto text-left whitespace-nowrap overflow-hidden transition-opacity duration-150 delay-75 lg:opacity-0 lg:group-hover:opacity-100"
                        >{{ item.label }}</span
                    >
                </NuxtLink>
            </template>
        </nav>

        <div class="px-3.5 pb-4 pt-3 shrink-0 border-t border-gray-50 dark:border-white/10">
            <button
                type="button"
                :disabled="loadingLogout"
                class="w-full flex items-center gap-3 lg:justify-center lg:gap-0 lg:px-0 lg:group-hover:justify-start lg:group-hover:gap-3 lg:group-hover:px-[13px] px-[13px] py-3 rounded-xl text-sm font-medium text-gray-400 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-gray-600 dark:hover:text-gray-200 disabled:opacity-50"
                @click="logout"
            >
                <LogOut class="w-[18px] h-[18px] shrink-0" />
                <span
                    class="whitespace-nowrap transition-opacity duration-150 delay-75 lg:opacity-0 lg:group-hover:opacity-100"
                >
                    {{ loadingLogout ? "Logging out..." : "Logout" }}
                </span>
            </button>
        </div>
    </aside>
</template>

<script setup lang="ts">
import logo from "assets/logo/logo.png";
import { computed } from "vue";
import { useRoute } from "vue-router";
import { LogOut, X } from "lucide-vue-next";
import { useLogout } from "~/composables/useLogout";
import { useSidebarPosition } from "~/composables/useSidebarPosition";

interface MenuItem {
    label: string;
    to: string;
    icon?: any;
    group?: string;
}

const props = withDefaults(
    defineProps<{
        open?: boolean;
        menus?: MenuItem[];
        homeLink?: string;
    }>(),
    {
        menus: () => [],
        homeLink: "/",
    },
);

const groupedMenus = computed(() => {
    const groups: { label: string | null; items: MenuItem[] }[] = [];

    for (const item of props.menus) {
        const label = item.group ?? null;
        const last = groups[groups.length - 1];

        if (last && last.label === label) {
            last.items.push(item);
        } else {
            groups.push({ label, items: [item] });
        }
    }

    return groups;
});

const emit = defineEmits<{
    close: [];
}>();

const route = useRoute();
const sidebarPosition = useSidebarPosition();

function isActive(to: string) {
    return route.path === to || route.path.startsWith(`${to}/`);
}

const { logout, loading: loadingLogout } = useLogout();
</script>

<style scoped>
.sidebar-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.6) transparent;
}
.sidebar-scroll::-webkit-scrollbar {
    width: 5px;
}
.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar-scroll::-webkit-scrollbar-thumb {
    background-color: rgba(148, 163, 184, 0.6);
    border-radius: 999px;
}
</style>