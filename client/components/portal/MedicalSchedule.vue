<template>
    <div class="rounded-2xl bg-white dark:bg-secondary">
        <div v-if="loading" class="space-y-3">
            <div
                v-for="i in 3"
                :key="i"
                class="h-32 animate-pulse rounded-2xl bg-gray-50 dark:bg-white/5"
            />
        </div>

        <div
            v-else-if="!logs.length"
            class="rounded-2xl border border-dashed border-gray-200 p-12 text-center dark:border-white/10"
        >
            <span
                class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 text-gray-300 dark:bg-white/5 dark:text-gray-500"
            >
                <Stethoscope class="h-6 w-6" />
            </span>

            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                No medical visits found
            </p>

            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                Try a different status or month to see more clinical visits.
            </p>
        </div>

        <div v-else class="space-y-6">
            <section
                v-for="group in groups"
                :key="group.key"
                class="space-y-3"
            >
                <div
                    v-if="group.title"
                    class="flex items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4 text-primary-500 dark:text-primary-300" />

                        <p class="text-sm font-semibold text-gray-800 dark:text-white">
                            {{ group.title }}
                        </p>
                    </div>

                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                        {{ group.schedules.length }}
                        {{ group.schedules.length === 1 ? "visit" : "visits" }}
                    </span>
                </div>

                <article
                    v-for="schedule in group.schedules"
                    :key="schedule.schedule_id"
                    class="overflow-hidden rounded-2xl border border-gray-100 bg-white transition hover:border-gray-200 dark:border-white/10 dark:bg-secondary dark:hover:border-white/10"
                >
                    <div
                        class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-400 dark:bg-white/5 dark:text-gray-500"
                            >
                                <Stethoscope class="h-4 w-4" />
                            </span>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p
                                        class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ schedule.schedule_code }}
                                    </p>

                                    <span
                                        v-if="dayBadge(schedule)"
                                        class="shrink-0 rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                                    >
                                        {{ dayBadge(schedule) }}
                                    </span>
                                </div>

                                <p
                                    class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs text-gray-400 dark:text-gray-500"
                                >
                                    <span class="whitespace-nowrap">
                                        {{
                                            formatDate(
                                                getScheduleDate(schedule),
                                            ) || "Date to be confirmed"
                                        }}
                                    </span>

                                    <template v-if="schedule.start_time">
                                        <span class="text-gray-300 dark:text-gray-500">·</span>

                                        <span class="whitespace-nowrap">
                                            {{ schedule.start_time }}
                                            <template v-if="schedule.end_time">
                                                – {{ schedule.end_time }}
                                            </template>
                                        </span>
                                    </template>

                                    <span class="text-gray-300 dark:text-gray-500">·</span>

                                    <span
                                        class="whitespace-nowrap"
                                        title="Estimated duration"
                                    >
                                        Est.
                                        {{ formatDurationShort(schedule.total_hours) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-full px-2.5 py-1 text-[11px] font-semibold sm:self-auto"
                            :class="scheduleStatusTheme(schedule.status).badge"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-current opacity-70"
                            />
                            {{ scheduleStatusLabel(schedule.status) }}
                        </span>
                    </div>

                    <p
                        v-if="schedule.note"
                        class="border-t border-gray-100 px-4 py-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400"
                    >
                        {{ schedule.note }}
                    </p>

                    <div
                        v-if="schedule.services?.length"
                        class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/10 dark:border-white/10"
                    >
                        <div
                            v-for="service in schedule.services"
                            :key="service.schedule_services_id"
                        >
                        <div
                            class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <template v-if="service.assignees?.length">
                                    <img
                                        v-if="service.assignees[0]?.avatar"
                                        :src="service.assignees[0].avatar!"
                                        class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white"
                                        alt=""
                                    />

                                    <span
                                        v-else
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-xs font-bold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                                    >
                                        {{ initials(service.assignees[0]?.full_name) }}
                                    </span>
                                </template>

                                <span
                                    v-else
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-500 dark:bg-amber-500/10 dark:text-amber-300"
                                >
                                    <UserRound class="h-4 w-4" />
                                </span>

                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium text-gray-800 dark:text-white"
                                    >
                                        {{ service.service_name || "Service" }}
                                    </p>

                                    <p
                                        v-if="assignmentSummary(service.assignees?.[0])"
                                        class="truncate text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ assignmentSummary(service.assignees?.[0]) }}
                                    </p>

                                    <p class="truncate text-xs text-gray-400 dark:text-gray-500">
                                        <template v-if="service.assignees?.length">
                                            {{ service.assignees[0]?.full_name }}

                                            <span
                                                v-if="service.assignees[0]?.employee_role"
                                                class="capitalize"
                                            >
                                                · {{ service.assignees[0]?.employee_role }}
                                            </span>

                                            <button
                                                v-if="service.assignees.length > 1"
                                                type="button"
                                                class="inline-flex items-center gap-0.5 font-medium text-primary-600 hover:underline dark:text-primary-300"
                                                :aria-expanded="isExpanded(service)"
                                                @click="toggleStaff(service)"
                                            >
                                                +{{ service.assignees.length - 1 }}
                                                more
                                                <ChevronDown
                                                    class="h-3 w-3 transition-transform"
                                                    :class="isExpanded(service) ? 'rotate-180' : ''"
                                                />
                                            </button>
                                        </template>

                                        <span v-else class="text-amber-600 dark:text-amber-300">
                                            Not assigned yet
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex shrink-0 items-center gap-2 pl-12 text-[11px] sm:pl-0"
                            >
                                <span
                                    class="inline-flex items-center gap-1 whitespace-nowrap font-semibold text-gray-500 dark:text-gray-400"
                                    title="Estimated duration"
                                >
                                    <Clock class="h-3 w-3 text-gray-300 dark:text-gray-500" />
                                    Est.
                                    {{
                                        formatDurationShort(
                                            (service.duration_minutes ?? 0) / 60,
                                        )
                                    }}
                                </span>

                                <span
                                    v-if="latestVisit(service)"
                                    class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-emerald-50 px-2 py-1 font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                    />
                                    {{ formatTimestamp(latestVisit(service)?.in_timestamp) }}
                                </span>
                            </div>
                        </div>

                        <ul
                            v-if="isExpanded(service)"
                            class="mx-4 mb-4 divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-100 dark:divide-white/10 dark:border-white/10"
                        >
                            <li
                                v-for="assignee in service.assignees"
                                :key="assignee.employee_id"
                                class="flex items-center gap-3 px-3 py-2.5"
                            >
                                <img
                                    v-if="assignee.avatar"
                                    :src="assignee.avatar"
                                    :alt="assignee.full_name"
                                    class="h-8 w-8 shrink-0 rounded-full object-cover"
                                />
                                <span
                                    v-else
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-[11px] font-bold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"
                                >
                                    {{ initials(assignee.full_name) }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-white">
                                        {{ assignee.full_name }}
                                    </p>
                                    <p class="truncate text-xs capitalize text-gray-400 dark:text-gray-500">
                                        {{ assignee.employee_role ?? "Staff" }}
                                        <template v-if="assignee.note">· {{ assignee.note }}</template>
                                    </p>
                                </div>

                                <span
                                    class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="staffState(assignee).class"
                                >
                                    {{ staffState(assignee).label }}
                                </span>
                            </li>
                        </ul>
                        </div>
                    </div>

                    <div
                        v-if="canShowQr(schedule)"
                        class="grid grid-cols-2 gap-2.5 border-t border-gray-100 p-3 dark:border-white/10"
                    >
                        <button
                            type="button"
                            :disabled="hasStarted(schedule) || generatingFor === schedule.schedule_id"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-primary-600 py-2 text-xs font-semibold text-primary-600 transition hover:bg-primary-600 hover:text-white disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-transparent disabled:text-gray-400 dark:text-primary-300 dark:disabled:border-white/10 dark:disabled:text-gray-500"
                            @click="openQr('in', schedule)"
                        >
                            <QrCode class="h-3.5 w-3.5" />
                            Time-In QR
                        </button>

                        <button
                            type="button"
                            :disabled="!hasStarted(schedule) || generatingFor === schedule.schedule_id"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 py-2 text-xs font-semibold text-gray-600 transition hover:border-primary-600 hover:text-primary-600 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-gray-400 dark:hover:text-primary-300"
                            @click="openQr('out', schedule)"
                        >
                            <QrCode class="h-3.5 w-3.5" />
                            Time-Out QR
                        </button>
                    </div>

                    <div
                        v-if="isReviewable(schedule)"
                        class="border-t border-gray-100 p-3 dark:border-white/10"
                    >
                        <button
                            type="button"
                            :disabled="
                                !canRequestReview(schedule) ||
                                sendingReview === schedule.schedule_id
                            "
                            class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:border-gray-100 disabled:bg-transparent disabled:text-gray-400 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20 dark:disabled:border-white/10 dark:disabled:bg-transparent dark:disabled:text-gray-500"
                            @click="requestScheduleReview(schedule)"
                        >
                            <Bell class="h-3.5 w-3.5 shrink-0" />
                            <span>{{ reviewButtonLabel(schedule) }}</span>
                        </button>
                    </div>
                </article>
            </section>
        </div>

        <QrCodeModal
            :show="showQrModal"
            :token="qrToken"
            :mode="qrMode"
            :caregivers="qrCaregivers"
            :checked-in="qrCheckedIn"
            description="Show this code to the care team to scan."
            @close="closeQr"
            @scanned="onQrScanned"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import {
    Bell,
    Stethoscope,
    UserRound,
    CalendarClock,
    Clock,
    QrCode,
    ChevronDown,
} from "lucide-vue-next";
import type { ScheduleAssignee, ScheduleItem, ScheduleServiceItem } from "~/types/schedule";
import { useSchedule } from "~/composables/useSchedule";
import { useToast } from "~/composables/useToast";
import { patientAccessService } from "~/api/patient-access/PatientAccessService";
import { attendanceService } from "~/api/attendance/AttendanceService";
import QrCodeModal from "~/components/ui/QrCodeModal.vue";
import { assignmentSummary, formatDurationShort } from "~/utils/time";

const props = withDefaults(
    defineProps<{
        logs?: ScheduleItem[];
        loading?: boolean;
        qrEnabled?: boolean;
    }>(),
    {
        logs: () => [],
        loading: false,
        qrEnabled: true,
    },
);

const emit = defineEmits<{
    refresh: [];
}>();

const {
    formatDate,
    getScheduleDate,
    scheduleStatusTheme,
    scheduleStatusLabel,
    today,
    toLocalDateString,
} = useSchedule();

const NOT_YET_DONE_STATUSES = ["pending"];

const upcomingSchedules = computed(() =>
    props.logs.filter((schedule) =>
        NOT_YET_DONE_STATUSES.includes(schedule.status),
    ),
);

const historySchedules = computed(() =>
    props.logs.filter(
        (schedule) => !NOT_YET_DONE_STATUSES.includes(schedule.status),
    ),
);

const groups = computed(() => {
    const list: {
        key: string;
        title: string | null;
        schedules: ScheduleItem[];
    }[] = [];

    if (upcomingSchedules.value.length) {
        list.push({
            key: "upcoming",
            title: "Scheduled Days",
            schedules: upcomingSchedules.value,
        });
    }

    if (historySchedules.value.length) {
        list.push({
            key: "history",
            title: upcomingSchedules.value.length ? "History" : null,
            schedules: historySchedules.value,
        });
    }

    return list;
});

function dayBadge(schedule: ScheduleItem) {
    const date = getScheduleDate(schedule);
    if (!date) return null;

    if (date === today()) return "Today";

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    return date === toLocalDateString(tomorrow) ? "Tomorrow" : null;
}

function latestVisit(service: ScheduleServiceItem) {
    const visits = (service.assignees ?? []).flatMap((a) => a.online ?? []);
    if (!visits.length) return null;

    return visits.reduce((latest, visit) => {
        if (!visit.in_timestamp) return latest;
        if (!latest?.in_timestamp) return visit;

        return new Date(visit.in_timestamp) > new Date(latest.in_timestamp)
            ? visit
            : latest;
    }, visits[0]);
}

const expandedStaff = ref<Set<number>>(new Set());

const isExpanded = (service: ScheduleServiceItem) =>
    expandedStaff.value.has(service.schedule_services_id);

function toggleStaff(service: ScheduleServiceItem) {
    const next = new Set(expandedStaff.value);
    if (!next.delete(service.schedule_services_id)) next.add(service.schedule_services_id);
    expandedStaff.value = next;
}

function staffState(assignee: ScheduleAssignee) {
    const rows = (assignee.online ?? []).filter((row) => row.in_timestamp);
    const open = rows.find((row) => !row.out_timestamp);
    const last = rows[rows.length - 1];

    if (open) {
        return {
            label: `Present since ${formatTimestamp(open.in_timestamp)}`,
            class: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
        };
    }

    if (last) {
        return {
            label: `Attended ${formatTimestamp(last.in_timestamp)}`,
            class: "bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300",
        };
    }

    return {
        label: "Not checked in",
        class: "bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400",
    };
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

const { success, error } = useToast();

const REVIEW_NOTIFY_COOLDOWN_MS = 10 * 60 * 1000;

const reviewNotifyTick = ref(0);
const sendingReview = ref<number | null>(null);
const secondsTick = ref(0);
let secondsTickInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    secondsTickInterval = setInterval(() => {
        secondsTick.value++;
    }, 1000);
});

onUnmounted(() => {
    if (secondsTickInterval) clearInterval(secondsTickInterval);
});

function isReviewable(schedule: ScheduleItem): boolean {
    return ["pending", "ongoing", "missed"].includes(
        (schedule.status ?? "").toLowerCase(),
    );
}

function reviewNotifyKey(schedule: ScheduleItem) {
    return `medical-review-notify:${schedule.schedule_id}`;
}

function lastReviewNotifyAt(schedule: ScheduleItem): number {
    try {
        const raw = window.localStorage.getItem(reviewNotifyKey(schedule));
        return raw ? Number(raw) || 0 : 0;
    } catch {
        return 0;
    }
}

function canRequestReview(schedule: ScheduleItem): boolean {
    reviewNotifyTick.value;
    secondsTick.value;

    return (
        Date.now() - lastReviewNotifyAt(schedule) >= REVIEW_NOTIFY_COOLDOWN_MS
    );
}

function reviewButtonLabel(schedule: ScheduleItem): string {
    if (canRequestReview(schedule)) return "Notify Admission Staff";

    const waitMs =
        REVIEW_NOTIFY_COOLDOWN_MS -
        (Date.now() - lastReviewNotifyAt(schedule));
    const totalSeconds = Math.max(0, Math.ceil(waitMs / 1000));

    return `Available in ${Math.floor(totalSeconds / 60)}:${String(totalSeconds % 60).padStart(2, "0")}`;
}

async function requestScheduleReview(schedule: ScheduleItem) {
    if (
        !canRequestReview(schedule) ||
        sendingReview.value === schedule.schedule_id
    ) {
        return;
    }

    sendingReview.value = schedule.schedule_id;

    try {
        await patientAccessService.executeAction({
            action: "request_schedule_review",
            patient_id: schedule.patient?.patient_id,
            schedule_id: schedule.schedule_id,
        });

        try {
            window.localStorage.setItem(
                reviewNotifyKey(schedule),
                String(Date.now()),
            );
        } catch {}

        reviewNotifyTick.value++;
        success("Admission staff have been notified to review this schedule.");
    } catch (err: any) {
        error(
            err?.data?.message ||
                err?.message ||
                "Failed to send the notification.",
        );
    } finally {
        sendingReview.value = null;
    }
}

const generatingFor = ref<number | null>(null);
const showQrModal = ref(false);
const qrToken = ref<string | null>(null);
const qrMode = ref<"clock-in" | "clock-out">("clock-in");
const qrCaregivers = ref<{ employee_id: number; name: string }[]>([]);
const qrCheckedIn = ref<{
    employee_id: number;
    name: string;
    in_timestamp: string | null;
} | null>(null);

const medicalServiceOf = (schedule: ScheduleItem) =>
    (schedule.services ?? []).find((service) => service.type === "Medical") ??
    schedule.services?.[0];

function hasStarted(schedule: ScheduleItem) {
    return (schedule.services ?? []).some((service) =>
        (service.assignees ?? []).some((assignee) =>
            (assignee.online ?? []).some((row) => row.in_timestamp),
        ),
    );
}

function canShowQr(schedule: ScheduleItem) {
    return (
        props.qrEnabled &&
        !schedule.patient?.is_admitted &&
        schedule.category !== "Facility" &&
        getScheduleDate(schedule) === today() &&
        ["pending", "ongoing"].includes((schedule.status ?? "").toLowerCase()) &&
        !!medicalServiceOf(schedule)
    );
}

async function openQr(type: "in" | "out", schedule: ScheduleItem) {
    const service = medicalServiceOf(schedule);
    if (!service) return;

    generatingFor.value = schedule.schedule_id;
    qrToken.value = null;
    qrMode.value = type === "in" ? "clock-in" : "clock-out";
    showQrModal.value = true;

    try {
        const res = await attendanceService.generateQr({
            type,
            schedule_services_id: service.schedule_services_id,
            as_family: true,
        });

        qrToken.value = res?.token ?? res?.data?.token ?? null;
        qrCaregivers.value = res?.caregivers ?? [];
        qrCheckedIn.value = res?.checked_in ?? null;
    } catch (err: any) {
        showQrModal.value = false;
        error(err?.message ?? "Unable to create the QR code.");

        if (err?.status === 409) emit("refresh");
    } finally {
        generatingFor.value = null;
    }
}

function closeQr() {
    showQrModal.value = false;
    qrToken.value = null;
}

function onQrScanned() {
    success("The care team scanned your code.");
    closeQr();
    emit("refresh");
}

function formatTimestamp(value?: string | null) {
    if (!value) return "—";

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return "—";

    return parsed.toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}
</script>
