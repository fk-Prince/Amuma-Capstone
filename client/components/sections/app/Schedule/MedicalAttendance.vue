<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Check, Loader2, Pencil, QrCode } from "lucide-vue-next";
import type { AdlAttendanceAssignment, ScheduleItem } from "~/types/schedule";
import { initials } from "~/utils/user";
import { formatDuration, formatTime } from "~/utils/time";
import { attendanceService } from "~/api/attendance/AttendanceService";
import { usePermissions } from "~/composables/usePermission";
import { useToast } from "~/composables/useToast";
import { Modules } from "~/types/module";

const props = defineProps<{
    schedule: ScheduleItem;
    branchUuid: string;
}>();

const { canUpdate } = usePermissions();
const { success, error: toastError } = useToast();

const editing = ref(false);
const saving = ref(false);
const selected = ref<Set<number>>(new Set());

type Badge = { label: string; class: string };

const TYPE_LABELS: Record<string, Badge> = {
    scanned: {
        label: "Scanned QR",
        class: "bg-primary/10 text-primary dark:bg-primary-500/15 dark:text-primary-300",
    },
    marked: {
        label: "Marked",
        class: "bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300",
    },
    force: {
        label: "Recorded",
        class: "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300",
    },
    handover: {
        label: "Handover",
        class: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
    },
};

const status = computed(() => (props.schedule.status ?? "").toLowerCase());

const medicalServices = computed(() =>
    (props.schedule.services ?? []).filter(
        (service) => service.type === "Medical",
    ),
);

const visible = computed(
    () =>
        props.schedule.category !== "Facility" &&
        medicalServices.value.length > 0,
);

const staff = computed(() => {
    const byEmployee = new Map<
        number,
        {
            employee_id: number;
            name: string;
            role: string | null;
            avatar: string | null;
            note: string | null;
            rows: AdlAttendanceAssignment[];
        }
    >();

    medicalServices.value.forEach((service) => {
        (service.assignees ?? []).forEach((assignee) => {
            const id = Number(assignee.employee_id);
            const entry = byEmployee.get(id) ?? {
                employee_id: id,
                name: assignee.full_name,
                role: assignee.employee_role,
                avatar: assignee.avatar,
                note: assignee.note,
                rows: [],
            };

            entry.rows.push(
                ...(assignee.online ?? []).filter((row) => row.in_timestamp),
            );
            byEmployee.set(id, entry);
        });
    });

    return [...byEmployee.values()].map((entry) => {
        const sorted = [...entry.rows].sort(
            (a, b) => ms(a.in_timestamp) - ms(b.in_timestamp),
        );
        const first = sorted[0] ?? null;
        const last = sorted[sorted.length - 1] ?? null;

        return {
            ...entry,
            in_at: first?.in_timestamp ?? null,
            out_at: last?.out_timestamp ?? null,
            badge: (first && TYPE_LABELS[first.type_in]) || null,
            notes: first?.notes ?? null,
        };
    });
});

const allRows = computed(() => staff.value.flatMap((entry) => entry.rows));

const timeIn = computed(() => {
    const times = allRows.value.map((row) => ms(row.in_timestamp));

    return times.length ? Math.min(...times) : null;
});

const timeOut = computed(() => {
    if (!allRows.value.length || allRows.value.some((row) => !row.out_timestamp)) {
        return null;
    }

    return Math.max(...allRows.value.map((row) => ms(row.out_timestamp)));
});

const duration = computed(() =>
    timeIn.value && timeOut.value
        ? formatDuration((timeOut.value - timeIn.value) / 3_600_000) ||
          "Less than a minute"
        : null,
);

const presentCount = computed(
    () => staff.value.filter((entry) => entry.in_at).length,
);

function ms(value: string | null) {
    return value ? new Date(value).getTime() : 0;
}

function clock(value: number | string | null) {
    return value ? formatTime(new Date(value).toISOString()) : "";
}

function stateOf(entry: (typeof staff.value)[number]) {
    if (entry.in_at) return "present";

    return status.value === "completed" ? "absent" : "pending";
}

const canEdit = computed(
    () =>
        ["ongoing", "completed"].includes(status.value) &&
        staff.value.length > 0 &&
        canUpdate(Modules.Schedules),
);

const changed = computed(
    () =>
        staff.value.some(
            (entry) => !!entry.in_at !== selected.value.has(entry.employee_id),
        ),
);

watch(
    () => props.schedule.schedule_id,
    () => (editing.value = false),
);

function startEdit() {
    selected.value = new Set(
        staff.value.filter((entry) => entry.in_at).map((entry) => entry.employee_id),
    );
    editing.value = true;
}

function toggle(employeeId: number) {
    const next = new Set(selected.value);
    next.has(employeeId) ? next.delete(employeeId) : next.add(employeeId);
    selected.value = next;
}

async function save() {
    saving.value = true;

    try {
        const res = await attendanceService.updateVisitAttendance({
            branch_uuid: props.branchUuid,
            schedule_id: props.schedule.schedule_id,
            employee_ids: [...selected.value],
        });

        const updates: {
            schedule_services_id: number;
            employee_id: number;
            online: AdlAttendanceAssignment[];
        }[] = res?.data ?? [];

        medicalServices.value.forEach((service) => {
            (service.assignees ?? []).forEach((assignee) => {
                const update = updates.find(
                    (row) =>
                        Number(row.schedule_services_id) ===
                            Number(service.schedule_services_id) &&
                        Number(row.employee_id) === Number(assignee.employee_id),
                );

                if (update) assignee.online = update.online;
            });
        });

        editing.value = false;
        success("Attendance updated.");
    } catch (err: any) {
        toastError(
            err?.response?.data?.message ??
                err?.message ??
                "Unable to update attendance.",
        );
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div
        v-if="visible"
        class="rounded-xl border border-slate-100 p-3 dark:border-white/10"
    >
        <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-semibold text-slate-800 dark:text-white">
                Attendance
            </p>

            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-semibold text-primary dark:bg-primary-500/15 dark:text-primary-300"
                >
                    <QrCode class="h-3.5 w-3.5" />
                    QR check-in
                </span>

                <button
                    v-if="canEdit && !editing"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                    @click="startEdit"
                >
                    <Pencil class="h-3.5 w-3.5" />
                    Edit
                </button>
            </div>
        </div>

        <template v-if="editing">
            <p class="mt-3 text-xs text-slate-500 dark:text-gray-400">
                {{
                    status === "ongoing"
                        ? "Tick the staff who are at this visit. Staff you add are recorded from the visit's time in."
                        : "Tick the staff who showed up for this visit. Staff you add are recorded for the visit's time in and time out."
                }}
            </p>

            <ul
                class="mt-2 divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-100 dark:divide-white/10 dark:border-white/10"
            >
                <li v-for="entry in staff" :key="entry.employee_id">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition hover:bg-slate-50 dark:hover:bg-white/5"
                        :disabled="saving"
                        @click="toggle(entry.employee_id)"
                    >
                        <span
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border"
                            :class="
                                selected.has(entry.employee_id)
                                    ? 'border-primary bg-primary text-white'
                                    : 'border-slate-300 dark:border-white/20'
                            "
                        >
                            <Check
                                v-if="selected.has(entry.employee_id)"
                                class="h-3.5 w-3.5"
                            />
                        </span>

                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-[11px] font-semibold text-primary"
                        >
                            <img
                                v-if="entry.avatar"
                                :src="entry.avatar"
                                :alt="entry.name"
                                class="h-full w-full object-cover"
                            />
                            <template v-else>{{ initials(entry.name) }}</template>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span
                                class="block truncate text-sm font-semibold text-slate-800 dark:text-white"
                            >
                                {{ entry.name }}
                            </span>
                            <span
                                class="block truncate text-xs capitalize text-slate-400 dark:text-gray-500"
                            >
                                {{ entry.role ?? "Staff" }}
                                <template v-if="entry.note">· {{ entry.note }}</template>
                            </span>
                        </span>

                        <span
                            class="shrink-0 text-xs font-semibold"
                            :class="
                                selected.has(entry.employee_id)
                                    ? 'text-emerald-600 dark:text-emerald-300'
                                    : 'text-rose-600 dark:text-rose-300'
                            "
                        >
                            {{ selected.has(entry.employee_id) ? "Present" : "Absent" }}
                        </span>
                    </button>
                </li>
            </ul>

            <div class="mt-3 flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                    :disabled="saving"
                    @click="editing = false"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:opacity-50"
                    :disabled="saving || !changed || (status === 'ongoing' && !selected.size)"
                    @click="save"
                >
                    <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                    Save attendance
                </button>
            </div>
        </template>

        <div
            v-else-if="!allRows.length"
            class="mt-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
        >
            {{
                status === "completed" || status === "cancelled" || status === "missed"
                    ? "No attendance was recorded for this visit."
                    : "Waiting for time in. An assigned staff member scans the patient's QR to start the visit."
            }}
        </div>

        <template v-else>
            <div class="mt-3 grid grid-cols-3 gap-2">
                <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-white/5">
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">
                        Time in
                    </p>
                    <p
                        class="text-sm font-semibold tabular-nums text-slate-800 dark:text-white"
                    >
                        {{ clock(timeIn) }}
                    </p>
                </div>

                <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-white/5">
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">
                        Time out
                    </p>
                    <p
                        class="text-sm font-semibold tabular-nums text-slate-800 dark:text-white"
                    >
                        {{ timeOut ? clock(timeOut) : "—" }}
                    </p>
                </div>

                <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-white/5">
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">
                        Duration
                    </p>
                    <p
                        class="text-sm font-semibold tabular-nums text-slate-800 dark:text-white"
                    >
                        {{ duration ?? "Ongoing" }}
                    </p>
                </div>
            </div>

            <ul
                class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-100 dark:divide-white/10 dark:border-white/10"
            >
                <li
                    v-for="entry in staff"
                    :key="entry.employee_id"
                    class="flex items-center gap-3 border-l-[3px] px-3 py-2.5"
                    :class="{
                        'border-l-emerald-500': stateOf(entry) === 'present',
                        'border-l-rose-500': stateOf(entry) === 'absent',
                        'border-l-slate-300 dark:border-l-white/20':
                            stateOf(entry) === 'pending',
                    }"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-[11px] font-semibold text-primary"
                    >
                        <img
                            v-if="entry.avatar"
                            :src="entry.avatar"
                            :alt="entry.name"
                            class="h-full w-full object-cover"
                        />
                        <template v-else>{{ initials(entry.name) }}</template>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                        >
                            {{ entry.name }}
                        </p>

                        <p
                            class="truncate text-xs capitalize text-slate-400 dark:text-gray-500"
                        >
                            {{ entry.role ?? "Staff" }}
                            <template v-if="entry.note">· {{ entry.note }}</template>
                        </p>

                        <p
                            class="text-xs tabular-nums"
                            :class="{
                                'text-emerald-600 dark:text-emerald-300':
                                    stateOf(entry) === 'present',
                                'text-rose-600 dark:text-rose-300':
                                    stateOf(entry) === 'absent',
                                'text-slate-400 dark:text-gray-500':
                                    stateOf(entry) === 'pending',
                            }"
                        >
                            <template v-if="stateOf(entry) === 'present'">
                                Present · {{ clock(entry.in_at) }}
                                {{
                                    entry.out_at
                                        ? `– ${clock(entry.out_at)}`
                                        : "– now"
                                }}
                            </template>
                            <template v-else-if="stateOf(entry) === 'absent'">
                                Absent
                            </template>
                            <template v-else>Not confirmed</template>
                        </p>

                        <p
                            v-if="entry.notes"
                            class="truncate text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            {{ entry.notes }}
                        </p>
                    </div>

                    <span
                        v-if="entry.badge"
                        class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                        :class="entry.badge.class"
                    >
                        {{ entry.badge.label }}
                    </span>
                </li>
            </ul>

            <p class="mt-2 text-xs text-slate-400 dark:text-gray-500">
                {{ presentCount }} of {{ staff.length }} staff present
            </p>
        </template>
    </div>
</template>
