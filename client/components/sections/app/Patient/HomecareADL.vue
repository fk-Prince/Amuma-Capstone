<template>
    <div class="rounded-2xl bg-white font-sans">
        <div
            class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3 class="text-base font-semibold text-secondary">
                    Online Schedule Audit Log
                </h3>

                <p class="mt-0.5 text-sm text-muted">
                    QR check-in / check-out history for scheduled visits.
                </p>
            </div>

            <div class="flex w-full gap-2 sm:w-auto">
                <ActionButton
                    v-if="variant === 1"
                    variant="primary"
                    @click="showScanner = true"
                >
                    Scan QR
                </ActionButton>

                <div v-if="variant === 1" class="relative w-full sm:w-64">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search employee or schedule code..."
                        class="w-full rounded-lg border border-muted-light py-2 pl-3 pr-3 text-sm text-secondary focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary"
                    />
                </div>
            </div>
        </div>

        <div v-if="loading" class="space-y-3 p-5">
            <div
                v-for="i in 4"
                :key="i"
                class="h-40 animate-pulse rounded-xl bg-muted-light"
            />
        </div>

        <div
            v-else-if="!filteredLogs.length"
            class="p-12 text-center text-sm text-muted"
        >
            No audit records found
        </div>

        <div v-else class="space-y-4 p-5">
            <div
                v-for="log in filteredLogs"
                :key="`${log.schedule_id}-${log.schedule_services_id}`"
                class="rounded-xl border border-muted-light bg-white overflow-hidden"
            >
                <button
                    type="button"
                    class="flex w-full flex-col gap-3 p-5 text-left sm:flex-row sm:items-center sm:justify-between"
                    :class="
                        isExpanded(log)
                            ? 'border-b border-muted-light'
                            : 'hover:bg-muted-light/40 transition-colors'
                    "
                    @click="onRowClick(log)"
                >
                    <div class="flex items-start gap-3 min-w-0">
                        <span
                            class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-muted transition-transform"
                            :class="isExpanded(log) ? 'rotate-180' : ''"
                        >
                            <ChevronDown class="h-4 w-4" />
                        </span>

                        <div class="min-w-0">
                            <h4 class="text-xl font-semibold text-secondary">
                                {{ log.schedule_code }}
                            </h4>

                            <p class="text-[14px] text-muted">
                                {{ formatDateTime(log.scheduled_at) }}
                            </p>
                            <p class="text-[13px] text-muted truncate">
                                {{ log.address }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-2 sm:shrink-0"
                        @click="$event.stopPropagation()"
                    >
                        <template v-if="variant === 1">
                            <ActionButton
                                @click="openAssignModal(log)"
                                variant="primary"
                            >
                                Assign
                            </ActionButton>
                            <ActionButton
                                :loading="generatingQr"
                                @click="generateQr('in', log)"
                                variant="primary"
                            >
                                CLOCK-IN
                            </ActionButton>
                            <ActionButton
                                :loading="generatingQr"
                                @click="generateQr('out', log)"
                                variant="primary"
                            >
                                CLOCK-OUT
                            </ActionButton>
                        </template>

                        <ActionButton
                            v-if="variant === 2"
                            variant="primary"
                            @click="goToPatientSchedule(log)"
                        >
                            View Information
                        </ActionButton>

                        <div
                            class="rounded-xl border border-primary/20 bg-primary/5 px-4 py-2"
                        >
                            <p class="text-[10px] uppercase text-primary/60">
                                Scheduled Duration
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
                                'border-orange-300 bg-orange-50':
                                    log.status === 'missed',
                            }"
                        >
                            <p class="text-[10px] uppercase text-muted">
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
                                    'text-orange-700': log.status === 'missed',
                                }"
                            >
                                {{ log.status }}
                            </p>
                        </div>
                    </div>
                </button>

                <Transition
                    name="row-collapse"
                    @enter="onEnter"
                    @after-enter="onAfterEnter"
                    @leave="onLeave"
                >
                    <div v-show="isExpanded(log)" class="overflow-hidden">
                        <div class="px-5">
                            <div
                                class="mt-4 space-y-2 border-b border-muted-light pb-4"
                            >
                                <p
                                    v-if="log.assignees.length"
                                    class="text-xs font-semibold uppercase text-muted"
                                >
                                    Assigned Medical Staff
                                </p>

                                <template v-if="log.assignees.length">
                                    <div
                                        v-for="assignee in log.assignees"
                                        :key="assignee.employee_id"
                                        class="flex items-center gap-3"
                                    >
                                        <img
                                            v-if="assignee.avatar"
                                            :src="assignee.avatar"
                                            class="h-10 w-10 rounded-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-muted-light text-sm font-semibold text-muted"
                                        >
                                            {{ initials(assignee.full_name) }}
                                        </div>

                                        <div>
                                            <p
                                                class="text-sm font-semibold text-secondary"
                                            >
                                                {{ assignee.full_name }}
                                            </p>

                                            <p
                                                v-if="assignee.employee_role"
                                                class="text-xs text-muted capitalize"
                                            >
                                                {{
                                                    assignee.employee_role ??
                                                    "—"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </template>

                                <div v-else class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700"
                                    >
                                        !
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-amber-700"
                                        >
                                            Service is unassigned
                                        </p>

                                        <p class="text-xs text-muted">
                                            No employee has been assigned yet
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-4 flex items-center justify-between rounded-lg border border-primary/20 bg-primary/5 px-4 py-3"
                            >
                                <div>
                                    <p class="text-[11px] uppercase text-muted">
                                        Currently Total Hours Worked
                                    </p>

                                    <p class="text-sm font-bold text-primary">
                                        {{
                                            formatMinutes(
                                                log.total_worked_minutes,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3 pb-5">
                                <p
                                    class="text-xs font-semibold uppercase text-muted"
                                >
                                    QR Scan History
                                </p>
                                <div
                                    v-if="!log.online_logs.length"
                                    class="rounded-lg border border-muted-light bg-muted-light/40 p-4 text-sm text-muted"
                                >
                                    No scan history available
                                </div>

                                <div
                                    v-else
                                    v-for="(scan, index) in log.online_logs"
                                    :key="index"
                                    class="rounded-lg border border-muted-light bg-muted-light/40 p-4"
                                >
                                    <div
                                        v-if="scan.employee_name"
                                        class="mb-3 flex items-center gap-2"
                                    >
                                        <img
                                            v-if="scan.employee_avatar"
                                            :src="scan.employee_avatar"
                                            class="h-6 w-6 rounded-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-6 w-6 items-center justify-center rounded-full bg-muted-light text-[10px] font-semibold text-muted"
                                        >
                                            {{ initials(scan.employee_name) }}
                                        </div>

                                        <p
                                            class="text-xs font-medium text-secondary"
                                        >
                                            {{ scan.employee_name }}
                                        </p>
                                    </div>

                                    <div class="grid gap-4 lg:grid-cols-4">
                                        <div>
                                            <p
                                                class="text-[11px] uppercase text-muted"
                                            >
                                                Check-in
                                            </p>

                                            <p class="text-sm text-secondary">
                                                {{
                                                    scan.in_timestamp
                                                        ? formatDateTime(
                                                              scan.in_timestamp,
                                                          )
                                                        : "Not checked in"
                                                }}
                                            </p>

                                            <p
                                                v-if="scan.in_timestamp"
                                                class="text-[11px] text-emerald-600"
                                            >
                                                QR scanned
                                            </p>
                                        </div>

                                        <div>
                                            <p
                                                class="text-[11px] uppercase text-muted"
                                            >
                                                Check-out
                                            </p>

                                            <p class="text-sm text-secondary">
                                                {{
                                                    scan.out_timestamp
                                                        ? formatDateTime(
                                                              scan.out_timestamp,
                                                          )
                                                        : "Not checked out"
                                                }}
                                            </p>

                                            <p
                                                v-if="scan.out_timestamp"
                                                class="text-[11px] text-emerald-600"
                                            >
                                                QR scanned
                                            </p>
                                        </div>

                                        <div>
                                            <p
                                                class="text-[11px] uppercase text-muted"
                                            >
                                                Worked
                                            </p>

                                            <p
                                                class="text-sm font-semibold text-secondary"
                                            >
                                                {{ duration(scan) }}
                                            </p>
                                        </div>

                                        <div>
                                            <p
                                                class="text-[11px] uppercase text-muted"
                                            >
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
                                        class="mt-3 border-t border-muted-light pt-3 text-xs text-muted"
                                    >
                                        {{ scan.notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <template v-if="variant === 1">
            <QrCodeModal
                :show="showQrModal"
                :token="qrToken"
                :mode="qrMode"
                @close="closeQrModal"
            />
            <AssignADLModal
                :open="showAssignModal"
                :schedule="selectedSchedule"
                :branch-uuid="String(route.params.uuid)"
                :is-saving="isAssigning"
                @close="closeAssignModal"
                @confirm="handleAssignConfirm"
            />

            <QrScanner v-if="showScanner" @close="showScanner = false" />
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { ChevronDown } from "lucide-vue-next";
import type { ScheduleItem, AuditRow } from "~/types/schedule";
import ActionButton from "~/components/ui/ActionButton.vue";
import { onlineScheduleService } from "~/api/online-schedule/OnlineScheduleService";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "~/composables/useToast";
import AssignADLModal from "./AssignADLModal.vue";
import QrCodeModal from "~/components/ui/QrCodeModal.vue";
import QrScanner from "~/components/ui/QrScanner.vue";
import { scheduleService } from "~/api/schedule/ScheduleService.js";

const { success, error } = useToast();

const route = useRoute();
const router = useRouter();

const props = withDefaults(
    defineProps<{
        logs?: ScheduleItem[];
        loading?: boolean;
        date?: string;
        rangeEnd?: string;
        variant?: 1 | 2;
    }>(),
    {
        logs: () => [],
        loading: false,
        variant: 1,
    },
);
const emit = defineEmits<{
    (e: "update", schedule: ScheduleItem[]): void;
}>();
const selectedSchedule = ref<AuditRow>();
const showAssignModal = ref(false);
const isAssigning = ref(false);
const showScanner = ref(false);

const expandedKey = ref<string | null>(null);

function rowKey(log: AuditRow) {
    return `${log.schedule_id}-${log.schedule_services_id}`;
}

function isExpanded(log: AuditRow) {
    return expandedKey.value === rowKey(log);
}

function toggleRow(log: AuditRow) {
    const key = rowKey(log);

    expandedKey.value = expandedKey.value === key ? null : key;
}

function goToPatientSchedule(log: AuditRow) {
    router.push(
        `/app/branches/${route.params.uuid}/patients/${log.patient_uuid}?tab=schedule`,
    );
}

function onRowClick(log: AuditRow) {
    toggleRow(log);
}

function openAssignModal(log: AuditRow) {
    selectedSchedule.value = log;
    showAssignModal.value = true;
}

function closeAssignModal() {
    showAssignModal.value = false;
    selectedSchedule.value = undefined;
}

async function handleAssignConfirm(payload: {
    schedule_service_id: number | null;
    assignments: unknown[];
}) {
    isAssigning.value = true;
    try {
        const res = await scheduleService.action({
            type: "assign",
            branch_uuid: route.params.uuid,
            schedule_id: selectedSchedule.value?.schedule_id,
            ...payload,
        });
        success(res.message ?? "Succesfully assigned employee");
        emit("update", res.data);
        closeAssignModal();
    } catch (err: any) {
        error(
            err?.response?.data?.message ??
                err?.message ??
                "Failed to assign staff.",
        );
        console.error(err);
    } finally {
        isAssigning.value = false;
    }
}

const generatingQr = ref(false);
const qrToken = ref<string | null>(null);
const showQrModal = ref(false);
const qrMode = ref<"clock-in" | "clock-out">("clock-in");

async function generateQr(type: "in" | "out", schedule: AuditRow) {
    generatingQr.value = true;
    qrToken.value = null;
    qrMode.value = type === "in" ? "clock-in" : "clock-out";
    showQrModal.value = true;

    try {
        const res = await onlineScheduleService.generateQr({
            type,
            branch_uuid: route.params.uuid,
            schedule_services_id: schedule.schedule_services_id,
        });
        qrToken.value = res.data?.token ?? res.token ?? res;
    } catch (err: any) {
        error(
            err?.response?.data?.message ??
                err?.message ??
                "Internal Server Error",
        );
        showQrModal.value = false;
        console.error(err);
    } finally {
        generatingQr.value = false;
    }
}

function closeQrModal() {
    showQrModal.value = false;
    qrToken.value = null;
}

const search = ref("");

const filteredLogs = computed<AuditRow[]>(() => {
    const rows: AuditRow[] = props.logs.flatMap((schedule) =>
        (schedule.services ?? []).map((service): AuditRow => {
            const activeAssignees = (service.assignees ?? []).filter(
                (assignee) => assignee.is_active,
            );

            const assignees = activeAssignees.map((assignee) => ({
                employee_id: assignee.employee_id,
                full_name: assignee.full_name ?? null,
                avatar: assignee.avatar ?? null,
                role: assignee.role ?? null,
                employee_role: assignee.employee_role ?? null,
            }));

            const online_logs = activeAssignees.flatMap((assignee) =>
                (assignee.online ?? []).map((scan) => ({
                    qr_in: scan.qr_in ?? null,
                    qr_out: scan.qr_out ?? null,
                    in_timestamp: scan.in_timestamp ?? null,
                    out_timestamp: scan.out_timestamp ?? null,
                    notes: scan.notes ?? null,
                    employee_id: assignee.employee_id,
                    employee_name: assignee.full_name ?? null,
                    employee_avatar: assignee.avatar ?? null,
                })),
            );

            online_logs.sort((a, b) => {
                const aTime = a.in_timestamp
                    ? new Date(a.in_timestamp).getTime()
                    : 0;
                const bTime = b.in_timestamp
                    ? new Date(b.in_timestamp).getTime()
                    : 0;
                return aTime - bTime;
            });

            const firstAssignee = assignees[0];

            return {
                status: schedule.status,
                schedule_id: schedule.schedule_id,
                schedule_code: schedule.schedule_code,
                scheduled_at: schedule.scheduled_at ?? null,
                schedule_services_id: service.schedule_services_id,
                total_hours: service.hours_booked ?? schedule.total_hours ?? 0,

                is_active: !!firstAssignee,
                employee_id: firstAssignee?.employee_id ?? null,
                full_name: firstAssignee?.full_name ?? null,
                avatar: firstAssignee?.avatar ?? null,
                role: firstAssignee?.role ?? null,

                assignees,

                address: schedule.patient?.address ?? null,
                patient_uuid: schedule.patient?.patient_uuid ?? "",
                patient_full_name: schedule.patient?.full_name ?? "",

                online_logs,

                total_worked_minutes: online_logs.reduce(
                    (total: any, scan: any) => total + workedMinutes(scan),
                    0,
                ),
            };
        }),
    );

    const query = search.value.trim().toLowerCase();

    return rows.filter((row) => {
        const searchMatch =
            !query ||
            row.schedule_code.toLowerCase().includes(query) ||
            row.assignees.some((a) =>
                a.full_name?.toLowerCase().includes(query),
            );

        return searchMatch;
        // const rowDate = row.scheduled_at
        //     ? new Date(row.scheduled_at).toISOString().slice(0, 10)
        //     : null;

        // const dateMatch =
        //     (!props.date && !props.rangeEnd) ||
        //     (rowDate !== null &&
        //         (!props.date || rowDate >= props.date) &&
        //         (!props.rangeEnd || rowDate <= props.rangeEnd));

        // return searchMatch && dateMatch;
    });
    // return rows;
});

function initials(name?: string | null) {
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

const onEnter = (el: Element) => {
    const panel = el as HTMLElement;

    panel.style.maxHeight = "0px";
    panel.style.opacity = "0";

    void panel.offsetHeight;

    panel.style.maxHeight = `${panel.scrollHeight}px`;
    panel.style.opacity = "1";
};

const onAfterEnter = (el: Element) => {
    (el as HTMLElement).style.maxHeight = "none";
};

const onLeave = (el: Element) => {
    const panel = el as HTMLElement;

    panel.style.maxHeight = `${panel.scrollHeight}px`;

    void panel.offsetHeight;

    panel.style.maxHeight = "0px";
    panel.style.opacity = "0";
};
</script>

<style scoped>
.row-collapse-enter-active,
.row-collapse-leave-active {
    transition:
        max-height 0.25s ease,
        opacity 0.2s ease;
}
</style>
