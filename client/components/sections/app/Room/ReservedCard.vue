<script setup lang="ts">
import type { Bed } from "~/types/bed.js";
import {
    ChevronDown,
    Pencil,
    Phone,
    Heart,
    CalendarClock,
    Bookmark,
} from "lucide-vue-next";
import { stringToDateTime } from "~/utils/time";

const props = defineProps<{
    bed: Bed;
}>();

const emit = defineEmits<{
    editBed: [bed_id: number];
}>();

const expanded = ref(false);

const toggleDetails = () => {
    expanded.value = !expanded.value;
};

const initials = (first?: string, last?: string) => {
    return `${first?.[0] ?? ""}${last?.[0] ?? ""}`.toUpperCase();
};

const reservation = computed(() => props.bed.reserved_admission);

const reservedPatientName = computed(() => {
    const p: any = reservation.value?.patient;
    if (!p) return "Unnamed patient";
    if (p.first_name || p.last_name) {
        return `${p.first_name ?? ""} ${p.last_name ?? ""}`.trim();
    }
    if (p.name) return p.name;
    return "Unnamed patient";
});

const reservedInitials = computed(() => {
    const p: any = reservation.value?.patient;
    if (!p) return "?";
    if (p.first_name || p.last_name) return initials(p.first_name, p.last_name);
    if (p.name) {
        const parts = p.name.split(" ");
        return initials(parts[0], parts[1]);
    }
    return "?";
});

const admissionStatusClasses = (status?: string) => {
    switch (status?.toLowerCase()) {
        case "admitted":
            return "bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300";

        case "waiting":
            return "bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300";

        case "discharged":
            return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300";

        case "cancelled":
            return "bg-red-100 text-red-700";

        default:
            return "bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400";
    }
};
</script>

<template>
    <div class="space-y-2.5" v-if="reservation">
        <button
            type="button"
            class="w-full flex items-start gap-2.5 text-left"
            @click="toggleDetails"
        >
            <div
                class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[11px] font-semibold shrink-0 ring-2 ring-white shadow-sm dark:bg-indigo-500/15 dark:text-indigo-300"
            >
                {{ reservedInitials }}
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-gray-800 truncate dark:text-white">
                    {{ reservedPatientName }}
                </p>

                <div
                    class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-0.5 text-[11px] text-gray-400 dark:text-gray-500"
                >
                    <span
                        v-if="reservation.booking_reference_id"
                        class="flex items-center gap-0.5"
                    >
                        <Bookmark class="h-3 w-3" />
                        {{ reservation.booking_reference_id }}
                    </span>

                    <span
                        v-if="reservation.status"
                        class="px-1.5 capitalize py-0.5 rounded-full font-medium"
                        :class="admissionStatusClasses(reservation.status)"
                    >
                        {{ reservation.status }}
                    </span>
                </div>
            </div>

            <ChevronDown
                class="h-3.5 w-3.5 text-gray-300 shrink-0 mt-1 transition-transform duration-200 dark:text-gray-500"
                :class="{ 'rotate-180': expanded }"
            />
        </button>

        <div
            v-if="expanded"
            class="grid grid-cols-1 sm:grid-cols-2 gap-x-2 gap-y-1.5 text-[11px] text-gray-500 rounded-lg border border-dashed border-gray-200 p-2 dark:text-gray-400 dark:border-white/10"
        >
            <div
                v-if="reservation.patient?.phone_number"
                class="flex items-center gap-1 truncate"
            >
                <Phone class="h-3 w-3 text-gray-400 shrink-0 dark:text-gray-500" />
                {{ reservation.patient.phone_number }}
            </div>

            <div
                v-if="reservation.patient?.gender"
                class="flex items-center gap-1 truncate"
            >
                <Heart class="h-3 w-3 text-gray-400 shrink-0 dark:text-gray-500" />
                {{ reservation.patient.gender }}
            </div>

            <div class="flex items-center gap-1 truncate col-span-2">
                <CalendarClock class="h-3 w-3 text-gray-400 shrink-0 dark:text-gray-500" />
                {{
                    reservation.admitted_at
                        ? stringToDateTime(reservation.admitted_at)
                        : "No admission date"
                }}
            </div>
        </div>

        <button
            type="button"
            @click="emit('editBed', bed.bed_id)"
            class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-blue-600 bg-white border border-blue-200 rounded-lg py-2 hover:bg-blue-50 transition-colors dark:text-blue-300 dark:bg-secondary dark:border-blue-500/20 dark:hover:bg-blue-500/10"
        >
            <Pencil class="h-3.5 w-3.5" />
            Update Bed
        </button>
    </div>
</template>
