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
                            class="border rounded-xl p-4 cursor-pointer transition"
                            :class="
                                model.type === item.value
                                    ? 'border-primary bg-primary/5'
                                    : 'border-slate-200'
                            "
                        >
                            <div class="flex items-center gap-5">
                                <div class="text-primary text-xl">
                                    <component
                                        v-if="typeof item.icon !== 'string'"
                                        :is="item.icon"
                                        class="h-6 w-6"
                                    />
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800">
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
                    <div>
                        <label class="text-sm font-semibold text-slate-700"
                            >Select Schedule Date</label
                        >
                        <input
                            type="date"
                            :value="model.date"
                            @input="
                                update(
                                    'date',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                            :min="todayStr"
                            class="w-full border rounded-lg p-2 mt-1"
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Combobox
                            :model-value="model.prefered_time"
                            @update:model-value="
                                update('prefered_time', $event)
                            "
                            label="Preferred Time"
                            :placeholder="displayTime"
                            required
                            :items="availableTimeSlots"
                        />

                        <p
                            class="text-[12px] text-muted"
                            v-if="model.type === 'Medical'"
                        >
                            Open {{ formatHour(openingHour) }} –
                            {{ formatHour(closingHour) }}
                        </p>
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
                            :class="
                                selectedServiceLabel
                                    ? 'text-slate-800'
                                    : 'text-muted'
                            "
                        >
                            <span class="truncate">
                                {{ selectedServiceLabel || "Select service" }}
                            </span>
                            <span class="text-slate-400 text-sm">›</span>
                        </button>

                        <p
                            v-if="selectedServicesTotal"
                            class="text-[12px] font-semibold text-primary"
                        >
                            Total: ₱{{ selectedServicesTotal.toFixed(2) }}
                        </p>
                    </div>

                    <div
                        v-if="model.type === 'ADL'"
                        class="flex flex-col gap-1.5"
                    >
                        <label class="text-sm font-semibold text-slate-700">
                            Duration (hours)
                            <span class="text-danger">*</span>
                        </label>
                        <input
                            type="number"
                            :min="minAdlHours"
                            step="1"
                            :value="model.time_span"
                            @input="
                                update(
                                    'time_span',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                            class="w-full border rounded-lg p-2"
                            placeholder="Enter total hours"
                        />

                        <p class="text-[12px] text-muted">
                            {{ adlRatePerHour }} / hour • Minimum
                            {{ minAdlHours }} hours
                        </p>
                        <p
                            v-if="adlTotal"
                            class="text-[12px] font-semibold text-primary"
                        >
                            Total: ₱{{ adlTotal.toLocaleString() }}
                        </p>
                    </div>

                    <BaseInput
                        :model-value="model.address"
                        @update:model-value="update('address', $event)"
                        label="Address"
                        placeholder="Target Location"
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
import Combobox from "~/components/ui/Combobox.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import LocationIcon from "~/components/icons/location.vue";
import { Stethoscope, Users } from "lucide-vue-next";
import ServiceModal from "~/components/sections/booking/provider/ServiceModal.vue";
import {
    formatHour,
    getTimeSlots,
    getLocalDateStr,
    filterAvailableSlots,
} from "~/utils/time";
import type { HomecareBooking } from "~/types/booking";
import type { Service } from "~/types/service";

const props = defineProps<{
    model: HomecareBooking;
    services: Service[];
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:model", value: HomecareBooking): void;
}>();

function update<K extends keyof HomecareBooking>(
    key: K,
    value: HomecareBooking[K],
) {
    emit("update:model", {
        ...props.model,
        [key]: value,
    });
}

const minAdlHours = 8;
const adlRatePerHour = 150;

const handleLocation = (data: any) => {
    const address =
        data.address ??
        [data.street, data.city, data.province, data.country]
            .filter(Boolean)
            .join(", ");

    update("address", address);
};
const medicalRateLabel = computed(() => {
    if (!props.services.length) return "No services";

    const prices = props.services
        .map((service) => Number(service.price))
        .filter((price) => !isNaN(price));

    if (!prices.length) return "No pricing";

    const min = Math.min(...prices);
    const max = Math.max(...prices);

    if (min === max) {
        return `₱${min.toLocaleString()}`;
    }

    return `₱${min.toLocaleString()} - ₱${max.toLocaleString()}`;
});

const medicalDescription = computed(() => {
    if (!props.services.length) {
        return "No medical services available";
    }

    const names = props.services.map((service) => service.service_name);

    if (names.length <= 3) {
        return names.join(", ");
    }

    return `${names.slice(0, 3).join(", ")} and ${names.length - 3} more`;
});
const bookingTypes = computed(() => [
    {
        value: "Medical",
        title: "Medical Services",
        description: medicalDescription,
        icon: Stethoscope,
        rateLabel: medicalRateLabel,
    },
    {
        value: "ADL",
        title: "Caregiver (ADL Services)",
        description: "Daily assistance like bathing, feeding, dressing",
        icon: Users,
        rateLabel: `${adlRatePerHour} / hour   •   Min ${minAdlHours} hrs`,
    },
]);

const adlTotal = computed(() => {
    const hours = Number(props.model.time_span);

    if (isNaN(hours) || hours < minAdlHours) {
        return 0;
    }

    return hours * adlRatePerHour;
});

const isServiceModalOpen = ref(false);

const selectedServiceLabel = computed(() => {
    const services = props.model.services ?? [];
    if (!services.length) return "";
    return services.map((s) => s.service_name).join(", ");
});

const selectedServicesTotal = computed(() => {
    const services = props.model.services ?? [];
    return services.reduce((sum, s) => sum + Number(s.price), 0);
});

const openingHour = 8;
const closingHour = 18;
const slotLengthHours = 2;

const todayStr = getLocalDateStr(new Date());

const allTimeSlots = computed(() =>
    getTimeSlots(openingHour, closingHour, slotLengthHours),
);

const availableTimeSlots = computed(() =>
    filterAvailableSlots(allTimeSlots.value, props.model.date),
);

const displayTime = computed(() =>
    availableTimeSlots.value.length ? "Select time" : "No slots available",
);

watch(availableTimeSlots, (slots) => {
    if (!slots.find((s) => s.value === props.model.prefered_time)) {
        update("prefered_time", "");
    }
});

watch(
    () => props.model.type,
    (type) => {
        if (type === "ADL") {
            update("services", []);
            if (!props.model.time_span) {
                update("time_span", String(minAdlHours));
            }
        } else {
            update("time_span", "");
        }
    },
);
</script>
