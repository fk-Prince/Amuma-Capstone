<script setup lang="ts">
import type { Bed } from "~/types/bed.js";
import {
    ChevronDown,
    Pencil,
    Phone,
    Globe2,
    Droplet,
    CalendarClock,
    Ruler,
    Weight,
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

const age = (dob?: string) => {
    if (!dob) return null;
    const birth = new Date(dob);
    if (Number.isNaN(birth.getTime())) return null;
    const diffMs = Date.now() - birth.getTime();
    const years = diffMs / (1000 * 60 * 60 * 24 * 365.25);
    return Math.floor(years);
};

const patient = computed(() => props.bed.current_admission?.patient);
const admission = computed(() => props.bed.current_admission);
</script>

<template>
    <div class="space-y-2.5" v-if="patient">
        <button
            type="button"
            class="w-full flex items-start gap-2.5 text-left"
            @click="toggleDetails"
        >
            <div
                class="w-9 h-9 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center text-[11px] font-semibold shrink-0 ring-2 ring-white shadow-sm"
            >
                {{ initials(patient.first_name, patient.last_name) }}
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-gray-800 truncate">
                    {{ patient.first_name }} {{ patient.last_name }}
                </p>

                <div
                    class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-0.5 text-[11px] text-gray-400"
                >
                    <span v-if="patient.gender">{{ patient.gender }}</span>
                    <span v-if="age(patient.date_of_birth)"
                        >· {{ age(patient.date_of_birth) }}y</span
                    >
                    <span
                        v-if="patient.blood_type"
                        class="inline-flex items-center gap-0.5 font-medium text-rose-500"
                    >
                        <Droplet class="h-3 w-3" />{{ patient.blood_type }}
                    </span>
                </div>
            </div>

            <ChevronDown
                class="h-3.5 w-3.5 text-gray-300 shrink-0 mt-1 transition-transform duration-200"
                :class="{ 'rotate-180': expanded }"
            />
        </button>

        <div
            v-if="expanded"
            class="grid grid-cols-2 gap-x-2 gap-y-1.5 text-[11px] text-gray-500 rounded-lg border border-dashed border-gray-200 p-2"
        >
            <div
                v-if="patient.phone_number"
                class="flex items-center gap-1 truncate"
            >
                <Phone class="h-3 w-3 text-gray-400 shrink-0" />
                {{ patient.phone_number }}
            </div>

            <div
                v-if="patient.citizenship"
                class="flex items-center gap-1 truncate"
            >
                <Globe2 class="h-3 w-3 text-gray-400 shrink-0" />
                {{ patient.citizenship }}
            </div>

            <div class="flex items-center gap-2">
                <div
                    v-if="patient.height"
                    class="flex items-center gap-1 truncate"
                >
                    <Ruler class="h-3 w-3 text-gray-400 shrink-0" />
                    {{ patient.height }} cm
                </div>
                <div
                    v-if="patient.weight"
                    class="flex items-center gap-1 truncate"
                >
                    <Weight class="h-3 w-3 text-gray-400 shrink-0" />
                    {{ patient.weight }} kg
                </div>
            </div>
        </div>

        <div
            class="flex items-center justify-between text-[11px] pt-1.5 border-t border-gray-100"
        >
            <span class="flex items-center gap-1 text-gray-400">
                <CalendarClock class="h-3 w-3" />

                <span class="font-medium text-gray-500">
                    Admission Period:
                </span>

                <span>
                    {{ stringToDateTime(admission?.admitted_at) }}

                    <template v-if="admission?.end_date">
                        → {{ stringToDateTime(admission.end_date) }}
                    </template>
                </span>
            </span>
        </div>

        <button
            type="button"
            @click="emit('editBed', bed.bed_id)"
            class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-blue-600 bg-white border border-blue-200 rounded-lg py-2 hover:bg-blue-50 transition-colors"
        >
            <Pencil class="h-3.5 w-3.5" />
            Update Bed
        </button>
    </div>
</template>
