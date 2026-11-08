<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
    Check,
    CheckCheck,
    Loader2,
    Lock,
    LogIn,
    LogOut,
    Stethoscope,
    X,
} from "lucide-vue-next";
import ActionButton from "~/components/ui/ActionButton.vue";
import TimeOutModal from "~/components/sections/app/Schedule/TimeOutModal.vue";
import PatientAvatar from "~/components/ui/PatientAvatar.vue";
import { attendanceService } from "~/api/attendance/AttendanceService";
import { useToast } from "~/composables/useToast";
import { initials } from "~/utils/user";
import { formatDate, formatTime } from "~/utils/time";
import type {
    PatientVisit,
    PatientVisitStaff as VisitStaff,
} from "~/types/schedule";

const props = defineProps<{
    visit: PatientVisit;
    patientUuid: string;
}>();

const emit = defineEmits<{
    close: [];
    confirmed: [];
}>();

const { success, error: toastError } = useToast();

const selected = ref<Set<number>>(new Set());
const saving = ref(false);

watch(
    () => props.visit,
    (visit) => {
        selected.value = new Set(
            visit.staff
                .filter((staff) => staff.is_me || staff.attended)
                .map((staff) => staff.employee_id),
        );
    },
    { immediate: true },
);

const alreadyCheckedIn = computed(() => Boolean(props.visit.checked_in_at));

const isTimedOut = (staff: VisitStaff) => !staff.attended && !!staff.timed_out_at;

const newlyPresent = computed(() =>
    props.visit.staff.filter(
        (staff) =>
            !staff.attended &&
            !isTimedOut(staff) &&
            selected.value.has(staff.employee_id),
    ),
);

const me = computed(() => props.visit.staff.find((staff) => staff.is_me));
const meTimedOut = computed(() => !!me.value && isTimedOut(me.value));
const othersPresent = computed(() =>
    props.visit.staff.some((staff) => !staff.is_me && staff.attended),
);

const isLocked = (staff: VisitStaff) =>
    staff.is_me || staff.attended || isTimedOut(staff);

function toggle(staff: VisitStaff) {
    if (isLocked(staff)) return;

    const next = new Set(selected.value);

    if (next.has(staff.employee_id)) {
        next.delete(staff.employee_id);
    } else {
        next.add(staff.employee_id);
    }

    selected.value = next;
}

async function confirm() {
    if (saving.value) return;

    saving.value = true;

    try {
        await attendanceService.confirmPatientVisit({
            patient_uuid: props.patientUuid,
            schedule_id: props.visit.schedule_id,
            employee_ids: [...selected.value],
        });

        success(
            alreadyCheckedIn.value
                ? `Timed in for ${props.visit.schedule_code}.`
                : `${props.visit.schedule_code} checked in.`,
        );

        emit("confirmed");
        emit("close");
    } catch (err: any) {
        toastError(
            err?.response?.data?.message ??
                err?.message ??
                "Unable to confirm attendance.",
        );
    } finally {
        saving.value = false;
    }
}

const timingOut = ref(false);
const showTimeOut = ref(false);
const unpaidFound = ref(false);

const othersPresentNames = computed(() =>
    props.visit.staff
        .filter((staff) => !staff.is_me && staff.attended)
        .map((staff) => staff.name),
);

const visitUnpaid = computed(() => !!props.visit.unpaid || unpaidFound.value);

async function timeOut(complete: boolean) {
    if (timingOut.value) return;

    timingOut.value = true;

    try {
        await attendanceService.timeOutPatientVisit({
            patient_uuid: props.patientUuid,
            schedule_id: props.visit.schedule_id,
            ...(complete && { complete: true }),
            ...(complete && visitUnpaid.value && { confirm_unpaid: true }),
        });

        showTimeOut.value = false;
        success(
            complete
                ? `${props.visit.schedule_code} completed.`
                : `You timed out of ${props.visit.schedule_code}.`,
        );

        emit("confirmed");
        emit("close");
    } catch (err: any) {
        if (err?.status === 428) {
            unpaidFound.value = true;
            showTimeOut.value = false;
            setTimeout(() => (showTimeOut.value = true));
            return;
        }

        showTimeOut.value = false;
        toastError(
            err?.response?.data?.message ??
                err?.message ??
                "Unable to time out this visit.",
        );
    } finally {
        timingOut.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div
                class="flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex items-start justify-between gap-3 border-b border-slate-100 p-5 dark:border-white/10"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <PatientAvatar
                            :src="visit.patient.avatar"
                            :name="visit.patient.name"
                            size-class="h-11 w-11 text-sm"
                            rounded-class="rounded-xl"
                        />

                        <div class="min-w-0">
                            <p
                                class="truncate text-base font-semibold text-slate-800 dark:text-white"
                            >
                                {{ visit.patient.name }}
                            </p>

                            <p
                                class="truncate text-xs text-slate-500 dark:text-gray-400"
                            >
                                <span class="font-mono">{{
                                    visit.schedule_code
                                }}</span>
                                <template v-if="visit.scheduled_at">
                                    · {{ formatDate(visit.scheduled_at) }},
                                    {{ formatTime(visit.scheduled_at) }}
                                </template>
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="shrink-0 rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10"
                        @click="emit('close')"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="service in visit.services"
                            :key="service"
                            class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-medium text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"
                        >
                            <Stethoscope class="h-3 w-3" />
                            {{ service }}
                        </span>
                    </div>

                    <p
                        v-if="alreadyCheckedIn"
                        class="rounded-xl border border-accent-100 bg-accent-50/60 px-3 py-2.5 text-xs text-accent-700 dark:border-accent-500/20 dark:bg-accent-500/10 dark:text-accent-300"
                    >
                        Checked in at {{ formatTime(visit.checked_in_at) }}.
                        <template v-if="newlyPresent.length">
                            Time in yourself and anyone who arrived later.
                        </template>
                        <template v-else-if="meTimedOut && me?.timed_out_at">
                            You timed out at
                            {{ formatTime(me.timed_out_at) }}. Complete the
                            visit once everyone is done.
                        </template>
                        <template v-else-if="othersPresent">
                            Time out when you leave. You can also mark the
                            visit as complete for everyone.
                        </template>
                        <template v-else>
                            You're the last one here. Timing out completes the
                            visit.
                        </template>
                    </p>

                    <div>
                        <p
                            class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Who attended?
                        </p>

                        <ul class="space-y-2">
                            <li
                                v-for="staff in visit.staff"
                                :key="staff.employee_id"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-xl border p-3 text-left transition"
                                    :class="[
                                        staff.attended || isTimedOut(staff)
                                            ? 'border-slate-200 opacity-50 dark:border-white/10'
                                            : selected.has(staff.employee_id)
                                              ? 'border-primary/40 bg-primary/[0.04] ring-1 ring-primary/20'
                                              : 'border-slate-200 hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5',
                                        isLocked(staff) ? 'cursor-default' : '',
                                    ]"
                                    @click="toggle(staff)"
                                >
                                    <span
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border"
                                        :class="
                                            staff.attended || isTimedOut(staff)
                                                ? 'border-slate-300 dark:border-white/20'
                                                : selected.has(staff.employee_id)
                                                  ? 'border-primary bg-primary text-white'
                                                  : 'border-slate-300 dark:border-white/20'
                                        "
                                    >
                                        <Lock
                                            v-if="
                                                staff.is_me &&
                                                !staff.attended &&
                                                !isTimedOut(staff)
                                            "
                                            class="h-3 w-3"
                                        />
                                        <Check
                                            v-else-if="
                                                !staff.attended &&
                                                !isTimedOut(staff) &&
                                                selected.has(staff.employee_id)
                                            "
                                            class="h-3.5 w-3.5"
                                        />
                                    </span>

                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-[11px] font-semibold text-primary"
                                    >
                                        <img
                                            v-if="staff.avatar"
                                            :src="staff.avatar"
                                            :alt="staff.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <template v-else>
                                            {{ initials(staff.name) }}
                                        </template>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block truncate text-sm font-semibold text-slate-800 dark:text-white"
                                        >
                                            {{ staff.name }}
                                            <span
                                                v-if="staff.is_me"
                                                class="font-normal text-slate-400"
                                                >(you)</span
                                            >
                                        </span>

                                        <span
                                            class="block truncate text-xs capitalize text-slate-500 dark:text-gray-400"
                                        >
                                            {{ staff.role ?? "Staff" }}
                                            <template v-if="staff.note">
                                                · {{ staff.note }}
                                            </template>
                                        </span>

                                        <span
                                            v-if="staff.attended_at"
                                            class="block truncate text-[11px] text-emerald-600 dark:text-emerald-300"
                                        >
                                            Present since
                                            {{ formatTime(staff.attended_at) }}
                                            <template
                                                v-if="staff.attendance_note"
                                            >
                                                ·
                                                {{ staff.attendance_note }}
                                            </template>
                                        </span>

                                        <span
                                            v-else-if="isTimedOut(staff)"
                                            class="block truncate text-[11px] text-slate-500 dark:text-gray-400"
                                        >
                                            Timed out at
                                            {{ formatTime(staff.timed_out_at) }}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 dark:border-white/10"
                >
                    <p class="text-xs text-slate-400 dark:text-gray-500">
                        {{ selected.size }} of {{ visit.staff.length }} present
                    </p>

                    <div class="flex gap-2">
                        <ActionButton
                            variant="outline"
                            :disabled="saving || timingOut"
                            @click="emit('close')"
                        >
                            Cancel
                        </ActionButton>

                        <ActionButton
                            v-if="alreadyCheckedIn && newlyPresent.length"
                            variant="primary"
                            :disabled="saving || timingOut"
                            @click="confirm"
                        >
                            <span class="inline-flex items-center gap-2">
                                <Loader2
                                    v-if="saving"
                                    class="h-4 w-4 animate-spin"
                                />
                                <LogIn v-else class="h-4 w-4" />
                                Time in
                            </span>
                        </ActionButton>

                        <ActionButton
                            v-else-if="alreadyCheckedIn"
                            variant="primary"
                            :disabled="saving || timingOut"
                            @click="showTimeOut = true"
                        >
                            <span class="inline-flex items-center gap-2">
                                <Loader2
                                    v-if="timingOut"
                                    class="h-4 w-4 animate-spin"
                                />
                                <CheckCheck v-else-if="meTimedOut" class="h-4 w-4" />
                                <LogOut v-else class="h-4 w-4" />
                                {{
                                    meTimedOut
                                        ? "Complete visit"
                                        : othersPresent
                                          ? "Time out"
                                          : "Time out & complete"
                                }}
                            </span>
                        </ActionButton>

                        <ActionButton
                            v-else
                            variant="primary"
                            :disabled="saving"
                            @click="confirm"
                        >
                            <span class="inline-flex items-center gap-2">
                                <Loader2
                                    v-if="saving"
                                    class="h-4 w-4 animate-spin"
                                />
                                <LogIn v-else class="h-4 w-4" />
                                Time in

                            </span>
                        </ActionButton>
                    </div>
                </div>
            </div>
        </div>

        <TimeOutModal
            :open="showTimeOut"
            :schedule-code="visit.schedule_code"
            :patient-name="visit.patient.name"
            :services="visit.services"
            :scanner-name="me?.name ?? 'You'"
            :scanner-timed-out="meTimedOut"
            :others-present="othersPresentNames"
            :ends-at="visit.ends_at ?? null"
            :unpaid="visitUnpaid"
            :loading="timingOut"
            @close="showTimeOut = false"
            @submit="timeOut"
        />
    </Teleport>
</template>
