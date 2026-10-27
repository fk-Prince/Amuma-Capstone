<template>
    <section
        class="rounded-2xl p-8 md:p-10"
        :class="{ 'animate-pulse': loading }"
    >
        <div class="mb-8 flex items-baseline gap-3">
            <template v-if="loading">
                <div
                    class="h-6 w-6 shrink-0 rounded bg-slate-200 dark:bg-white/15"
                />
                <div class="flex-1 space-y-2">
                    <div
                        class="h-5 w-40 rounded bg-slate-200 dark:bg-white/15"
                    />
                    <div
                        class="h-3 w-64 rounded bg-slate-200 dark:bg-white/15"
                    />
                </div>
            </template>

            <template v-else>
                <span class="text-2xl text-primary">01</span>

                <div>
                    <h2 class="text-xl text-primary">Booking Request</h2>

                    <p class="text-[13px] text-muted dark:text-gray-400">
                        Select your service and schedule your appointment
                    </p>
                </div>
            </template>
        </div>

        <div class="space-y-8">
            <div>
                <template v-if="loading">
                    <div
                        class="mb-3 h-4 w-36 rounded bg-slate-200 dark:bg-white/15"
                    />

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div
                            v-for="n in 2"
                            :key="n"
                            class="h-28 animate-pulse rounded-2xl border border-slate-200 bg-slate-50 dark:bg-secondary dark:border-white/10"
                        />
                    </div>
                </template>

                <template v-else>
                    <div class="mb-4">
                        <h3
                            class="text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            Booking Type
                            <span class="text-danger">*</span>
                        </h3>

                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
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
                                class="group relative flex h-full flex-col overflow-hidden rounded-2xl border p-5 text-left transition-all duration-200"
                                :class="
                                    model.type === item.value
                                        ? 'border-primary bg-primary-50/60 shadow-md shadow-primary/10 ring-2 ring-primary/15 dark:bg-primary-500/10 dark:ring-primary-500/20'
                                        : 'border-slate-200 bg-white hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-md dark:bg-secondary dark:border-white/10 dark:hover:border-primary-500/40'
                                "
                                @click="
                                    update(
                                        'type',
                                        item.value as HomecareBooking['type'],
                                    )
                                "
                            >
                                <span
                                    class="absolute right-4 top-4 flex h-6 w-6 items-center justify-center rounded-full border-2 transition"
                                    :class="
                                        model.type === item.value
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-slate-300 bg-white dark:border-white/20 dark:bg-transparent'
                                    "
                                >
                                    <Check
                                        v-if="model.type === item.value"
                                        class="h-3.5 w-3.5"
                                    />
                                </span>

                                <div class="flex items-center gap-3 pr-8">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition"
                                        :class="
                                            model.type === item.value
                                                ? 'bg-primary text-white shadow-sm shadow-primary/30'
                                                : 'bg-primary/10 text-primary'
                                        "
                                    >
                                        <component
                                            v-if="typeof item.icon !== 'string'"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                    </div>

                                    <h4
                                        class="text-sm font-bold leading-snug"
                                        :class="
                                            model.type === item.value
                                                ? 'text-primary'
                                                : 'text-slate-900 dark:text-white'
                                        "
                                    >
                                        {{ item.title }}
                                    </h4>
                                </div>

                                <p
                                    :ref="
                                        (el) =>
                                            setDescriptionRef(
                                                item.value,
                                                el as HTMLElement | null,
                                            )
                                    "
                                    class="mt-3 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                    :class="
                                        expandedTypes[item.value]
                                            ? ''
                                            : 'line-clamp-3'
                                    "
                                >
                                    {{ item.description }}
                                </p>

                                <span
                                    v-if="
                                        overflowingTypes[item.value] ||
                                        expandedTypes[item.value]
                                    "
                                    role="button"
                                    tabindex="0"
                                    class="mt-1 self-start text-xs font-semibold text-primary hover:underline"
                                    @click.stop="toggleDescription(item.value)"
                                    @keydown.enter.stop.prevent="
                                        toggleDescription(item.value)
                                    "
                                    @keydown.space.stop.prevent="
                                        toggleDescription(item.value)
                                    "
                                >
                                    {{
                                        expandedTypes[item.value]
                                            ? "Show less"
                                            : "Show more"
                                    }}
                                </span>

                                <div class="mt-auto pt-4">
                                    <div
                                        class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3 dark:border-white/10"
                                    >
                                        <span
                                            class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                        >
                                            {{
                                                item.value === "ADL"
                                                    ? "Rate"
                                                    : "Price range"
                                            }}
                                        </span>
                                        <span
                                            class="text-sm font-bold text-primary"
                                        >
                                            {{ item.rateLabel }}
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </template>

                    <template v-else>
                        <div
                            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-6 py-10 text-center dark:border-white/10 dark:bg-white/5"
                        >
                            <div
                                class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-secondary dark:text-gray-500"
                            >
                                <Stethoscope class="h-5 w-5" />
                            </div>

                            <h4
                                class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >
                                No services available
                            </h4>

                            <p
                                class="mt-1 max-w-sm text-xs leading-5 text-slate-500 dark:text-gray-400"
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

            <div class="h-px bg-[#E4E0D6] dark:bg-white/10" />

            <div>
                <div class="mb-2">
                    <h3
                        class="text-sm font-semibold text-slate-900 dark:text-white"
                    >
                        Schedule
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Choose when you would like the service to take place.
                    </p>
                </div>

                <div class="bg-white dark:bg-secondary">
                    <template v-if="loading">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <div
                                    class="h-4 w-32 rounded bg-slate-200 dark:bg-white/15"
                                />
                                <div
                                    class="h-11 w-full rounded-xl bg-slate-200 dark:bg-white/15"
                                />
                            </div>

                            <div class="space-y-2">
                                <div
                                    class="h-4 w-28 rounded bg-slate-200 dark:bg-white/15"
                                />
                                <div
                                    class="h-11 w-full rounded-xl bg-slate-200 dark:bg-white/15"
                                />
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <DatePickerField
                                    label="Date"
                                    :model-value="model.date"
                                    @update:model-value="update('date', $event)"
                                    :min="todayStr"
                                    placeholder="Select a date"
                                    required
                                />
                                <p
                                    v-if="errors?.date"
                                    class="mt-1.5 text-xs text-red-500"
                                >
                                    {{ errors.date }}
                                </p>
                            </div>

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

                                <div class="mt-1.5">
                                    <span
                                        v-if="branchStatus.is24Hours"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-indigo-50 to-violet-50 py-1 pl-2 pr-3 text-[11px] font-semibold text-indigo-600 ring-1 ring-inset ring-indigo-100"
                                    >
                                        <Infinity class="h-3.5 w-3.5" />
                                        {{ branchStatus.label }}
                                    </span>

                                    <span
                                        v-else-if="branchStatus.isOpen"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 py-1 pl-2 pr-3 text-[11px] font-semibold text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                                    >
                                        <span class="relative flex h-2 w-2">
                                            <span
                                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                            />
                                            <span
                                                class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                                            />
                                        </span>

                                        Open now · {{ branchStatus.label }}
                                    </span>

                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 py-1 pl-2 pr-3 text-[11px] font-semibold text-slate-500 dark:bg-secondary dark:text-gray-400"
                                    >
                                        <Clock class="h-3 w-3" />

                                        {{
                                            scheduledHoursLabel
                                                ? `${branchStatus.label} · ${scheduledHoursLabel}`
                                                : branchStatus.label
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div
                v-if="!loading && model.type === 'Medical'"
                class="bg-white dark:bg-secondary"
            >
                <div class="mb-2">
                    <h3
                        class="text-sm font-semibold text-slate-900 dark:text-white"
                    >
                        Medical Service
                        <span class="text-danger">*</span>
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Select one medical service required for this booking.
                    </p>
                </div>

                <button
                    type="button"
                    class="group flex w-full items-center justify-between gap-3 rounded-xl border p-3.5 text-left transition-all duration-200"
                    :class="
                        errors?.services
                            ? 'border-red-400 bg-red-50/30 dark:bg-red-500/10'
                            : selectedService
                              ? 'border-primary bg-primary/5 dark:bg-primary-500/10'
                              : 'border-slate-200 hover:border-primary-200 hover:bg-primary-50/30 dark:border-white/10 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/5'
                    "
                    @click="isServiceModalOpen = true"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                            :class="
                                selectedService
                                    ? 'bg-primary text-white'
                                    : 'bg-primary/10 text-primary'
                            "
                        >
                            <Check v-if="selectedService" class="h-5 w-5" />

                            <Stethoscope v-else class="h-5 w-5" />
                        </div>

                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold"
                                :class="
                                    selectedService
                                        ? 'text-slate-800 dark:text-white'
                                        : 'text-slate-400 dark:text-gray-500'
                                "
                            >
                                {{
                                    selectedService?.service_name ||
                                    "Choose the service for this booking"
                                }}
                            </p>

                            <p
                                class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                {{
                                    selectedService
                                        ? formatCurrency(selectedService.price)
                                        : "One service per booking"
                                }}
                            </p>
                        </div>
                    </div>

                    <span
                        class="shrink-0 whitespace-nowrap text-xs font-semibold transition-colors"
                        :class="
                            selectedService
                                ? 'text-primary'
                                : 'text-slate-400 group-hover:text-primary dark:text-gray-500'
                        "
                    >
                        {{ selectedService ? "Change" : "Choose" }}
                    </span>
                </button>

                <p v-if="errors?.services" class="mt-2 text-xs text-red-500">
                    {{ errors.services }}
                </p>

                <div
                    v-if="selectedService"
                    class="mt-4 flex items-center justify-between rounded-xl bg-primary/5 px-4 py-3"
                >
                    <span
                        class="text-xs font-medium text-slate-500 dark:text-gray-400"
                    >
                        Estimated service total
                    </span>

                    <span class="text-sm font-bold text-primary">
                        {{ formatCurrency(selectedService.price) }}
                    </span>
                </div>

                <div
                    v-if="selectedService && downpaymentVisible"
                    class="mt-2 flex items-center justify-between px-4 text-xs"
                >
                    <span class="text-slate-500 dark:text-gray-400">
                        Downpayment ({{ downpaymentPercent }}%)
                    </span>

                    <span
                        class="font-semibold text-slate-700 dark:text-gray-300"
                    >
                        {{
                            formatCurrency(
                                downpaymentOf(selectedService.price),
                            )
                        }}
                    </span>
                </div>
            </div>

            <div
                v-if="
                    !loading &&
                    model.type === 'ADL' &&
                    Number(adlRatePerHour) > 0
                "
                class="bg-white dark:bg-secondary"
            >
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3
                            class="text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            Care Duration
                        </h3>

                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Specify how many hours of care you require.
                        </p>
                    </div>

                    <div
                        class="inline-flex shrink-0 rounded-lg border border-slate-200 p-0.5 dark:border-white/10"
                    >
                        <button
                            v-for="unit in durationUnits"
                            :key="unit.value"
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-semibold transition"
                            :class="
                                durationUnit === unit.value
                                    ? 'bg-primary text-white'
                                    : 'text-slate-500 hover:text-primary dark:text-gray-400'
                            "
                            @click="durationUnit = unit.value"
                        >
                            {{ unit.label }}
                        </button>
                    </div>
                </div>

                <BaseInput
                    :label="`Duration (${currentDurationUnit.label.toLowerCase()})`"
                    :model-value="durationAmount"
                    @update:model-value="setDurationAmount"
                    mode="number"
                    :placeholder="`Enter total ${currentDurationUnit.label.toLowerCase()}`"
                    :error="errors?.time_span"
                    required
                />

                <p
                    v-if="durationUnit !== 'hours' && Number(model.time_span) > 0"
                    class="mt-1.5 text-xs text-slate-500 dark:text-gray-400"
                >
                    = {{ Number(model.time_span) }} hrs
                </p>

                <div class="mt-4">
                    <p
                        class="mb-2 text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                    >
                        Quick duration
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="preset in durationPresets"
                            :key="preset.label"
                            type="button"
                            class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-primary hover:bg-primary/5 hover:text-primary dark:border-white/10 dark:text-gray-300"
                            @click="addDurationPreset(preset.hours)"
                        >
                            + {{ preset.label }}
                        </button>

                        <button
                            v-if="Number(model.time_span) > minAdlHours"
                            type="button"
                            class="rounded-full px-3 py-1.5 text-xs font-medium text-slate-400 transition hover:text-red-500 dark:text-gray-500"
                            @click="resetDuration"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <div
                    class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:bg-secondary dark:border-white/10"
                >
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500 dark:text-gray-400"
                            >Hourly Rate</span
                        >

                        <span
                            class="font-semibold text-slate-700 dark:text-gray-300"
                        >
                            {{ formatCurrency(adlRatePerHour) }} / hour
                        </span>
                    </div>

                    <div class="mt-2 flex items-center justify-between text-xs">
                        <span class="text-slate-500 dark:text-gray-400"
                            >Minimum Hours</span
                        >

                        <span
                            class="font-semibold text-slate-700 dark:text-gray-300"
                        >
                            {{ minAdlHours }} hours
                        </span>
                    </div>

                    <div
                        v-if="adlTotal"
                        class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3 dark:border-white/10"
                    >
                        <span
                            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Estimated Total
                        </span>

                        <span class="text-base font-bold text-primary">
                            {{ formatCurrency(adlTotal) }}
                        </span>
                    </div>

                    <div
                        v-if="adlTotal && downpaymentVisible"
                        class="mt-2 flex items-center justify-between text-xs"
                    >
                        <span class="text-slate-500 dark:text-gray-400">
                            Downpayment ({{ downpaymentPercent }}%)
                        </span>

                        <span
                            class="font-semibold text-slate-700 dark:text-gray-300"
                        >
                            {{ formatCurrency(downpaymentOf(adlTotal)) }}
                        </span>
                    </div>
                </div>

                <div
                    class="mt-3 flex items-start gap-2 rounded-xl bg-sky-50 px-4 py-3 text-xs leading-5 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300"
                >
                    <Info class="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                        Want a long-term, stay-in caregiver? Please visit the
                        facility to arrange it in person.
                    </p>
                </div>
            </div>

            <div>
                <div
                    class="mb-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h3
                            class="text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            Homecare Service Address
                            <span class="text-danger">*</span>
                        </h3>

                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Where the caregiver or nurse should visit. This can
                            differ from the patient's home address.
                        </p>
                    </div>

                    <button
                        v-if="model.address"
                        type="button"
                        class="text-xs font-medium text-red-500 hover:text-red-600 whitespace-nowrap"
                        @click="locationSelectorRef?.clearSelection()"
                    >
                        Reset
                    </button>
                </div>

                <div class="bg-white dark:bg-secondary">
                    <ClientOnly>
                        <LocationSelector
                            :initial-lat="model.latitude ?? undefined"
                            :initial-lng="model.longitude ?? undefined"
                            :initial-street="model.address || undefined"
                            ref="locationSelectorRef"
                            manual-fallback
                            @location-selected="handleLocationSelected"
                            @location-cleared="handleLocationCleared"
                        />

                        <template #fallback>
                            <div
                                class="w-full h-[400px] rounded-xl border border-gray-200 bg-slate-50 animate-pulse dark:border-white/10 dark:bg-secondary"
                            />
                        </template>
                    </ClientOnly>

                    <p v-if="errors?.address" class="mt-2 text-xs text-danger">
                        {{ errors.address }}
                    </p>
                </div>
            </div>

            <div v-if="!loading">
                <div class="mb-2">
                    <h3
                        class="text-sm font-semibold text-slate-900 dark:text-white"
                    >
                        Location Instructions
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Help the caregiver or nurse find the exact spot: building,
                        floor, unit or door number, gate, and nearby landmarks.
                    </p>
                </div>

                <BaseInput
                    :model-value="model.note ?? ''"
                    @update:model-value="update('note', $event)"
                    mode="textarea"
                    :rows="3"
                    :allowResize="true"
                    :textMax="500"
                    placeholder="e.g. Tower B, 5th floor, Unit 512. Enter through the side gate beside the 7-Eleven."
                    :error="errors?.note"
                />
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
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";
import { Check, Stethoscope, Clock, Infinity, Info } from "lucide-vue-next";
import Combobox from "~/components/ui/Combobox.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import LocationSelector from "~/components/ui/LocationSelector.vue";
import ServiceModal from "~/components/sections/booking/provider/ServiceModal.vue";
import {
    getLocalDateStr,
    generateAvailableAmPmTimesBySchedule,
    getBranchTimeDisplay,
    format24To12,
} from "~/utils/time";
import { formatCurrency } from "~/utils/currency";
import type { HomecareBooking } from "~/types/booking";
import type { Service } from "~/types/service";
import type { BranchSettings, BranchHomecare } from "~/types/branch";
import { useMedicalServices } from "~/composables/useBooking";

const props = defineProps<{
    model: HomecareBooking;
    services: Service[];
    loading?: boolean;
    errors?: Record<string, string> | null;
    homecare?: BranchHomecare;
    settings?: BranchSettings;
    showDownpayment?: boolean;
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

const descriptionRefs: Record<string, HTMLElement> = {};
const expandedTypes = ref<Record<string, boolean>>({});
const overflowingTypes = ref<Record<string, boolean>>({});

const setDescriptionRef = (key: string, el: HTMLElement | null) => {
    if (!el) {
        delete descriptionRefs[key];
        return;
    }

    if (descriptionRefs[key] !== el) {
        descriptionRefs[key] = el;
        nextTick(measureDescriptions);
    }
};

const measureDescriptions = () => {
    for (const [key, el] of Object.entries(descriptionRefs)) {
        if (expandedTypes.value[key]) continue;
        overflowingTypes.value[key] = el.scrollHeight > el.clientHeight + 1;
    }
};

const toggleDescription = (key: string) => {
    expandedTypes.value[key] = !expandedTypes.value[key];
};

onMounted(() => window.addEventListener("resize", measureDescriptions));

onBeforeUnmount(() =>
    window.removeEventListener("resize", measureDescriptions),
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

const handleLocationSelected = (location: {
    lat: number | null;
    lng: number | null;
    label: string;
    street: string;
    city: string;
    province: string;
    country: string;
}) => {
    const address =
        location.label ||
        [location.street, location.city, location.province, location.country]
            .filter(Boolean)
            .join(", ");

    emit("update:model", {
        ...props.model,
        address,
        latitude: location.lat,
        longitude: location.lng,
    });

    clearError("address");
};

const handleLocationCleared = () => {
    emit("update:model", {
        ...props.model,
        address: "",
        latitude: null,
        longitude: null,
    });
};

const adlTotal = computed(() => {
    const hours = Number(props.model.time_span);

    if (isNaN(hours) || hours < minAdlHours.value) {
        return 0;
    }

    return hours * adlRatePerHour.value;
});

const downpaymentPercent = computed<number>(
    () => props.settings?.homecare_downpayment_percent ?? 50,
);

const downpaymentVisible = computed(
    () => !!props.showDownpayment && downpaymentPercent.value < 100,
);

function downpaymentOf(amount: number) {
    return (
        Math.round(
            Number(amount ?? 0) * (downpaymentPercent.value / 100) * 100,
        ) / 100
    );
}

const branchStatus = computed(() => getBranchTimeDisplay(props.settings));

const scheduledHoursLabel = computed(() => {
    const opening = props.settings?.opening;
    const closing = props.settings?.closing;

    if (!opening || !closing) return null;
    if (opening === "00:00" && (closing === "00:00" || closing === "23:59")) return null;

    return `${format24To12(opening)} – ${format24To12(closing)}`;
});

const isServiceModalOpen = ref(false);
const locationSelectorRef = ref<InstanceType<typeof LocationSelector> | null>(
    null,
);

const todayStr = getLocalDateStr(new Date());

const HOMECARE_LEAD_MINUTES = 60;

const now = ref(new Date());
let clock: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    now.value = new Date();
    clock = setInterval(() => (now.value = new Date()), 60_000);
});

onBeforeUnmount(() => clearInterval(clock));

const availableTimeSlots = computed(() =>
    generateAvailableAmPmTimesBySchedule(
        props.settings?.opening ?? "00:00",
        props.settings?.closing ?? "00:00",
        props.model.date,
        30,
        now.value,
        HOMECARE_LEAD_MINUTES,
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

const durationUnits = [
    { label: "Hours", value: "hours", hours: 1 },
    { label: "Days", value: "days", hours: 24 },
] as const;

const durationUnit = ref<(typeof durationUnits)[number]["value"]>("hours");

const currentDurationUnit = computed(
    () => durationUnits.find((unit) => unit.value === durationUnit.value)!,
);

const durationAmount = ref("");

const syncDurationAmount = () => {
    const hours = Number(props.model.time_span);
    durationAmount.value =
        props.model.time_span === "" || isNaN(hours)
            ? ""
            : String(
                  Math.round((hours / currentDurationUnit.value.hours) * 100) /
                      100,
              );
};

const setDurationAmount = (value: string | number) => {
    durationAmount.value = String(value ?? "");
    const amount = Number(value);
    update(
        "time_span",
        value === "" || isNaN(amount)
            ? ""
            : String(Math.round(amount * currentDurationUnit.value.hours)),
    );
};

watch(durationUnit, syncDurationAmount);

watch(
    () => props.model.time_span,
    (hours) => {
        const expected = Math.round(
            Number(durationAmount.value) * currentDurationUnit.value.hours,
        );
        if (durationAmount.value === "" || Number(hours) !== expected) {
            syncDurationAmount();
        }
    },
    { immediate: true },
);
</script>
