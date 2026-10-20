<template>
    <div
        class="rounded-xl bg-white dark:bg-white/5 p-3 shadow-sm border border-slate-200/70 dark:border-white/10 ring-1 ring-slate-100/60 dark:ring-white/10 transition-all hover:-translate-y-0.5 hover:shadow-md"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br"
                    :class="styles.iconBox"
                >
                    <component :is="icon" class="h-4 w-4" />
                </div>
                <div>
                    <h2
                        class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                    >
                        {{ title }}
                    </h2>
                    <p class="text-[11px] text-muted dark:text-gray-400">
                        {{ subtitle }}
                    </p>
                </div>
            </div>

            <span
                v-if="live"
                class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.1em] text-amber-700 dark:text-amber-300"
            >
                <span class="h-1 w-1 animate-pulse rounded-full bg-amber-500" />
                Live
            </span>
        </div>

        <div v-if="hasData" class="relative mt-2 h-[180px]">
            <canvas
                ref="canvasRef"
                class="!h-full !w-full"
                role="img"
                :aria-label="`${title} chart`"
            />

            <div
                class="pointer-events-none absolute inset-0 flex items-center justify-center"
            >
                <div class="text-center">
                    <p
                        class="text-xl font-bold tabular-nums tracking-tight text-secondary dark:text-white"
                    >
                        {{ centerValue }}
                    </p>
                    <p
                        class="text-[8px] font-bold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
                    >
                        {{ centerLabel }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-else
            class="mt-2 flex h-[180px] items-center justify-center rounded-lg"
            :class="styles.empty"
        >
            <p class="text-xs font-semibold text-secondary dark:text-white">
                {{ emptyText }}
            </p>
        </div>

        <div class="mt-2 space-y-0.5">
            <div
                v-for="segment in segments"
                :key="segment.key"
                class="flex items-center justify-between rounded-md px-1.5 py-1 transition-colors hover:bg-light dark:hover:bg-white/5"
            >
                <div class="flex min-w-0 items-center gap-1.5">
                    <span
                        class="h-1.5 w-1.5 shrink-0 rounded-full"
                        :style="{
                            background: segment.color,
                            boxShadow: `0 0 0 3px ${segment.color}22`,
                        }"
                    />
                    <p
                        class="text-[11px] font-semibold text-secondary dark:text-white"
                    >
                        {{ segment.label }}
                    </p>
                </div>

                <p
                    class="text-[11px] font-bold tabular-nums text-secondary dark:text-white"
                >
                    {{ formatValue ? formatValue(segment.count) : segment.count }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
    type Component,
} from "vue";
import {
    ArcElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from "chart.js";

Chart.register(
    CategoryScale,
    LinearScale,
    DoughnutController,
    ArcElement,
    Title,
    Tooltip,
    Legend,
);

export interface DonutSegment {
    key: string;
    label: string;
    count: number;
    color: string;
}

const props = withDefaults(
    defineProps<{
        title: string;
        subtitle: string;
        icon: Component;
        segments: DonutSegment[];
        centerValue: string | number;
        centerLabel: string;
        emptyText?: string;
        tone?: "accent" | "amber";
        live?: boolean;
        formatValue?: (value: number) => string;
    }>(),
    { emptyText: "No data yet", tone: "accent", live: false },
);

const TONES = {
    accent: {
        iconBox:
            "from-accent-100 to-accent-50 dark:from-accent-500/20 dark:to-accent-500/10 text-accent-600 dark:text-accent-300",
        empty: "bg-accent-50/40 dark:bg-accent-500/10",
    },
    amber: {
        iconBox:
            "from-amber-100 to-amber-50 dark:from-amber-500/20 dark:to-amber-500/10 text-amber-600 dark:text-amber-300",
        empty: "bg-amber-50/40 dark:bg-amber-500/10",
    },
};

const styles = computed(() => TONES[props.tone]);

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;

const hasData = computed(() =>
    props.segments.some((segment) => segment.count > 0),
);

const destroy = () => {
    chart?.destroy();
    chart = null;
};

const render = async () => {
    destroy();

    await nextTick();

    if (!canvasRef.value || !hasData.value) return;

    const visible = props.segments.filter((segment) => segment.count > 0);

    chart = new Chart(canvasRef.value, {
        type: "doughnut",
        data: {
            labels: visible.map((segment) => segment.label),
            datasets: [
                {
                    data: visible.map((segment) => segment.count),
                    backgroundColor: visible.map((segment) => segment.color),
                    borderWidth: 0,
                    hoverOffset: 6,
                    hoverBorderWidth: 0,
                },
            ],
        },
        options: {
            cutout: "72%",
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "rgba(15, 22, 35, 0.94)",
                    padding: 8,
                    displayColors: true,
                },
            },
        } as any,
    });
};

onMounted(render);
watch(() => props.segments, render, { deep: true });
onBeforeUnmount(destroy);
</script>