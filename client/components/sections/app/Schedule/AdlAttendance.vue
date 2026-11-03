<script setup lang="ts">
import { computed } from "vue";
import { ArrowLeftRight, LogIn, LogOut } from "lucide-vue-next";
import type { AdlAttendanceType, ScheduleItem } from "~/types/schedule";
import { formatDate, formatTime, toLocalDateString } from "~/utils/time";

const props = defineProps<{
    schedule: ScheduleItem;
}>();

type Row = {
    attendance_id: number;
    name: string;
    type_in: AdlAttendanceType;
    type_out: AdlAttendanceType | null;
    in_at: number;
    out_at: number | null;
};

type Event =
    | { kind: "in"; at: number; who: string; how: AdlAttendanceType }
    | { kind: "out"; at: number; who: string; how: AdlAttendanceType }
    | { kind: "handover"; at: number; from: string; to: string };

const HOW: Record<string, string> = {
    scanned: "scanned",
    handover: "handover",
    force: "recorded",
    marked: "marked",
};

const KIND = {
    in: { label: "Time in", icon: LogIn, class: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" },
    handover: { label: "Shift change", icon: ArrowLeftRight, class: "bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300" },
    out: { label: "Time out", icon: LogOut, class: "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300" },
};

const ms = (value: string | null) => (value ? new Date(value).getTime() : 0);

const rows = computed<Row[]>(() =>
    (props.schedule.services ?? [])
        .filter((service) => service.type === "ADL")
        .flatMap((service) =>
            (service.assignees ?? []).flatMap((assignee) =>
                (assignee.online ?? [])
                    .filter((row) => row.in_timestamp)
                    .map((row) => ({
                        attendance_id: row.attendance_id,
                        name: assignee.full_name,
                        type_in: row.type_in,
                        type_out: row.type_out,
                        in_at: ms(row.in_timestamp),
                        out_at: row.out_timestamp ? ms(row.out_timestamp) : null,
                    })),
            ),
        )
        .sort((a, b) => a.in_at - b.in_at),
);

const events = computed<Event[]>(() => {
    const list: Event[] = [];
    const handedOver = new Set<number>();

    for (const row of rows.value) {
        const previous =
            row.type_in === "handover"
                ? rows.value.find(
                      (other) =>
                          other.attendance_id !== row.attendance_id &&
                          other.type_out === "handover" &&
                          other.out_at !== null &&
                          !handedOver.has(other.attendance_id) &&
                          Math.abs(other.out_at - row.in_at) < 120_000,
                  )
                : undefined;

        if (previous) {
            handedOver.add(previous.attendance_id);
            list.push({ kind: "handover", at: row.in_at, from: previous.name, to: row.name });
        } else {
            list.push({ kind: "in", at: row.in_at, who: row.name, how: row.type_in });
        }
    }

    for (const row of rows.value) {
        if (row.out_at && !handedOver.has(row.attendance_id)) {
            list.push({ kind: "out", at: row.out_at, who: row.name, how: row.type_out ?? "scanned" });
        }
    }

    return list.sort((a, b) => b.at - a.at);
});

const onDuty = computed(() => [...rows.value].reverse().find((row) => !row.out_at) ?? null);

const shiftChanges = computed(() => events.value.filter((e) => e.kind === "handover").length);

function dayLabel(at: number) {
    const iso = toLocalDateString(new Date(at));
    const today = toLocalDateString(new Date());
    const yesterday = toLocalDateString(new Date(Date.now() - 86_400_000));

    if (iso === today) return "Today";
    if (iso === yesterday) return "Yesterday";
    return formatDate(new Date(at));
}

const clock = (at: number) => formatTime(new Date(at).toISOString());

function detail(event: Event) {
    if (event.kind === "handover") {
        return `${event.from} timed out (handover) · ${event.to} timed in (handover)`;
    }

    const verb = event.kind === "in" ? "timed in" : "timed out";
    return `${event.who} ${verb} (${HOW[event.how] ?? event.how})`;
}
</script>

<template>
    <div class="rounded-xl border border-slate-100 p-3 dark:border-white/10">
        <p class="text-sm font-semibold text-slate-800 dark:text-white">Attendance</p>

        <div
            v-if="!rows.length"
            class="mt-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
        >
            No attendance recorded yet.
        </div>

        <template v-else>
            <div class="mt-3 grid grid-cols-3 gap-2">
                <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-white/5">
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">On duty now</p>
                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">
                        {{ onDuty ? onDuty.name : "No one" }}
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-white/5">
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">Shift changes</p>
                    <p class="text-sm font-semibold tabular-nums text-slate-800 dark:text-white">
                        {{ shiftChanges }}
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-white/5">
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">Sessions</p>
                    <p class="text-sm font-semibold tabular-nums text-slate-800 dark:text-white">
                        {{ rows.length }}
                    </p>
                </div>
            </div>

            <div
                v-if="onDuty"
                class="mt-3 flex items-center gap-3 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 dark:border-sky-500/25 dark:bg-sky-500/10"
            >
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inset-0 animate-ping rounded-full bg-sky-400 motion-reduce:animate-none" />
                    <span class="relative h-2.5 w-2.5 rounded-full bg-sky-500" />
                </span>
                <p class="text-xs text-sky-800 dark:text-sky-200">
                    <span class="font-semibold">{{ onDuty.name }}</span>
                    is on duty since {{ clock(onDuty.in_at) }}.
                </p>
            </div>

            <ol class="mt-3">
                <li
                    v-for="(event, i) in events"
                    :key="`${event.kind}-${event.at}-${i}`"
                    class="relative flex gap-3 pb-3 last:pb-0"
                >
                    <span
                        v-if="i < events.length - 1"
                        class="absolute bottom-0 left-[17px] top-9 w-px bg-slate-200 dark:bg-white/10"
                    />
                    <span
                        class="relative z-[1] flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                        :class="KIND[event.kind].class"
                    >
                        <component :is="KIND[event.kind].icon" class="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1 pt-0.5">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <span class="text-sm font-semibold text-slate-800 dark:text-white">
                                {{ KIND[event.kind].label }}
                            </span>
                            <span class="text-xs text-slate-500 dark:text-gray-400">
                                {{ event.kind === "handover" ? `${event.from} → ${event.to}` : event.who }}
                            </span>
                            <span class="ml-auto text-[11px] tabular-nums text-slate-400 dark:text-gray-500">
                                {{ dayLabel(event.at) }} · {{ clock(event.at) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            {{ detail(event) }}
                        </p>
                    </div>
                </li>
            </ol>
        </template>
    </div>
</template>
