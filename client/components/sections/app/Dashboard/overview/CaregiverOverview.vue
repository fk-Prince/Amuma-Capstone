<template>
    <div class="space-y-3">
        <div
            v-if="accessDenied"
            class="flex min-h-[60vh] flex-col items-center justify-center gap-2 rounded-xl bg-white/50 p-8 text-center dark:bg-white/5"
        >
            <p class="text-lg font-semibold text-secondary dark:text-white">
                You can't access this branch
            </p>
            <p class="max-w-md text-sm text-muted dark:text-gray-400">
                {{ accessDeniedMessage }}
            </p>
        </div>

        <div v-else-if="loading" class="space-y-3">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="h-24 animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                />
            </div>
            <div
                class="h-40 animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
            />
            <div
                class="h-[260px] animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
            />
        </div>

        <template v-else>
            <header>
                <h1
                    class="text-base font-bold tracking-tight text-secondary dark:text-white"
                >
                    My day · {{ roleLabel }}
                </h1>
                <p class="text-[11px] text-muted dark:text-gray-400">
                    {{ todayLabel }}
                </p>
            </header>

            <section class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <OverviewStatCard
                    label="Assignments today"
                    :value="blocks.length"
                    :hint="`${doneCount} done · ${blocks.length - doneCount} to go`"
                    :icon="ClipboardList"
                    tone="primary"
                />
                <OverviewStatCard
                    label="Scheduled hours"
                    :value="scheduledHours"
                    hint="Facility Shifts, Activities of Daily Living (ADL), Medical Services"
                    :icon="Clock"
                    tone="accent"
                />
                <OverviewStatCard
                    label="Patients today"
                    :value="patientCount"
                    :hint="
                        homecareCount
                            ? `${homecareCount} homecare task(s)`
                            : 'No homecare tasks'
                    "
                    :icon="Users"
                    tone="amber"
                />
            </section>

            <DutyStatusCard v-if="duty" :duty="duty" @refresh="reload" />

            <section
                class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm ring-1 ring-slate-100/60 dark:border-white/10 dark:bg-white/5 dark:ring-white/10"
            >
                <div
                    class="border-b border-slate-100 px-3 py-2.5 dark:border-white/10"
                >
                    <h2
                        class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                    >
                        Today's assignments
                    </h2>
                    <p class="text-[11px] text-muted dark:text-gray-400">
                        In the order you'll do them
                    </p>
                </div>

                <div
                    v-if="!blocks.length"
                    class="flex min-h-[160px] flex-col items-center justify-center gap-1 px-6 py-8 text-center"
                >
                    <CalendarClock
                        class="h-5 w-5 text-primary-400 dark:text-primary-300"
                    />
                    <p
                        class="text-xs font-semibold text-secondary dark:text-white"
                    >
                        No assignments today
                    </p>
                    <p class="text-[11px] text-muted dark:text-gray-400">
                        New shifts and visits assigned to you will show up here.
                    </p>
                </div>

                <ul
                    v-else
                    class="divide-y divide-slate-100 dark:divide-white/10"
                >
                    <li
                        v-for="(block, index) in blocks"
                        :key="`${block.start_at}-${index}`"
                        class="flex items-center gap-3 px-3 py-2.5"
                        :class="block.state === 'done' ? 'opacity-60' : ''"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white"
                            :class="
                                block.type === 'adl'
                                    ? 'bg-gradient-to-br from-amber-400 to-amber-600'
                                    : block.type === 'medical'
                                      ? 'bg-gradient-to-br from-violet-400 to-violet-600'
                                      : 'bg-gradient-to-br from-accent to-accent-700'
                            "
                        >
                            <HandHeart
                                v-if="block.type === 'adl'"
                                class="h-3.5 w-3.5"
                            />
                            <Stethoscope
                                v-else-if="block.type === 'medical'"
                                class="h-3.5 w-3.5"
                            />
                            <BedDouble v-else class="h-3.5 w-3.5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-xs font-semibold text-secondary dark:text-white"
                            >
                                {{ block.patient_name ?? block.title }}
                            </p>
                            <p
                                class="truncate text-[11px] text-muted dark:text-gray-400"
                            >
                                {{ block.title
                                }}<template v-if="block.setting === 'homecare'">
                                    · Homecare</template
                                ><template v-if="block.location">
                                    · {{ block.location }}</template
                                >
                            </p>
                        </div>

                        <div class="shrink-0 text-right">
                            <p
                                class="text-[11px] font-bold tabular-nums text-secondary dark:text-white"
                            >
                                {{ formatTime(block.start_at) }} –
                                {{ formatTime(block.end_at) }}
                            </p>
                            <span
                                class="mt-0.5 inline-block rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                :class="STATE_STYLES[block.state]"
                            >
                                {{ STATE_LABELS[block.state] }}
                            </span>
                        </div>
                    </li>
                </ul>
            </section>

            <section
                v-if="quickLinks.length"
                class="grid grid-cols-2 gap-2 sm:grid-cols-3"
            >
                <NuxtLink
                    v-for="link in quickLinks"
                    :key="link.label"
                    :to="link.to"
                    class="flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-xs font-semibold text-secondary shadow-sm transition-colors hover:bg-light dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
                >
                    <component
                        :is="link.icon"
                        class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-300"
                    />
                    <span class="truncate">{{ link.label }}</span>
                </NuxtLink>
            </section>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, toRef, watch } from "vue";
import {
    BedDouble,
    CalendarClock,
    ClipboardList,
    Clock,
    HandHeart,
    MessageSquare,
    Stethoscope,
    Users,
} from "lucide-vue-next";

import DutyStatusCard from "~/components/sections/app/Dashboard/DutyStatusCard.vue";
import OverviewStatCard from "~/components/sections/app/Dashboard/overview/OverviewStatCard.vue";import { useBranchOverview } from "~/composables/useBranchOverview";
import { usePermissions } from "~/composables/usePermission";
import { Modules } from "~/types/module";

const props = withDefaults(
    defineProps<{
        branchUuid: string;
        roleLabel?: string;
    }>(),
    { roleLabel: "Caregiver" },
);

const { overview, loading, accessDenied, accessDeniedMessage, reload } =
    useBranchOverview(toRef(props, "branchUuid"));

const { hasModule } = usePermissions();

const duty = computed(() => overview.value.my_duty);

const TICK_MS = 30000;
const clockOffset = ref(0);
const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | null = null;

const tick = () => {
    now.value = Date.now() + clockOffset.value;
};

watch(
    duty,
    (value) => {
        const serverNow = value ? Date.parse(value.server_now) : NaN;

        if (!Number.isNaN(serverNow)) {
            clockOffset.value = serverNow - Date.now();
        }

        tick();
    },
    { immediate: true },
);

onMounted(() => {
    timer = setInterval(tick, TICK_MS);
});

onBeforeUnmount(() => {
    if (timer) clearInterval(timer);
});

type BlockState = "done" | "now" | "upcoming";

const STATE_LABELS: Record<BlockState, string> = {
    done: "Done",
    now: "In progress",
    upcoming: "Upcoming",
};

const STATE_STYLES: Record<BlockState, string> = {
    done: "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300",
    now: "bg-accent-500/10 text-accent-700 dark:text-accent-300",
    upcoming:
        "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
};

const blocks = computed(() =>
    (duty.value?.blocks ?? []).map((block) => {
        const start = Date.parse(block.start_at);
        const end = Date.parse(block.end_at);

        const state: BlockState =
            now.value >= end ? "done" : now.value >= start ? "now" : "upcoming";

        return { ...block, state };
    }),
);

const doneCount = computed(
    () => blocks.value.filter((block) => block.state === "done").length,
);

const homecareCount = computed(
    () =>
        blocks.value.filter((block) => block.setting === "homecare").length,
);

const patientCount = computed(
    () =>
        new Set(blocks.value.map((block) => block.patient_name).filter(Boolean))
            .size,
);

const scheduledHours = computed(() => {
    const minutes = blocks.value.reduce(
        (sum, block) => sum + (block.total_minutes || 0),
        0,
    );

    return `${Math.round((minutes / 60) * 10) / 10} hrs`;
});

const todayLabel = computed(() =>
    new Date(now.value).toLocaleDateString("en-US", {
        weekday: "long",
        month: "long",
        day: "numeric",
    }),
);

const formatTime = (value: string) =>
    new Date(value).toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
    });

const quickLinks = computed(() => {
    const base = `/app/branches/${props.branchUuid}`;

    return [
        {
            label: "My patients",
            icon: Users,
            to: `${base}/patients?assignment=mine`,
            show: hasModule(Modules.Patients),
        },
        {
            label: "Schedule",
            icon: CalendarClock,
            to: `${base}/schedules?assignment=mine`,
            show: hasModule(Modules.Schedules),
        },
        {
            label: "Messages",
            icon: MessageSquare,
            to: `${base}/messages`,
            show: true,
        },
    ].filter((link) => link.show);
});
</script>
