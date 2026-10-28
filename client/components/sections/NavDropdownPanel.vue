<script setup lang="ts">
import type { Component } from "vue";
import { useRoute } from "vue-router";

defineProps<{
    open: boolean;
    items: { label: string; to: string; icon?: Component; description?: string }[];
}>();

const emit = defineEmits<{ select: [] }>();

const route = useRoute();

const linkClass = (to: string) =>
    route.path === to
        ? "bg-primary/10 dark:bg-primary/15"
        : "hover:bg-primary/5 dark:hover:bg-white/5";

const iconClass = (to: string) =>
    route.path === to
        ? "bg-primary text-white"
        : "bg-primary-50 text-primary group-hover/item:bg-primary group-hover/item:text-white dark:bg-white/10 dark:text-primary-300";

const titleClass = (to: string) =>
    route.path === to
        ? "text-primary dark:text-primary-300"
        : "text-secondary dark:text-white";
</script>

<template>
    <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 -translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-1"
    >
        <div v-if="open" class="absolute left-1/2 top-full -translate-x-1/2 pt-3">
            <div
                role="menu"
                class="grid w-[680px] max-w-[calc(100vw-2rem)] grid-cols-2 gap-1 rounded-2xl border border-muted-light bg-white p-2 shadow-[0_16px_40px_-12px_rgba(15,23,42,0.25)] dark:border-white/10 dark:bg-secondary"
            >
                <NuxtLink
                    v-for="c in items"
                    :key="c.to"
                    :to="c.to"
                    role="menuitem"
                    class="group/item flex items-start gap-3 rounded-xl p-3 transition-colors duration-200"
                    :class="linkClass(c.to)"
                    @click="emit('select')"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors duration-200"
                        :class="iconClass(c.to)"
                    >
                        <component :is="c.icon" v-if="c.icon" class="h-4 w-4" />
                    </span>

                    <span class="flex min-w-0 flex-col">
                        <span
                            class="text-sm font-semibold"
                            :class="titleClass(c.to)"
                        >
                            {{ c.label }}
                        </span>
                        <span
                            v-if="c.description"
                            class="mt-0.5 text-xs leading-5 text-muted dark:text-gray-400"
                        >
                            {{ c.description }}
                        </span>
                    </span>
                </NuxtLink>
            </div>
        </div>
    </Transition>
</template>
