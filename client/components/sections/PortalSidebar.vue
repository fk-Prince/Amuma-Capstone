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
                leave-active-class="transition-opacity duration-300 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open && !isDesktop"
                    class="fixed inset-0 z-[60] bg-black/50 backdrop-blur-sm"
                    @click="$emit('close')"
                />
            </Transition>

            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-300 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <aside
                    v-if="open && !isDesktop"
                    class="fixed left-0 top-0 h-full w-72 bg-white shadow-2xl z-[70] flex flex-col lg:hidden"
                >
                    <div
                        class="flex items-center justify-between px-5 h-[90px] border-b"
                    >
                        <NuxtLink to="/" @click="$emit('close')">
                            <img :src="logo" class="w-[150px] object-contain" />
                        </NuxtLink>

                        <button
                            @click="$emit('close')"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"
                        >
                            ✕
                        </button>
                    </div>

                    <nav
                        class="flex flex-col px-4 py-6 gap-1 flex-1 overflow-y-auto"
                    >
                        <NuxtLink
                            v-for="item in navItems"
                            :key="item.to"
                            :to="item.to"
                            :class="navClass(item.to)"
                            @click="$emit('close')"
                        >
                            <component
                                :is="item.icon"
                                class="w-5 h-5 shrink-0"
                            />
                            <span class="truncate">{{ item.label }}</span>
                        </NuxtLink>
                    </nav>
                </aside>
            </Transition>
        </Teleport>
    </ClientOnly>

    <aside
        v-if="isDesktop"
        :class="[
            'hidden lg:flex h-full relative bg-white shadow-2xl flex-col shrink-0 transition-all duration-300',
            desktopCollapsed ? 'w-20' : 'w-72',
        ]"
    >
        <button
            @click="desktopCollapsed = !desktopCollapsed"
            class="absolute bg-white border border-primary w-8 h-8 flex items-center justify-center rounded-full mx-auto"
            :class="
                desktopCollapsed
                    ? '-right-[21%] top-[1%] pl-0.5'
                    : '-right-[6%] top-[1%] pr-0.5'
            "
        >
            <component
                :is="desktopCollapsed ? ChevronRight : ChevronLeft"
                class="text-primary"
            />
        </button>

        <div class="flex flex-col px-4 py-6 gap-1 flex-1 overflow-y-auto">
            <NuxtLink
                v-for="item in navItems"
                :key="item.to"
                :to="item.to"
                :class="navClass(item.to)"
                :title="desktopCollapsed ? item.label : undefined"
            >
                <component :is="item.icon" class="w-5 h-5 shrink-0" />
                <span v-if="!desktopCollapsed" class="truncate">{{
                    item.label
                }}</span>
            </NuxtLink>
        </div>
    </aside>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from "vue";
import { useRoute } from "vue-router";
import {
    User,
    CreditCard,
    ChevronLeft,
    ChevronRight,
    Calendar,
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

function navClass(to: string) {
    return [
        "px-3 py-1.5 rounded-lg transition flex gap-2 items-center",
        desktopCollapsed.value ? "lg:justify-center" : "",
        isActive(to)
            ? "bg-primary hover:bg-primary/70 font-medium text-white hover:text-black stroke-white"
            : "text-gray-700 hover:bg-gray-200 stroke-black",
    ];
}
</script>
