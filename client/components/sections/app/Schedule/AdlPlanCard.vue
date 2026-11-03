<template>
    <section
        class="overflow-hidden rounded-2xl border border-slate-200 dark:border-white/10"
    >
        <div class="h-1.5" :class="STRIPE[status] ?? STRIPE.pending" />

        <div class="space-y-3 p-4">
            <div class="flex flex-wrap items-center gap-1.5">
                <span
                    class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold"
                    :class="scheduleStatusTheme(status).badge"
                >
                    {{ scheduleStatusLabel(status) }}
                </span>
                <span
                    class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-white/10 dark:text-gray-300"
                >
                    ADL
                </span>
                <span class="ml-auto font-mono text-[10px] text-slate-400 dark:text-gray-500">
                    {{ plan.schedule.schedule_code }}
                </span>
            </div>

            <p class="text-base font-bold leading-snug text-slate-800 dark:text-white">
                {{ plan.patient }}
            </p>

            <dl class="space-y-2 text-xs text-slate-700 dark:text-gray-200">
                <div class="flex gap-2">
                    <dt><CalendarDays class="h-3.5 w-3.5 text-slate-400" /></dt>
                    <dd>
                        <template v-if="plan.hours < 24">
                            <span class="font-semibold">{{ formatIso(plan.start, { weekday: "long", month: "short", day: "numeric" }) }}</span>
                            <span class="block text-slate-500 dark:text-gray-400">
                                {{ formatTime(new Date(plan.startAt).toISOString()) }} –
                                {{ formatTime(new Date(plan.startAt + plan.hours * 3_600_000).toISOString()) }}
                                · {{ plan.label }}
                            </span>
                        </template>
                        <template v-else>
                            <span class="font-semibold">
                                {{ formatIso(plan.start, { month: "short", day: "numeric" }) }} –
                                {{ formatIso(plan.end, { month: "short", day: "numeric" }) }}
                            </span>
                            <span class="block text-slate-500 dark:text-gray-400">
                                {{ plan.label }} · {{ plan.hours }} hrs
                            </span>
                        </template>
                    </dd>
                </div>

                <div v-if="plan.schedule.address" class="flex gap-2">
                    <dt><MapPin class="h-3.5 w-3.5 text-slate-400" /></dt>
                    <dd>{{ plan.schedule.address }}</dd>
                </div>

                <div class="flex gap-2">
                    <dt><Users class="h-3.5 w-3.5 text-slate-400" /></dt>
                    <dd v-if="plan.caregivers.length">
                        {{ plan.caregivers.map((c) => c.full_name).join(" · ") }}
                    </dd>
                    <dd
                        v-else
                        class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                    >
                        <UserX class="h-3 w-3" />
                        No caregiver assigned yet
                    </dd>
                </div>
            </dl>

            <div v-if="progress">
                <div
                    class="mb-1 flex justify-between text-[11px] font-semibold text-slate-500 dark:text-gray-400"
                >
                    <span>{{ progress.label }}</span>
                    <span>{{ progress.percent }}%</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                    <div
                        class="h-full rounded-full bg-primary"
                        :style="{ width: `${progress.percent}%` }"
                    />
                </div>
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-primary hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
                    @click="emit('details')"
                >
                    Details
                </button>
                <button
                    v-if="canAssign && isOpen"
                    type="button"
                    class="flex-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    :class="
                        plan.caregivers.length
                            ? 'border border-primary/20 bg-primary/5 text-primary hover:bg-primary/10'
                            : 'bg-rose-500 text-white shadow-sm hover:bg-rose-600'
                    "
                    @click="emit('assign')"
                >
                    {{ plan.caregivers.length ? "Change caregiver" : "Assign caregiver" }}
                </button>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { CalendarDays, MapPin, Users, UserX } from "lucide-vue-next";
import type { AdlPlan } from "~/utils/adlPlan";
import { daysBetween, formatIso } from "~/composables/useCalendarMonth";
import { scheduleStatusLabel, scheduleStatusTheme } from "~/utils/schedule-status";
import { formatTime } from "~/utils/time";

const props = defineProps<{
    plan: AdlPlan;
    today: string;
    canAssign: boolean;
}>();

const emit = defineEmits<{
    (e: "details"): void;
    (e: "assign"): void;
}>();

const STRIPE: Record<string, string> = {
    pending: "bg-violet-500",
    ongoing: "bg-emerald-500",
    completed: "bg-sky-500",
    missed: "bg-rose-500",
    cancelled: "bg-slate-400",
};

const status = computed(() => props.plan.schedule.status?.toLowerCase() ?? "pending");
const isOpen = computed(() => ["pending", "ongoing"].includes(status.value));

const progress = computed(() => {
    const { plan, today } = props;
    if (status.value !== "ongoing" || today < plan.start || today > plan.end) return null;

    if (plan.hours < 24) {
        const elapsed = Math.min(Math.max((Date.now() - plan.startAt) / 3_600_000, 0), plan.hours);
        const h = Math.floor(elapsed);
        const m = Math.round((elapsed - h) * 60);
        return {
            label: `${h}h ${m}m of ${plan.hours}h`,
            percent: Math.round((elapsed / plan.hours) * 100),
        };
    }

    const day = daysBetween(plan.start, today) + 1;
    return {
        label: `Day ${day} of ${plan.days}`,
        percent: Math.round((day / plan.days) * 100),
    };
});
</script>
