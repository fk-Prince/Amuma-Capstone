<template>
    <div
        class="group relative overflow-hidden rounded-xl bg-gradient-to-br p-3 shadow-sm ring-1 transition-all hover:-translate-y-0.5 hover:shadow-md dark:border dark:border-white/10"
        :class="styles.card"
    >
        <div
            class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full blur-2xl"
            :class="styles.blob"
        />

        <div class="relative flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p
                    class="truncate text-[10px] font-semibold uppercase tracking-wider"
                    :class="styles.label"
                >
                    {{ label }}
                </p>
                <p
                    class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                >
                    {{ value }}
                </p>
            </div>

            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br text-white shadow-md"
                :class="styles.icon"
            >
                <component :is="icon" class="h-4 w-4" />
            </div>
        </div>

        <div
            v-if="chipText || note"
            class="relative mt-2 flex flex-wrap items-center gap-1.5"
        >
            <span
                v-if="chipText"
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                :class="styles.chip"
            >
                {{ chipText }}
            </span>
            <span
                v-if="note"
                class="text-[10px] font-medium text-slate-400 dark:text-gray-500"
            >
                {{ note }}
            </span>
        </div>

        <NuxtLink
            v-if="to && actionLabel"
            :to="to"
            class="group/link relative mt-2 inline-flex items-center gap-1 text-[10px] font-bold"
            :class="styles.action"
        >
            {{ actionLabel }}
            <span class="transition-transform group-hover/link:translate-x-0.5"
                >→</span
            >
        </NuxtLink>
    </div>
</template>

<script setup lang="ts">
import { computed, type Component } from "vue";

type Tone = "primary" | "accent" | "amber" | "rose";

const props = withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        icon: Component;
        tone?: Tone;
        chip?: string;
        hint?: string;
        note?: string;
        to?: string;
        actionLabel?: string;
    }>(),
    { tone: "primary" },
);

const chipText = computed(() => props.chip ?? props.hint);

const TONES: Record<
    Tone,
    {
        card: string;
        blob: string;
        label: string;
        icon: string;
        chip: string;
        action: string;
    }
> = {
    primary: {
        card: "from-primary-50 via-white to-white ring-primary-100/60 dark:from-primary-500/10 dark:via-secondary dark:to-secondary dark:ring-primary-500/20",
        blob: "bg-primary-200/30 dark:bg-primary-500/10",
        label: "text-primary-600/80 dark:text-primary-300/80",
        icon: "from-primary to-primary-700 shadow-primary-500/30",
        chip: "bg-primary-500/10 text-primary-700 dark:text-primary-300",
        action: "text-primary-700 hover:text-primary-900 dark:text-primary-300 dark:hover:text-primary-200",
    },
    accent: {
        card: "from-accent-50 via-white to-white ring-accent-100/60 dark:from-accent-500/10 dark:via-secondary dark:to-secondary dark:ring-accent-500/20",
        blob: "bg-accent-200/30 dark:bg-accent-500/10",
        label: "text-accent-600/80 dark:text-accent-300/80",
        icon: "from-accent to-accent-700 shadow-accent-500/30",
        chip: "bg-accent-500/10 text-accent-700 dark:text-accent-300",
        action: "text-accent-700 hover:text-accent-900 dark:text-accent-300 dark:hover:text-accent-200",
    },
    amber: {
        card: "from-amber-50 via-white to-white ring-amber-100/60 dark:from-amber-500/10 dark:via-secondary dark:to-secondary dark:ring-amber-500/20",
        blob: "bg-amber-200/30 dark:bg-amber-500/10",
        label: "text-amber-600/80 dark:text-amber-300/80",
        icon: "from-amber-400 to-amber-600 shadow-amber-500/30",
        chip: "bg-amber-500/10 text-amber-700 dark:text-amber-300",
        action: "text-amber-700 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-200",
    },
    rose: {
        card: "from-rose-50 via-white to-white ring-rose-100/60 dark:from-rose-500/10 dark:via-secondary dark:to-secondary dark:ring-rose-500/20",
        blob: "bg-rose-200/30 dark:bg-rose-500/10",
        label: "text-rose-600/80 dark:text-rose-300/80",
        icon: "from-rose-400 to-rose-600 shadow-rose-500/30",
        chip: "bg-rose-500/10 text-rose-700 dark:text-rose-300",
        action: "text-rose-700 hover:text-rose-900 dark:text-rose-300 dark:hover:text-rose-200",
    },
};

const styles = computed(() => TONES[props.tone]);
</script>