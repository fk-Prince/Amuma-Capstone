<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { Building2, CalendarCheck, MessageCircleQuestion, Search, X } from "lucide-vue-next";

const route = useRoute();
const open = ref(false);
const root = ref<HTMLElement | null>(null);

const links = [
    {
        icon: Search,
        label: "Find a care provider",
        hint: "Search agencies near you",
        to: "/booking/search",
    },
    {
        icon: CalendarCheck,
        label: "How booking works",
        hint: "From search to first visit",
        to: "/how-booking-works",
    },
    {
        icon: Building2,
        label: "I run a care agency",
        hint: "See what AMUMA does for you",
        to: "/for-agencies",
    },
    {
        icon: MessageCircleQuestion,
        label: "Read the FAQ",
        hint: "Quick answers",
        to: "/faq",
    },
];

function onPointerDown(event: PointerEvent) {
    if (open.value && root.value && !root.value.contains(event.target as Node)) {
        open.value = false;
    }
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") open.value = false;
}

onMounted(() => {
    document.addEventListener("pointerdown", onPointerDown);
    document.addEventListener("keydown", onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener("pointerdown", onPointerDown);
    document.removeEventListener("keydown", onKeydown);
});

watch(
    () => route.fullPath,
    () => (open.value = false),
);
</script>

<template>
    <div ref="root" class="fixed right-4 bottom-4 z-40 sm:right-6 sm:bottom-6">
        <Transition
            enter-active-class="motion-safe:animate-popIn"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                id="help-panel"
                role="dialog"
                aria-label="Help"
                class="absolute right-0 bottom-16 w-[300px] origin-bottom-right overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] dark:border-white/10 dark:bg-secondary-800"
            >
                <div class="bg-gradient-to-br from-primary to-blue-700 px-5 py-4">
                    <p class="text-base font-bold text-white">
                        How can we help?
                    </p>
                    <p class="text-xs text-white/75">
                        Pick where you'd like to go.
                    </p>
                </div>

                <nav class="p-2">
                    <NuxtLink
                        v-for="link in links"
                        :key="link.to"
                        :to="link.to"
                        class="group flex items-center gap-3 rounded-2xl p-3 transition-colors hover:bg-light dark:hover:bg-white/5"
                    >
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-light text-primary transition-colors group-hover:bg-primary group-hover:text-white dark:bg-primary/15"
                        >
                            <component :is="link.icon" class="h-5 w-5" />
                        </span>

                        <span class="flex flex-col leading-tight">
                            <span
                                class="text-sm font-semibold text-secondary dark:text-white"
                            >
                                {{ link.label }}
                            </span>
                            <span class="text-xs text-muted dark:text-gray-400">
                                {{ link.hint }}
                            </span>
                        </span>
                    </NuxtLink>
                </nav>
            </div>
        </Transition>

        <button
            type="button"
            :aria-expanded="open"
            aria-controls="help-panel"
            class="group flex h-[52px] items-center overflow-hidden rounded-full border border-white/30 bg-white/90 pl-[9px] shadow-[0_8px_24px_rgba(15,23,42,0.1)] backdrop-blur-xl transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_32px_rgba(15,23,42,0.14)] dark:border-white/10 dark:bg-secondary-800/90"
            :class="open ? 'w-[52px]' : 'w-[52px] hover:w-[145px]'"
            @click="open = !open"
        >
            <span
                class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-[0_4px_12px_rgba(37,99,235,0.25)]"
            >
                <X v-if="open" class="h-4 w-4" />
                <svg
                    v-else
                    width="15"
                    height="15"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="white"
                    stroke-width="2.5"
                >
                    <circle cx="12" cy="12" r="10" />
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            </span>

            <span
                class="ml-2 w-0 whitespace-nowrap text-sm font-medium text-secondary opacity-0 transition-all duration-300 group-hover:w-[80px] group-hover:opacity-100 dark:text-white"
                :class="open ? 'hidden' : ''"
            >
                Need help?
            </span>

            <span class="sr-only">{{ open ? "Close help" : "Open help" }}</span>
        </button>
    </div>
</template>