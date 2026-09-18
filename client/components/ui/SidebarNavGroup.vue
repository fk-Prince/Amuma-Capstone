<template>
    <div class="space-y-1.5">
        <button
            v-if="showLabel"
            type="button"
            class="flex w-full items-center justify-between gap-2 rounded-md px-[13px] py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-400 whitespace-nowrap transition-opacity duration-150 delay-75 hover:text-gray-600 lg:pointer-events-none lg:opacity-0 lg:group-hover:pointer-events-auto lg:group-hover:opacity-100 dark:text-gray-500 dark:hover:text-gray-300"
            :aria-expanded="!collapsed"
            @click="toggle"
        >
            {{ label }}
            <ChevronDown
                class="h-3 w-3 shrink-0 transition-transform duration-200"
                :class="collapsed ? '-rotate-90' : ''"
            />
        </button>

        <div
            class="space-y-1.5"
            :class="collapsed ? 'max-lg:hidden lg:group-hover:hidden' : ''"
        >
            <NuxtLink
                v-for="item in items"
                :key="item.to"
                :to="item.to"
                class="w-full flex items-center gap-3 lg:justify-center lg:gap-0 lg:px-0 lg:group-hover:justify-start lg:group-hover:gap-3 lg:group-hover:px-[13px] px-[13px] py-3 rounded-xl text-sm font-medium transition-colors"
                :class="
                    isActive(item.to)
                        ? 'bg-primary-500 text-white shadow-sm'
                        : 'text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:text-white/40 dark:hover:bg-white/5 dark:hover:text-white/80'
                "
                @click="emit('navigate')"
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
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { ChevronDown } from "lucide-vue-next";

const props = withDefaults(
    defineProps<{
        label: string;
        items: { label: string; to: string; icon?: any }[];
        storageKey: string;
        showLabel?: boolean;
    }>(),
    { showLabel: true },
);

const emit = defineEmits<{ navigate: [] }>();

const route = useRoute();
const collapsed = ref(false);

const key = () => `sidebar:${props.storageKey}:${props.label}`;

function isActive(to: string) {
    return route.path === to || route.path.startsWith(`${to}/`);
}

function toggle() {
    collapsed.value = !collapsed.value;

    try {
        localStorage.setItem(key(), collapsed.value ? "1" : "0");
    } catch {}
}

onMounted(() => {
    try {
        collapsed.value = localStorage.getItem(key()) === "1";
    } catch {}

    if (props.items.some((item) => isActive(item.to))) {
        collapsed.value = false;
    }
});

watch(
    () => route.path,
    () => {
        if (props.items.some((item) => isActive(item.to))) {
            collapsed.value = false;
        }
    },
);
</script>
