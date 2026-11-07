<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
    Check,
    Loader2,
    Lock,
    LogIn,
    LogOut,
    Stethoscope,
    X,
} from "lucide-vue-next";
import ActionButton from "~/components/ui/ActionButton.vue";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
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

const newlyPresent = computed(() =>
    props.visit.staff.filter(
        (staff) => !staff.attended && selected.value.has(staff.employee_id),
    ),
);

const isLocked = (staff: VisitStaff) => staff.is_me || staff.attended;

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
const unpaidMessage = ref("");

async function timeOut(confirmUnpaid = false) {
    if (timingOut.value) return;

    timingOut.value = true;

    try {
        await attendanceService.timeOutPatientVisit({
            patient_uuid: props.patientUuid,
            schedule_id: props.visit.schedule_id,
            ...(confirmUnpaid && { confirm_unpaid: true }),
        });

        unpaidMessage.value = "";
        success(`${props.visit.schedule_code} timed out and completed.`);

        emit("confirmed");
        emit("close");
    } catch (err: any) {
        if (err?.status === 428) {
            unpaidMessage.value = err.message;
            return;
        }

        unpaidMessage.value = "";
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
                        <template v-else>
                            Time out to complete the visit.
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
                                        staff.attended
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
                                            staff.attended
                                                ? 'border-slate-300 dark:border-white/20'
                                                : selected.has(staff.employee_id)
                                                  ? 'border-primary bg-primary text-white'
                                                  : 'border-slate-300 dark:border-white/20'
                                        "
                                    >
                                        <Lock
                                            v-if="staff.is_me && !staff.attended"
                                            class="h-3 w-3"
                                        />
                                        <Check
                                            v-else-if="
                                                !staff.attended &&
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
                            @click="timeOut()"
                        >
                            <span class="inline-flex items-center gap-2">
                                <Loader2
                                    v-if="timingOut"
                                    class="h-4 w-4 animate-spin"
                                />
                                <LogOut v-else class="h-4 w-4" />
                                Time out
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

        <ConfirmDialog
            :open="!!unpaidMessage"
            title="Not fully paid"
            :message="unpaidMessage"
            confirm-label="Time out"
            :loading="timingOut"
            @confirm="timeOut(true)"
            @cancel="unpaidMessage = ''"
        />
    </Teleport>
</template>
