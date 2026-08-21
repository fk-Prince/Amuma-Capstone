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
                    @click="emit('close')"
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
                    class="fixed left-0 top-0 z-[70] flex h-full w-64 flex-col bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)]"
                >
                    <div
                        class="flex h-[72px] items-center justify-between border-b border-primary-100/80 px-5"
                    >
                        <NuxtLink to="/owner/dashboard" @click="emit('close')">
                            <img
                                v-if="logo"
                                :src="logo"
                                alt="Logo"
                                class="w-[130px] object-contain"
                            />

                            <span
                                v-else
                                class="text-lg font-bold text-primary-700"
                            >
                                Admin
                            </span>
                        </NuxtLink>

                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-primary-500 transition-colors duration-200 hover:bg-primary-50 hover:text-primary-700"
                            @click="emit('close')"
                        >
                            <X class="h-[18px] w-[18px]" />
                        </button>
                    </div>

                    <nav class="scroll-left flex-1 overflow-y-auto">
                        <div
                            class="scroll-left-inner flex flex-col gap-1 px-3 py-4"
                        >
                            <div
                                class="mb-2 px-2 text-[10px] font-semibold uppercase tracking-wider text-primary-400"
                            >
                                Platform
                            </div>

                            <NuxtLink
                                v-for="item in privateMenu"
                                :key="item.to"
                                :to="item.to"
                                :class="navClass(item.to)"
                                @click="emit('close')"
                            >
                                <span :class="iconWrapClass(item.to)">
                                    <component
                                        :is="item.icon"
                                        class="h-4 w-4"
                                    />
                                </span>

                                <span class="truncate">
                                    {{ item.label }}
                                </span>

                                <ChevronRight
                                    v-if="isActive(item.to)"
                                    class="ml-auto h-3.5 w-3.5 opacity-70"
                                />
                            </NuxtLink>
                        </div>
                    </nav>
                </aside>
            </Transition>
        </Teleport>
    </ClientOnly>

    <aside
        v-if="isDesktop"
        :class="[
            'relative hidden h-full shrink-0 flex-col border-r border-primary-100/70 bg-white shadow-[0_0_40px_rgba(10,40,87,0.08)] transition-[width] duration-300 ease-in-out lg:flex',
            desktopCollapsed ? 'w-[76px]' : 'w-60',
        ]"
    >
        <!-- Collapse Button -->
        <button
            type="button"
            :aria-label="
                desktopCollapsed ? 'Expand sidebar' : 'Collapse sidebar'
            "
            class="absolute -right-3 top-7 z-10 flex h-7 w-7 items-center justify-center rounded-full border border-primary-200 bg-white text-primary-600 shadow-md transition-colors duration-200 hover:border-primary hover:bg-primary hover:text-white"
            @click="desktopCollapsed = !desktopCollapsed"
        >
            <component
                :is="desktopCollapsed ? ChevronRight : ChevronLeft"
                class="h-4 w-4"
            />
        </button>

        <div class="scroll-left flex-1 overflow-y-auto overflow-x-hidden py-4">
            <div class="scroll-left-inner flex flex-col gap-1 px-2.5">
                <div
                    v-if="!desktopCollapsed"
                    class="mb-2 px-2 text-[10px] font-semibold uppercase tracking-wider text-primary-400"
                >
                    Platform
                </div>

                <NuxtLink
                    v-for="item in privateMenu"
                    :key="item.to"
                    :to="item.to"
                    :class="navClass(item.to, true)"
                    :title="desktopCollapsed ? item.label : undefined"
                >
                    <span :class="iconWrapClass(item.to)">
                        <component :is="item.icon" class="h-4 w-4" />
                    </span>

                    <span v-if="!desktopCollapsed" class="truncate">
                        {{ item.label }}
                    </span>
                </NuxtLink>
            </div>
        </div>

        <!-- <div class="border-t border-primary-100/70 p-2.5">
            <NuxtLink
                to="/owner/settings"
                :class="navClass('/owner/settings', true)"
                :title="desktopCollapsed ? 'Settings' : undefined"
            >
                <span :class="iconWrapClass('/owner/settings')">
                    <Settings class="h-4 w-4" />
                </span>

                <span v-if="!desktopCollapsed" class="truncate">
                    Settings
                </span>
            </NuxtLink>
        </div> -->
    </aside>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRoute } from "vue-router";
import { privateMenu } from "~/config/privateMenu";

import { ChevronLeft, ChevronRight, X } from "lucide-vue-next";

const props = defineProps<{
    open: boolean;
    logo?: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const route = useRoute();

const desktopCollapsed = ref(false);
const isDesktop = ref(false);

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
    if (to === "/owner/dashboard") {
        return route.path === to;
    }

    return route.path === to || route.path.startsWith(`${to}/`);
}

function navClass(to: string, desktop = false) {
    return [
        "relative flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium transition-all duration-200",
        desktop && desktopCollapsed.value ? "justify-center px-0" : "",
        isActive(to)
            ? "bg-primary text-white shadow-md shadow-primary-500/30"
            : "text-primary-700 hover:bg-primary-50",
    ];
}

function iconWrapClass(to: string) {
    return [
        "flex h-7 w-7 shrink-0 items-center justify-center rounded-md transition-colors duration-200",
        isActive(to)
            ? "bg-white/20 text-white"
            : "bg-primary-50 text-primary-500",
    ];
}
</script>

<style scoped>
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
