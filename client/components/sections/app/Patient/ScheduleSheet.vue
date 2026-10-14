<template>
    <div class="space-y-5 bg-white p-8 text-black">
        <div
            class="flex items-start justify-between gap-4 border-b border-gray-200 pb-4"
        >
            <div>
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500"
                >
                    {{ branchName || "Amuma Care" }}
                </p>

                <h1 class="mt-1 text-lg font-semibold">Schedule Details</h1>
            </div>

            <div class="text-right">
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500"
                >
                    {{ schedule.schedule_code }}
                </p>

                <p class="text-sm font-semibold">
                    {{ scheduleStatusLabel(schedule.status) }}
                </p>
            </div>
        </div>

        <section v-for="group in groups" :key="group.title" class="space-y-2">
            <p
                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500"
            >
                {{ group.title }}
            </p>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-1.5">
                <div
                    v-for="row in group.rows"
                    :key="row.label"
                    class="flex items-baseline justify-between gap-3 border-b border-dashed border-gray-200 py-1"
                >
                    <dt class="text-[11px] text-gray-500">{{ row.label }}</dt>

                    <dd class="text-right text-[12px] font-medium">
                        {{ row.value }}
                    </dd>
                </div>
            </dl>
        </section>

        <section v-if="schedule.note" class="space-y-1">
            <p
                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500"
            >
                Note
            </p>

            <p class="whitespace-pre-wrap text-[12px]">{{ schedule.note }}</p>
        </section>

        <section class="space-y-3">
            <p
                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500"
            >
                Services
            </p>

            <div
                v-for="service in schedule.services ?? []"
                :key="service.schedule_services_id"
                class="break-inside-avoid space-y-2 rounded-xl border border-gray-300 p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[13px] font-semibold">
                            {{
                                service.service_name ??
                                "Activity of Daily Living (ADL)"
                            }}
                        </p>

                        <p v-if="service.type" class="text-[11px] text-gray-500">
                            Performed by: {{ performedByLabel(service) }}
                        </p>
                    </div>

                    <p
                        v-if="durationFor(service)"
                        class="shrink-0 text-[11px] font-medium"
                    >
                        {{ durationFor(service) }}
                    </p>
                </div>

                <p
                    v-if="service.description"
                    class="text-[11px] leading-5 text-gray-600"
                >
                    {{ service.description }}
                </p>

                <table
                    v-if="activeAssignees(service).length"
                    class="w-full text-left text-[11px]"
                >
                    <thead>
                        <tr
                            class="border-b border-gray-300 text-[10px] uppercase tracking-wide text-gray-500"
                        >
                            <th class="py-1 pr-2 font-semibold">Employee</th>
                            <th class="py-1 pr-2 font-semibold">Role</th>
                            <th class="py-1 pr-2 font-semibold">Assignment</th>
                            <th
                                v-if="hasShiftTimes(service)"
                                class="py-1 pr-2 font-semibold"
                            >
                                Shift
                            </th>
                            <th class="py-1 font-semibold">Contact</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="assignee in activeAssignees(service)"
                            :key="assignee.employee_id"
                            class="border-b border-dashed border-gray-200 align-top"
                        >
                            <td class="py-1 pr-2 font-medium">
                                {{ assignee.full_name }}
                            </td>
                            <td class="py-1 pr-2 capitalize">
                                {{ assignee.employee_role ?? "Staff" }}
                            </td>
                            <td class="py-1 pr-2">{{ assignee.note || "—" }}</td>
                            <td v-if="hasShiftTimes(service)" class="py-1 pr-2">
                                {{ shiftLabel(assignee) }}
                            </td>
                            <td class="py-1">
                                {{ formatPhone(assignee.phone_number) || "—" }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p v-else class="text-[11px] italic text-gray-500">
                    No employee has been assigned yet.
                </p>

                <p
                    v-if="service.previous_assignees?.length"
                    class="text-[11px] text-gray-500"
                >
                    Previously assigned (not active):
                    {{ previousNames(service) }}
                </p>
            </div>
        </section>

        <section v-if="notes?.length" class="space-y-2">
            <p
                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500"
            >
                Staff notes
            </p>

            <div
                v-for="note in notes"
                :key="note.uuid"
                class="break-inside-avoid border-b border-dashed border-gray-200 pb-2"
            >
                <p class="whitespace-pre-wrap text-[12px]">{{ note.note }}</p>

                <p class="mt-0.5 text-[10px] text-gray-500">
                    {{ note.employee_name }}
                    <template v-if="note.recorded_by">
                        · by {{ note.recorded_by }}
                    </template>
                    · {{ stringToDateTime(note.created_at) }}
                </p>
            </div>
        </section>

        <p class="border-t border-gray-200 pt-3 text-[10px] text-gray-500">
            Printed {{ printedAt }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useSchedule } from "~/composables/useSchedule";
import { formatPhone } from "~/utils/phone";
import {
    formatDate,
    formatDuration,
    formatTime,
    stringToDateTime,
} from "~/utils/time";
import type { EmployeeNote } from "~/types/employee-note";
import type {
    ScheduleAssignee,
    ScheduleItem,
    ScheduleServiceItem,
} from "~/types/schedule";

const props = defineProps<{
    schedule: ScheduleItem;
    branchName?: string | null;
    notes?: EmployeeNote[];
}>();

const { scheduleStatusLabel } = useSchedule();

const printedAt = computed(() => stringToDateTime(new Date()));

const PERFORMED_BY_LABELS = {
    both: "Nurse & Caregiver",
    nurse: "Nurse",
    caregiver: "Caregiver",
} as const;

function performedByLabel(service: ScheduleServiceItem) {
    if (service.type === "ADL") return "Caregiver";

    return PERFORMED_BY_LABELS[service.performed_by ?? "both"];
}

function hoursLabel(hours: number) {
    const formatted = formatDuration(hours);

    if (!formatted) return "";

    return hours >= 24 ? `${formatted} (${hours} hrs)` : formatted;
}

function durationFor(service: ScheduleServiceItem) {
    if (!service.duration_minutes) return "";

    if (service.type === "ADL") {
        return hoursLabel(Math.round((service.duration_minutes / 60) * 100) / 100);
    }

    return `${service.duration_minutes} min`;
}

function activeAssignees(service: ScheduleServiceItem) {
    return (service.assignees ?? []).filter(
        (assignee) => assignee.is_active !== false,
    );
}

function hasShiftTimes(service: ScheduleServiceItem) {
    return activeAssignees(service).some(
        (assignee) => assignee.start_time && assignee.end_time,
    );
}

function shiftLabel(assignee: ScheduleAssignee) {
    if (!assignee.start_time || !assignee.end_time) return "—";

    return `${formatTime(assignee.start_time)} – ${formatTime(assignee.end_time)}`;
}

function previousNames(service: ScheduleServiceItem) {
    return (service.previous_assignees ?? [])
        .map((assignee) =>
            assignee.employee_role
                ? `${assignee.full_name} (${assignee.employee_role})`
                : assignee.full_name,
        )
        .join(", ");
}

const hasAdl = computed(() =>
    (props.schedule.services ?? []).some((service) => service.type === "ADL"),
);

const durationLabel = computed(() => {
    if (hasAdl.value) return hoursLabel(props.schedule.total_hours);

    return props.schedule.total_duration_minutes
        ? `${props.schedule.total_duration_minutes} min`
        : "";
});

const location = computed(() => {
    const { patient, is_onsite: isOnsite } = props.schedule;
    const bed = patient?.admission?.bed;

    if (isOnsite) {
        if (!patient?.is_admitted || !bed) return "On-site";

        return [
            bed.room?.room_type,
            bed.room?.room_no ? `Room ${bed.room.room_no}` : "",
            `Bed ${bed.bed_no}`,
            bed.room?.floor ? `${bed.room.floor} Floor` : "",
        ]
            .filter(Boolean)
            .join(" · ");
    }

    return props.schedule.address ?? patient?.address ?? "";
});

const groups = computed(() => {
    const { schedule } = props;
    const guardian = schedule.patient?.guardian;

    return [
        {
            title: "Visit",
            rows: [
                { label: "Date", value: formatDate(schedule.scheduled_date) },
                { label: "Time", value: schedule.start_time ?? "" },
                { label: "Duration", value: durationLabel.value },
                {
                    label: "Type",
                    value: hasAdl.value ? "Activities of daily living" : "Medical",
                },
                { label: "Category", value: schedule.category ?? "" },
            ].filter((row) => !!row.value && row.value !== "—"),
        },
        {
            title: "Patient",
            rows: [
                { label: "Name", value: schedule.patient?.full_name ?? "" },
                {
                    label: schedule.is_onsite ? "Location" : "Address",
                    value: location.value,
                },
                {
                    label: "Guardian",
                    value: guardian
                        ? [guardian.full_name, guardian.relationship]
                              .filter(Boolean)
                              .join(" · ")
                        : "",
                },
                {
                    label: "Guardian contact",
                    value: formatPhone(guardian?.phone_number),
                },
            ].filter((row) => !!row.value),
        },
    ].filter((group) => group.rows.length);
});
</script>
