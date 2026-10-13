<template>
    <!-- Phones: 4 shortcuts + Menu. Tablets: 6 shortcuts + Menu. Hidden on desktop. -->
    <nav
        v-for="bar in barViews"
        :key="bar.max"
        v-show="items.length"
        class="fixed inset-x-3 bottom-[max(0.75rem,env(safe-area-inset-bottom))] z-20 mx-auto p-2 print:hidden"
        :class="bar.class"
        aria-label="Primary navigation"
    >
        <span
            class="absolute inset-0 rounded-[30px] bg-white/80 shadow-[0_20px_50px_-12px_rgba(15,23,42,0.35)] ring-1 ring-black/5 backdrop-blur-2xl dark:bg-secondary/80 dark:shadow-black/60 dark:ring-white/10"
            aria-hidden="true"
        />

        <div class="relative grid auto-cols-fr grid-flow-col">
            <!-- Sliding highlight: glides to whichever item is active -->
            <span
                class="mbn-ind pointer-events-none absolute inset-y-0 left-0"
                :style="{
                    width: `${100 / (bar.list.length + 1)}%`,
                    transform: `translateX(${Math.max(bar.active, 0) * 100}%)`,
                    opacity: bar.active >= 0 ? 1 : 0,
                }"
                aria-hidden="true"
            >
                <span
                    class="absolute inset-0 rounded-[22px] bg-gradient-to-b from-primary-400 to-primary-600 shadow-lg shadow-primary-500/40"
                />
            </span>

            <NuxtLink
                v-for="item in bar.list"
                :key="item.to"
                :to="item.to"
                class="group relative z-10 flex min-w-0 flex-col items-center justify-center gap-1 rounded-[22px] px-1 py-2.5 transition-colors duration-300 active:scale-95"
                :class="
                    isActive(item.to)
                        ? 'text-white'
                        : 'text-gray-500 hover:text-gray-700 dark:text-white/55 dark:hover:text-white/80'
                "
            >
                <component
                    :is="item.icon ?? Circle"
                    class="h-[22px] w-[22px] shrink-0 transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                    :class="isActive(item.to) ? '-translate-y-0.5 scale-110' : 'group-hover:scale-105'"
                    :stroke-width="isActive(item.to) ? 2.25 : 1.9"
                />

                <span
                    class="w-full truncate text-center text-[10.5px] leading-none transition-all duration-300"
                    :class="isActive(item.to) ? 'font-bold' : 'font-medium'"
                >
                    {{ item.label }}
                </span>
            </NuxtLink>

            <button
                type="button"
                class="group relative z-10 flex min-w-0 flex-col items-center justify-center gap-1 rounded-[22px] px-1 py-2.5 text-gray-500 transition-colors duration-300 hover:text-gray-700 active:scale-95 dark:text-white/55 dark:hover:text-white/80"
                @click="emit('more')"
            >
                <Menu
                    class="h-[22px] w-[22px] shrink-0 transition-transform duration-300 group-hover:scale-105"
                    :stroke-width="1.9"
                />
                <span class="w-full truncate text-center text-[10.5px] font-medium leading-none">
                    Menu
                </span>
            </button>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { Circle, Menu } from "lucide-vue-next";

interface NavItem {
    label: string;
    to: string;
    icon?: any;
}

const props = defineProps<{
    items: NavItem[];
}>();

const emit = defineEmits<{ more: [] }>();

const route = useRoute();

const bars = [
    { max: 4, class: "max-w-[26rem] md:hidden" },
    { max: 6, class: "hidden max-w-2xl md:block lg:hidden" },
];

function isActive(to: string) {
    return route.path === to || route.path.startsWith(`${to}/`);
}

// The first few items, but the page you are on is always one of them.
function pick(max: number) {
    const list = props.items.slice(0, max);
    const active = props.items.find((item) => isActive(item.to));

    if (active && !list.includes(active)) {
        list[list.length - 1] = active;
    }

    return list;
}

// Each bar with its visible items and the index of the active one (-1 if none).
const barViews = computed(() =>
    bars.map((bar) => {
        const list = pick(bar.max);

        return {
            max: bar.max,
            class: bar.class,
            list,
            active: list.findIndex((item) => isActive(item.to)),
        };
    }),
);

</script>

<style scoped>
/* Tapping another item slides the highlight across with a little overshoot */
.mbn-ind {
    transition:
        transform 0.5s cubic-bezier(0.34, 1.45, 0.64, 1),
        opacity 0.3s ease;
}

@media (prefers-reduced-motion: reduce) {
    .mbn-ind {
        transition: none;
    }
}
</style>