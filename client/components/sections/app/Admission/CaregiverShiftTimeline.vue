<template>
    <div class="rounded-xl border border-slate-200 p-4 dark:border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-[130px] shrink-0 sm:w-[180px]" />

            <div class="relative h-4 flex-1">
                <span
                    v-for="tick in ticks"
                    :key="tick.hour"
                    class="absolute top-0 whitespace-nowrap text-[10px] font-medium text-slate-400 dark:text-gray-500"
                    :class="tick.align"
                    :style="{ left: tick.pct + '%' }"
                >
                    {{ tick.label }}
                </span>
            </div>
        </div>

        <div class="mt-4 divide-y divide-slate-100 dark:divide-white/5">
            <div
                v-for="shift in rows"
                :key="shift.caregiver_facility_shift_id"
                class="py-3 first:pt-0 last:pb-0"
                :class="isDraft(shift) ? 'opacity-70' : ''"
            >
                <div class="flex items-center gap-3">
                    <div class="flex w-[130px] shrink-0 items-start gap-2 sm:w-[180px]">
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-[10px] font-semibold text-primary"
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

                        <div class="min-w-0">
                            <p
                                class="truncate text-xs font-semibold text-slate-700 dark:text-white"
                                :title="shift.caregiver_name ?? ''"
                            >
                                {{ shift.caregiver_name }}
                            </p>

                            <p
                                v-if="shift.phone_number"
                                class="truncate text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                {{ formatPhone(shift.phone_number) }}
                            </p>

                            <span
                                v-if="isDraft(shift)"
                                class="text-[11px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Not saved yet
                            </span>

                            <button
                                v-else-if="!readonly"
                                type="button"
                                :disabled="busyId === shift.caregiver_facility_shift_id"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 transition hover:underline disabled:opacity-50 dark:text-rose-300"
                                @click="$emit('unassign', shift)"
                            >
                                <Loader2
                                    v-if="busyId === shift.caregiver_facility_shift_id"
                                    class="h-3 w-3 animate-spin"
                                />
                                {{
                                    busyId === shift.caregiver_facility_shift_id
                                        ? "Unassigning..."
                                        : "Unassign"
                                }}
                            </button>
                        </div>
                    </div>

                    <div
                        class="relative h-7 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-white/5"
                    >
                        <span
                            v-for="tick in ticks.slice(1, -1)"
                            :key="tick.hour"
                            class="absolute inset-y-0 w-px bg-slate-200 dark:bg-white/10"
                            :style="{ left: tick.pct + '%' }"
                        />

                        <div
                            v-for="(segment, i) in segmentsFor(shift)"
                            :key="i"
                            class="absolute inset-y-0 rounded-full"
                            :class="
                                isDraft(shift)
                                    ? 'border-2 border-dashed border-primary bg-primary/10'
                                    : 'bg-primary shadow-sm'
                            "
                            :style="{
                                left: segment.left + '%',
                                width: segment.width + '%',
                            }"
                            :title="`${formatTime(shift.start_time)} – ${formatTime(shift.end_time)}`"
                        />
                    </div>
                </div>

                <p
                    class="mt-1 pl-[142px] text-[11px] text-slate-500 sm:pl-[192px] dark:text-gray-400"
                >
                    {{ formatTime(shift.start_time) }} –
                    {{ formatTime(shift.end_time) }}
                    <template v-if="shift.note"> · {{ shift.note }}</template>
                </p>

                <p
                    v-if="!isDraft(shift) && dutyHoursOf(shift.caregiver_id) > dutyLimit"
                    class="mt-0.5 inline-flex items-center gap-1 pl-[142px] text-[11px] font-semibold text-amber-600 sm:pl-[192px] dark:text-amber-300"
                >
                    <TriangleAlert class="h-3 w-3" />
                    On duty {{ dutyHoursOf(shift.caregiver_id) }} hours a day ·
                    over the limit of {{ dutyLimit }}
                </p>

                <p
                    v-if="!isDraft(shift) && isOverLimit(shift.caregiver_id)"
                    class="mt-0.5 inline-flex items-center gap-1 pl-[142px] text-[11px] font-semibold text-rose-600 sm:pl-[192px] dark:text-rose-300"
                >
                    <TriangleAlert class="h-3 w-3" />
                    Looking after {{ residentsOf(shift.caregiver_id) }}
                    residents · over the limit of {{ limit }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Loader2, TriangleAlert } from "lucide-vue-next";

import type { CaregiverShift, FacilityCaregiver } from "~/types/caregiver-shift";
import { formatPhone } from "~/utils/phone";
import { formatHourLabel, formatTime } from "~/utils/time";
import { initials } from "~/utils/user";

const props = withDefaults(
    defineProps<{
        shifts: CaregiverShift[];
        draftShift?: CaregiverShift | null;
        caregivers?: FacilityCaregiver[];
        limit?: number;
        dutyLimit?: number;
        busyId?: number | null;
        readonly?: boolean;
    }>(),
    {
        draftShift: null,
        caregivers: () => [],
        limit: 3,
        dutyLimit: 12,
        busyId: null,
        readonly: false,
    },
);

defineEmits<{
    (e: "unassign", shift: CaregiverShift): void;
}>();

const rows = computed(() =>
    props.draftShift ? [...props.shifts, props.draftShift] : props.shifts,
);

function isDraft(shift: CaregiverShift) {
    return shift.caregiver_facility_shift_id === -1;
}

const ticks = computed(() =>
    [0, 6, 12, 18, 24].map((hour, index, all) => ({
        hour,
        pct: (hour / 24) * 100,
        label: formatHourLabel(hour === 24 ? 0 : hour),
        align:
            index === 0
                ? "translate-x-0"
                : index === all.length - 1
                  ? "-translate-x-full"
                  : "-translate-x-1/2",
    })),
);

function caregiverById(id: number) {
    return props.caregivers.find((caregiver) => caregiver.employee_id === id);
}

function dutyHoursOf(id: number) {
    return caregiverById(id)?.duty_hours ?? 0;
}

function residentsOf(id: number) {
    return caregiverById(id)?.active_residents ?? 0;
}

function isOverLimit(id: number) {
    return caregiverById(id)?.over_limit ?? false;
}

function toMinutes(time: string): number {
    const [hours, minutes] = time.split(":").map(Number);

    return (hours || 0) * 60 + (minutes || 0);
}

function segmentsFor(shift: CaregiverShift) {
    const start = toMinutes(shift.start_time);
    const end = toMinutes(shift.end_time);

    if (end > start) {
        return [{ left: (start / 1440) * 100, width: ((end - start) / 1440) * 100 }];
    }

    return [
        { left: (start / 1440) * 100, width: ((1440 - start) / 1440) * 100 },
        { left: 0, width: (end / 1440) * 100 },
    ];
}
</script>
