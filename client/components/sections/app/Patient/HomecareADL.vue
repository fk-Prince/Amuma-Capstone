<template>
    <div class="rounded-2xl bg-white font-sans">
        <div
            v-if="variant !== 3"
            class="flex flex-col gap-3 p-4 sm:p-5 sm:flex-row sm:items-center sm:justify-between"
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

        <div v-if="loading" class="space-y-3 p-4 sm:p-5">
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
            {{
                variant === 3
                    ? "No schedule records found"
                    : "No audit records found"
            }}
        </div>

        <div v-else class="p-4 sm:p-5 space-y-6">
            <div
                v-for="group in logGroups"
                :key="group.key"
                class="space-y-4"
            >
                <div
                    v-if="group.title"
                    class="flex items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4 text-primary" />
                        <p class="text-sm font-semibold text-secondary">
                            {{ group.title }}
                        </p>
                    </div>
                    <span class="text-xs text-muted">
                        {{ group.logs.length }} upcoming
                    </span>
                </div>

                <div
                    v-for="log in group.logs"
                    :key="rowKey(log)"
                    class="rounded-xl border border-muted-light bg-white overflow-hidden"
                >
                    <button
                        type="button"
                        class="flex w-full flex-col gap-3 p-4 sm:p-5 text-left sm:flex-row sm:items-center sm:justify-between"
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
                                <h4
                                    class="text-xl font-semibold text-secondary"
                                >
                                    {{ log.schedule_code }}
                                </h4>

                                <p class="text-[14px] text-muted">
                                    {{ formatDateTime(log.scheduled_at) }}
                                </p>
                                <p
                                    class="text-[13px] text-muted truncate flex gap-3 ite"
                                >
                                    <MapPinned
                                        class="w-3.5 h-3.5 text-muted shrink-0"
                                    />
                                    {{ log.address }}
                                </p>
                                <p
                                    v-if="variant === 3 && latestCheckIn(log)"
                                    class="text-[13px] font-medium text-emerald-600"
                                >
                                    Checked in
                                    {{
                                        formatDateTime(
                                            latestCheckIn(log)?.in_timestamp,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex flex-wrap items-center gap-2 sm:shrink-0"
                            @click="$event.stopPropagation()"
                        >
                            <template v-if="variant === 1">
                                <ActionButton
                                    variant="outline"
                                    @click="viewDetails(log)"
                                >
                                    View details
                                </ActionButton>

                                <ActionButton
                                    v-if="log.status !== 'cancelled'"
                                    @click="openAssignModal(log)"
                                    variant="primary"
                                >
                                    Assign
                                </ActionButton>
                            </template>

                            <ActionButton
                                v-if="variant === 2"
                                variant="primary"
                                @click="goToPatientSchedule(log)"
                            >
                                View Information
                            </ActionButton>

                            <ActionButton
                                v-if="
                                    variant === 3 &&
                                    canGenerateQr(log) &&
                                    isCurrentlyCheckedIn(log)
                                "
                                :loading="generatingQr"
                                @click="generateQr('out', log)"
                                variant="primary"
                            >
                                Generate QR Out
                            </ActionButton>

                            <ActionButton
                                v-if="
                                    variant === 3 &&
                                    canGenerateQr(log) &&
                                    !isCurrentlyCheckedIn(log)
                                "
                                :loading="generatingQr"
                                @click="generateQr('in', log)"
                                variant="primary"
                            >
                                Generate QR In
                            </ActionButton>

                            <div
                                class="rounded-xl border border-primary/20 bg-primary/5 px-4 py-2"
                            >
                                <p
                                    class="text-[10px] uppercase text-primary/60"
                                >
                                    Scheduled Duration
                                </p>

                                <p class="text-sm font-bold text-primary">
                                    {{
                                        formatDuration(log.total_hours) ||
                                        "0 hrs"
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2"
                            >
                                <p
                                    class="text-[10px] uppercase text-amber-600/70"
                                >
                                    Remaining
                                </p>

                                <p class="text-sm font-bold text-amber-700">
                                    {{ formatRemaining(log) }}
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
                                        'text-amber-700':
                                            log.status === 'pending',
                                        'text-blue-700':
                                            log.status === 'ongoing',
                                        'text-green-700':
                                            log.status === 'completed',
                                        'text-red-700':
                                            log.status === 'cancelled',
                                        'text-orange-700':
                                            log.status === 'missed',
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
                            <div class="px-4 sm:px-5">
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
                                                {{
                                                    initials(assignee.full_name)
                                                }}
                                            </div>

                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-secondary"
                                                >
                                                    {{ assignee.full_name }}
                                                </p>

                                                <p
                                                    v-if="
                                                        assignee.employee_role
                                                    "
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
                                                No employee has been assigned
                                                yet
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="mt-4 flex items-center justify-between rounded-lg border border-primary/20 bg-primary/5 px-4 py-3"
                                >
                                    <div>
                                        <p
                                            class="text-[11px] uppercase text-muted"
                                        >
                                            Currently Total Hours Worked
                                        </p>

                                        <p
                                            class="text-sm font-bold text-primary"
                                        >
                                            {{
                                                formatDuration(
                                                    log.total_worked_minutes /
                                                        60,
                                                ) || "0 hrs"
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
                                                {{
                                                    initials(scan.employee_name)
                                                }}
                                            </div>

                                            <p
                                                class="text-xs font-medium text-secondary"
                                            >
                                                {{ scan.employee_name }}
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                            <div>
                                                <p
                                                    class="text-[11px] uppercase text-muted"
                                                >
                                                    Check-in
                                                </p>

                                                <p
                                                    class="text-sm text-secondary"
                                                >
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

                                                <p
                                                    class="text-sm text-secondary"
                                                >
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
                                                    Worked Hours
                                                </p>

                                                <p
                                                    class="text-sm font-semibold text-secondary"
                                                >
                                                    {{ duration(scan) }}
                                                </p>
                                            </div>

                                            <!-- <div>
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
                                            </div> -->
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
        </div>

        <QrCodeModal
            v-if="variant === 1 || variant === 3"
            :show="showQrModal"
            :token="qrToken"
            :mode="qrMode"
            @close="closeQrModal"
            @scanned="handleQrScanned"
        />

        <template v-if="variant === 1">
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
import { ChevronDown, CalendarClock, MapPinned } from "lucide-vue-next";
import type { ScheduleItem, AuditRow } from "~/types/schedule";
import ActionButton from "~/components/ui/ActionButton.vue";
import { onlineScheduleService } from "~/api/online-schedule/OnlineScheduleService";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "~/composables/useToast";
import { formatDuration } from "~/utils/time";
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
        variant?: 1 | 2 | 3;
    }>(),
    {
        logs: () => [],
        loading: false,
        variant: 1,
    },
);
const emit = defineEmits<{
    (e: "update", schedule: ScheduleItem[]): void;
    (e: "refresh"): void;
    (e: "view-details", schedule: ScheduleItem): void;
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

// AuditRow is a flattened one-row-per-service view of a schedule; the
// "Update" flow (ScheduleDetails) needs the original nested ScheduleItem,
// same as the Medical schedule list already provides via view-details.
function viewDetails(log: AuditRow) {
    const schedule = props.logs.find((s) => s.schedule_id === log.schedule_id);
    if (schedule) emit("view-details", schedule);
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
            // Variant 3 is the family portal view — a dual-role account
            // (also has a staff record) needs this explicit signal so the
            // backend resolves the patient's assigned caregiver instead
            // of assuming the portal user is clocking themselves in.
            as_family: props.variant === 3,
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

// Fires on the device that GENERATED the QR once the scanning device
// verifies it (relayed over the .qr.scanned broadcast inside QrCodeModal),
// so the person waiting to be scanned also finds out it went through.
function handleQrScanned() {
    success(
        qrMode.value === "clock-in"
            ? "Clocked in successfully."
            : "Clocked out successfully.",
    );
    emit("refresh");
    closeQrModal();
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

                // schedule.address resolves to "On-site" for facility schedules
                // and to the visit address for homecare ones.
                address:
                    schedule.address ??
                    schedule.patient?.address ??
                    null,
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

// Only visits that have already happened (or are happening now) count as
// "history" — a pending/confirmed visit hasn't occurred yet, so the
// portal splits it out into its own "Scheduled Days" list instead of
// mixing it in with past attendance records.
const NOT_YET_DONE_STATUSES = ["pending", "confirmed"];

const upcomingLogs = computed(() =>
    filteredLogs.value.filter((log) =>
        NOT_YET_DONE_STATUSES.includes(log.status),
    ),
);

const historyLogs = computed(() =>
    filteredLogs.value.filter(
        (log) => !NOT_YET_DONE_STATUSES.includes(log.status),
    ),
);

// Variant 3 (portal) keeps upcoming visits under their own "Scheduled
// Days" heading while everything else (dashboard/staff variants) shows a
// single flat list — but every group renders through the same card markup
// so the two views only differ by grouping, not by layout.
const logGroups = computed(() => {
    if (props.variant === 3) {
        const groups: { key: string; title: string | null; logs: AuditRow[] }[] =
            [];

        if (upcomingLogs.value.length) {
            groups.push({
                key: "upcoming",
                title: "Scheduled Days",
                logs: upcomingLogs.value,
            });
        }

        if (historyLogs.value.length) {
            groups.push({ key: "history", title: null, logs: historyLogs.value });
        }

        return groups;
    }

    return [{ key: "all", title: null, logs: filteredLogs.value }];
});

// QR generation should stay available for the whole lifecycle of a visit
// (upcoming, then ongoing) and only stop once it's cancelled or finished.
const QR_GENERATION_BLOCKED_STATUSES = ["cancelled", "completed", "missed"];

function canGenerateQr(log: AuditRow): boolean {
    return (
        !!log.assignees.length &&
        !QR_GENERATION_BLOCKED_STATUSES.includes(log.status)
    );
}

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

function latestCheckIn(log: AuditRow) {
    return log.online_logs.reduce<AuditRow["online_logs"][number] | null>(
        (latest, scan) => {
            if (!scan.in_timestamp) return latest;
            if (!latest?.in_timestamp) return scan;

            return new Date(scan.in_timestamp) > new Date(latest.in_timestamp)
                ? scan
                : latest;
        },
        null,
    );
}

// A schedule with several services can be "ongoing" overall while a given
// service's own caregiver hasn't clocked in yet, so whether to offer
// Generate QR In vs Out has to follow this service's own latest scan, not
// just the schedule-level status.
function isCurrentlyCheckedIn(log: AuditRow): boolean {
    const latest = latestCheckIn(log);
    return Boolean(latest && !latest.out_timestamp);
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
    if (!scan.in_timestamp) {
        return "—";
    }

    const minutes = workedMinutes(scan);

    return formatDuration(minutes / 60) || "0 hrs";
}

function formatRemaining(log: AuditRow) {
    const remainingMinutes = Math.max(
        log.total_hours * 60 - log.total_worked_minutes,
        0,
    );

    return formatDuration(remainingMinutes / 60) || "0 hrs";
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
