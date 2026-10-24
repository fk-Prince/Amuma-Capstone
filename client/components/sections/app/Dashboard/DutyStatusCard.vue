<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import {
    BedDouble,
    CalendarClock,
    Clock,
    HandHeart,
    Hourglass,
    Stethoscope,
} from "lucide-vue-next";

interface DutyBlock {
    type: "shift" | "adl" | "medical";
    setting?: "facility" | "homecare";
    title: string;
    patient_name: string | null;
    schedule_code?: string | null;
    location: string | null;
    start_at: string;
    end_at: string;
    visit_start_at?: string | null;
    visit_end_at?: string | null;
    total_minutes: number;
}

interface DutyData {
    on_duty: boolean;
    current: DutyBlock | null;
    next: DutyBlock | null;
    blocks: DutyBlock[];
    server_now: string;
}

const props = defineProps<{
    duty: DutyData;
}>();

const TICK_MS = 15000;

const clockOffset = ref(0);
const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | null = null;

const tick = () => {
    now.value = Date.now() + clockOffset.value;
};

onMounted(() => {
    const serverNow = Date.parse(props.duty.server_now);

    if (!Number.isNaN(serverNow)) {
        clockOffset.value = serverNow - Date.now();
    }

    tick();
    timer = setInterval(tick, TICK_MS);
});

onBeforeUnmount(() => {
    if (timer) clearInterval(timer);
});

const ms = (value: string) => new Date(value).getTime();

const blocks = computed(() =>
    (props.duty.blocks ?? []).map((block) => {
        const start = ms(block.start_at);
        const end = ms(block.end_at);

        return {
            ...block,
            start,
            end,
            state:
                now.value >= end
                    ? "done"
                    : now.value >= start
                      ? "now"
                      : "upcoming",
        };
    }),
);

const current = computed(
    () => blocks.value.find((block) => block.state === "now") ?? null,
);

const next = computed(
    () => blocks.value.find((block) => block.state === "upcoming") ?? null,
);

const onDuty = computed(() => Boolean(current.value));

const currentWindow = computed(() => {
    const block = current.value;

    if (!block) return null;

    return block.visit_start_at && block.visit_end_at
        ? { start: ms(block.visit_start_at), end: ms(block.visit_end_at) }
        : { start: block.start, end: block.end };
});

const remainingMinutes = computed(() => {
    if (!currentWindow.value) return 0;

    return Math.max(Math.ceil((currentWindow.value.end - now.value) / 60000), 0);
});

const percent = computed(() => {
    const block = currentWindow.value;

    if (!block) return 0;

    const total = block.end - block.start;

    if (total <= 0) return 0;

    return Math.min(
        Math.max(Math.round(((now.value - block.start) / total) * 1000) / 10, 0),
        100,
    );
});

const minutesUntilNext = computed(() => {
    if (!next.value) return 0;

    return Math.max(Math.ceil((next.value.start - now.value) / 60000), 0);
});

function formatSpan(totalMinutes: number) {
    const minutes = Math.max(Math.round(totalMinutes), 0);

    if (minutes < 1) return "less than a minute";

    const days = Math.floor(minutes / 1440);
    const hours = Math.floor((minutes % 1440) / 60);
    const rest = minutes % 60;

    const parts: string[] = [];

    if (days) parts.push(`${days} ${days === 1 ? "day" : "days"}`);
    if (hours) parts.push(`${hours} ${hours === 1 ? "hr" : "hrs"}`);
    if (rest) parts.push(`${rest} ${rest === 1 ? "min" : "mins"}`);

    return parts.join(" ");
}

function formatWindowEdge(value: number) {
    const sameDay =
        new Date(value).toDateString() === new Date(now.value).toDateString();

    return sameDay
        ? formatTime(value)
        : new Date(value).toLocaleString("en-US", {
              month: "short",
              day: "numeric",
              hour: "numeric",
              minute: "2-digit",
          });
}

function formatTime(value: number) {
    return new Date(value).toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
    });
}

const dayStart = computed(() => {
    const date = new Date(now.value);
    date.setHours(0, 0, 0, 0);
    return date.getTime();
});

const DAY_MS = 24 * 60 * 60 * 1000;

const segments = computed(() =>
    blocks.value.map((block) => {
        const left = Math.max(block.start - dayStart.value, 0);
        const right = Math.min(block.end - dayStart.value, DAY_MS);
        const width = Math.max(right - left, 0);

        return {
            ...block,
            left: (left / DAY_MS) * 100,
            width: Math.max((width / DAY_MS) * 100, 1.2),
        };
    }),
);

const nowPercent = computed(() =>
    Math.min(Math.max(((now.value - dayStart.value) / DAY_MS) * 100, 0), 100),
);

const hourLabels = ["12 AM", "6 AM", "12 PM", "6 PM", "12 AM"];

const stateLabel = (state: string) =>
    state === "now" ? "On duty now" : state === "done" ? "Done" : "Upcoming";

const stateClass = (state: string) =>
    state === "now"
        ? "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
        : state === "done"
          ? "bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400"
          : "bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300";

const typeBar = (type: string) =>
    type === "adl"
        ? "bg-amber-500"
        : type === "medical"
          ? "bg-violet-500"
          : "bg-primary";

const typeIcon = (type: string) =>
    type === "adl" ? HandHeart : type === "medical" ? Stethoscope : BedDouble;
</script>

<template>
    <section
        class="relative overflow-hidden rounded-xl bg-white p-4 shadow-sm ring-1 ring-primary-100/60 dark:border dark:border-white/10 dark:bg-secondary dark:ring-primary-500/20"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                    :class="
                        onDuty
                            ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
                            : 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400'
                    "
                >
                    <CalendarClock class="h-5 w-5" />
                </span>

                <div>
                    <h2
                        class="text-sm font-bold text-secondary dark:text-white"
                    >
                        My Duty
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Your schedule for today
                    </p>
                </div>
            </div>

            <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                :class="
                    onDuty
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                        : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300'
                "
            >
                <span class="relative flex h-2 w-2">
                    <span
                        v-if="onDuty"
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                    />
                    <span
                        class="relative inline-flex h-2 w-2 rounded-full"
                        :class="onDuty ? 'bg-emerald-500' : 'bg-gray-400'"
                    />
                </span>
                {{ onDuty ? "On duty" : "Off duty" }}
            </span>
        </div>

        <!-- On duty: remaining time + progress -->
        <div
            v-if="current"
            class="mt-4 rounded-xl bg-emerald-50/60 p-4 dark:bg-emerald-500/5"
        >
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <p
                        class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-emerald-700/80 dark:text-emerald-300/80"
                    >
                        <Hourglass class="h-3.5 w-3.5" />
                        Time left
                    </p>
                    <p
                        class="mt-0.5 text-2xl font-bold tabular-nums text-secondary dark:text-white"
                    >
                        {{ formatSpan(remainingMinutes) }}
                    </p>
                </div>

                <p
                    class="text-right text-xs text-gray-500 dark:text-gray-400"
                >
                    <template v-if="currentWindow">
                        {{ formatWindowEdge(currentWindow.start) }} –
                        {{ formatWindowEdge(currentWindow.end) }}
                    </template>
                </p>
            </div>

            <div class="mt-3">
                <div class="mb-1 flex items-center justify-between">
                    <span
                        class="text-[10px] font-semibold uppercase text-gray-400 dark:text-gray-500"
                    >
                        Progress
                    </span>
                    <span
                        class="text-[11px] font-semibold text-gray-600 dark:text-gray-300"
                    >
                        {{ percent }}%
                    </span>
                </div>

                <div
                    class="h-2 overflow-hidden rounded-full bg-emerald-100 dark:bg-white/10"
                >
                    <div
                        class="h-full rounded-full bg-emerald-500 transition-all duration-700"
                        :style="{ width: percent + '%' }"
                    />
                </div>
            </div>

            <p
                class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-600 dark:text-gray-300"
            >
                <component
                    :is="typeIcon(current.type)"
                    class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-300"
                />
                <span class="font-medium">{{ current.title }}</span>
                <span v-if="current.patient_name">
                    · {{ current.patient_name }}
                </span>
                <span v-if="current.setting === 'homecare'">· Homecare</span>
                <span v-if="current.schedule_code" class="font-mono"
                    >· {{ current.schedule_code }}</span
                >
                <span v-if="current.location">· {{ current.location }}</span>
            </p>
        </div>

        <!-- Off duty -->
        <div
            v-else
            class="mt-4 rounded-xl bg-gray-50 p-4 dark:bg-white/5"
        >
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                You're not on duty right now
            </p>

            <p
                v-if="next"
                class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400"
            >
                <Clock class="h-3.5 w-3.5" />
                Next: {{ next.title }}
                <span v-if="next.patient_name">for {{ next.patient_name }}</span>
                starts in {{ formatSpan(minutesUntilNext) }} ({{
                    formatTime(next.start)
                }}
                – {{ formatTime(next.end) }})
            </p>

            <p v-else class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                No more duty scheduled for today.
            </p>
        </div>

        <!-- Timeline diagram -->
        <div class="mt-5">
            <div class="relative h-9 rounded-lg bg-gray-100 dark:bg-white/10">
                <div
                    v-for="(segment, index) in segments"
                    :key="index"
                    class="absolute top-1 bottom-1 rounded-md opacity-90"
                    :class="[
                        typeBar(segment.type),
                        segment.state === 'done' ? '!opacity-40' : '',
                    ]"
                    :style="{
                        left: segment.left + '%',
                        width: segment.width + '%',
                    }"
                    :title="`${segment.title} · ${formatTime(segment.start)} – ${formatTime(segment.end)}`"
                />

                <div
                    class="absolute -top-1 -bottom-1 w-0.5 rounded-full bg-red-500"
                    :style="{ left: nowPercent + '%' }"
                >
                    <span
                        class="absolute -top-1.5 left-1/2 h-2 w-2 -translate-x-1/2 rounded-full bg-red-500"
                    />
                </div>
            </div>

            <div
                class="mt-1 flex justify-between text-[10px] text-gray-400 dark:text-gray-500"
            >
                <span v-for="(label, index) in hourLabels" :key="index">
                    {{ label }}
                </span>
            </div>

            <div
                class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500 dark:text-gray-400"
            >
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-sm bg-primary" />
                    Facility Shifts
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-sm bg-amber-500" />
                    Activities of Daily Living (ADL)
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-sm bg-violet-500" />
                    Medical Services
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-0.5 rounded-full bg-red-500" />
                    Now
                </span>
            </div>
        </div>

        <!-- Today's list -->
        <ul
            v-if="blocks.length"
            class="mt-4 divide-y divide-gray-100 dark:divide-white/10"
        >
            <li
                v-for="(block, index) in blocks"
                :key="index"
                class="flex items-center justify-between gap-3 py-2"
            >
                <div class="min-w-0">
                    <p
                        class="truncate text-xs font-semibold text-gray-800 dark:text-white"
                    >
                        {{ block.title }}
                        <span
                            v-if="block.patient_name"
                            class="font-normal text-gray-500 dark:text-gray-400"
                        >
                            · {{ block.patient_name }}
                        </span>
                    </p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500">
                        <span
                            v-if="block.schedule_code"
                            class="font-mono text-gray-500 dark:text-gray-400"
                            >{{ block.schedule_code }} ·</span
                        >
                        {{ formatTime(block.start) }} –
                        {{ formatTime(block.end) }}
                        <template v-if="block.setting === 'homecare'">
                            · Homecare
                        </template>
                    </p>
                </div>

                <span
                    class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                    :class="stateClass(block.state)"
                >
                    {{ stateLabel(block.state) }}
                </span>
            </li>
        </ul>
    </section>
</template>