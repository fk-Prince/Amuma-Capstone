<template>
    <div class="rounded-2xl bg-white">
        <div
            class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3 class="text-base font-semibold text-slate-800">
                    Online Schedule Audit Log
                </h3>

                <p class="mt-0.5 text-sm text-slate-400">
                    QR check-in / check-out history for scheduled visits.
                </p>
            </div>

            <div class="flex w-full gap-2 sm:w-auto">
                <input
                    v-model="selectedDate"
                    type="date"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700"
                />

                <div class="relative w-full sm:w-64">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search employee or schedule code..."
                        class="w-full rounded-lg border border-slate-200 py-2 pl-3 pr-3 text-sm"
                    />
                </div>
            </div>
        </div>

        <div v-if="loading" class="space-y-3 p-5">
            <div
                v-for="i in 4"
                :key="i"
                class="h-40 animate-pulse rounded-xl bg-slate-100"
            />
        </div>

        <div
            v-else-if="!filteredLogs.length"
            class="p-12 text-center text-sm text-slate-400"
        >
            No audit records found
        </div>

        <div v-else class="space-y-4 p-5">
            <div
                v-for="log in filteredLogs"
                :key="`${log.schedule_id}-${log.employee_id}`"
                class="rounded-xl border border-slate-200 bg-white p-5"
            >
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h4 class="text-xl font-semibold text-slate-800">
                            {{ log.schedule_code }}
                        </h4>

                        <p class="text-[14px] text-slate-400">
                            {{ formatDateTime(log.scheduled_at) }}
                        </p>
                        <p class="text-[13px] text-slate-400">
                            {{ log.address }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <div
                            class="rounded-xl border border-primary/20 bg-primary/5 px-4 py-2"
                        >
                            <p class="text-[10px] uppercase text-primary/60">
                                Scheduled Hours
                            </p>

                            <p class="text-sm font-bold text-primary">
                                {{ formatBookedHours(log.total_hours) }}
                            </p>
                        </div>

                        <div
                            class="rounded-xl border px-4 py-2"
                            :class="{
                                'border-amber-200 bg-amber-50':
                                    log.status === 'pending',
                                'border-blue-200 bg-blue-50':
                                    log.status === 'ongoing',
                                'border-green-200 bg-green-50':
                                    log.status === 'completed',
                                'border-red-200 bg-red-50':
                                    log.status === 'cancelled',
                            }"
                        >
                            <p class="text-[10px] uppercase text-slate-400">
                                Status
                            </p>

                            <p
                                class="text-sm font-bold capitalize"
                                :class="{
                                    'text-amber-700': log.status === 'pending',
                                    'text-blue-700': log.status === 'ongoing',
                                    'text-green-700':
                                        log.status === 'completed',
                                    'text-red-700': log.status === 'cancelled',
                                }"
                            >
                                {{ log.status }}
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="mt-4 flex items-center gap-3 border-b border-slate-100 pb-4"
                >
                    <template v-if="log.full_name">
                        <img
                            v-if="log.avatar"
                            :src="log.avatar"
                            class="h-10 w-10 rounded-full object-cover"
                        />

                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                {{ log.full_name }}
                            </p>

                            <p class="text-xs text-slate-400">
                                {{ log.address ?? "—" }}
                            </p>
                        </div>
                    </template>

                    <template v-else>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700"
                        >
                            !
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-amber-700">
                                Service is unassigned
                            </p>

                            <p class="text-xs text-slate-400">
                                No employee has been assigned yet
                            </p>
                        </div>
                    </template>
                </div>

                <div
                    class="mt-4 flex items-center justify-between rounded-lg border border-primary/20 bg-primary/5 px-4 py-3"
                >
                    <div>
                        <p class="text-[11px] uppercase text-black">
                            Currently Total Hours Worked
                        </p>

                        <p class="text-sm font-bold text-primary">
                            {{ formatMinutes(log.total_worked_minutes) }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <p class="text-xs font-semibold uppercase text-slate-400">
                        QR Scan History
                    </p>
                    <div
                        v-if="!log.online_logs.length"
                        class="rounded-lg border border-slate-100 bg-slate-50 p-4 text-sm text-slate-400"
                    >
                        No scan history available
                    </div>

                    <div
                        v-else
                        v-for="(scan, index) in log.online_logs"
                        :key="index"
                        class="rounded-lg border border-slate-100 bg-slate-50 p-4"
                    >
                        <div class="grid gap-4 lg:grid-cols-4">
                            <div>
                                <p class="text-[11px] uppercase text-slate-400">
                                    Check-in
                                </p>

                                <p class="text-sm text-slate-700">
                                    {{
                                        scan.in_timestamp
                                            ? formatDateTime(scan.in_timestamp)
                                            : "Not checked in"
                                    }}
                                </p>

                                <p
                                    v-if="scan.qr_in"
                                    class="text-[11px] text-emerald-600"
                                >
                                    QR scanned
                                </p>
                            </div>

                            <!-- Out -->
                            <div>
                                <p class="text-[11px] uppercase text-slate-400">
                                    Check-out
                                </p>

                                <p class="text-sm text-slate-700">
                                    {{
                                        scan.out_timestamp
                                            ? formatDateTime(scan.out_timestamp)
                                            : "Not checked out"
                                    }}
                                </p>

                                <p
                                    v-if="scan.qr_out"
                                    class="text-[11px] text-emerald-600"
                                >
                                    QR scanned
                                </p>
                            </div>

                            <!-- Duration -->
                            <div>
                                <p class="text-[11px] uppercase text-slate-400">
                                    Worked
                                </p>

                                <p class="text-sm font-semibold text-slate-700">
                                    {{ duration(scan) }}
                                </p>
                            </div>

                            <!-- Status -->
                            <div>
                                <p class="text-[11px] uppercase text-slate-400">
                                    Status
                                </p>

                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                    :class="
                                        scan.out_timestamp
                                            ? 'bg-primary/10 text-primary'
                                            : 'bg-amber-100 text-amber-700'
                                    "
                                >
                                    {{
                                        scan.out_timestamp
                                            ? "Completed"
                                            : "Ongoing"
                                    }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="scan.notes"
                            class="mt-3 border-t border-slate-200 pt-3 text-xs text-slate-500"
                        >
                            {{ scan.notes }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, ref } from "vue";
import type { ScheduleItem } from "~/types/schedule";

interface AuditRow {
    schedule_id: number;
    schedule_code: string;
    scheduled_at?: string | null;
    total_hours: number;
    status: string;

    employee_id: number | null;
    full_name: string | null;
    avatar: string | null;
    role: string | null;
    address: string | null;
    patient_full_name: string;
    total_worked_minutes: number;

    online_logs: {
        qr_in: string | null;
        qr_out: string | null;
        in_timestamp: string | null;
        out_timestamp: string | null;
        notes: string | null;
    }[];
}

const props = withDefaults(
    defineProps<{
        logs?: ScheduleItem[];
        loading?: boolean;
    }>(),
    {
        logs: () => [],
        loading: false,
    },
);

const search = ref("");
const selectedDate = ref(new Date().toISOString().slice(0, 10));
const filteredLogs = computed<AuditRow[]>(() => {
    const rows: AuditRow[] = props.logs.flatMap((schedule) =>
        (schedule.services ?? []).flatMap((service): AuditRow[] => {
            const assignees = service.assignees ?? [];

            if (assignees.length) {
                return assignees.map((assignee): AuditRow => {
                    const online_logs = (assignee.online ?? []).map((scan) => ({
                        qr_in: scan.qr_in ?? null,
                        qr_out: scan.qr_out ?? null,
                        in_timestamp: scan.in_timestamp ?? null,
                        out_timestamp: scan.out_timestamp ?? null,
                        notes: scan.notes ?? null,
                    }));

                    return {
                        status: schedule.status,
                        schedule_id: schedule.schedule_id,
                        schedule_code: schedule.schedule_code,
                        scheduled_at: schedule.scheduled_at ?? null,

                        total_hours:
                            service.hours_booked ?? schedule.total_hours ?? 0,

                        employee_id: assignee.employee_id,

                        full_name: assignee.full_name ?? null,
                        avatar: assignee.avatar ?? null,
                        role: assignee.role ?? null,

                        address: schedule.patient?.address ?? null,
                        patient_full_name: schedule.patient?.full_name ?? "",

                        online_logs,

                        total_worked_minutes: online_logs.reduce(
                            (total, scan) => total + workedMinutes(scan),
                            0,
                        ),
                    };
                });
            }

            return [
                {
                    status: schedule.status,

                    schedule_id: schedule.schedule_id,
                    schedule_code: schedule.schedule_code,
                    scheduled_at: schedule.scheduled_at ?? null,

                    total_hours:
                        service.hours_booked ?? schedule.total_hours ?? 0,

                    employee_id: null,
                    patient_full_name: schedule.patient?.full_name ?? "",

                    full_name: null,
                    avatar: null,
                    role: null,
                    address: schedule.patient?.address ?? null,

                    online_logs: [],

                    total_worked_minutes: 0,
                } satisfies AuditRow,
            ];
        }),
    );

    const query = search.value.trim().toLowerCase();

    return rows.filter((row) => {
        const searchMatch =
            !query ||
            row.schedule_code.toLowerCase().includes(query) ||
            row.full_name?.toLowerCase().includes(query);

        const dateMatch =
            !selectedDate.value ||
            (row.scheduled_at &&
                new Date(row.scheduled_at).toISOString().slice(0, 10) ===
                    selectedDate.value);

        return searchMatch && dateMatch;
    });
});
function initials(name?: string) {
    if (!name) return "?";

    return name
        .split(" ")
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
}

function formatDateTime(value?: string | null) {
    if (!value) return "—";

    return new Date(value).toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
}

function formatScheduleDate(value?: string | null) {
    if (!value) return "—";

    return new Date(value).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}
function workedMinutes(scan: {
    in_timestamp: string | null;
    out_timestamp: string | null;
}) {
    if (!scan.in_timestamp) {
        return 0;
    }

    const start = new Date(scan.in_timestamp).getTime();

    const end = scan.out_timestamp
        ? new Date(scan.out_timestamp).getTime()
        : Date.now();

    return Math.max(0, Math.round((end - start) / 60000));
}

function duration(scan: {
    in_timestamp: string | null;
    out_timestamp: string | null;
}) {
    const minutes = workedMinutes(scan);

    if (!scan.in_timestamp) {
        return "—";
    }

    return formatMinutes(minutes);
}

function formatMinutes(minutes: number) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    return `${hours}h ${mins}m`;
}
function formatBookedHours(hours: number) {
    if (!hours || hours <= 0) {
        return "0 hrs";
    }

    const months = Math.floor(hours / 720);
    const days = Math.floor((hours % 720) / 24);
    const remainingHours = hours % 24;

    const parts = [];

    if (months) {
        parts.push(`${months} month${months > 1 ? "s" : ""}`);
    }

    if (days) {
        parts.push(`${days} day${days > 1 ? "s" : ""}`);
    }

    if (remainingHours) {
        parts.push(`${remainingHours} hr${remainingHours > 1 ? "s" : ""}`);
    }

    return parts.join(" ");
}
</script>
