<template>
    <div
        v-if="open"
        class="fixed inset-0 z-30 bg-gray-900/40 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
        @click="emit('close')"
    />

    <aside
        class="group fixed inset-y-0 left-0 z-40 flex h-full w-64 shrink-0 flex-col overflow-hidden border-r border-gray-100 dark:border-white/10 bg-white dark:bg-secondary transition-transform duration-200 ease-in-out lg:static lg:z-20 lg:w-[76px] lg:translate-x-0 lg:transition-[width] lg:hover:w-64"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
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
                <div
                    class="whitespace-nowrap leading-tight transition-opacity duration-150 delay-75 lg:opacity-0 lg:group-hover:opacity-100"
                >
                    <p
                        class="font-extrabold text-primary-500 text-2xl tracking-wide [text-shadow:0_4px_8px_rgb(49_130_237_/_35%)]"
                    >
                        AMUMA
                    </p>
                </div>
            </NuxtLink>

            <button
                type="button"
                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:text-white/40 dark:hover:bg-white/10 dark:hover:text-white/80 lg:hidden"
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
                        : 'text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:text-white/40 dark:hover:bg-white/5 dark:hover:text-white/80'
                "
                @click="emit('close')"
            >
                <component
                    :is="item.icon"
                    class="w-[18px] h-[18px] shrink-0"
                />
                <span
                    class="flex-1 text-left whitespace-nowrap transition-opacity duration-150 delay-75 lg:opacity-0 lg:group-hover:opacity-100"
                    >{{ item.label }}</span
                >
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
    scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
}
.sidebar-scroll::-webkit-scrollbar {
    width: 4px;
}
.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar-scroll::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 999px;
}
</style>
