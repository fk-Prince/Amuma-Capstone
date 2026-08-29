<script setup lang="ts">
import { CalendarClock, Activity, Utensils, Users, Plus, Pencil } from "lucide-vue-next";
import type { PatientActivity } from "~/types/patient-activity";

withDefaults(
    defineProps<{
        activities?: PatientActivity[];
    }>(),
    {
        activities: () => [],
    },
);

const emit = defineEmits<{
    (e: "add-activity"): void;
    (e: "edit-activity", activity: PatientActivity): void;
}>();

const typeStyles: Record<
    string,
    { icon: any; bg: string; text: string; label: string }
> = {
    appointment: {
        icon: CalendarClock,
        bg: "bg-violet-50",
        text: "text-violet-600",
        label: "Appointment",
    },
    therapy: {
        icon: Activity,
        bg: "bg-emerald-50",
        text: "text-emerald-600",
        label: "Therapy",
    },
    meal: {
        icon: Utensils,
        bg: "bg-primary-50",
        text: "text-primary-600",
        label: "Meal",
    },
    activity: {
        icon: Users,
        bg: "bg-amber-50",
        text: "text-amber-600",
        label: "Activity",
    },
};

function styleFor(type: string) {
    return typeStyles[type] ?? typeStyles.activity;
}

function formatOccurredAt(value: string) {
    if (!value) return "";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return date.toLocaleString(undefined, {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-end">
            <button
                type="button"
                class="rounded-xl bg-primary flex items-center gap-3 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary/50"
                @click="emit('add-activity')"
            >
                <Plus class="h-4 w-4" />

                Add Activity
            </button>
        </div>

        <div
            class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm"
        >
            <p
                v-if="!activities.length"
                class="py-16 text-center text-sm text-slate-400"
            >
                No activities recorded yet.
            </p>

            <ul v-else class="divide-y divide-slate-50">
                <li
                    v-for="item in activities"
                    :key="item.id"
                    class="flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50/60"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                        :class="[
                            styleFor(item.type).bg,
                            styleFor(item.type).text,
                        ]"
                    >
                        <component :is="styleFor(item.type).icon" class="h-5 w-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800">
                            {{ item.title }}
                        </p>
                        <p v-if="item.subtitle" class="text-xs text-slate-400">
                            {{ item.subtitle }}
                        </p>
                        <p
                            v-if="item.description"
                            class="mt-0.5 line-clamp-1 text-xs text-slate-400"
                        >
                            {{ item.description }}
                        </p>
                    </div>

                    <span class="shrink-0 text-xs text-slate-400">
                        {{ formatOccurredAt(item.occurredAt) }}
                    </span>

                    <span
                        class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-medium"
                        :class="[
                            styleFor(item.type).bg,
                            styleFor(item.type).text,
                        ]"
                    >
                        {{ styleFor(item.type).label }}
                    </span>

                    <button
                        type="button"
                        class="shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-slate-100"
                        @click="emit('edit-activity', item)"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
