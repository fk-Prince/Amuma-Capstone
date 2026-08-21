<template>
    <section
        class="rounded-2xl p-8 md:p-10"
        :class="{ 'animate-pulse': loading }"
    >
        <div class="mb-8 flex items-baseline gap-3">
            <template v-if="loading">
                <div class="h-6 w-6 shrink-0 rounded bg-slate-200" />
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
                        Select your service and schedule your appointment
                    </p>
                </div>
            </template>
        </div>

        <div class="space-y-8">
            <div>
                <template v-if="loading">
                    <div class="mb-3 h-4 w-36 rounded bg-slate-200" />

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div
                            v-for="n in 2"
                            :key="n"
                            class="h-28 animate-pulse rounded-2xl border border-slate-200 bg-slate-50"
                        />
                    </div>
                </template>

                <template v-else>
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-slate-900">
                            Booking Type
                            <span class="text-danger">*</span>
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500">
                            Choose the type of care you need.
                        </p>
                    </div>

                    <template v-if="bookingTypes.some((t) => t.visible)">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <button
                                v-for="item in bookingTypes.filter(
                                    (t) => t.visible,
                                )"
                                :key="item.value"
                                type="button"
                                class="group relative overflow-hidden rounded-2xl border p-5 text-left transition-all duration-200"
                                :class="
                                    model.type === item.value
                                        ? 'border-primary-300 bg-primary-50/60 shadow-sm ring-2 ring-primary-100'
                                        : 'border-slate-200 bg-white hover:border-primary-200 hover:bg-primary-50/30 hover:shadow-sm'
                                "
                                @click="
                                    update(
                                        'type',
                                        item.value as HomecareBooking['type'],
                                    )
                                "
                            >
                                <div
                                    v-if="model.type === item.value"
                                    class="absolute right-4 top-4 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white"
                                >
                                    <Check class="h-3.5 w-3.5" />
                                </div>

                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/10"
                                        :class="
                                            model.type === item.value
                                                ? 'bg-primary text-white'
                                                : ''
                                        "
                                    >
                                        <component
                                            v-if="typeof item.icon !== 'string'"
                                            :is="item.icon"
                                            class="h-6 w-6"
                                        />
                                    </div>

                                    <div class="min-w-0 pr-8">
                                        <h4
                                            class="text-sm font-bold text-slate-900"
                                            :class="
                                                model.type === item.value
                                                    ? 'text-primary'
                                                    : ''
                                            "
                                        >
                                            {{ item.title }}
                                        </h4>

                                        <p
                                            class="mt-1 text-xs leading-5 text-slate-500"
                                        >
                                            {{ item.description }}
                                        </p>

                                        <p
                                            class="mt-3 text-xs font-bold text-primary"
                                        >
                                            {{ item.rateLabel }}
                                        </p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </template>

                    <template v-else>
                        <div
                            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-6 py-10 text-center"
                        >
                            <div
                                class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                            >
                                <Stethoscope class="h-5 w-5" />
                            </div>

                            <h4 class="text-sm font-semibold text-slate-700">
                                No services available
                            </h4>

                            <p
                                class="mt-1 max-w-sm text-xs leading-5 text-slate-500"
                            >
                                There are currently no booking services
                                available.
                            </p>
                        </div>
                    </template>

                    <p v-if="errors?.type" class="mt-2 text-xs text-red-500">
                        {{ errors.type }}
                    </p>
                </template>
            </div>

            <div class="h-px bg-[#E4E0D6]" />

            <div>
                <div class="mb-2">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Schedule
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500">
                        Choose when you would like the service to take place.
                    </p>
                </div>

                <div class="bg-white">
                    <template v-if="loading">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <div class="h-4 w-32 rounded bg-slate-200" />
                                <div
                                    class="h-11 w-full rounded-xl bg-slate-200"
                                />
                            </div>

                            <div class="space-y-2">
                                <div class="h-4 w-28 rounded bg-slate-200" />
                                <div
                                    class="h-11 w-full rounded-xl bg-slate-200"
                                />
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <BaseInput
                                label="Date"
                                :model-value="model.date"
                                @update:model-value="update('date', $event)"
                                mode="date"
                                :min="todayStr"
                                :error="errors?.date"
                                required
                            />

                            <div class="flex flex-col gap-1.5">
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
                                    class="mt-1"
                                >
                                    <span
                                        v-if="operatingHours === 'Open 24 hrs'"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-600"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                                        />

                                        Open 24 hrs
                                    </span>

                                    <span
                                        v-else
                                        class="text-[11px] text-slate-400"
                                    >
                                        {{ operatingHours }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div v-if="!loading && model.type === 'Medical'" class="bg-white">
                <div class="mb-2">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Medical Service
                        <span class="text-danger">*</span>
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500">
                        Select one medical service required for this booking.
                    </p>
                </div>

                <button
                    type="button"
                    class="group flex w-full items-center justify-between rounded-xl border p-3.5 text-left transition-all duration-200"
                    :class="
                        errors?.services
                            ? 'border-red-400 bg-red-50/30'
                            : 'border-slate-200 hover:border-primary-200 hover:bg-primary-50/30'
                    "
                    @click="isServiceModalOpen = true"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <span class="text-lg">+</span>
                        </div>

                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold"
                                :class="
                                    selectedService
                                        ? 'text-slate-800'
                                        : 'text-slate-400'
                                "
                            >
                                {{
                                    selectedService?.service_name ||
                                    "Select medical service"
                                }}
                            </p>

                            <p
                                v-if="selectedService"
                                class="mt-0.5 text-[11px] text-slate-400"
                            >
                                ₱{{ Number(selectedService.price).toFixed(2) }}
                            </p>
                        </div>
                    </div>

                    <span
                        class="text-lg text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-primary"
                    >
                        →
                    </span>
                </button>

                <p v-if="errors?.services" class="mt-2 text-xs text-red-500">
                    {{ errors.services }}
                </p>

                <div
                    v-if="selectedService"
                    class="mt-4 flex items-center justify-between rounded-xl bg-primary/5 px-4 py-3"
                >
                    <span class="text-xs font-medium text-slate-500">
                        Estimated service total
                    </span>

                    <span class="text-sm font-bold text-primary">
                        ₱{{ Number(selectedService.price).toFixed(2) }}
                    </span>
                </div>
            </div>

            <div
                v-if="
                    !loading &&
                    model.type === 'ADL' &&
                    Number(adlRatePerHour) > 0
                "
                class="bg-white"
            >
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Care Duration
                        <span class="text-danger">*</span>
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500">
                        Specify how many hours of care you require.
                    </p>
                </div>

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

                <div class="mt-4">
                    <p
                        class="mb-2 text-[11px] font-medium uppercase tracking-wide text-slate-400"
                    >
                        Quick duration
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="preset in durationPresets"
                            :key="preset.label"
                            type="button"
                            class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-primary hover:bg-primary/5 hover:text-primary"
                            @click="addDurationPreset(preset.hours)"
                        >
                            + {{ preset.label }}
                        </button>

                        <button
                            v-if="Number(model.time_span) > minAdlHours"
                            type="button"
                            class="rounded-full px-3 py-1.5 text-xs font-medium text-slate-400 transition hover:text-red-500"
                            @click="resetDuration"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <div
                    class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4"
                >
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Hourly Rate</span>

                        <span class="font-semibold text-slate-700">
                            ₱{{ adlRatePerHour.toLocaleString() }} / hour
                        </span>
                    </div>

                    <div class="mt-2 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Minimum Hours</span>

                        <span class="font-semibold text-slate-700">
                            {{ minAdlHours }} hours
                        </span>
                    </div>

                    <div
                        v-if="adlTotal"
                        class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3"
                    >
                        <span class="text-sm font-semibold text-slate-700">
                            Estimated Total
                        </span>

                        <span class="text-base font-bold text-primary">
                            ₱{{ adlTotal.toLocaleString() }}
                        </span>
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-2">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Patient - Service Location
                        <span class="text-danger">*</span>
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500">
                        Enter the location where the caregiver or nurse should
                        visit.
                    </p>
                </div>

                <div class="bg-white">
                    <BaseInput
                        v-model="model.address"
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

                    <p class="mt-2 text-[11px] text-slate-400">
                        You can use your current location to automatically fill
                        in the address.
                    </p>
                </div>
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
import { Check, Stethoscope } from "lucide-vue-next";
import Combobox from "~/components/ui/Combobox.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import LocationIcon from "~/components/icons/location.vue";
import ServiceModal from "~/components/sections/booking/provider/ServiceModal.vue";
import { formatHour, getLocalDateStr } from "~/utils/time";
import type { HomecareBooking } from "~/types/booking";
import type { Service } from "~/types/service";
import type { BranchSettings, BranchHomecare } from "~/types/branch";
import { useMedicalServices } from "~/composables/useBooking";
import { generateAvailableAmPmTimesBySchedule } from "~/utils/time-slot";

const props = defineProps<{
    model: HomecareBooking;
    services: Service[];
    loading?: boolean;
    errors?: Record<string, string> | null;
    homecare?: BranchHomecare;
    settings?: BranchSettings;
}>();

const emit = defineEmits<{
    (e: "update:model", value: HomecareBooking): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

const { bookingTypes } = useMedicalServices(
    () => props.services,
    () => props.model.services,
    () => adlRatePerHour.value,
    () => minAdlHours.value,
    props.homecare?.description,
);

function update<K extends keyof HomecareBooking>(
    key: K,
    value: HomecareBooking[K],
) {
    emit("update:model", {
        ...props.model,
        [key]: value,
    });

    clearError(String(key));
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

const selectedService = computed(() => {
    const services = props.model.services ?? [];

    return services.length ? services[0] : null;
});

const selectedServicesTotal = computed(() => {
    return selectedService.value ? Number(selectedService.value.price) : 0;
});

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

    if (isNaN(hours) || hours < minAdlHours.value) {
        return 0;
    }

    return hours * adlRatePerHour.value;
});

const operatingHours = computed(() => {
    const opening = props.settings?.opening ?? "00:00";
    const closing = props.settings?.closing ?? "00:00";

    if (opening === "00:00" && closing === "00:00") {
        return "Open 24 hrs";
    }

    return `Open ${formatHour(parseHourString(opening))} – ${formatHour(
        parseHourString(closing),
    )}`;
});

const isServiceModalOpen = ref(false);

const todayStr = getLocalDateStr(new Date());

const availableTimeSlots = computed(() =>
    generateAvailableAmPmTimesBySchedule(
        props.settings?.opening ?? "00:00",
        props.settings?.closing ?? "00:00",
        props.model.date,
        30,
    ),
);

const displayTime = computed(() =>
    availableTimeSlots.value.length
        ? "Select time"
        : "No slots available / Please Select different date",
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
    update("time_span", String(current + hours));
};

const resetDuration = () => {
    update("time_span", String(minAdlHours.value));
};
</script>
