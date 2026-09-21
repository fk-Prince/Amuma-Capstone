<template>
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
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="close"
            />

            <div
                class="relative z-50 flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Facility caregivers
                        </p>

                        <h2
                            class="mt-1 truncate text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ patientName || "Resident" }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div
                    v-if="loading"
                    class="min-h-0 flex-1 animate-pulse space-y-4 overflow-y-auto p-6"
                >
                    <div class="h-4 w-32 rounded bg-slate-200 dark:bg-white/10" />
                    <div
                        v-for="i in 2"
                        :key="i"
                        class="h-16 rounded-xl bg-slate-100 dark:bg-white/5"
                    />
                    <div class="h-4 w-40 rounded bg-slate-200 dark:bg-white/10" />
                    <div class="h-40 rounded-xl bg-slate-100 dark:bg-white/5" />
                </div>

                <div
                    v-else-if="loadError"
                    class="flex-1 p-10 text-center text-sm text-slate-500 dark:text-gray-400"
                >
                    {{ loadError }}
                </div>

                <div
                    v-else
                    class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6"
                >
                    <section>
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Assigned · {{ activeShifts.length }}
                        </p>

                        <div
                            v-if="!activeShifts.length"
                            class="mt-3 rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500 dark:border-white/10 dark:text-gray-400"
                        >
                            No caregiver is assigned to this resident yet.
                        </div>

                        <ul v-else class="mt-3 space-y-2">
                            <li
                                v-for="shift in activeShifts"
                                :key="shift.caregiver_facility_shift_id"
                                class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 dark:border-white/10"
                            >
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-xs font-semibold text-primary"
                                >
                                    <img
                                        v-if="shift.avatar"
                                        :src="shift.avatar"
                                        :alt="shift.caregiver_name ?? ''"
                                        class="h-full w-full object-cover"
                                    />
                                    <template v-else>
                                        {{ initials(shift.caregiver_name ?? "") }}
                                    </template>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                    >
                                        {{ shift.caregiver_name }}
                                    </p>

                                    <p
                                        class="truncate text-xs text-slate-500 dark:text-gray-400"
                                    >
                                        {{ formatTime(shift.start_time) }} –
                                        {{ formatTime(shift.end_time) }}
                                        <template v-if="shift.note">
                                            · {{ shift.note }}
                                        </template>
                                        <template v-if="shift.phone_number">
                                            · {{ formatPhone(shift.phone_number) }}
                                        </template>
                                    </p>

                                    <p
                                        v-if="dutyHoursOf(shift.caregiver_id) > dutyLimit"
                                        class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-amber-600 dark:text-amber-300"
                                    >
                                        <TriangleAlert class="h-3 w-3" />
                                        On duty
                                        {{ dutyHoursOf(shift.caregiver_id) }}
                                        hours a day · over the limit of
                                        {{ dutyLimit }}
                                    </p>

                                    <p
                                        v-if="isOverLimit(shift.caregiver_id)"
                                        class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 dark:text-rose-300"
                                    >
                                        <TriangleAlert class="h-3 w-3" />
                                        Looking after
                                        {{ residentsOf(shift.caregiver_id) }}
                                        residents · over the limit of {{ limit }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    :disabled="busyId === shift.caregiver_facility_shift_id"
                                    class="shrink-0 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 disabled:opacity-50 dark:border-rose-500/30 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                    @click="setActive(shift, false)"
                                >
                                    Unassign
                                </button>
                            </li>
                        </ul>

                        <div v-if="inactiveShifts.length" class="mt-3">
                            <button
                                type="button"
                                class="text-xs font-medium text-slate-500 hover:text-primary dark:text-gray-400"
                                @click="showPrevious = !showPrevious"
                            >
                                {{ showPrevious ? "Hide" : "Show" }} previously
                                assigned ({{ inactiveShifts.length }})
                            </button>

                            <ul v-if="showPrevious" class="mt-2 space-y-2">
                                <li
                                    v-for="shift in inactiveShifts"
                                    :key="shift.caregiver_facility_shift_id"
                                    class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 dark:bg-white/5"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-medium text-slate-600 dark:text-gray-300"
                                        >
                                            {{ shift.caregiver_name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-slate-400 dark:text-gray-500"
                                        >
                                            {{ formatTime(shift.start_time) }} –
                                            {{ formatTime(shift.end_time) }}
                                            <template v-if="shift.note">
                                                · {{ shift.note }}
                                            </template>
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        :disabled="busyId === shift.caregiver_facility_shift_id || isAssigned(shift.caregiver_id)"
                                        :title="isAssigned(shift.caregiver_id) ? 'Already assigned to this resident.' : undefined"
                                        class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-primary/40 hover:text-primary disabled:opacity-50 dark:border-white/10 dark:text-gray-300"
                                        @click="setActive(shift, true)"
                                    >
                                        Assign again
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <section class="border-t border-slate-100 pt-6 dark:border-white/10">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Assign a caregiver
                        </p>

                        <div
                            v-if="!caregivers.length"
                            class="mt-3 rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500 dark:border-white/10 dark:text-gray-400"
                        >
                            No in-house facility caregiver is available in this
                            branch. Set a caregiver's assignment to Inhouse
                            Facility in Employees first.
                        </div>

                        <form
                            v-else
                            class="mt-3 space-y-4"
                            @submit.prevent="submit"
                        >
                            <div class="grid gap-2 sm:grid-cols-2">
                                <button
                                    v-for="caregiver in caregivers"
                                    :key="caregiver.employee_id"
                                    type="button"
                                    :disabled="isAssigned(caregiver.employee_id)"
                                    class="flex items-center gap-3 rounded-xl border p-3 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="
                                        form.caregiver_id === caregiver.employee_id
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                            : 'border-slate-200 hover:border-primary/40 dark:border-white/10'
                                    "
                                    @click="form.caregiver_id = caregiver.employee_id"
                                >
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-[11px] font-semibold text-primary"
                                    >
                                        <img
                                            v-if="caregiver.avatar"
                                            :src="caregiver.avatar"
                                            :alt="caregiver.full_name"
                                            class="h-full w-full object-cover"
                                        />
                                        <template v-else>
                                            {{ initials(caregiver.full_name) }}
                                        </template>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block truncate text-sm font-medium text-slate-800 dark:text-white"
                                        >
                                            {{ caregiver.full_name }}
                                        </span>
                                        <span
                                            class="block text-xs"
                                            :class="
                                                caregiver.over_limit
                                                    ? 'font-semibold text-rose-600 dark:text-rose-300'
                                                    : caregiver.active_residents >= limit
                                                      ? 'font-medium text-amber-600 dark:text-amber-300'
                                                      : 'text-slate-400 dark:text-gray-500'
                                            "
                                        >
                                            <template v-if="isAssigned(caregiver.employee_id)">
                                                Already assigned to this resident
                                            </template>
                                            <template v-else>
                                                {{ caregiver.active_residents }} of
                                                {{ limit }} residents
                                                <template v-if="caregiver.over_limit">
                                                    · Over the limit
                                                </template>
                                            </template>
                                        </span>
                                    </span>
                                </button>
                            </div>

                            <p
                                v-if="willExceed"
                                class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300"
                            >
                                <TriangleAlert class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                {{ selected?.full_name }} already looks after
                                {{ selected?.active_residents }} residents.
                                Assigning this one goes over the limit of
                                {{ limit }}. You can still continue.
                            </p>

                            <p
                                v-if="dutyNote"
                                class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300"
                            >
                                <TriangleAlert class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                {{ dutyNote }}
                            </p>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    class="text-[11px] text-slate-400 dark:text-gray-500"
                                >
                                    Quick fill:
                                </span>
                                <button
                                    v-for="preset in PRESETS"
                                    :key="preset.note"
                                    type="button"
                                    class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition"
                                    :class="
                                        form.note === preset.note
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-slate-200 text-slate-500 hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-400'
                                    "
                                    @click="applyPreset(preset)"
                                >
                                    {{ preset.note }}
                                </button>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <label class="block">
                                    <span
                                        class="text-xs font-medium text-slate-500 dark:text-gray-400"
                                    >
                                        Start time
                                    </span>
                                    <input
                                        v-model="form.start_time"
                                        type="time"
                                        class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:bg-secondary dark:text-gray-300"
                                    />
                                </label>

                                <label class="block">
                                    <span
                                        class="text-xs font-medium text-slate-500 dark:text-gray-400"
                                    >
                                        End time
                                    </span>
                                    <input
                                        v-model="form.end_time"
                                        type="time"
                                        class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:bg-secondary dark:text-gray-300"
                                    />
                                </label>

                                <label class="block">
                                    <span
                                        class="text-xs font-medium text-slate-500 dark:text-gray-400"
                                    >
                                        Note
                                    </span>
                                    <input
                                        v-model="form.note"
                                        type="text"
                                        maxlength="255"
                                        placeholder="Optional"
                                        class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:bg-secondary dark:text-gray-300"
                                    />
                                </label>
                            </div>

                            <p
                                v-if="formError"
                                class="text-xs font-medium text-rose-600 dark:text-rose-300"
                            >
                                {{ formError }}
                            </p>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="saving"
                                    class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <Loader2
                                        v-if="saving"
                                        class="h-4 w-4 animate-spin"
                                    />
                                    {{ saving ? "Assigning..." : "Assign caregiver" }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Loader2, TriangleAlert, X } from "lucide-vue-next";

import { caregiverShiftService } from "~/api/caregiver-shift/CaregiverShiftService";
import type {
    CaregiverShift,
    CaregiverShiftList,
    CaregiverShiftOutcome,
    FacilityCaregiver,
} from "~/types/caregiver-shift";
import { useToast } from "~/composables/useToast";
import { formatPhone } from "~/utils/phone";
import { formatTime } from "~/utils/time";
import { initials } from "~/utils/user";

const props = defineProps<{
    open: boolean;
    admissionId: number | null;
    patientName?: string | null;
    branchUuid: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "count", value: number): void;
}>();

const { success, error } = useToast();

const PRESETS = [
    { note: "AM Shift", start_time: "00:00", end_time: "12:00" },
    { note: "PM Shift", start_time: "12:00", end_time: "00:00" },
    { note: "Full Shift", start_time: "00:00", end_time: "23:59" },
];

const loading = ref(false);
const loadError = ref("");
const saving = ref(false);
const busyId = ref<number | null>(null);
const showPrevious = ref(false);
const formError = ref("");

const shifts = ref<CaregiverShift[]>([]);
const caregivers = ref<FacilityCaregiver[]>([]);
const limit = ref(3);
const dutyLimit = ref(12);

const form = ref({
    caregiver_id: null as number | null,
    start_time: "",
    end_time: "",
    note: "",
});

const activeShifts = computed(() => shifts.value.filter((s) => s.is_active));
const inactiveShifts = computed(() => shifts.value.filter((s) => !s.is_active));

const selected = computed(
    () =>
        caregivers.value.find(
            (caregiver) => caregiver.employee_id === form.value.caregiver_id,
        ) ?? null,
);

const willExceed = computed(
    () => !!selected.value && selected.value.active_residents >= limit.value,
);

function toMinutes(time: string): number {
    const [hours, minutes] = time.split(":").map(Number);

    return (hours || 0) * 60 + (minutes || 0);
}

function dutyMinutes(windows: { start_time: string; end_time: string }[]) {
    const spans: [number, number][] = [];

    for (const window of windows) {
        const start = toMinutes(window.start_time);
        const end = toMinutes(window.end_time);

        if (end > start) {
            spans.push([start, end]);
        } else {
            spans.push([start, 1440], [0, end]);
        }
    }

    spans.sort((a, b) => a[0] - b[0]);

    let total = 0;
    let reach = 0;

    for (const [start, end] of spans) {
        const from = Math.max(start, reach);

        if (end > from) total += end - from;

        reach = Math.max(reach, end);
    }

    return total;
}

const dutyNote = computed(() => {
    const caregiver = selected.value;

    if (!caregiver || !form.value.start_time || !form.value.end_time) {
        return "";
    }

    if (form.value.start_time === form.value.end_time) return "";

    const hours =
        Math.round(
            (dutyMinutes([
                ...caregiver.duty_windows,
                {
                    start_time: form.value.start_time,
                    end_time: form.value.end_time,
                },
            ]) /
                60) *
                10,
        ) / 10;

    if (hours <= dutyLimit.value) return "";

    return `${caregiver.full_name} would be on duty about ${hours} hours a day. That is over the limit of ${dutyLimit.value} hours. You can still continue.`;
});

function dutyHoursOf(id: number) {
    return caregiverById(id)?.duty_hours ?? 0;
}

function isAssigned(id: number) {
    return activeShifts.value.some((shift) => shift.caregiver_id === id);
}

function caregiverById(id: number) {
    return caregivers.value.find((caregiver) => caregiver.employee_id === id);
}

function residentsOf(id: number) {
    return caregiverById(id)?.active_residents ?? 0;
}

function isOverLimit(id: number) {
    return caregiverById(id)?.over_limit ?? false;
}

function applyPreset(preset: (typeof PRESETS)[number]) {
    form.value.note = preset.note;
    form.value.start_time = preset.start_time;
    form.value.end_time = preset.end_time;
}

function resetForm() {
    form.value = { caregiver_id: null, start_time: "", end_time: "", note: "" };
    formError.value = "";
}

function applyOutcome(outcome: CaregiverShiftOutcome) {
    const index = shifts.value.findIndex(
        (s) =>
            s.caregiver_facility_shift_id ===
            outcome.shift.caregiver_facility_shift_id,
    );

    if (index === -1) {
        shifts.value = [...shifts.value, outcome.shift];
    } else {
        shifts.value = shifts.value.map((s, i) =>
            i === index ? outcome.shift : s,
        );
    }

    shifts.value = [...shifts.value].sort(
        (a, b) =>
            Number(b.is_active) - Number(a.is_active) ||
            a.start_time.localeCompare(b.start_time),
    );

    caregivers.value = caregivers.value.map((caregiver) =>
        caregiver.employee_id === outcome.caregiver.employee_id
            ? { ...caregiver, ...outcome.caregiver }
            : caregiver,
    );

    emit("count", outcome.active_count);
}

async function load() {
    if (!props.admissionId) return;

    loading.value = true;
    loadError.value = "";
    shifts.value = [];
    showPrevious.value = false;
    resetForm();

    try {
        const res = await caregiverShiftService.list({
            branch_uuid: props.branchUuid,
            admission_id: props.admissionId,
        });
        const data: CaregiverShiftList = res.data ?? res;

        shifts.value = data.shifts ?? [];
        caregivers.value = data.caregivers ?? [];
        limit.value = data.limit ?? 3;
        dutyLimit.value = data.duty_limit ?? 12;
    } catch (err: any) {
        loadError.value =
            err?.message ?? "Unable to load the caregivers for this resident.";
    } finally {
        loading.value = false;
    }
}

async function submit() {
    if (!props.admissionId || saving.value) return;

    formError.value = "";

    if (!form.value.caregiver_id) {
        formError.value = "Pick a caregiver.";
        return;
    }

    if (isAssigned(form.value.caregiver_id)) {
        formError.value = "This caregiver is already assigned to this resident.";
        return;
    }

    if (!form.value.start_time || !form.value.end_time) {
        formError.value = "Set the start and end time.";
        return;
    }

    if (form.value.start_time === form.value.end_time) {
        formError.value = "The end time must be different from the start time.";
        return;
    }

    saving.value = true;

    try {
        const res = await caregiverShiftService.assign({
            branch_uuid: props.branchUuid,
            admission_id: props.admissionId,
            caregiver_id: form.value.caregiver_id,
            start_time: form.value.start_time,
            end_time: form.value.end_time,
            note: form.value.note.trim() || null,
        });

        applyOutcome(res.data);
        success(res.message ?? "Caregiver assigned successfully.");
        resetForm();
    } catch (err: any) {
        formError.value = err?.message ?? "Unable to assign this caregiver.";
        error(formError.value);
    } finally {
        saving.value = false;
    }
}

async function setActive(shift: CaregiverShift, isActive: boolean) {
    if (busyId.value) return;

    busyId.value = shift.caregiver_facility_shift_id;

    try {
        const res = await caregiverShiftService.update(
            shift.caregiver_facility_shift_id,
            { branch_uuid: props.branchUuid, is_active: isActive },
        );

        applyOutcome(res.data);
        success(res.message);
    } catch (err: any) {
        error(err?.message ?? "Unable to update this assignment.");
    } finally {
        busyId.value = null;
    }
}

function close() {
    emit("close");
}

watch(
    () => props.open,
    (open) => {
        if (open) load();
    },
);
</script>
