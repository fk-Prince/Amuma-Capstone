<template>
    <div class="rounded-2xl bg-white font-sans dark:bg-secondary">
        <div
            v-if="variant !== 3"
            class="flex flex-col gap-3 p-4 sm:p-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3
                    class="text-base font-semibold text-secondary dark:text-white"
                >
                    Online Schedule Audit Log
                </h3>

                <p class="mt-0.5 text-sm text-muted dark:text-gray-400">
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
                        class="w-full rounded-lg border border-muted-light dark:border-white/10 py-2 pl-3 pr-3 text-sm text-secondary dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="loading"
            class="space-y-3"
            :class="variant === 3 ? '' : 'p-4 sm:p-5'"
        >
            <div
                v-for="i in 4"
                :key="i"
                class="h-32 animate-pulse rounded-2xl bg-muted-light dark:bg-white/10"
            />
        </div>

        <div
            v-else-if="!filteredLogs.length"
            class="rounded-2xl border border-dashed border-muted-light dark:border-white/10 p-12 text-center"
        >
            <span
                class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted-light dark:bg-white/10 text-muted dark:text-gray-400"
            >
                <CalendarClock class="h-6 w-6" />
            </span>

            <p class="text-sm font-semibold text-secondary dark:text-white">
                {{
                    variant === 3
                        ? "No schedule records found"
                        : "No audit records found"
                }}
            </p>

            <p
                v-if="variant === 3"
                class="mt-1 text-xs text-muted dark:text-gray-400"
            >
                Try a different status or month to see more visits.
            </p>
        </div>

        <div
            v-else
            class="space-y-6"
            :class="variant === 3 ? '' : 'p-4 sm:p-5'"
        >
            <div v-for="group in logGroups" :key="group.key" class="space-y-3">
                <div
                    v-if="group.title"
                    class="flex items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4 text-primary" />
                        <p
                            class="text-sm font-semibold text-secondary dark:text-white"
                        >
                            {{ group.title }}
                        </p>
                    </div>
                    <span class="text-xs text-muted dark:text-gray-400">
                        {{ group.logs.length }} upcoming
                    </span>
                </div>

                <div
                    v-for="log in group.logs"
                    :key="rowKey(log)"
                    class="overflow-hidden rounded-2xl border border-muted-light dark:border-white/10 bg-white transition hover:border-primary/30 dark:bg-secondary"
                >
                    <button
                        type="button"
                        class="flex w-full flex-col gap-3 p-4 sm:p-5 text-left sm:flex-row sm:items-center sm:justify-between"
                        :class="
                            isExpanded(log)
                                ? 'border-b border-muted-light dark:border-white/10'
                                : 'hover:bg-muted-light/40 dark:hover:bg-white/5 transition-colors'
                        "
                        @click="onRowClick(log)"
                    >
                        <div class="flex items-start gap-3 min-w-0">
                            <span
                                class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-muted dark:text-gray-400 transition-transform"
                                :class="isExpanded(log) ? 'rotate-180' : ''"
                            >
                                <ChevronDown class="h-4 w-4" />
                            </span>

                            <div class="min-w-0">
                                <h4
                                    class="font-semibold text-secondary dark:text-white"
                                    :class="
                                        variant === 3 ? 'text-base' : 'text-xl'
                                    "
                                >
                                    {{ log.schedule_code }}
                                </h4>

                                <div
                                    v-if="variant === 3"
                                    class="mt-0.5 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted dark:text-gray-400"
                                >
                                    <span class="whitespace-nowrap">
                                        {{ formatDateTime(log.scheduled_at) }}
                                    </span>

                                    <span
                                        v-if="log.address"
                                        class="flex min-w-0 items-center gap-1"
                                    >
                                        <span class="text-muted-light">·</span>
                                        <MapPinned class="h-3 w-3 shrink-0" />
                                        <span class="min-w-0 truncate">
                                            {{ log.address }}
                                        </span>
                                    </span>
                                </div>

                                <template v-else>
                                    <p
                                        class="text-[14px] text-muted dark:text-gray-400"
                                    >
                                        {{ formatDateTime(log.scheduled_at) }}
                                    </p>
                                    <p
                                        class="flex min-w-0 items-center gap-1.5 text-[13px] text-muted dark:text-gray-400"
                                    >
                                        <MapPinned
                                            class="w-3.5 h-3.5 text-muted dark:text-gray-400 shrink-0"
                                        />
                                        <span class="min-w-0 truncate">{{
                                            log.address
                                        }}</span>
                                    </p>
                                </template>

                                <span
                                    v-if="variant === 3 && latestCheckIn(log)"
                                    class="mt-1.5 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        :class="
                                            isCurrentlyCheckedIn(log)
                                                ? 'animate-pulse'
                                                : ''
                                        "
                                    />
                                    {{
                                        isCurrentlyCheckedIn(log)
                                            ? "On duty"
                                            : "Checked in"
                                    }}
                                    ·
                                    {{
                                        formatCheckInTime(
                                            latestCheckIn(log)?.in_timestamp,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>

                        <div
                            :class="
                                variant === 3
                                    ? 'flex w-full flex-col items-stretch gap-2.5 sm:w-60 sm:shrink-0'
                                    : 'flex flex-wrap items-center gap-2 sm:shrink-0'
                            "
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
                                class="order-2"
                                extra-class="w-full !py-1.5 !text-xs"
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
                                class="order-2"
                                extra-class="w-full !py-1.5 !text-xs"
                                :loading="generatingQr"
                                @click="generateQr('in', log)"
                                variant="primary"
                            >
                                Generate QR In
                            </ActionButton>

                            <template v-if="variant === 3">
                                <div
                                    class="order-1 flex w-full flex-col gap-2.5"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2 whitespace-nowrap"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold capitalize ring-1"
                                            :class="statusPill(log.status)"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-current opacity-70"
                                            />
                                            {{ log.status }}
                                        </span>

                                        <span
                                            class="text-xs font-semibold text-muted dark:text-gray-400"
                                        >
                                            {{
                                                formatDurationShort(
                                                    log.total_hours,
                                                )
                                            }}
                                            booked
                                        </span>
                                    </div>

                                    <div class="w-full">
                                        <div
                                            class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10"
                                        >
                                            <div
                                                class="h-full rounded-full transition-[width] duration-500 ease-out"
                                                :class="progressFill(log)"
                                                :style="{
                                                    width: progressWidth(log),
                                                }"
                                            />
                                        </div>

                                        <div
                                            class="mt-2 flex items-center justify-between gap-2 whitespace-nowrap text-[11px] font-semibold"
                                        >
                                            <span
                                                :class="progressTextTone(log)"
                                            >
                                                {{ progressLabel(log) }}
                                            </span>

                                            <span
                                                class="text-muted dark:text-gray-400"
                                            >
                                                {{ remainingLabel(log) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-else>
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
                                    class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 dark:border-amber-500/20 dark:bg-amber-500/10"
                                >
                                    <p
                                        class="text-[10px] uppercase text-amber-600/70 dark:text-amber-300"
                                    >
                                        Remaining
                                    </p>

                                    <p
                                        class="text-sm font-bold text-amber-700 dark:text-amber-300"
                                    >
                                        {{ formatRemaining(log) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border px-4 py-2 dark:border-white/10"
                                    :class="{
                                        'border-amber-200 bg-amber-50 dark:border-amber-500/20 dark:bg-amber-500/10':
                                            log.status === 'pending',
                                        'border-blue-200 bg-blue-50 dark:border-blue-500/20 dark:bg-blue-500/10':
                                            log.status === 'ongoing',
                                        'border-green-200 bg-green-50':
                                            log.status === 'completed',
                                        'border-red-200 bg-red-50':
                                            log.status === 'cancelled',
                                        'border-orange-300 bg-orange-50':
                                            log.status === 'missed',
                                    }"
                                >
                                    <p
                                        class="text-[10px] uppercase text-muted dark:text-gray-400"
                                    >
                                        Status
                                    </p>

                                    <p
                                        class="text-sm font-bold capitalize"
                                        :class="{
                                            'text-amber-700 dark:text-amber-300':
                                                log.status === 'pending',
                                            'text-blue-700 dark:text-blue-300':
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
                            </template>
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
                                    class="mt-4 space-y-2 border-b border-muted-light dark:border-white/10 pb-4"
                                >
                                    <p
                                        v-if="log.assignees.length"
                                        class="text-xs font-semibold uppercase text-muted dark:text-gray-400"
                                    >
                                        Assigned Medical Staff
                                    </p>

                                    <template v-if="log.assignees.length">
                                        <div
                                            v-for="assignee in log.assignees"
                                            :key="assignee.employee_id"
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <div
                                                class="flex items-center gap-3 min-w-0"
                                            >
                                                <img
                                                    v-if="assignee.avatar"
                                                    :src="assignee.avatar"
                                                    class="h-10 w-10 rounded-full object-cover"
                                                />
                                                <div
                                                    v-else
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-muted-light dark:bg-white/10 text-sm font-semibold text-muted dark:text-gray-400"
                                                >
                                                    {{
                                                        initials(
                                                            assignee.full_name,
                                                        )
                                                    }}
                                                </div>

                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-sm font-semibold text-secondary dark:text-white"
                                                    >
                                                        {{ assignee.full_name }}
                                                    </p>

                                                    <p
                                                        v-if="
                                                            assignee.employee_role
                                                        "
                                                        class="text-xs text-muted dark:text-gray-400 capitalize"
                                                    >
                                                        {{
                                                            assignee.employee_role ??
                                                            "—"
                                                        }}
                                                    </p>

                                                    <p
                                                        v-if="
                                                            assignee.phone_number
                                                        "
                                                        class="flex items-center gap-1 text-[11px] text-muted dark:text-gray-400"
                                                    >
                                                        <Phone
                                                            class="h-3 w-3 shrink-0"
                                                        />
                                                        {{
                                                            assignee.phone_number
                                                        }}
                                                    </p>

                                                    <p
                                                        v-if="assignee.email"
                                                        class="flex min-w-0 items-center gap-1 text-[11px] text-muted dark:text-gray-400"
                                                    >
                                                        <Mail
                                                            class="h-3 w-3 shrink-0"
                                                        />
                                                        <span
                                                            class="truncate"
                                                            >{{
                                                                assignee.email
                                                            }}</span
                                                        >
                                                    </p>
                                                </div>
                                            </div>

                                            <div
                                                class="flex shrink-0 flex-col items-end gap-1"
                                            >
                                                <span
                                                    v-if="
                                                        isOnDuty(
                                                            log,
                                                            assignee.employee_id,
                                                        )
                                                    "
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                                    />
                                                    On Duty
                                                </span>

                                                <span
                                                    v-if="assignee.note"
                                                    class="rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-medium text-primary"
                                                >
                                                    {{ assignee.note }}
                                                </span>
                                            </div>
                                        </div>
                                    </template>

                                    <div v-else class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                                        >
                                            !
                                        </div>

                                        <div>
                                            <p
                                                class="text-sm font-semibold text-amber-700 dark:text-amber-300"
                                            >
                                                Service is unassigned
                                            </p>

                                            <p
                                                class="text-xs text-muted dark:text-gray-400"
                                            >
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
                                            class="text-[11px] text-muted dark:text-gray-400"
                                        >
                                            Hours worked so far
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
                                        class="text-xs font-semibold uppercase text-muted dark:text-gray-400"
                                    >
                                        QR Scan History
                                    </p>
                                    <div
                                        v-if="!log.online_logs.length"
                                        class="rounded-lg border border-muted-light dark:border-white/10 bg-muted-light/40 dark:bg-white/5 p-4 text-sm text-muted dark:text-gray-400"
                                    >
                                        No scan history available
                                    </div>

                                    <div
                                        v-else
                                        v-for="(scan, index) in log.online_logs"
                                        :key="index"
                                        class="rounded-lg border border-muted-light dark:border-white/10 bg-muted-light/40 dark:bg-white/5 p-4"
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
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-muted-light dark:bg-white/10 text-[10px] font-semibold text-muted dark:text-gray-400"
                                            >
                                                {{
                                                    initials(scan.employee_name)
                                                }}
                                            </div>

                                            <p
                                                class="text-xs font-medium text-secondary dark:text-white"
                                            >
                                                {{ scan.employee_name }}
                                            </p>
                                        </div>

                                        <div
                                            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                                        >
                                            <div>
                                                <p
                                                    class="text-[11px] uppercase text-muted dark:text-gray-400"
                                                >
                                                    Check-in
                                                </p>

                                                <p
                                                    class="text-sm text-secondary dark:text-white"
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
                                                    class="text-[11px] text-emerald-600 dark:text-emerald-300"
                                                >
                                                    QR scanned
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-[11px] uppercase text-muted dark:text-gray-400"
                                                >
                                                    Check-out
                                                </p>

                                                <p
                                                    class="text-sm text-secondary dark:text-white"
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
                                                    class="text-[11px] text-emerald-600 dark:text-emerald-300"
                                                >
                                                    QR scanned
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-[11px] uppercase text-muted dark:text-gray-400"
                                                >
                                                    Worked Hours
                                                </p>

                                                <p
                                                    class="text-sm font-semibold text-secondary dark:text-white"
                                                >
                                                    {{ duration(scan) }}
                                                </p>
                                            </div>

                                            <!-- <div>
                                                <p
                                                    class="text-[11px] uppercase text-muted dark:text-gray-400"
                                                >
                                                    Status
                                                </p>

                                                <span
                                                    class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                                    :class="
                                                        scan.out_timestamp
                                                            ? 'bg-primary/10 text-primary'
                                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'
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
                                            class="mt-3 border-t border-muted-light dark:border-white/10 pt-3 text-xs text-muted dark:text-gray-400"
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
import {
    ChevronDown,
    CalendarClock,
    MapPinned,
    Phone,
    Mail,
} from "lucide-vue-next";
import type { ScheduleItem, AuditRow } from "~/types/schedule";
import ActionButton from "~/components/ui/ActionButton.vue";
import { onlineScheduleService } from "~/api/online-schedule/OnlineScheduleService";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "~/composables/useToast";
import { formatDuration, formatDurationShort } from "~/utils/time";
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
            as_family: props.variant === 3,
        });
        qrToken.value = res.data?.token ?? res.token ?? res;
    } catch (err: any) {
        error(
            err?.response?.data?.message ??
                err?.data?.message ??
                err?.message ??
                "Internal Server Error",
        );
        showQrModal.value = false;
        console.error(err);

        if (err?.response?.status === 409 || err?.status === 409) {
            emit("refresh");
        }
    } finally {
        generatingQr.value = false;
    }
}

function closeQrModal() {
    showQrModal.value = false;
    qrToken.value = null;
}

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
                note: assignee.note ?? null,
                employee_role: assignee.employee_role ?? null,
                phone_number: assignee.phone_number ?? null,
                email: assignee.email ?? null,
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
                category: schedule.category ?? null,
                schedule_id: schedule.schedule_id,
                schedule_code: schedule.schedule_code,
                scheduled_at: schedule.scheduled_at ?? null,
                schedule_services_id: service.schedule_services_id,
                total_hours: service.hours_booked ?? schedule.total_hours ?? 0,

                is_active: !!firstAssignee,
                employee_id: firstAssignee?.employee_id ?? null,
                full_name: firstAssignee?.full_name ?? null,
                avatar: firstAssignee?.avatar ?? null,
                note: firstAssignee?.note ?? null,

                assignees,

                address: schedule.address ?? schedule.patient?.address ?? null,
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
    });
});

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

const logGroups = computed(() => {
    if (props.variant === 3) {
        const groups: {
            key: string;
            title: string | null;
            logs: AuditRow[];
        }[] = [];

        if (upcomingLogs.value.length) {
            groups.push({
                key: "upcoming",
                title: "Scheduled Days",
                logs: upcomingLogs.value,
            });
        }

        if (historyLogs.value.length) {
            groups.push({
                key: "history",
                title: null,
                logs: historyLogs.value,
            });
        }

        return groups;
    }

    return [{ key: "all", title: null, logs: filteredLogs.value }];
});

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

function isCurrentlyCheckedIn(log: AuditRow): boolean {
    const latest = latestCheckIn(log);
    return Boolean(latest && !latest.out_timestamp);
}

function isOnDuty(
    log: AuditRow,
    employeeId: string | number | null | undefined,
): boolean {
    if (!employeeId) return false;

    return log.online_logs.some(
        (scan) =>
            Number(scan.employee_id) === Number(employeeId) &&
            scan.in_timestamp &&
            !scan.out_timestamp,
    );
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

function statusPill(status: string) {
    const map: Record<string, string> = {
        pending:
            "bg-violet-50 text-violet-700 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/20",
        confirmed:
            "bg-blue-50 text-blue-700 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20",
        ongoing:
            "bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20",
        completed:
            "bg-sky-50 text-sky-700 ring-sky-100 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20",
        missed: "bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20",
        cancelled:
            "bg-gray-100 text-gray-500 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
    };

    return (
        map[status?.toLowerCase()] ??
        "bg-gray-100 text-gray-500 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10"
    );
}

function progressPercent(log: AuditRow) {
    const booked = (log.total_hours ?? 0) * 60;
    if (booked <= 0) return log.total_worked_minutes > 0 ? 100 : 0;

    return Math.min(100, Math.round((log.total_worked_minutes / booked) * 100));
}

function progressWidth(log: AuditRow) {
    const percent = progressPercent(log);

    return `${percent > 0 ? Math.max(percent, 6) : 0}%`;
}

function overtimeMinutes(log: AuditRow) {
    return Math.max(log.total_worked_minutes - log.total_hours * 60, 0);
}

function progressFill(log: AuditRow) {
    if (overtimeMinutes(log) > 0) return "bg-amber-400";

    switch (log.status?.toLowerCase()) {
        case "ongoing":
            return "bg-emerald-500";
        case "completed":
            return "bg-sky-500";
        case "missed":
            return "bg-rose-300";
        case "cancelled":
            return "bg-slate-300 dark:bg-white/20";
        default:
            return "bg-primary";
    }
}

function progressTextTone(log: AuditRow) {
    if (overtimeMinutes(log) > 0) return "text-amber-600 dark:text-amber-300";
    if (log.total_worked_minutes <= 0) return "text-muted dark:text-gray-400";

    return log.status?.toLowerCase() === "ongoing"
        ? "text-emerald-600 dark:text-emerald-300"
        : "text-secondary dark:text-white";
}

function progressLabel(log: AuditRow) {
    if (log.total_worked_minutes <= 0) return "Not started";

    return `${progressPercent(log)}% · ${formatDurationShort(
        log.total_worked_minutes / 60,
    )} done`;
}

function remainingLabel(log: AuditRow) {
    const overtime = overtimeMinutes(log);

    if (overtime > 0) {
        return `+${formatDurationShort(overtime / 60)} over`;
    }

    return `${remainingShort(log)} left`;
}

function remainingMinutes(log: AuditRow) {
    return Math.max(log.total_hours * 60 - log.total_worked_minutes, 0);
}

function formatRemaining(log: AuditRow) {
    return formatDuration(remainingMinutes(log) / 60) || "0 hrs";
}

function remainingShort(log: AuditRow) {
    return formatDurationShort(remainingMinutes(log) / 60);
}

function formatCheckInTime(value?: string | null) {
    if (!value) return "—";

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return "—";

    const isToday = parsed.toDateString() === new Date().toDateString();

    return parsed.toLocaleString("en-US", {
        month: isToday ? undefined : "short",
        day: isToday ? undefined : "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
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
