<template>
    <div
        v-if="open"
        class="fixed inset-0 z-30 bg-gray-900/40 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
        @click="emit('close')"
    />

    <aside
        class="fixed inset-y-3 left-3 z-40 flex w-64 shrink-0 flex-col overflow-hidden rounded-3xl bg-white dark:bg-secondary-900 shadow-[0_20px_45px_-18px_rgba(15,23,42,0.18)] ring-1 ring-black/[0.04] dark:ring-white/[0.06] transition-transform duration-200 ease-in-out lg:static lg:inset-y-0 lg:left-0 lg:my-4 lg:ml-4 lg:h-[calc(100dvh-2rem)] lg:translate-x-0"
        :class="open ? 'translate-x-0' : '-translate-x-[120%] lg:translate-x-0'"
    >
        <div
            class="flex shrink-0 items-center justify-between px-[19px] pt-4 pb-3"
        >
            <NuxtLink
                to="/app/owner/dashboard"
                class="flex items-center gap-2.5"
                @click="emit('close')"
            >
                <img
                    :src="logo"
                    alt="AMUMA"
                    class="w-9 h-9 rounded-lg object-contain shrink-0"
                />
                <p
                    class="font-extrabold text-primary-500 text-2xl tracking-wide leading-tight whitespace-nowrap [text-shadow:0_4px_8px_rgb(49_130_237_/_35%)]"
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
            <NuxtLink
                v-for="item in privateMenu"
                :key="item.to"
                :to="item.to"
                class="w-full flex items-center gap-3 px-[13px] py-3 rounded-xl text-sm font-medium transition-colors"
                :class="
                    isActive(item.to)
                        ? 'bg-primary-500 text-white shadow-sm'
                        : 'text-gray-400 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-gray-600 dark:hover:text-gray-200'
                "
                @click="emit('close')"
            >
                <component
                    :is="item.icon"
                    class="w-[18px] h-[18px] shrink-0"
                />
                <span class="flex-1 text-left whitespace-nowrap overflow-hidden">{{
                    item.label
                }}</span>
            </NuxtLink>
        </nav>
    </aside>
</template>

<script setup lang="ts">
import { watch } from "vue";
import { useRoute } from "vue-router";
import { X } from "lucide-vue-next";
import logo from "assets/logo/logo.png";
import { privateMenu } from "~/config/privateMenu";

defineProps<{
    open?: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const route = useRoute();

watch(
    () => route.path,
    () => emit("close"),
);

function isActive(to: string) {
    return route.path === to || route.path.startsWith(`${to}/`);
}
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
