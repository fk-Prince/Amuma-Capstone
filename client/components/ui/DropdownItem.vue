<template>
    <button
        type="button"
        @click="emit('click')"
        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm transition-colors"
        :class="[
            danger
                ? 'text-rose-500 hover:bg-rose-50 dark:hover:bg-white/10 dark:text-rose-300'
                : 'text-gray-600 dark:text-white/80 hover:bg-gray-50 dark:hover:bg-white/10',
            disabled
                ? 'opacity-40 cursor-not-allowed pointer-events-none'
                : 'cursor-pointer',
        ]"
    >
        <component :is="resolvedIcon" v-if="resolvedIcon" class="w-4 h-4 text-gray-400 dark:text-white/40" />

        <span class="flex-1 text-left">{{ label }}</span>

        <span
            v-if="badge"
            class="text-[11px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400"
        >
            {{ badge }}
        </span>
    </button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import {
    UserCheck,
    LayoutDashboard,
    Users,
    CreditCard,
    Settings,
} from "lucide-vue-next";

const props = defineProps<{
    label: string;
    icon?: string;
    badge?: string;
    danger?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits<{ (e: "click"): void }>();

const ICON_MAP: Record<string, any> = {
    "My profile": UserCheck,
    Dashboard: LayoutDashboard,
    "Family Portal": Users,
    "Subscription Management": CreditCard,
    Settings: Settings,
};

const resolvedIcon = computed(
    () => ICON_MAP[props.label] ?? ICON_MAP[props.icon ?? ""] ?? UserCheck,
);
</script>
