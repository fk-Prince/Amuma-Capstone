<template>
    <div class="w-full shrink-0">
        <div
            class="bg-white border border-slate-200 p-5 flex flex-col dark:bg-secondary dark:border-white/10"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-3"
                :class="{ 'mb-5': open }"
            >
                <button
                    type="button"
                    class="flex items-center gap-3"
                    @click="open = !open"
                >
                    <div
                        class="h-10 w-10 rounded-xl bg-primary-50 flex items-center justify-center text-primary shrink-0 dark:bg-primary-500/10"
                    >
                        <Wallet class="h-5 w-5" />
                    </div>

                    <div class="text-left">
                        <h3
                            class="font-semibold text-slate-800 dark:text-white"
                        >
                            Billing Overview
                        </h3>
                        <p
                            class="text-xs text-slate-400 mt-0.5 dark:text-gray-500"
                        >
                            {{ overview?.period ?? "Financial summary" }}
                        </p>
                    </div>
                </button>

                <div class="flex flex-wrap items-center gap-3">
                    <div
                        class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-white/10 dark:bg-white/5"
                    >
                        <button
                            v-for="option in periodOptions"
                            :key="option.value"
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                            :class="
                                period === option.value
                                    ? 'bg-white text-primary shadow-sm dark:bg-secondary dark:text-primary-300'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                            "
                            @click="setPeriod(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <div
                        v-if="period === 'month'"
                        class="flex items-center rounded-xl border border-slate-200 bg-slate-50 px-1 py-1 shrink-0 dark:border-white/10 dark:bg-white/5"
                    >
                        <button
                            type="button"
                            class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-700 transition-colors dark:text-gray-500 dark:hover:text-gray-400 dark:hover:bg-white/10"
                            @click="shiftMonth(-1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>

                        <span
                            class="min-w-[120px] text-center text-sm font-medium text-slate-700 dark:text-gray-400"
                        >
                            {{ currentMonthLabel }}
                        </span>

                        <button
                            type="button"
                            class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-700 transition-colors dark:text-gray-500 dark:hover:text-gray-400 dark:hover:bg-white/10"
                            @click="shiftMonth(1)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        v-else-if="period === 'date'"
                        class="flex items-center gap-2"
                    >
                        <DatePickerField
                            v-model="dateFrom"
                            class-name="w-[150px]"
                            placeholder="From"
                            :default-to-today="false"
                            @update:model-value="emitFilter"
                        />

                        <span class="text-xs text-slate-400 dark:text-gray-500">
                            to
                        </span>

                        <DatePickerField
                            v-model="dateTo"
                            class-name="w-[150px]"
                            placeholder="To"
                            :min="dateFrom"
                            :default-to-today="false"
                            @update:model-value="emitFilter"
                        />
                    </div>

                    <button
                        type="button"
                        class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors shrink-0 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-400"
                        @click="open = !open"
                    >
                        <svg
                            class="h-5 w-5 transition-transform duration-300"
                            :class="{ 'rotate-180': open }"
                            viewBox="0 0 20 20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M5 7.5L10 12.5L15 7.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="grid-rows-[0fr] opacity-0"
                enter-to-class="grid-rows-[1fr] opacity-100"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="grid-rows-[1fr] opacity-100"
                leave-to-class="grid-rows-[0fr] opacity-0"
            >
                <div v-show="open" class="grid overflow-hidden">
                    <div class="min-h-0">
                        <div
                            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-3"
                        >
                            <div
                                v-for="metric in metrics"
                                :key="metric.key"
                                class="group rounded-xl border border-slate-100 bg-slate-50/50 p-4 hover:border-primary-200 hover:shadow-sm transition-all duration-200 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500/20"
                            >
                                <template v-if="props.loading && !overview">
                                    <div class="animate-pulse">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <div
                                                class="h-3 w-20 rounded bg-slate-200 dark:bg-white/15"
                                            ></div>
                                            <div
                                                class="h-7 w-7 rounded-lg bg-slate-200 dark:bg-white/15"
                                            ></div>
                                        </div>

                                        <div
                                            class="mt-3 h-8 w-28 rounded bg-slate-200 dark:bg-white/15"
                                        ></div>

                                        <div
                                            class="mt-2 h-3 w-24 rounded bg-slate-200 dark:bg-white/15"
                                        ></div>
                                    </div>
                                </template>

                                <div
                                    v-else
                                    class="transition-opacity"
                                    :class="{ 'opacity-50': props.loading }"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                        >
                                            {{ metric.label }}
                                        </p>

                                        <div
                                            class="h-7 w-7 rounded-lg flex items-center justify-center shrink-0"
                                            :class="metric.iconBg"
                                        >
                                            <component
                                                :is="metric.icon"
                                                class="h-3.5 w-3.5"
                                                :class="metric.iconColor"
                                            />
                                        </div>
                                    </div>

                                    <p
                                        class="mt-2.5 text-2xl font-semibold text-slate-800 dark:text-white"
                                    >
                                        {{ metric.display }}
                                    </p>

                                    <p
                                        v-if="metric.secondary"
                                        class="mt-1 text-xs truncate"
                                        :class="metric.secondaryColor"
                                    >
                                        {{ metric.secondary }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-5 border-t border-slate-100 pt-4 dark:border-white/10"
                        >
                            <div
                                class="mb-3 flex flex-wrap items-center justify-between gap-2"
                            >
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                >
                                    {{ chartTitle }}
                                </p>

                                <p
                                    v-if="period !== 'all' && overview"
                                    class="text-xs font-semibold"
                                    :class="
                                        trendColor(
                                            overview?.total_revenue?.trend,
                                        )
                                    "
                                >
                                    Revenue
                                    {{ overview?.total_revenue?.secondary }}
                                </p>
                            </div>

                            <div
                                class="relative h-64 rounded-xl border border-slate-100 bg-white p-4 transition-opacity dark:border-white/10 dark:bg-white/[0.03]"
                                :class="{ 'opacity-50': props.loading }"
                            >
                                <canvas
                                    ref="chartCanvas"
                                    role="img"
                                    :aria-label="chartTitle"
                                />

                                <p
                                    v-if="!props.loading && !hasChartData"
                                    class="absolute inset-0 flex items-center justify-center text-xs text-slate-400 dark:text-gray-500"
                                >
                                    Nothing billed in this period.
                                </p>
                            </div>

                            <table v-if="chart" class="sr-only">
                                <caption>{{ chartTitle }}</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Period</th>
                                        <th scope="col">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(label, index) in chart.labels"
                                        :key="label"
                                    >
                                        <th scope="row">{{ label }}</th>
                                        <td>
                                            {{ formatCurrency(chart.revenue[index] ?? 0) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import {
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from "chart.js";
import { Wallet, DollarSign, Receipt, ChevronLeft, ChevronRight } from "lucide-vue-next";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import { formatCurrency as formatCurrencyUtil } from "~/utils/currency";
import { useIsDark } from "~/composables/useTheme";

Chart.register(
    LineController,
    LineElement,
    PointElement,
    Filler,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
);

type Period = "month" | "date" | "all";

interface OverviewChart {
    title: string;
    labels: string[];
    revenue: number[];
    highlight: number[];
}

const SERIES = {
    light: {
        revenue: "#3182ED",
        grid: "#eef2f7",
        text: "#64748b",
        surface: "#ffffff",
    },
    dark: {
        revenue: "#3987e5",
        grid: "rgba(255,255,255,0.08)",
        text: "#9ca3af",
        surface: "#1A2133",
    },
};

const open = ref(false);

const props = defineProps<{
    overview: any;
    loading?: boolean;
}>();

const emit = defineEmits<{
    "filter-change": [
        {
            period: Period;
            month?: number;
            year?: number;
            from?: string;
            to?: string;
        },
    ];
}>();

const periodOptions: { value: Period; label: string }[] = [
    { value: "month", label: "Month" },
    { value: "date", label: "Date" },
    { value: "all", label: "All" },
];

const months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

const now = new Date();
const period = ref<Period>("month");
const currentMonthIndex = ref(now.getMonth());
const currentYear = ref(now.getFullYear());
const today = toDateString(now);
const dateFrom = ref(today);
const dateTo = ref(today);

const currentMonthLabel = computed(
    () => `${months[currentMonthIndex.value]} ${currentYear.value}`,
);

function toDateString(date: Date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
}

function emitFilter() {
    if (period.value === "all") {
        emit("filter-change", { period: "all" });
        return;
    }

    if (period.value === "date") {
        if (!dateFrom.value || !dateTo.value) return;

        emit("filter-change", {
            period: "date",
            from: dateFrom.value,
            to: dateTo.value,
        });
        return;
    }

    emit("filter-change", {
        period: "month",
        month: currentMonthIndex.value + 1,
        year: currentYear.value,
    });
}

function setPeriod(value: Period) {
    if (period.value === value) return;

    period.value = value;
    emitFilter();
}

let monthDebounceTimer: ReturnType<typeof setTimeout> | null = null;

function shiftMonth(delta: number) {
    const date = new Date(
        currentYear.value,
        currentMonthIndex.value + delta,
        1,
    );

    currentMonthIndex.value = date.getMonth();
    currentYear.value = date.getFullYear();

    if (monthDebounceTimer) {
        clearTimeout(monthDebounceTimer);
    }

    monthDebounceTimer = setTimeout(emitFilter, 400);
}

const formatCurrency = (value: number) => {
    return formatCurrencyUtil(value, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
};

const compactPeso = new Intl.NumberFormat("en-PH", {
    notation: "compact",
    maximumFractionDigits: 1,
});

const trendColor = (trend: string) => {
    if (trend === "up") return "text-emerald-600 dark:text-emerald-300";
    if (trend === "down") return "text-red-500";
    if (trend === "warning") return "text-orange-500";
    return "text-slate-400 dark:text-gray-500";
};

const metrics = computed(() => [
    {
        key: "revenue",
        label: "Revenue",
        display: formatCurrency(props.overview?.total_revenue?.value ?? 0),
        secondary: props.overview?.total_revenue?.secondary ?? "",
        secondaryColor:
            period.value === "all"
                ? "text-slate-400 dark:text-gray-500"
                : trendColor(props.overview?.total_revenue?.trend),
        icon: DollarSign,
        iconBg: "bg-primary-50 dark:bg-primary-500/10",
        iconColor: "text-primary",
    },
    {
        key: "payments",
        label: "Payments",
        display: formatCurrency(props.overview?.payments_received?.value ?? 0),
        secondary: props.overview?.payments_received?.secondary ?? "",
        secondaryColor:
            period.value === "all"
                ? "text-slate-400 dark:text-gray-500"
                : trendColor(props.overview?.payments_received?.trend),
        icon: Wallet,
        iconBg: "bg-emerald-50 dark:bg-emerald-500/10",
        iconColor: "text-emerald-600 dark:text-emerald-300",
    },
    {
        key: "outstanding",
        label: "Receivable",
        display: formatCurrency(
            props.overview?.outstanding_balance?.value ?? 0,
        ),
        secondary: props.overview?.outstanding_balance?.secondary ?? "",
        secondaryColor: "text-slate-400 dark:text-gray-500",
        icon: Receipt,
        iconBg: "bg-orange-50 dark:bg-orange-500/10",
        iconColor: "text-orange-500",
    },
]);

const chart = computed<OverviewChart | null>(
    () => props.overview?.chart ?? null,
);

const chartTitle = computed(() => chart.value?.title ?? "Revenue");

const hasChartData = computed(() =>
    (chart.value?.revenue ?? []).some((value) => Number(value) > 0),
);

const isDark = useIsDark();
const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

function withAlpha(hex: string, alpha: number) {
    const value = parseInt(hex.slice(1), 16);

    return `rgba(${(value >> 16) & 255}, ${(value >> 8) & 255}, ${value & 255}, ${alpha})`;
}

function wash(canvas: HTMLCanvasElement, color: string) {
    const gradient = canvas
        .getContext("2d")
        ?.createLinearGradient(0, 0, 0, canvas.clientHeight || 220);

    gradient?.addColorStop(0, withAlpha(color, 0.18));
    gradient?.addColorStop(1, withAlpha(color, 0));

    return gradient ?? withAlpha(color, 0.1);
}

function series(
    canvas: HTMLCanvasElement,
    label: string,
    values: number[],
    color: string,
    data: OverviewChart,
    surface: string,
) {
    return {
        label,
        data: values,
        borderColor: color,
        backgroundColor: wash(canvas, color),
        fill: true,
        tension: 0.35,
        borderWidth: 2,
        pointRadius: data.labels.map((_, index) =>
            data.highlight.includes(index) ? 5 : 3,
        ),
        pointHoverRadius: 6,
        pointHitRadius: 12,
        pointBackgroundColor: color,
        pointBorderColor: surface,
        pointBorderWidth: 2,
    };
}

function renderChart() {
    const data = chart.value;
    const canvas = chartCanvas.value;

    if (!canvas || !data) return;

    const theme = isDark.value ? SERIES.dark : SERIES.light;

    chartInstance?.destroy();

    chartInstance = new Chart(canvas, {
        type: "line",
        data: {
            labels: data.labels,
            datasets: [
                series(canvas, "Revenue", data.revenue, theme.revenue, data, theme.surface),
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 500 },
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "rgba(15, 22, 35, 0.94)",
                    padding: 8,
                    titleFont: { size: 11, weight: 600 },
                    bodyFont: { size: 11 },
                    callbacks: {
                        label: (item) =>
                            ` ${formatCurrency(Number(item.raw ?? 0))}  ${item.dataset.label}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: theme.text, font: { size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: theme.grid, lineWidth: 1 },
                    border: { display: false },
                    ticks: {
                        color: theme.text,
                        font: { size: 11 },
                        maxTicksLimit: 5,
                        callback: (value) => `₱${compactPeso.format(Number(value))}`,
                    },
                },
            },
        },
    });
}

watch(
    [chart, isDark],
    async () => {
        await nextTick();
        renderChart();
    },
    { deep: true },
);

onMounted(renderChart);

onBeforeUnmount(() => {
    chartInstance?.destroy();
    chartInstance = null;
});
</script>
