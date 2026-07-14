<script setup>
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

    const result = [];

    for (let i = 0; i < firstDay; i++) {
        result.push(null);
    }

    for (let i = 1; i <= totalDays; i++) {
        result.push(i);
    }

    return result;
});

function previousMonth() {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() - 1,
        1,
    );
}

function nextMonth() {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() + 1,
        1,
    );
}

function isToday(day) {
    return (
        day === today.getDate() &&
        currentDate.value.getMonth() === today.getMonth() &&
        currentDate.value.getFullYear() === today.getFullYear()
    );
}
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm p-5 w-full max-w-sm">
        <div class="flex items-center justify-between mb-5">
            <button
                @click="previousMonth"
                class="w-8 h-8 rounded-lg pr-1 flex justify-center items-center"
            >
                <ChevronLeft />
            </button>

            <div class="text-center">
                <h2 class="font-bold">{{ monthName }} {{ year }}</h2>
            </div>

            <button
                @click="nextMonth"
                class="w-8 h-8 rounded-lg pl-1 flex justify-center items-center"
            >
                <ChevronRight />
            </button>
        </div>

        <div class="grid grid-cols-7 mb-2">
            <div
                v-for="day in weekDays"
                :key="day"
                class="text-center text-xs font-semibold text-gray-400"
            >
                {{ day }}
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <div
                v-for="(day, index) in days"
                :key="index"
                class="h-10 flex items-center justify-center rounded-lg text-sm"
                :class="
                    isToday(day)
                        ? 'bg-primary text-white font-bold'
                        : 'text-gray-700'
                "
            >
                {{ day }}
            </div>
        </div>
    </div>
</template>
