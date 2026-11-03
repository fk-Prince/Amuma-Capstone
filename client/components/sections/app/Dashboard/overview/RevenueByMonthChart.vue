<template>
    <div class="relative h-[240px] w-full sm:h-[260px]">
        <canvas ref="canvasRef" role="img" aria-label="Revenue by month chart" />
    </div>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import {
    CategoryScale,
    Chart,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from "chart.js";
import { formatCurrency } from "~/utils/currency";

Chart.register(CategoryScale, LinearScale, LineController, LineElement, PointElement, Filler, Tooltip);

// Same look as the platform dashboard's "Revenue by Month": teal line, dots, soft fill.
const props = defineProps<{
    labels: string[];
    values: number[];
    /** Extra tooltip lines per month (e.g. each branch's share). */
    details?: (index: number) => string[];
}>();

const TEAL = "#0E7C7B";
const TICK = "#94a3b8";
const GRID = "rgba(148, 163, 184, 0.15)";

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;
let themeObserver: MutationObserver | null = null;

const money = (value: number) =>
    formatCurrency(Number(value) || 0, { minimumFractionDigits: 0, maximumFractionDigits: 0 });

function render() {
    const canvas = canvasRef.value;
    if (!canvas) return;

    chart?.destroy();

    const dark = document.documentElement.classList.contains("dark");
    const gradient = canvas.getContext("2d")?.createLinearGradient(0, 0, 0, 240);
    gradient?.addColorStop(0, "rgba(14, 124, 123, 0.35)");
    gradient?.addColorStop(1, "rgba(14, 124, 123, 0)");

    chart = new Chart(canvas, {
        type: "line",
        data: {
            labels: props.labels,
            datasets: [
                {
                    label: "Revenue",
                    data: props.values,
                    borderColor: TEAL,
                    backgroundColor: gradient ?? "rgba(14, 124, 123, 0.15)",
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: TEAL,
                    pointBorderColor: dark ? "#1a2133" : "#ffffff",
                    pointBorderWidth: 1.5,
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 500 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "rgba(15, 22, 35, 0.94)",
                    padding: 8,
                    titleFont: { size: 11, weight: 600 },
                    bodyFont: { size: 11 },
                    displayColors: false,
                    callbacks: {
                        label: (context: any) => ` ${money(Number(context.parsed.y))}`,
                        afterLabel: (context: any) => props.details?.(context.dataIndex) ?? [],
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: { size: 9 },
                        color: TICK,
                        callback: (value: any) => money(Number(value)),
                    },
                    grid: { color: GRID },
                    border: { display: false },
                },
                x: {
                    ticks: { font: { size: 9 }, color: TICK, maxRotation: 0, autoSkipPadding: 12 },
                    grid: { display: false },
                    border: { display: false },
                },
            },
        } as any,
    });
}

onMounted(async () => {
    themeObserver = new MutationObserver(render);
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });

    await nextTick();
    render();
});

watch(
    () => [props.labels, props.values],
    () => nextTick(render),
    { deep: true },
);

onBeforeUnmount(() => {
    chart?.destroy();
    themeObserver?.disconnect();
});
</script>
