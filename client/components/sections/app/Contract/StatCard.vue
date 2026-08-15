<template>
    <div class="rounded-2xl bg-light/40 p-4 flex flex-col gap-3 font-sans">
        <div class="flex items-center justify-between">
            <p class="text-xs font-medium text-muted">
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
                class="h-7 w-16 rounded-md bg-muted-light animate-pulse"
            />
            <p v-else class="text-2xl font-bold text-secondary leading-none">
                {{ value }}
            </p>

            <p class="text-xs text-muted mt-1.5">
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
    primary: { iconBg: "bg-primary-100", iconColor: "text-primary-600" },
    accent: { iconBg: "bg-accent-100", iconColor: "text-accent-600" },
    secondary: { iconBg: "bg-white", iconColor: "text-secondary" },
    muted: { iconBg: "bg-muted-light", iconColor: "text-muted-dark" },
    danger: { iconBg: "bg-danger/10", iconColor: "text-danger" },
};

const toneClasses = computed(() => toneMap[props.tone ?? "primary"]);
</script>
