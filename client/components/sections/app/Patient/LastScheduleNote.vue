<template>
    <div
        class="mt-1.5 flex items-start gap-1.5 rounded-lg bg-slate-50 px-2 py-1.5 text-[11px] text-slate-500 dark:bg-white/5 dark:text-gray-400"
    >
        <History class="mt-px h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-gray-500" />

        <p class="min-w-0">
            <span class="font-medium text-slate-600 dark:text-gray-300">
                Last schedule:
            </span>
            {{ schedule.schedule_code }}
            <span
                class="ml-0.5 rounded-full px-1.5 py-px text-[10px] font-medium"
                :class="
                    schedule.category === 'medical'
                        ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'
                        : 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300'
                "
            >
                {{ schedule.category === "medical" ? "Medical" : "ADL" }}
            </span>
            <br />
            {{ formatDate(schedule.scheduled_at) }},
            {{ formatTime(schedule.scheduled_at) }}
            <template v-if="schedule.ends_at">
                –
                <template v-if="!sameDay">
                    {{ formatDate(schedule.ends_at) }},
                </template>
                {{ formatTime(schedule.ends_at) }}
            </template>
            <template v-if="durationLabel"> ({{ durationLabel }})</template>
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { History } from "lucide-vue-next";
import type { LastSchedule } from "~/types/employee";
import { formatDate, formatTime, formatDuration } from "~/utils/time";

const props = defineProps<{
    schedule: LastSchedule;
}>();

const sameDay = computed(
    () =>
        !!props.schedule.ends_at &&
        new Date(props.schedule.scheduled_at).toDateString() ===
            new Date(props.schedule.ends_at).toDateString(),
);

const durationLabel = computed(() =>
    props.schedule.duration_minutes
        ? formatDuration(props.schedule.duration_minutes / 60)
        : "",
);
</script>
