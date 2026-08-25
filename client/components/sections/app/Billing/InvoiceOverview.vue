<template>
    <div class="w-full shrink-0">
        <div class="bg-white border border-slate-200 p-5 flex flex-col">
            <div
                class="flex items-center justify-between"
                :class="{ 'mb-5': open }"
            >
                <button
                    type="button"
                    class="flex items-center gap-3"
                    @click="open = !open"
                >
                    <div
                        class="h-10 w-10 rounded-xl bg-primary-50 flex items-center justify-center text-primary shrink-0"
                    >
                        <Wallet class="h-5 w-5" />
                    </div>

                    <div class="text-left">
                        <h3 class="font-semibold text-slate-800">
                            Billing Overview
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Financial summary
                        </p>
                    </div>
                </button>

                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center rounded-xl border border-slate-200 bg-slate-50 px-1 py-1 shrink-0"
                    >
                        <button
                            type="button"
                            class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-700 transition-colors"
                            @click="shiftMonth(-1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>

                        <span
                            class="min-w-[120px] text-center text-sm font-medium text-slate-700"
                        >
                            {{ currentMonthLabel }}
                        </span>

                        <button
                            type="button"
                            class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-700 transition-colors"
                            @click="shiftMonth(1)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>

                    <button
                        type="button"
                        class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors shrink-0"
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
                            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3"
                        >
                            <div
                                v-for="metric in metrics"
                                :key="metric.key"
                                class="group rounded-xl border border-slate-100 bg-slate-50/50 p-4 hover:border-primary-200 hover:shadow-sm transition-all duration-200"
                            >
                                <template v-if="props.loading">
                                    <div class="animate-pulse">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <div
                                                class="h-3 w-20 rounded bg-slate-200"
                                            ></div>
                                            <div
                                                class="h-7 w-7 rounded-lg bg-slate-200"
                                            ></div>
                                        </div>

                                        <div
                                            class="mt-3 h-8 w-28 rounded bg-slate-200"
                                        ></div>

                                        <div
                                            class="mt-2 h-3 w-24 rounded bg-slate-200"
                                        ></div>
                                    </div>
                                </template>

                                <template v-else>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            class="text-[11px] font-medium uppercase tracking-wide text-slate-400"
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
                                        class="mt-2.5 text-2xl font-semibold text-slate-800 tabular-nums"
                                    >
                                        {{ metric.display }}
                                    </p>

                                    <p
                                        class="mt-1 text-xs truncate"
                                        :class="metric.secondaryColor"
                                    >
                                        {{ metric.secondary }}
                                    </p>
                                </template>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-slate-400 mb-3"
                            >
                                Monthly Performance
                            </p>

                            <div
                                class="rounded-xl border border-slate-100 bg-gradient-to-br from-slate-50 to-primary-50/40 p-4 flex items-center justify-between"
                            >
                                <template v-if="props.loading">
                                    <div class="animate-pulse w-full">
                                        <div
                                            class="h-3 w-28 bg-slate-200 rounded"
                                        ></div>
                                        <div
                                            class="mt-3 h-8 w-20 bg-slate-200 rounded"
                                        ></div>
                                    </div>
                                </template>

                                <template v-else>
                                    <div>
                                        <p class="text-sm text-slate-400">
                                            Revenue Growth
                                        </p>

                                        <p
                                            class="mt-1 text-2xl font-semibold"
                                            :class="
                                                trendColor(
                                                    overview?.total_revenue
                                                        ?.trend,
                                                )
                                            "
                                        >
                                            {{
                                                overview?.total_revenue
                                                    ?.secondary ?? "0%"
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="h-11 w-11 rounded-xl bg-primary-50 flex items-center justify-center"
                                    >
                                        <TrendingUp
                                            class="h-5 w-5 text-primary"
                                        />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";

import {
    Wallet,
    DollarSign,
    Receipt,
    AlertTriangle,
    CalendarClock,
    TrendingUp,
    ChevronLeft,
    ChevronRight,
    RotateCcw,
} from "lucide-vue-next";

const open = ref(false);

const props = defineProps<{
    overview: any;
    loading?: boolean;
}>();

const emit = defineEmits<{
    "month-change": [{ month: number; year: number }];
}>();

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
const currentMonthIndex = ref(now.getMonth());
const currentYear = ref(now.getFullYear());

const currentMonthLabel = computed(
    () => `${months[currentMonthIndex.value]} ${currentYear.value}`,
);

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

    monthDebounceTimer = setTimeout(() => {
        emit("month-change", {
            month: currentMonthIndex.value + 1,
            year: currentYear.value,
        });
    }, 400);
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(value);
};

const trendColor = (trend: string) => {
    if (trend === "up") return "text-emerald-600";
    if (trend === "down") return "text-red-500";
    if (trend === "warning") return "text-orange-500";
    return "text-slate-400";
};

const metrics = computed(() => [
    {
        key: "revenue",
        label: "Revenue",
        display: formatCurrency(props.overview?.total_revenue?.value ?? 0),
        secondary: props.overview?.total_revenue?.secondary ?? "No changes",
        secondaryColor: trendColor(props.overview?.total_revenue?.trend),
        icon: DollarSign,
        iconBg: "bg-primary-50",
        iconColor: "text-primary",
    },
    {
        key: "payments",
        label: "Payments",
        display: formatCurrency(props.overview?.payments_received?.value ?? 0),
        secondary: props.overview?.payments_received?.secondary ?? "No changes",
        secondaryColor: trendColor(props.overview?.payments_received?.trend),
        icon: Wallet,
        iconBg: "bg-emerald-50",
        iconColor: "text-emerald-600",
    },
    {
        key: "refunded",
        label: "Refunded",
        display: formatCurrency(props.overview?.refunds_issued?.value ?? 0),
        secondary: props.overview?.refunds_issued?.secondary ?? "No changes",
        secondaryColor: trendColor(props.overview?.refunds_issued?.trend),
        icon: RotateCcw,
        iconBg: "bg-amber-50",
        iconColor: "text-amber-600",
    },
    {
        key: "outstanding",
        label: "Outstanding",
        display: formatCurrency(
            props.overview?.outstanding_balance?.value ?? 0,
        ),
        secondary:
            props.overview?.outstanding_balance?.secondary ?? "No changes",
        secondaryColor: "text-slate-400",
        icon: Receipt,
        iconBg: "bg-orange-50",
        iconColor: "text-orange-500",
    },
    {
        key: "overdue",
        label: "Overdue",
        display: props.overview?.overdue_invoices?.value ?? 0,
        secondary:
            props.overview?.overdue_invoices?.secondary ??
            "No overdue invoices",
        secondaryColor: "text-slate-400",
        icon: AlertTriangle,
        iconBg: "bg-red-50",
        iconColor: "text-red-500",
    },
    {
        key: "upcoming",
        label: "Upcoming",
        display: props.overview?.upcoming_payments?.value ?? 0,
        secondary:
            props.overview?.upcoming_payments?.secondary ??
            "No upcoming payments",
        secondaryColor: "text-slate-400",
        icon: CalendarClock,
        iconBg: "bg-primary-50",
        iconColor: "text-primary",
    },
]);
</script>
