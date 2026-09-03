<template>
    <div class="rounded-2xl bg-light/40 p-4 flex flex-col gap-3 font-sans dark:bg-white/5">
        <div class="flex items-center justify-between">
            <p class="text-xs font-medium text-muted dark:text-gray-400">
                {{ title }}
            </p>

            <div
                v-if="icon"
                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                :class="toneClasses.iconBg"
            >
                <component
                    :is="icon"
                    class="w-4 h-4"
                    :class="toneClasses.iconColor"
                />
            </div>
        </div>

        <div>
            <div
                v-if="loading"
                class="h-7 w-16 rounded-md bg-muted-light animate-pulse dark:bg-white/10"
            />
            <p v-else class="text-2xl font-bold text-secondary leading-none dark:text-white">
                {{ value }}
            </p>

            <p class="text-xs text-muted mt-1.5 dark:text-gray-400">
                {{ subtitle }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, type Component } from "vue";

const props = defineProps<{
    title: string;
    value: string;
    subtitle?: string;
    icon?: Component;
    tone?: "primary" | "accent" | "secondary" | "muted" | "danger";
    loading?: boolean;
}>();

const toneMap = {
    primary: { iconBg: "bg-primary-100 dark:bg-primary-500/15", iconColor: "text-primary-600 dark:text-primary-300" },
    accent: { iconBg: "bg-accent-100 dark:bg-accent-500/15", iconColor: "text-accent-600 dark:text-accent-300" },
    secondary: { iconBg: "bg-white dark:bg-secondary", iconColor: "text-secondary dark:text-white" },
    muted: { iconBg: "bg-muted-light dark:bg-white/10", iconColor: "text-muted-dark dark:text-gray-300" },
    danger: { iconBg: "bg-danger/10", iconColor: "text-danger" },
};

const toneClasses = computed(() => toneMap[props.tone ?? "primary"]);
</script>
