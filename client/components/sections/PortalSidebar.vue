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
                    class="fixed left-0 top-0 h-full w-64 bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)] z-[70] flex flex-col lg:hidden"
                >
                    <div
                        class="flex items-center justify-between px-5 h-[72px] border-b border-primary-100/80"
                    >
                        <NuxtLink to="/" @click="$emit('close')">
                            <img :src="logo" class="w-[130px] object-contain" />
                        </NuxtLink>

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
                            <NuxtLink
                                v-for="item in navItems"
                                :key="item.to"
                                :to="item.to"
                                :class="navClass(item.to)"
                                @click="$emit('close')"
                            >
                                <span :class="iconWrapClass(item.to)">
                                    <component
                                        :is="item.icon"
                                        class="w-4 h-4"
                                    />
                                </span>
                                <span class="truncate">{{ item.label }}</span>
                                <ChevronRight
                                    v-if="isActive(item.to)"
                                    class="w-3.5 h-3.5 ml-auto opacity-70"
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
            'hidden lg:flex h-full relative bg-white shadow-[0_0_40px_rgba(10,40,87,0.08)] flex-col shrink-0 transition-[width] duration-300 ease-in-out border-r border-primary-100/70',
            desktopCollapsed ? 'w-[76px]' : 'w-60',
        ]"
    >
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
                <NuxtLink
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    :class="navClass(item.to, true)"
                    :title="desktopCollapsed ? item.label : undefined"
                >
                    <span :class="iconWrapClass(item.to)">
                        <component :is="item.icon" class="w-4 h-4" />
                    </span>
                    <span v-if="!desktopCollapsed" class="truncate">{{
                        item.label
                    }}</span>
                </NuxtLink>
            </div>
        </div>
    </aside>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRoute } from "vue-router";
import {
    User,
    CreditCard,
    Calendar,
    ChevronLeft,
    ChevronRight,
    X,
} from "lucide-vue-next";

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

const navItems = [
    { label: "Patient", to: "/", icon: User },
    { label: "Booking", to: "/", icon: Calendar },
    { label: "Billing", to: "/", icon: CreditCard },
];

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
            : "text-primary-700 hover:bg-primary-50",
    ];
}

function iconWrapClass(to: string) {
    return [
        "w-7 h-7 rounded-md flex items-center justify-center shrink-0 transition-colors duration-200",
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
