<script setup lang="ts">
import { ref, computed } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

const today = new Date();

const currentDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));

const monthName = computed(() =>
    currentDate.value.toLocaleString("default", {
        month: "long",
    }),
);

const year = computed(() => currentDate.value.getFullYear());

const weekDays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const days = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();

    const result: (number | null)[] = [];

    for (let i = 0; i < firstDay; i++) {
        result.push(null);
    }

    for (let i = 1; i <= totalDays; i++) {
        result.push(i);
    }

    return result;
});

function changeMonth(amount: number) {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() + amount,
        1,
    );
}

function isToday(day: number | null) {
    if (!day) return false;

    return (
        day === today.getDate() &&
        currentDate.value.getMonth() === today.getMonth() &&
        currentDate.value.getFullYear() === today.getFullYear()
    );
}
</script>
<template>
    <div
        class="relative overflow-hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-4 w-full dark:bg-secondary dark:border-white/10"
    >
        <!-- Decorative background -->
        <div
            class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-primary-100/40 blur-2xl dark:bg-primary-500/15"
        />

        <!-- Header -->
        <div class="relative flex items-center justify-between mb-5">
            <button
                type="button"
                @click="changeMonth(-1)"
                class="h-9 w-9 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-primary-50 hover:text-primary transition dark:bg-white/5 dark:text-gray-500 dark:hover:bg-primary-500/10"
            >
                <ChevronLeft class="h-5 w-5" />
            </button>

            <div class="flex flex-col items-center">
                <div
                    class="px-4 py-1.5 rounded-full bg-primary-50 text-primary text-xs font-semibold uppercase tracking-wide dark:bg-primary-500/10"
                >
                    {{ monthName }}
                </div>

                <span class="mt-1 text-sm font-semibold text-slate-700 dark:text-gray-400">
                    {{ year }}
                </span>
            </div>

            <button
                type="button"
                @click="changeMonth(1)"
                class="h-9 w-9 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-primary-50 hover:text-primary transition dark:bg-white/5 dark:text-gray-500 dark:hover:bg-primary-500/10"
            >
                <ChevronRight class="h-5 w-5" />
            </button>
        </div>

        <!-- Week header -->
        <div class="grid grid-cols-7 mb-2 bg-slate-50 rounded-xl py-2 dark:bg-white/5">
            <div
                v-for="day in weekDays"
                :key="day"
                class="text-center text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
            >
                {{ day.charAt(0) }}
            </div>
        </div>

        <!-- Calendar -->
        <div class="grid grid-cols-7 gap-1.5">
            <button
                v-for="(day, index) in days"
                :key="index"
                type="button"
                :disabled="!day"
                class="relative h-10 rounded-xl flex items-center justify-center text-sm transition-all duration-200"
                :class="[
                    !day
                        ? 'cursor-default'
                        : isToday(day)
                          ? 'bg-primary text-white shadow-lg shadow-primary/30 font-bold scale-105'
                          : 'text-slate-700 hover:bg-primary-50 hover:text-primary hover:-translate-y-0.5 dark:text-gray-400 dark:hover:bg-primary-500/10',
                ]"
            >
                {{ day }}

                <!-- today dot -->
                <span
                    v-if="isToday(day)"
                    class="absolute bottom-1 h-1 w-1 rounded-full bg-white dark:bg-secondary"
                />
            </button>
        </div>

        <!-- Footer -->
        <div
            class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between dark:border-white/10"
        >
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-primary animate-pulse" />

                <span class="text-xs text-slate-500 dark:text-gray-400"> Current day </span>
            </div>

            <button
                type="button"
                class="px-3 py-1.5 rounded-lg bg-slate-50 text-xs font-medium text-slate-600 hover:bg-primary-50 hover:text-primary transition dark:bg-white/5 dark:text-gray-400 dark:hover:bg-primary-500/10"
                @click="
                    currentDate = new Date(
                        today.getFullYear(),
                        today.getMonth(),
                        1,
                    )
                "
            >
                Today
            </button>
        </div>
    </div>
</template>
