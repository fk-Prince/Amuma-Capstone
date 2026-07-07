<!-- <template>
    <section class="rounded-2xl p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">01</span>
            <div>
                <h2 class="text-xl text-primary">Booking Request</h2>
                <p class="text-[13px] text-muted">
                    Select service type and schedule your appointment
                </p>
            </div>
        </div>

        <div class="space-y-8">
            <div>
                <label class="text-sm font-semibold text-slate-700 mb-3 block">
                    Choose Booking Category
                    <span class="text-danger">*</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        @click="selectCategory('homecare')"
                        class="border rounded-xl p-4 cursor-pointer transition"
                        :class="
                            model.category === 'homecare'
                                ? 'border-primary bg-primary/5'
                                : 'border-slate-200'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div class="text-primary text-xl">🏠</div>
                            <div>
                                <p class="font-semibold text-slate-800">
                                    Homecare Service
                                </p>
                                <p class="text-[13px] text-muted">
                                    Medical and caregiver services at home
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        @click="selectCategory('facility')"
                        class="border rounded-xl p-4 cursor-pointer transition"
                        :class="
                            model.category === 'facility'
                                ? 'border-primary bg-primary/5'
                                : 'border-slate-200'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div class="text-primary text-xl">🏥</div>
                            <div>
                                <p class="font-semibold text-slate-800">
                                    Facility Admission
                                </p>
                                <p class="text-[13px] text-muted">
                                    Admission and pre-admission requests
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="model.category === 'homecare'">
                <label class="text-sm font-semibold text-slate-700 mb-3 block">
                    Choose Booking Type
                    <span class="text-danger">*</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="item in bookingTypes"
                        :key="item.value"
                        @click="update('type', item.value)"
                        class="border rounded-xl p-4 cursor-pointer transition"
                        :class="
                            model.type === item.value
                                ? 'border-primary bg-primary/5'
                                : 'border-slate-200'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div class="text-primary text-xl">
                                {{ item.icon }}
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
                                    ₱1,500 - ₱3,500
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="model.category === 'facility'">
                <label class="text-sm font-semibold text-slate-700 mb-3 block">
                    Admission Plan
                    <span class="text-danger">*</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        @click="update('service', 'monthly')"
                        class="border rounded-xl p-4 cursor-pointer transition"
                        :class="
                            model.service === 'monthly'
                                ? 'border-primary bg-primary/5'
                                : 'border-slate-200'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div class="text-primary text-xl">📅</div>
                            <div>
                                <p class="font-semibold text-slate-800">
                                    Monthly
                                </p>
                                <p class="text-[13px] text-muted">
                                    Monthly admission plan
                                </p>
                                <p
                                    class="text-sm font-semibold text-primary mt-1"
                                >
                                    ₱8,000 / month
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        @click="update('service', 'annually')"
                        class="border rounded-xl p-4 cursor-pointer transition"
                        :class="
                            model.service === 'annually'
                                ? 'border-primary bg-primary/5'
                                : 'border-slate-200'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div class="text-primary text-xl">🗓️</div>
                            <div>
                                <p class="font-semibold text-slate-800">
                                    Annually
                                </p>
                                <p class="text-[13px] text-muted">
                                    Annual admission plan
                                </p>
                                <p
                                    class="text-sm font-semibold text-primary mt-1"
                                >
                                    ₱85,000 / year
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-[#E4E0D6]" />

            <div
                v-if="model.category === 'homecare'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start"
            >
                <div>
                    <label class="text-sm font-medium"
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
                        :model-value="model.time"
                        @update:model-value="update('time', $event)"
                        label="Preferred Time"
                        :placeholder="displayTime"
                        required
                        :items="availableTimeSlots"
                    />

                    <p class="text-[12px] text-muted">
                        Open {{ formatHour(openingHour) }} –
                        {{ formatHour(closingHour) }}
                    </p>
                </div>

                <Combobox
                    v-if="model.type !== 'caregiver'"
                    :model-value="model.service"
                    @update:model-value="update('service', $event)"
                    label="Medical Service"
                    placeholder="Select service"
                    required
                    searchBar
                    :items="serviceItems"
                />

                <BaseInput
                    v-model="searchLocation"
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
            </div>

            <div
                v-else-if="model.category === 'facility'"
                class="grid grid-cols-1 gap-6"
            >
                <div>
                    <label class="text-sm font-medium">Admission Date</label>
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
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, watch, ref } from "vue";
import Combobox from "~/components/ui/Combobox.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import LocationIcon from "~/components/icons/location.vue";
import {
    formatHour,
    getTimeSlots,
    getLocalDateStr,
    filterAvailableSlots,
} from "~/utils/time-slot";
import type { Location } from "~/types/location";

const props = defineProps<{
    model: BookingService;
    serviceLocation?: Location;
}>();

const emit = defineEmits<{
    (e: "update:model", value: BookingService): void;
    (e: "update:serviceLocation", value: Location): void;
}>();

const searchLocation = ref(props.serviceLocation?.address ?? "");

watch(
    () => props.serviceLocation,
    (loc) => {
        if (loc?.address && loc.address !== searchLocation.value) {
            searchLocation.value = loc.address;
        }
    },
);

const handleLocation = (data: any) => {
    searchLocation.value =
        data.address ??
        [data.street, data.city, data.province, data.country]
            .filter(Boolean)
            .join(", ");

    emit("update:serviceLocation", {
        address: data.address,
        street: data.street,
        city: data.city,
        province: data.province,
        country: data.country,
        latitude: data.latitude,
        longitude: data.longitude,
    });
};

function update<K extends keyof BookingService>(
    key: K,
    value: BookingService[K],
) {
    emit("update:model", {
        ...props.model,
        [key]: value,
    });
}

function selectCategory(value: string) {
    emit("update:model", {
        ...props.model,
        category: value,
        type: "",
        service: "",
        date: "",
        time: "",
    });
}

const bookingTypes = computed(() => {
    if (props.model.category === "homecare") {
        return [
            {
                value: "medical",
                title: "Medical Services",
                description: "Nursing care, wound care, medication, injections",
                icon: "🩺",
            },
            {
                value: "caregiver",
                title: "Caregiver (ADL Services)",
                description: "Daily assistance like bathing, feeding, dressing",
                icon: "👥",
            },
        ];
    }

    return [
        {
            value: "admission",
            title: "Complete Admission",
            description: "Proceed directly with facility admission",
            icon: "🏥",
        },
        {
            value: "preadmission",
            title: "Pre-Admission Request",
            description: "Assessment before admission to facility",
            icon: "📝",
        },
    ];
});

const serviceItems = computed(() => {
    if (props.model.category === "homecare") {
        return [
            { label: "General Checkup", value: "General Checkup" },
            { label: "Injection", value: "Injection" },
            { label: "Wound Dressing", value: "Wound Dressing" },
            { label: "Physiotherapy", value: "Physiotherapy" },
        ];
    }

    return [
        { label: "Complete Admission", value: "Complete Admission" },
        { label: "Pre-Admission Request", value: "Pre-Admission Request" },
    ];
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
    if (!slots.find((s) => s.value === props.model.time)) {
        update("time", "");
    }
});
</script> -->

<template>
    <div>HEWA</div>
</template>
