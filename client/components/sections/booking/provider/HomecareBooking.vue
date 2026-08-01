<template>
    <section
        class="rounded-2xl p-8 md:p-10"
        :class="{ 'animate-pulse': loading }"
    >
        <div class="flex items-baseline gap-3 mb-8">
            <template v-if="loading">
                <div class="h-6 w-6 rounded bg-slate-200 shrink-0" />
                <div class="flex-1 space-y-2">
                    <div class="h-5 w-40 rounded bg-slate-200" />
                    <div class="h-3 w-64 rounded bg-slate-200" />
                </div>
            </template>
            <template v-else>
                <span class="text-2xl text-primary">01</span>
                <div>
                    <h2 class="text-xl text-primary">Booking Request</h2>
                    <p class="text-[13px] text-muted">
                        Select service type and schedule your appointment
                    </p>
                </div>
            </template>
        </div>

        <div class="space-y-8">
            <div>
                <template v-if="loading">
                    <div class="h-4 w-36 rounded bg-slate-200 mb-3" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="n in 2"
                            :key="n"
                            class="border border-slate-200 rounded-xl p-4"
                        >
                            <div class="flex items-center gap-5">
                                <div
                                    class="h-6 w-6 rounded bg-slate-200 shrink-0"
                                />
                                <div class="flex-1 space-y-2">
                                    <div
                                        class="h-4 w-32 rounded bg-slate-200"
                                    />
                                    <div
                                        class="h-3 w-40 rounded bg-slate-200"
                                    />
                                    <div
                                        class="h-3 w-24 rounded bg-slate-200"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <label
                        class="text-sm font-semibold text-slate-700 mb-3 block"
                    >
                        Choose Booking Type
                        <span class="text-danger">*</span>
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="item in bookingTypes"
                            :key="item.value"
                            @click="
                                update(
                                    'type',
                                    item.value as HomecareBooking['type'],
                                )
                            "
                            class="group relative border rounded-xl p-4 cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                            :class="
                                model.type === item.value
                                    ? 'border-primary bg-primary/5 shadow-sm'
                                    : 'border-slate-200'
                            "
                        >
                            <span
                                v-if="model.type === item.value"
                                class="absolute top-3 right-3 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white"
                            >
                                <Check class="h-3 w-3" />
                            </span>

                            <div class="flex items-center gap-5">
                                <div
                                    class="text-primary text-xl w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 group-hover:bg-primary/10 transition shrink-0"
                                >
                                    <component
                                        v-if="typeof item.icon !== 'string'"
                                        :is="item.icon"
                                        class="h-6 w-6"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="font-semibold text-slate-800 group-hover:text-primary"
                                    >
                                        {{ item.title }}
                                    </p>
                                    <p class="text-[13px] text-muted">
                                        {{ item.description }}
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-primary mt-1"
                                    >
                                        {{ item.rateLabel }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="errors?.type" class="text-xs text-red-500 mt-2">
                        {{ errors.type }}
                    </p>
                </template>
            </div>

            <div class="h-px bg-[#E4E0D6]" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <template v-if="loading">
                    <div>
                        <div class="h-4 w-32 rounded bg-slate-200 mb-1.5" />
                        <div class="h-[38px] w-full rounded-lg bg-slate-200" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div class="h-4 w-28 rounded bg-slate-200" />
                        <div class="h-[38px] w-full rounded-lg bg-slate-200" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div class="h-4 w-32 rounded bg-slate-200" />
                        <div class="h-[38px] w-full rounded-lg bg-slate-200" />
                        <div class="h-3 w-24 rounded bg-slate-200" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div class="h-4 w-20 rounded bg-slate-200" />
                        <div class="h-[38px] w-full rounded-lg bg-slate-200" />
                    </div>
                </template>

                <template v-else>
                    <BaseInput
                        label="Select Schedule Date"
                        :model-value="model.date"
                        @update:model-value="update('date', $event)"
                        mode="date"
                        :min="todayStr"
                        :error="errors?.date"
                        required
                    />

                    <div class="flex flex-col gap-1.5 mt-0.5">
                        <Combobox
                            :model-value="model.prefered_time"
                            @update:model-value="
                                update('prefered_time', $event)
                            "
                            label="Preferred Time"
                            :placeholder="displayTime"
                            :error="errors?.prefered_time"
                            required
                            :items="availableTimeSlots"
                        />

                        <div
                            v-if="model.type === 'Medical'"
                            class="text-[12px] flex items-center gap-1.5"
                        >
                            <span
                                v-if="operatingHours === 'Open 24 hrs'"
                                class="inline-flex uppercase items-center gap-1 rounded-full bg-green-50 px-4 py-1 text-green-600 font-medium"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"
                                />
                                Open 24 hrs
                            </span>

                            <span v-else class="text-muted">
                                {{ operatingHours }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="model.type === 'Medical'"
                        class="flex flex-col gap-1.5"
                    >
                        <label class="text-sm font-semibold text-slate-700">
                            Medical Service
                            <span class="text-danger">*</span>
                        </label>

                        <button
                            type="button"
                            @click="isServiceModalOpen = true"
                            class="w-full border rounded-lg p-2 text-left flex items-center justify-between"
                            :class="[
                                selectedServiceLabel
                                    ? 'text-slate-800'
                                    : 'text-muted',
                                errors?.services
                                    ? 'border-red-400'
                                    : 'border-slate-200',
                            ]"
                        >
                            <span class="truncate">
                                {{ selectedServiceLabel || "Select service" }}
                            </span>
                            <span class="text-slate-400 text-sm">›</span>
                        </button>

                        <p v-if="errors?.services" class="text-xs text-red-500">
                            {{ errors.services }}
                        </p>

                        <p
                            v-else-if="selectedServicesTotal"
                            class="text-[12px] font-semibold text-primary"
                        >
                            Total: ₱{{ selectedServicesTotal.toFixed(2) }}
                        </p>
                    </div>
                    <div
                        v-if="
                            model.type === 'ADL' && Number(adlRatePerHour) > 0
                        "
                        class="flex flex-col gap-3"
                    >
                        <BaseInput
                            label="Duration (hours)"
                            :model-value="model.time_span"
                            @update:model-value="update('time_span', $event)"
                            mode="number"
                            :min="String(minAdlHours)"
                            placeholder="Enter total hours"
                            :error="errors?.time_span"
                            required
                        />

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                v-for="preset in durationPresets"
                                :key="preset.label"
                                type="button"
                                @click="addDurationPreset(preset.hours)"
                                class="text-xs font-semibold px-3 py-1.5 rounded-full border border-slate-200 text-slate-600 hover:border-primary hover:text-primary hover:bg-primary/5 transition"
                            >
                                + {{ preset.label }}
                            </button>

                            <button
                                v-if="Number(model.time_span) > minAdlHours"
                                type="button"
                                @click="resetDuration"
                                class="text-xs font-medium px-3 py-1.5 rounded-full text-slate-400 hover:text-red-500 transition"
                            >
                                Reset
                            </button>
                        </div>

                        <div
                            class="rounded-lg bg-slate-50 border border-slate-200 p-3 space-y-2"
                        >
                            <div
                                class="flex items-center justify-between text-[12px]"
                            >
                                <span class="text-muted">Hourly Rate</span>
                                <span class="font-semibold text-slate-700">
                                    ₱{{ adlRatePerHour.toLocaleString() }} /
                                    hours
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between text-[12px]"
                            >
                                <span class="text-muted">Minimum Hours</span>
                                <span class="font-semibold text-slate-700">
                                    {{ minAdlHours }} hours
                                </span>
                            </div>

                            <div
                                v-if="adlTotal"
                                class="pt-2 mt-2 border-t border-slate-200 flex items-center justify-between"
                            >
                                <span
                                    class="text-sm font-semibold text-slate-700"
                                >
                                    Total
                                </span>

                                <span class="text-sm font-bold text-primary">
                                    ₱{{ adlTotal.toLocaleString() }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <BaseInput
                        v-model="model.address"
                        label="Service Location / Patient Location"
                        placeholder="Where should the caregiver/nurse visit?"
                        :error="errors?.address"
                        boxClass="pr-3 border-[1.5px] focus-within:ring-2"
                        required
                    >
                        <template #suffix>
                            <LocationIcon
                                clickable
                                @get-location="handleLocation"
                            />
                        </template>
                    </BaseInput>
                </template>
            </div>
        </div>

        <ServiceModal
            v-if="!loading"
            :open="isServiceModalOpen"
            :services="services"
            :model-value="model.services ?? []"
            @update:model-value="update('services', $event)"
            @close="isServiceModalOpen = false"
        />
    </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Check } from "lucide-vue-next";
import Combobox from "~/components/ui/Combobox.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import LocationIcon from "~/components/icons/location.vue";
import ServiceModal from "~/components/sections/booking/provider/ServiceModal.vue";
import {
    formatHour,
    getTimeSlots,
    getLocalDateStr,
    filterAvailableSlots,
} from "~/utils/time";
import type { HomecareBooking } from "~/types/booking";
import type { Service } from "~/types/service";
import type { BranchAvailability, BranchHomecare } from "~/types/branch";
import { useMedicalServices } from "~/composables/useBooking";
import { generateAvailableAmPmTimesBySchedule } from "~/utils/time-slot";

const props = defineProps<{
    model: HomecareBooking;
    services: Service[];
    loading?: boolean;
    errors?: Record<string, string> | null;
    homecare?: BranchHomecare;
    settings?: BranchAvailability;
}>();

const { selectedServiceLabel, selectedServicesTotal, bookingTypes } =
    useMedicalServices(
        () => props.services,
        () => props.model.services,
        () => adlRatePerHour.value,
        () => minAdlHours.value,
        props.homecare?.description,
    );

const emit = defineEmits<{
    (e: "update:model", value: HomecareBooking): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

function update<K extends keyof HomecareBooking>(
    key: K,
    value: HomecareBooking[K],
) {
    emit("update:model", {
        ...props.model,
        [key]: value,
    });

    clearError(key as string);
}

function clearError(field: string) {
    if (!props.errors) return;

    const updated = { ...props.errors };
    delete updated[field];

    emit("update:errors", updated);
}

const minAdlHours = computed<number>(() =>
    Number(props.homecare?.adl_min_hour ?? 0),
);

const adlRatePerHour = computed<number>(() =>
    Number(props.homecare?.adl_hourly_rate ?? 0),
);

const handleLocation = (data: any) => {
    const address =
        data.address ??
        [data.street, data.city, data.province, data.country]
            .filter(Boolean)
            .join(", ");

    update("address", address);
};

const adlTotal = computed(() => {
    const hours = Number(props.model.time_span);
    if (isNaN(hours) || hours < minAdlHours.value) return 0;
    return hours * adlRatePerHour.value;
});

const operatingHours = computed(() => {
    const opening = props.settings?.opening ?? "00:00";
    const closing = props.settings?.closing ?? "00:00";

    if (opening === "00:00" && closing === "00:00") {
        return "Open 24 hrs";
    }

    return `Open ${formatHour(parseHourString(opening))} – ${formatHour(parseHourString(closing))}`;
});

const isServiceModalOpen = ref(false);
const todayStr = getLocalDateStr(new Date());

const availableTimeSlots = computed(() =>
    generateAvailableAmPmTimesBySchedule(
        props.settings?.opening ?? "00:00",
        props.settings?.closing ?? "00:00",
        props.model.date,
        60,
    ),
);

const displayTime = computed(() =>
    availableTimeSlots.value.length ? "Select time" : "No slots available",
);

watch(availableTimeSlots, (slots) => {
    if (!slots.find((s: any) => s.value === props.model.prefered_time)) {
        update("prefered_time", "");
    }
});

watch(
    () => props.model.type,
    (type) => {
        if (type === "ADL") {
            update("services", []);
            if (!props.model.time_span) {
                update("time_span", String(minAdlHours.value));
            }
        } else {
            update("time_span", "");
        }

        if (props.errors) {
            emit("update:errors", {});
        }
    },
);

const durationPresets = [
    { label: "1 Day", hours: 24 },
    { label: "1 Week", hours: 24 * 7 },
    { label: "1 Month", hours: 24 * 30 },
];

const addDurationPreset = (hours: number) => {
    const current = Number(props.model.time_span) || 0;
    const next = current + hours;

    update("time_span", String(next));
};

const resetDuration = () => {
    update("time_span", String(minAdlHours.value));
};
</script>
