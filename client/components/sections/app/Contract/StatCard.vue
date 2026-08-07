<template>
    <div
        class="relative overflow-hidden group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
        :class="colors.hover"
    >
        <div
            class="absolute -top-10 -right-10 h-28 w-28 rounded-full blur-2xl"
            :class="colors.glow"
        />

        <div
            class="absolute right-0 top-5 bottom-5 w-1 rounded-l-full opacity-40"
            :class="colors.side"
        />

        <div class="relative">
            <div class="flex items-center justify-between">
                <div
                    class="h-10 w-10 rounded-xl flex items-center justify-center"
                    :class="colors.bg"
                >
                    <component
                        :is="icon"
                        class="h-5 w-5"
                        :class="colors.text"
                    />
                </div>

                <span
                    v-if="badge"
                    class="px-2.5 py-1 rounded-full text-xs font-semibold"
                    :class="colors.badge"
                >
                    {{ badge }}
                </span>
            </div>

            <p
                class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
            >
                {{ title }}
            </p>

            <p class="mt-1 text-3xl font-bold text-slate-800 tabular-nums">
                {{ loading ? "—" : value }}
            </p>

            <div v-if="progress !== undefined" class="mt-4">
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-700"
                        :class="colors.progress"
                        :style="{
                            width: `${Math.min(progress, 100)}%`,
                        }"
                    />
                </div>

                <div class="mt-2 flex justify-between text-xs">
                    <span class="text-slate-400">
                        {{ progressLabel }}
                    </span>

                    <span class="font-medium text-slate-600">
                        {{ progressValue }}
                    </span>
                </div>
            </div>

            <div
                v-else-if="footer"
                class="mt-3 flex items-center gap-2 text-xs"
                :class="colors.text"
            >
                <span
                    v-if="live"
                    class="h-1.5 w-1.5 rounded-full animate-pulse"
                    :class="colors.dot"
                />

                {{ footer }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Users, Building2, HeartHandshake, FileText } from "lucide-vue-next";

const props = defineProps<{
    title: string;
    value: string | number;
    color: "teal" | "blue" | "purple" | "orange" | "green" | "rose";
    loading?: boolean;
    badge?: string;
    progress?: number;
    progressLabel?: string;
    progressValue?: string;
    footer?: string;
    live?: boolean;
}>();

const icon = {
    teal: HeartHandshake,
    blue: Users,
    purple: Building2,
    orange: FileText,
    green: Users,
    rose: FileText,
}[props.color];

const colors: any = {
    teal: {
        bg: "bg-primary-50",
        text: "text-primary",
        glow: "bg-primary-100/40",
        badge: "bg-primary-50 text-primary",
        side: "bg-primary",
        progress: "bg-gradient-to-r from-primary to-primary-400",
        dot: "bg-primary",
        hover: "hover:border-primary-200",
    },

    blue: {
        bg: "bg-blue-50",
        text: "text-blue-500",
        glow: "bg-blue-100/40",
        badge: "bg-blue-50 text-blue-500",
        side: "bg-blue-500",
        progress: "bg-blue-500",
        dot: "bg-blue-500",
        hover: "hover:border-blue-200",
    },

    purple: {
        bg: "bg-purple-50",
        text: "text-purple-500",
        glow: "bg-purple-100/40",
        badge: "bg-purple-50 text-purple-500",
        side: "bg-purple-500",
        progress: "bg-purple-500",
        dot: "bg-purple-500",
        hover: "hover:border-purple-200",
    },

    orange: {
        bg: "bg-orange-50",
        text: "text-orange-500",
        glow: "bg-orange-100/40",
        badge: "bg-orange-50 text-orange-500",
        side: "bg-orange-500",
        progress: "bg-orange-500",
        dot: "bg-orange-500",
        hover: "hover:border-orange-200",
    },

    green: {
        bg: "bg-emerald-50",
        text: "text-emerald-600",
        glow: "bg-emerald-100/50",
        badge: "bg-emerald-50 text-emerald-600",
        side: "bg-emerald-500",
        progress: "bg-emerald-500",
        dot: "bg-emerald-500",
        hover: "hover:border-emerald-200",
    },

    rose: {
        bg: "bg-rose-50",
        text: "text-rose-500",
        glow: "bg-rose-100/50",
        badge: "bg-rose-50 text-rose-500",
        side: "bg-rose-500",
        progress: "bg-rose-500",
        dot: "bg-rose-500",
        hover: "hover:border-rose-200",
    },
}[props.color];
</script>
