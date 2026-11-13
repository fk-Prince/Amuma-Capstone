<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[49] flex items-center justify-center p-4"
            >
                <div
                    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                    @click="emit('close')"
                />

                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="attention-title"
                    class="relative z-50 flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-light shadow-2xl ring-1 ring-black/5 dark:bg-surface dark:ring-white/10"
                >
                    <div
                        class="flex shrink-0 items-center justify-between gap-4 px-5 py-4"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300"
                            >
                                <TriangleAlert class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <h2
                                    id="attention-title"
                                    class="text-lg font-bold tracking-tight text-secondary dark:text-white"
                                >
                                    Needs attention
                                </h2>
                                <p
                                    class="text-xs text-muted dark:text-gray-400"
                                >
                                    Discharges, unstaffed services and
                                    unassigned schedules
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            aria-label="Close"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:text-white"
                            @click="emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="min-h-0 space-y-3 overflow-y-auto px-5 pb-5">
                        <div
                            class="grid grid-cols-1 gap-2"
                            :class="
                                groups.length === 3
                                    ? 'sm:grid-cols-3'
                                    : 'sm:grid-cols-2'
                            "
                        >
                            <button
                                v-for="group in groups"
                                :key="group.key"
                                type="button"
                                class="flex flex-col gap-1.5 rounded-xl border p-3 text-left transition"
                                :class="
                                    active === group.key
                                        ? group.activeClass
                                        : 'border-slate-200 bg-white hover:border-slate-300 dark:border-white/10 dark:bg-white/5 dark:hover:border-white/20'
                                "
                                :aria-pressed="active === group.key"
                                @click="active = group.key"
                            >
                                <span
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span
                                        class="text-xs font-semibold text-slate-600 dark:text-gray-300"
                                    >
                                        {{ group.title }}
                                    </span>
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                                        :class="group.iconClass"
                                    >
                                        <component
                                            :is="group.icon"
                                            class="h-4 w-4"
                                        />
                                    </span>
                                </span>
                                <span
                                    class="text-2xl font-bold tabular-nums leading-none text-secondary dark:text-white"
                                >
                                    {{ group.count }}
                                </span>
                                <span
                                    class="text-[11px] text-muted dark:text-gray-400"
                                >
                                    {{ group.count ? group.note : "All clear" }}
                                </span>
                            </button>
                        </div>

                        <section
                            v-if="activeGroup"
                            class="rounded-xl border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-white/5"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                                        :class="activeGroup.iconClass"
                                    >
                                        <component
                                            :is="activeGroup.icon"
                                            class="h-4 w-4"
                                        />
                                    </span>
                                    <div>
                                        <h3
                                            class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                                        >
                                            {{ activeGroup.title }}
                                        </h3>
                                        <p
                                            class="text-[11px] text-muted dark:text-gray-400"
                                        >
                                            {{ activeGroup.subtitle }}
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-bold"
                                    :class="activeGroup.iconClass"
                                >
                                    {{ activeGroup.count }}
                                </span>
                            </div>

                            <p
                                v-if="!activeRows.length"
                                class="py-6 text-center text-xs text-muted dark:text-gray-400"
                            >
                                Nothing needs attention here.
                            </p>

                            <ul
                                v-else
                                class="mt-2 divide-y divide-slate-100 dark:divide-white/10"
                            >
                                <li
                                    v-for="row in activeRows"
                                    :key="row.key"
                                    class="flex items-center justify-between gap-3 py-2.5"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="flex min-w-0 items-center gap-1.5 text-sm font-semibold text-slate-800 dark:text-white"
                                        >
                                            <span class="truncate">{{
                                                row.title
                                            }}</span>
                                            <span
                                                v-if="row.tag"
                                                class="shrink-0 rounded-md border border-slate-200 px-1.5 py-px text-[10px] font-semibold text-slate-600 dark:border-white/10 dark:text-gray-300"
                                            >
                                                {{ row.tag }}
                                            </span>
                                        </p>
                                        <p
                                            class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                                        >
                                            {{ row.subtitle }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-2"
                                    >
                                        <span
                                            v-if="row.day"
                                            class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                            :class="DAY_TONES[row.day.tone]"
                                        >
                                            {{ row.day.label }}
                                        </span>

                                        <NuxtLink
                                            v-if="row.to"
                                            :to="row.to"
                                            class="rounded-md border px-2.5 py-1 text-[11px] font-semibold transition"
                                            :class="activeGroup.buttonClass"
                                        >
                                            {{ row.action }}
                                        </NuxtLink>
                                    </div>
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import {
    DoorOpen,
    Stethoscope,
    TriangleAlert,
    UserX,
    X,
} from "lucide-vue-next";
import type { RouteLocationRaw } from "vue-router";
import { useBranchPlan } from "~/composables/useBranchPlan";
import { usePermissions } from "~/composables/usePermission";
import type { AttentionData, AttentionGroup } from "~/types/attention";
import { Modules } from "~/types/module";
import { formatDate, formatTime, getLocalDateStr } from "~/utils/time";

type DayTone = "overdue" | "today" | "soon" | "later";

interface Row {
    key: string;
    title: string;
    tag?: string;
    subtitle: string;
    day?: { label: string; tone: DayTone };
    to?: RouteLocationRaw;
    action?: string;
}

const props = defineProps<{
    open: boolean;
    data: AttentionData;
    branchUuid: string;
}>();

const emit = defineEmits<{ (e: "close"): void }>();

const DAY_TONES: Record<DayTone, string> = {
    overdue: "bg-rose-600 text-white",
    today: "bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300",
    soon: "bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300",
    later: "bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-400",
};

const ROLE_TAGS = {
    nurse: "Needs nurse",
    caregiver: "Needs caregiver",
    both: "Needs staff",
} as const;

const ASSIGN_LABELS = {
    nurse: "Assign Nurse",
    caregiver: "Assign Caregiver",
    both: "Assign Staff",
} as const;

const { hasModule, canAssign } = usePermissions();
const { hasFacilityPlan } = useBranchPlan();

const base = computed(() => `/app/branches/${props.branchUuid}`);

function startOfDay(date: Date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function dayInfo(value: string): { label: string; tone: DayTone } {
    const diff = Math.round(
        (startOfDay(new Date(value)).getTime() -
            startOfDay(new Date()).getTime()) /
            86_400_000,
    );

    if (diff < 0) return { label: "Overdue", tone: "overdue" };
    if (diff === 0) return { label: "Today", tone: "today" };
    if (diff === 1) return { label: "Tomorrow", tone: "soon" };

    return { label: `In ${diff} days`, tone: "later" };
}

function summarize(labels: string[]) {
    const counts = new Map<string, number>();

    labels.forEach((label) => counts.set(label, (counts.get(label) ?? 0) + 1));

    return [...counts.entries()]
        .map(([label, count]) => `${count} ${label.toLowerCase()}`)
        .join(" · ");
}

function roleNote(count: number, role: string) {
    return `${count} ${count === 1 ? "needs" : "need"} a ${role}`;
}

function dateTime(value: string) {
    return `${formatDate(value)}, ${formatTime(value)}`;
}

function scheduleDate(value: string) {
    const date = new Date(value);
    const today = new Date();

    return getLocalDateStr(date < startOfDay(today) ? today : date);
}

const dischargeRows = computed<Row[]>(() => {
    const canOpen = hasModule(Modules.Admissions);

    return props.data.discharges.map((item, index) => ({
        key: `discharge-${item.patient_uuid ?? index}`,
        title: item.patient_name || "Unnamed patient",
        subtitle: [
            item.room_no ? `Room ${item.room_no}` : null,
            item.bed_no ? `Bed ${item.bed_no}` : null,
            `Ends ${dateTime(item.discharge_at)}`,
        ]
            .filter(Boolean)
            .join(" · "),
        day: dayInfo(item.discharge_at),
        to:
            canOpen && item.patient_uuid
                ? `${base.value}/admissions/${item.patient_uuid}`
                : undefined,
        action: "Open",
    }));
});

const serviceRows = computed<Row[]>(() => {
    const canAssignServices =
        hasModule(Modules.Services) && canAssign(Modules.Services);

    return props.data.services.map((item) => {
        const role = item.performed_by ?? "both";

        return {
            key: `service-${item.service_uuid}`,
            title: item.service_name,
            tag: ROLE_TAGS[role],
            subtitle: [item.category_name, item.type_formatted]
                .filter(Boolean)
                .join(" · "),
            to: canAssignServices
                ? {
                      path: `${base.value}/services`,
                      query: { assign: item.service_uuid },
                  }
                : undefined,
            action: ASSIGN_LABELS[role],
        };
    });
});

const scheduleRows = computed<Row[]>(() => {
    const canAssignSchedules =
        hasModule(Modules.Schedules) && canAssign(Modules.Schedules);

    return props.data.schedules.map((item) => {
        const date = scheduleDate(item.scheduled_at);

        return {
            key: `schedule-${item.schedule_code}`,
            title: item.patient_name || "Unnamed patient",
            subtitle: [
                item.schedule_code,
                dateTime(item.scheduled_at),
                item.services.join(", "),
            ]
                .filter(Boolean)
                .join(" · "),
            day: dayInfo(item.scheduled_at),
            to: canAssignSchedules
                ? {
                      path: `${base.value}/schedules`,
                      query: {
                          ...(item.type === "adl" ? { type: "adl" } : {}),
                          date_from: date,
                          date_to: date,
                          search: item.schedule_code,
                          assign: item.schedule_code,
                      },
                  }
                : undefined,
            action: "Assign",
        };
    });
});

const groups = computed(() => {
    const services = props.data.services;
    const nurse = services.filter((s) => s.performed_by === "nurse").length;
    const caregiver = services.filter(
        (s) => s.performed_by === "caregiver",
    ).length;
    const either = services.length - nurse - caregiver;

    return [
        {
            key: "discharges" as AttentionGroup,
            title: "Discharging soon",
            subtitle: "Admissions ending within 3 days",
            icon: DoorOpen,
            iconClass:
                "bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300",
            activeClass:
                "border-primary-300 bg-primary-50 dark:border-primary-500/40 dark:bg-primary-500/10",
            buttonClass:
                "border-primary/20 bg-primary/[0.04] text-primary hover:bg-primary/10",
            rows: dischargeRows.value,
            note: summarize(dischargeRows.value.map((r) => r.day!.label)),
        },
        {
            key: "services" as AttentionGroup,
            title: "Services without staff",
            subtitle: "Available services with no nurse or caregiver assigned",
            icon: Stethoscope,
            iconClass:
                "bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300",
            activeClass:
                "border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10",
            buttonClass:
                "border-amber-600/20 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300",
            rows: serviceRows.value,
            note: [
                nurse ? roleNote(nurse, "nurse") : null,
                caregiver ? roleNote(caregiver, "caregiver") : null,
                either ? roleNote(either, "nurse or caregiver") : null,
            ]
                .filter(Boolean)
                .join(" · "),
        },
        {
            key: "schedules" as AttentionGroup,
            title: "Unassigned schedules",
            subtitle: "Visits in the next 3 days with no staff assigned",
            icon: UserX,
            iconClass:
                "bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300",
            activeClass:
                "border-rose-300 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10",
            buttonClass:
                "border-rose-600/20 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300",
            rows: scheduleRows.value,
            note: summarize(scheduleRows.value.map((r) => r.day!.label)),
        },
    ]
        .filter((group) => group.key !== "discharges" || hasFacilityPlan.value)
        .map((group) => ({ ...group, count: group.rows.length }));
});

const active = ref<AttentionGroup | null>(null);

const activeGroup = computed(
    () =>
        groups.value.find((g) => g.key === active.value) ??
        groups.value.find((g) => g.count > 0) ??
        groups.value[0],
);

const activeRows = computed(() => activeGroup.value?.rows ?? []);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen)
            active.value = groups.value.find((g) => g.count > 0)?.key ?? null;
    },
);

function onKeydown(event: KeyboardEvent) {
    if (event.key === "Escape" && props.open) emit("close");
}

onMounted(() => document.addEventListener("keydown", onKeydown));
onUnmounted(() => document.removeEventListener("keydown", onKeydown));
</script>
