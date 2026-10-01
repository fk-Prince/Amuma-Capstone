<template>
    <article
        class="flex flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-[0_2px_16px_rgba(15,23,42,0.04)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_40px_rgba(49,130,237,0.10)] dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-white/20"
    >
        <div class="flex items-center justify-between gap-3">
            <div class="flex gap-0.5">
                <Star
                    v-for="n in 5"
                    :key="n"
                    class="h-4 w-4"
                    :class="
                        n <= Math.round(Number(rate))
                            ? 'fill-amber-400 text-amber-400'
                            : 'fill-gray-200 text-gray-200 dark:fill-white/10 dark:text-white/10'
                    "
                />
            </div>
            <Quote class="h-5 w-5 text-primary/20 dark:text-white/10" />
        </div>

        <p
            class="mt-4 flex-1 whitespace-pre-line text-sm leading-7 text-secondary/80 dark:text-gray-300"
        >
            <template v-for="(part, idx) in textParts" :key="idx"
                ><span v-if="part.brand" class="font-bold text-primary">{{
                    part.text
                }}</span
                ><template v-else>{{ part.text }}</template></template
            >
        </p>

        <img
            v-if="image"
            :src="image"
            alt="Review attachment"
            class="mt-4 max-h-56 w-full rounded-xl object-cover ring-1 ring-gray-200 dark:ring-white/10"
        />

        <div
            class="mt-5 flex items-center gap-3 border-t border-gray-100 pt-4 dark:border-white/10"
        >
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
                :style="{ background: avatar ? undefined : avatarColor }"
            >
                <img
                    v-if="avatar"
                    :src="avatar"
                    alt=""
                    class="h-full w-full object-cover"
                />
                <template v-else>{{ initials }}</template>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <p
                        class="truncate text-sm font-semibold text-secondary dark:text-white"
                    >
                        {{ name }}
                    </p>
                    <span
                        v-if="role"
                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                        :class="roleBadgeClass"
                    >
                        {{ role }}
                    </span>
                </div>
                <p
                    v-if="organization || date"
                    class="truncate text-xs text-muted dark:text-gray-400"
                >
                    {{ [organization, date].filter(Boolean).join(" · ") }}
                </p>
            </div>
        </div>
    </article>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Star, Quote } from "lucide-vue-next";

const props = defineProps<{
    name: string;
    text: string;
    rate: number | string;
    role?: string | null;
    roleTone?: "owner" | "staff" | "client" | "team";
    organization?: string | null;
    date?: string | null;
    avatar?: string | null;
    image?: string | null;
}>();

const AVATAR_COLORS = [
    "#f87171",
    "#60a5fa",
    "#34d399",
    "#a78bfa",
    "#fb923c",
    "#22d3ee",
    "#4ade80",
    "#e879f9",
];

const initials = computed(() => {
    const parts = props.name.trim().split(/\s+/);
    return (
        ((parts[0]?.[0] ?? "") + (parts[parts.length - 1]?.[0] ?? "")).toUpperCase() || "U"
    );
});

const avatarColor = computed(() => {
    const hash = [...props.name].reduce((sum, ch) => sum + ch.charCodeAt(0), 0);
    return AVATAR_COLORS[hash % AVATAR_COLORS.length];
});

const roleBadgeClass = computed(() => {
    switch (props.roleTone) {
        case "owner":
            return "bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-300";
        case "team":
            return "bg-primary text-white";
        case "client":
            return "bg-accent-50 text-accent dark:bg-accent/15 dark:text-accent-200";
        default:
            return "bg-primary-50 text-primary dark:bg-primary/15 dark:text-primary-300";
    }
});

const textParts = computed(() =>
    (props.text ?? "")
        .split(/(AMUMA)/gi)
        .filter(Boolean)
        .map((part) => ({ text: part, brand: part.toUpperCase() === "AMUMA" })),
);
</script>
