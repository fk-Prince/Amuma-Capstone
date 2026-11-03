<template>
    <div
        class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-slate-100 bg-white px-3 py-3 sm:px-5 dark:border-white/10 dark:bg-secondary"
    >
        <div class="flex items-center gap-1.5">
            <button
                type="button"
                :aria-label="mode === 'week' ? 'Previous week' : 'Previous day'"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-300"
                @click="step(-1)"
            >
                <ChevronLeft class="h-4 w-4" />
            </button>
            <button
                type="button"
                :aria-label="mode === 'week' ? 'Next week' : 'Next day'"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-300"
                @click="step(1)"
            >
                <ChevronRight class="h-4 w-4" />
            </button>
            <button
                type="button"
                class="h-9 rounded-lg border px-3 text-xs font-semibold transition"
                :class="
                    isTodayOnly
                        ? 'border-primary bg-primary/10 text-primary dark:text-primary-300'
                        : 'border-slate-200 text-slate-600 hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-300'
                "
                @click="goToday"
            >
                Today
            </button>
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-slate-800 sm:text-base dark:text-white">
                {{ title }}
            </p>
            <p
                v-if="subtitle"
                class="truncate text-[11px] text-slate-500 dark:text-gray-400"
            >
                {{ subtitle }}
            </p>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { computed } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

/**
 * Quick date switcher above the timeline and card views: step through the
 * dates set in the filter, or jump to today.
 */
const props = defineProps<{
    dateFrom?: string;
    dateTo?: string;
}>();

const emit = defineEmits<{
    (e: "change-range", range: { from: string; to: string }): void;
}>();

type Mode = "day" | "week" | "upcoming" | "range";

const pad = (n: number) => String(n).padStart(2, "0");
const iso = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const parse = (value: string) => new Date(`${value}T00:00:00`);

function addDays(value: string, days: number) {
    const d = parse(value);
    d.setDate(d.getDate() + days);
    return iso(d);
}

const today = () => iso(new Date());
// Same starting point the filter uses when nothing is picked.
const yesterday = () => new Date(Date.now() - 86_400_000).toISOString().slice(0, 10);

const from = computed(() => props.dateFrom || yesterday());
const to = computed(() => props.dateTo || "");

const span = computed(() =>
    to.value ? Math.round((parse(to.value).getTime() - parse(from.value).getTime()) / 86_400_000) : 0,
);

const mode = computed<Mode>(() => {
    if (!to.value) return "upcoming";
    if (from.value === to.value) return "day";
    if (span.value === 6 && parse(from.value).getDay() === 0) return "week";
    return "range";
});

// The day the arrows and the picker work from.
const anchor = computed(() => (mode.value === "upcoming" ? today() : from.value));

const isTodayOnly = computed(() => mode.value === "day" && from.value === today());

function weekOf(value: string) {
    const start = addDays(value, -parse(value).getDay());
    return { from: start, to: addDays(start, 6) };
}

function emitRange(range: { from: string; to: string }) {
    emit("change-range", range);
}

function step(direction: 1 | -1) {
    if (mode.value === "week") {
        emitRange({ from: addDays(from.value, 7 * direction), to: addDays(to.value, 7 * direction) });
        return;
    }

    if (mode.value === "range") {
        const length = span.value + 1;
        emitRange({
            from: addDays(from.value, length * direction),
            to: addDays(to.value, length * direction),
        });
        return;
    }

    // Day (and "all upcoming", which steps into single days from today).
    const day = addDays(anchor.value, direction);
    emitRange({ from: day, to: day });
}

function goToday() {
    emitRange(mode.value === "week" ? weekOf(today()) : { from: today(), to: today() });
}

const format = (value: string, options: Intl.DateTimeFormatOptions) =>
    parse(value).toLocaleDateString("en-US", options);

function relative(value: string) {
    const diff = Math.round((parse(value).getTime() - parse(today()).getTime()) / 86_400_000);
    if (diff === 0) return "Today";
    if (diff === 1) return "Tomorrow";
    if (diff === -1) return "Yesterday";
    return "";
}

const title = computed(() => {
    if (mode.value === "upcoming") return `From ${format(from.value, { month: "short", day: "numeric" })} onward`;
    if (mode.value === "day") return format(from.value, { weekday: "long", month: "long", day: "numeric", year: "numeric" });

    const sameMonth = from.value.slice(0, 7) === to.value.slice(0, 7);
    const start = format(from.value, { month: "short", day: "numeric" });
    const end = sameMonth
        ? `${parse(to.value).getDate()}, ${parse(to.value).getFullYear()}`
        : format(to.value, { month: "short", day: "numeric", year: "numeric" });
    return `${start} – ${end}`;
});

const subtitle = computed(() => {
    if (mode.value === "upcoming") return "Everything scheduled from this date on";
    if (mode.value === "day") return relative(from.value);
    if (mode.value === "week") {
        const t = today();
        return t >= from.value && t <= to.value ? "This week" : "";
    }
    return `${span.value + 1} days`;
});
</script>
