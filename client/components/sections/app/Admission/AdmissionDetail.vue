<template>
    <component
        :is="variant === 'modal' || variant === 'new' ? 'div' : 'section'"
        :class="
            variant === 'modal' || variant === 'new'
                ? 'fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-5'
                : ''
        "
        @click.self="
            variant === 'modal' || variant === 'new' ? emit('close') : null
        "
    >
        <div
            :class="
                variant === 'modal' || variant === 'new'
                    ? 'relative bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 md:p-8 shadow-xl dark:bg-secondary'
                    : 'rounded-2xl bg-white p-6 md:p-8 dark:bg-secondary'
            "
        >
            <button
                v-if="variant === 'modal' || variant === 'new'"
                type="button"
                class="absolute top-5 right-5 h-8 w-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus:outline-none dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                aria-label="Close"
                @click="emit('close')"
            >
                ✕
            </button>

            <div
                v-if="errorEntries.length"
                class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/20 dark:bg-rose-500/10"
            >
                <div class="flex items-start gap-2.5">
                    <svg
                        class="h-5 w-5 text-rose-500 shrink-0 mt-0.5 dark:text-rose-300"
                        viewBox="0 0 20 20"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="10" cy="10" r="7" />
                        <path d="M10 7v3.5M10 13.5h.01" />
                    </svg>

                    <div>
                        <p class="text-sm font-semibold text-rose-700 dark:text-rose-300">
                            Please fix the following before continuing
                        </p>

                        <ul
                            class="mt-1.5 space-y-1 text-sm text-rose-600 list-disc list-inside dark:text-rose-300"
                        >
                            <li
                                v-for="[key, message] in errorEntries"
                                :key="key"
                            >
                                {{ message }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-5"
            >
                <div class="flex items-baseline gap-3 mb-8">
                    <span
                        v-if="variant === 'page'"
                        class="text-2xl text-primary"
                    >
                        01
                    </span>

                    <div>
                        <h2 class="text-xl text-primary">
                            Choose Accommodation
                        </h2>

                        <p class="text-[13px] text-muted dark:text-gray-400">
                            Select your preferred room type and available bed.
                        </p>
                    </div>
                </div>

                <div
                    v-if="hasYearlyOption && variant === 'page'"
                    class="flex items-center gap-3 shrink-0"
                >
                    <span
                        class="text-sm font-medium transition-colors"
                        :class="
                            billingCycle === 'monthly'
                                ? 'text-slate-900 dark:text-white'
                                : 'text-slate-400 dark:text-gray-500'
                        "
                    >
                        Monthly
                    </span>

                    <button
                        type="button"
                        role="switch"
                        :aria-checked="billingCycle === 'yearly'"
                        aria-label="Toggle billing interval"
                        @click="
                            billingCycle =
                                billingCycle === 'monthly'
                                    ? 'yearly'
                                    : 'monthly'
                        "
                        class="relative h-7 w-12 rounded-full transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                        :class="
                            billingCycle === 'yearly'
                                ? 'bg-primary'
                                : 'bg-slate-200 dark:bg-white/15'
                        "
                    >
                        <span
                            class="absolute top-0.5 left-0.5 h-6 w-6 rounded-full bg-white shadow transition-transform duration-200 dark:bg-secondary"
                            :class="
                                billingCycle === 'yearly'
                                    ? 'translate-x-5'
                                    : 'translate-x-0'
                            "
                        />
                    </button>

                    <span
                        class="text-sm font-medium transition-colors flex items-center gap-1.5"
                        :class="
                            billingCycle === 'yearly'
                                ? 'text-slate-900 dark:text-white'
                                : 'text-slate-400 dark:text-gray-500'
                        "
                    >
                        Yearly

                        <span
                            v-if="activeDiscountPercent !== null"
                            class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                        >
                            Save {{ activeDiscountPercent }}%
                        </span>
                    </span>
                </div>
            </div>

            <div v-if="loading" class="grid md:grid-cols-2 gap-5">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="rounded-2xl border p-6 animate-pulse dark:border-white/10"
                >
                    <div class="flex items-center justify-between">
                        <div class="space-y-3">
                            <div class="h-5 w-32 bg-slate-200 rounded dark:bg-white/15"></div>

                            <div class="h-3 w-48 bg-slate-100 rounded dark:bg-white/10"></div>
                        </div>

                        <div class="h-12 w-12 rounded-xl bg-slate-200 dark:bg-white/15"></div>
                    </div>

                    <div class="mt-6 flex justify-between items-end">
                        <div class="h-4 w-28 bg-slate-200 rounded dark:bg-white/15"></div>

                        <div class="space-y-2 text-right">
                            <div class="h-5 w-20 bg-slate-200 rounded dark:bg-white/15"></div>

                            <div class="h-3 w-16 bg-slate-100 rounded dark:bg-white/10"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else-if="!contracts.length"
                class="rounded-2xl border border-dashed p-10 text-center text-slate-400 dark:text-gray-500 dark:border-white/10"
            >
                No accommodation options are currently available for this
                branch.
            </div>

            <div
                v-else-if="viewMode === 'types'"
                class="grid md:grid-cols-2 gap-5"
                :class="
                    errors.room || errors.bed || errors.contract_id
                        ? 'ring-1 ring-rose-200 rounded-2xl p-2 dark:ring-rose-500/20'
                        : ''
                "
            >
                <button
                    v-for="type in accommodationTypes"
                    :key="type.value"
                    :disabled="roomsFor(type.value).length === 0"
                    @click="selectType(type.value)"
                    class="text-left rounded-2xl border p-6 transition hover:border-primary hover:bg-primary/5 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:border-inherit disabled:hover:bg-transparent disabled:hover:shadow-none dark:border-white/10"
                    :class="
                        activeType === type.value
                            ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                            : ''
                    "
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-lg">
                                {{ type.label }}
                            </h3>

                            <p class="text-sm text-slate-500 mt-1 dark:text-gray-400">
                                {{ type.description }}
                            </p>
                        </div>

                        <div
                            class="h-12 w-12 shrink-0 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-xl"
                        >
                            {{ type.icon }}
                        </div>
                    </div>

                    <div class="mt-5 flex items-end justify-between">
                        <span
                            class="text-sm"
                            :class="
                                roomsFor(type.value).length === 0
                                    ? 'text-rose-500 font-medium dark:text-rose-300'
                                    : 'text-slate-500 dark:text-gray-400'
                            "
                        >
                            {{
                                roomsFor(type.value).length === 0
                                    ? "No rooms available"
                                    : `${roomsFor(type.value).length} room${
                                          roomsFor(type.value).length === 1
                                              ? ""
                                              : "s"
                                      } available`
                            }}
                        </span>

                        <div class="text-right">
                            <span class="font-semibold text-primary">
                                {{ formatPrice(type.value) }}
                            </span>

                            <span class="block text-xs text-slate-400 dark:text-gray-500">
                                {{
                                    billingCycle === "yearly"
                                        ? "billed yearly"
                                        : "billed monthly"
                                }}
                            </span>
                        </div>
                    </div>

                    <p
                        v-if="activeType === type.value"
                        class="mt-3 text-xs font-medium text-primary"
                    >
                        Currently selected
                    </p>
                </button>
            </div>

            <div v-if="viewMode === 'rooms' && activeType" class="space-y-6">
                <button
                    class="text-sm text-primary font-medium inline-flex items-center gap-1 hover:underline focus:outline-none"
                    @click="viewMode = 'types'"
                >
                    ← Change accommodation
                </button>

                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold">
                        {{ activeType }} Rooms
                    </h3>

                    <span class="text-sm font-medium text-primary">
                        {{ formatPrice(activeType) }}

                        <span class="text-slate-400 font-normal dark:text-gray-500">
                            /
                            {{ billingCycle === "yearly" ? "year" : "month" }}
                        </span>
                    </span>
                </div>

                <div
                    v-if="filteredRooms.length === 0"
                    class="rounded-2xl border border-dashed p-10 text-center text-slate-400 dark:text-gray-500 dark:border-white/10"
                >
                    No rooms currently available for this type.
                </div>

                <div v-else class="grid md:grid-cols-2 gap-5">
                    <div
                        v-for="room in filteredRooms"
                        :key="room.room_id"
                        class="rounded-2xl border p-5 transition hover:shadow-sm dark:border-white/10"
                        :class="
                            selectedRoom?.room_id === room.room_id
                                ? 'border-primary ring-1 ring-primary/30'
                                : ''
                        "
                    >
                        <div class="flex justify-between">
                            <div>
                                <h4 class="font-semibold text-lg">
                                    Room {{ room.room_no }}
                                </h4>

                                <p class="text-sm text-slate-500 dark:text-gray-400">
                                    {{ room.floor }} Floor
                                </p>
                            </div>

                            <span
                                class="text-xs h-fit rounded-full px-3 py-1 font-medium"
                                :class="
                                    availableBeds(room).length > 0
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
                                "
                            >
                                {{
                                    !room.beds?.length
                                        ? "No Available Bed"
                                        : availableBeds(room).length > 0
                                          ? "Available"
                                          : "Fully Reserved"
                                }}
                            </span>
                        </div>

                        <div class="mt-5 space-y-2 text-sm">
                            <p class="flex justify-between">
                                <span class="text-slate-500 dark:text-gray-400"> Capacity </span>

                                <strong>{{ room.capacity }}</strong>
                            </p>

                            <p class="flex justify-between">
                                <span class="text-slate-500 dark:text-gray-400">
                                    Available Beds
                                </span>

                                <strong class="text-emerald-600 dark:text-emerald-300">
                                    {{ availableBeds(room).length }}
                                </strong>
                            </p>

                            <p class="flex justify-between">
                                <span class="text-slate-500 dark:text-gray-400">
                                    Reserved Beds
                                </span>

                                <strong class="text-rose-600 dark:text-rose-300">
                                    {{ reservedBeds(room).length }}
                                </strong>
                            </p>

                            <p class="flex justify-between pt-2 border-t">
                                <span class="text-slate-500 dark:text-gray-400"> Price </span>

                                <strong class="text-primary">
                                    {{ formatPrice(activeType) }}

                                    <span
                                        class="text-slate-400 font-normal text-xs dark:text-gray-500"
                                    >
                                        /
                                        {{
                                            billingCycle === "yearly"
                                                ? "yr"
                                                : "mo"
                                        }}
                                    </span>
                                </strong>
                            </p>
                        </div>

                        <button
                            :disabled="availableBeds(room).length === 0"
                            class="mt-5 w-full rounded-xl bg-primary text-white py-2.5 font-medium transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            @click="openBeds(room)"
                        >
                            Select Bed
                        </button>
                    </div>
                </div>
            </div>

            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="selectedRoom && selectedBed"
                    class="mt-8 rounded-2xl bg-primary/5 border border-primary/20 p-5"
                >
                    <h3 class="font-semibold">Selected Accommodation</h3>

                    <div
                        class="mt-3 text-sm grid grid-cols-2 gap-y-1 gap-x-4 sm:grid-cols-4"
                    >
                        <p>
                            Room
                            <strong class="block">
                                {{ selectedRoom.room_no }}
                            </strong>
                        </p>

                        <p>
                            Bed
                            <strong class="block">
                                {{ selectedBed.bed_no }}
                            </strong>
                        </p>

                        <p>
                            Type
                            <strong class="block">
                                {{ activeType }}
                            </strong>
                        </p>

                        <p>
                            Price
                            <strong class="block text-primary">
                                {{ formatPrice(activeType) }}
                                /
                                {{ billingCycle === "yearly" ? "yr" : "mo" }}
                            </strong>
                        </p>
                    </div>

                    <div class="mt-4" v-if="requireAdmissionDate">
                        <BaseInput
                            label="Admission Date"
                            mode="date"
                            v-model="admittedAt"
                            :max="maxDateStr"
                            :min="todayStr"
                        />
                    </div>
                </div>
            </transition>

            <transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showBeds"
                    class="fixed inset-0 z-[60] bg-black/40 flex items-center justify-center p-5"
                    @click.self="showBeds = false"
                >
                    <div
                        class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl dark:bg-secondary"
                    >
                        <div class="flex justify-between items-center mb-5">
                            <div>
                                <h3 class="font-semibold text-lg">
                                    Select Bed
                                </h3>

                                <p class="text-sm text-slate-500 dark:text-gray-400">
                                    Room {{ modalRoom?.room_no }}
                                </p>
                            </div>

                            <button
                                class="h-8 w-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus:outline-none dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                                aria-label="Close"
                                @click="showBeds = false"
                            >
                                ✕
                            </button>
                        </div>

                        <div class="space-y-3">
                            <button
                                v-for="bed in modalRoom?.beds"
                                :key="bed.bed_id"
                                :disabled="!isAvailable(bed)"
                                @click="chooseBed(bed)"
                                class="w-full rounded-xl border p-4 flex justify-between items-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary dark:border-white/10"
                                :class="
                                    isAvailable(bed)
                                        ? 'hover:border-primary hover:bg-primary/5'
                                        : 'opacity-50 cursor-not-allowed'
                                "
                            >
                                <span class="font-medium">
                                    Bed {{ bed.bed_no }}
                                </span>

                                <span
                                    class="text-xs rounded-full px-3 py-1 font-medium"
                                    :class="statusBadgeClass(bed.status)"
                                >
                                    {{ bed.status }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <div
                v-if="variant === 'modal' || variant === 'new'"
                class="mt-8 pt-5 border-t flex justify-end"
            >
                <button
                    type="button"
                    :disabled="
                        !selectedRoom ||
                        !selectedBed ||
                        (props.requireAdmissionDate && !admittedAt)
                    "
                    class="px-6 py-2.5 rounded-xl bg-primary text-white font-medium transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    @click="handleDone"
                >
                    Done
                </button>
            </div>
        </div>
    </component>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { Room } from "~/types/room";
import type { RoomContract, Reserved } from "~/types/contract";
import type { Bed } from "~/types/bed";
import BaseInput from "~/components/ui/BaseInput.vue";
import { toLocalDateString } from "~/utils/time";
import { formatCurrency } from "~/utils/currency";

type ApiBed = Bed;
type ApiRoom = Room;

const props = withDefaults(
    defineProps<{
        loading?: boolean;
        roomContract: RoomContract[] | null | undefined;
        model?: Reserved | null;
        variant?: "page" | "modal" | "new";
        errors?: Record<string, string>;
        accommodation?: string | null;
        // The billing cycle the family actually chose when they submitted
        // this booking. Without it, the modal has no way to know and
        // silently falls back to "monthly" — which then picks the wrong
        // contract (and price) for a booking made under a yearly plan.
        initialBillingCycle?: "monthly" | "yearly" | null;
        requireAdmissionDate?: boolean;
    }>(),
    {
        loading: false,
        roomContract: () => [],
        model: null,
        variant: "page",
        errors: () => ({}),
        accommodation: null,
        initialBillingCycle: null,
        requireAdmissionDate: false,
    },
);

const admittedAt = ref("");

const contracts = computed<RoomContract[]>(() => {
    const data = props.roomContract ?? [];

    if (!props.accommodation) {
        return data;
    }

    return data.filter(
        (contract) =>
            contract.accommodation_type.toUpperCase() ===
            props.accommodation?.toUpperCase(),
    );
});

const errorEntries = computed(() =>
    Object.entries(props.errors).filter(([, message]) => !!message),
);

const emit = defineEmits<{
    (e: "update:model", value: Reserved): void;
    (e: "close"): void;
    (e: "confirm", value: Reserved): void;
}>();

const typeMeta: Record<
    string,
    {
        label: string;
        description: string;
        icon: string;
    }
> = {
    VIP: {
        label: "VIP Room",
        description: "Private premium accommodation",
        icon: "★",
    },
    COMMON: {
        label: "Common Room",
        description: "Shared accommodation",
        icon: "🛏",
    },
};

function metaFor(type: string) {
    return (
        typeMeta[type.toUpperCase()] ?? {
            label: type,
            description: "",
            icon: "🏠",
        }
    );
}

const viewMode = ref<"types" | "rooms">("types");
const activeType = ref<string | null>(null);

const modalRoom = ref<ApiRoom | null>(null);
const showBeds = ref(false);
const selectedRoom = ref<ApiRoom | null>(null);
const selectedBed = ref<ApiBed | null>(null);
const billingCycle = ref<"monthly" | "yearly">("monthly");

watch(
    [() => props.roomContract, () => props.model],
    () => {
        showBeds.value = false;

        if (!props.model?.room || !props.model?.bed) {
            viewMode.value = "types";
            activeType.value = null;
            selectedRoom.value = null;
            selectedBed.value = null;
            admittedAt.value = "";
            billingCycle.value = props.initialBillingCycle ?? "monthly";
            return;
        }

        selectedRoom.value = props.model.room;
        selectedBed.value = props.model.bed;

        activeType.value = props.model.accommodation_type;
        admittedAt.value = props.model.admitted_at ?? "";

        billingCycle.value =
            props.model.billing_cycle?.toLowerCase() === "yearly"
                ? "yearly"
                : "monthly";

        viewMode.value = "rooms";
    },
    {
        immediate: true,
        deep: true,
    },
);

const accommodationTypes = computed(() => {
    const seen = new Set<string>();

    const list: {
        value: "Common" | "VIP";
        label: string;
        description: string;
        icon: string;
    }[] = [];

    for (const contract of contracts.value) {
        const normalized = normalizeAccommodationType(
            contract.accommodation_type,
        );

        if (seen.has(normalized)) continue;

        seen.add(normalized);

        list.push({
            value: normalized,
            ...metaFor(normalized),
        });
    }

    return list;
});

function contractFor(type: string, interval: "monthly" | "yearly") {
    return contracts.value.find(
        (c) =>
            normalizeAccommodationType(c.accommodation_type) ===
                normalizeAccommodationType(type) &&
            c.billing_cycle.toLowerCase() === interval.toLowerCase(),
    );
}

const hasYearlyOption = computed(() =>
    contracts.value.some((c) => c.billing_cycle.toUpperCase() === "YEARLY"),
);

const activeDiscountPercent = computed(() => {
    const type =
        activeType.value ??
        accommodationTypes.value.find(
            (t) =>
                contractFor(t.value, "monthly") &&
                contractFor(t.value, "yearly"),
        )?.value;

    if (!type) return null;

    const monthly = contractFor(type, "monthly");
    const yearly = contractFor(type, "yearly");

    if (!monthly || !yearly) return null;

    const monthlyAnnual = monthly.price * 12;
    const yearlyPrice = yearly.price;

    if (!monthlyAnnual) return null;

    const percent = Math.round((1 - yearlyPrice / monthlyAnnual) * 100);

    return percent > 0 ? percent : null;
});

function formatPrice(type: string | null | undefined) {
    if (!type) return "";

    const contract =
        contractFor(type, billingCycle.value) ??
        contractFor(
            type,
            billingCycle.value === "monthly" ? "yearly" : "monthly",
        );

    if (!contract) return "N/A";

    return formatCurrency(contract.price);
}

function roomsFor(type: string): ApiRoom[] {
    const contract =
        contractFor(type, billingCycle.value) ??
        contractFor(
            type,
            billingCycle.value === "monthly" ? "yearly" : "monthly",
        );

    return contract?.rooms ?? [];
}

const filteredRooms = computed(() => {
    if (!activeType.value) return [];

    return roomsFor(activeType.value);
});

function isAvailable(bed: ApiBed) {
    return bed.status.toLowerCase() === "available";
}

function availableBeds(room: ApiRoom) {
    if (!room.beds?.length) {
        return [];
    }

    return room.beds.filter((bed) => bed.status?.toLowerCase() === "available");
}

function reservedBeds(room: ApiRoom) {
    return room.beds.filter((bed) => bed.status.toLowerCase() === "reserved");
}

function statusBadgeClass(status: string) {
    switch (status.toLowerCase()) {
        case "available":
            return "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300";

        case "occupied":
            return "bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300";

        case "maintenance":
            return "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300";

        default:
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
    }
}

function selectType(type: string) {
    if (activeType.value !== type) {
        selectedRoom.value = null;
        selectedBed.value = null;
    }

    activeType.value = type;
    viewMode.value = "rooms";
}

function openBeds(room: ApiRoom) {
    modalRoom.value = room;
    showBeds.value = true;
}

function emitModel(contract: RoomContract) {
    if (!selectedRoom.value || !selectedBed.value) return;

    const payload: Reserved = {
        room: selectedRoom.value,
        bed: selectedBed.value,
        contract_id: contract.contract_id,
        billing_cycle: billingCycle.value,
        price: contract.price,
        accommodation_type: normalizeAccommodationType(
            contract.accommodation_type,
        ),
        admitted_at: admittedAt.value,
    };

    emit("update:model", payload);
}

function chooseBed(bed: ApiBed) {
    if (!isAvailable(bed) || !modalRoom.value || !activeType.value) {
        return;
    }

    const contract =
        contractFor(activeType.value, billingCycle.value) ??
        contractFor(
            activeType.value,
            billingCycle.value === "monthly" ? "yearly" : "monthly",
        );

    if (!contract) return;

    selectedRoom.value = modalRoom.value;
    selectedBed.value = bed;

    emitModel(contract);

    showBeds.value = false;
}

watch(billingCycle, () => {
    if (!activeType.value) return;

    const rooms = roomsFor(activeType.value);

    if (
        selectedRoom.value &&
        !rooms.some((room) => room.room_id === selectedRoom.value?.room_id)
    ) {
        selectedRoom.value = null;
        selectedBed.value = null;
        return;
    }

    if (
        selectedRoom.value &&
        selectedBed.value &&
        !availableBeds(selectedRoom.value).some(
            (bed) => bed.bed_id === selectedBed.value?.bed_id,
        )
    ) {
        selectedRoom.value = null;
        selectedBed.value = null;
        return;
    }

    const contract =
        contractFor(activeType.value, billingCycle.value) ??
        contractFor(
            activeType.value,
            billingCycle.value === "monthly" ? "yearly" : "monthly",
        );

    if (contract) {
        emitModel(contract);
    }
});

watch(admittedAt, () => {
    if (!selectedRoom.value || !selectedBed.value || !activeType.value) {
        return;
    }

    const contract =
        contractFor(activeType.value, billingCycle.value) ??
        contractFor(
            activeType.value,
            billingCycle.value === "monthly" ? "yearly" : "monthly",
        );

    if (contract) {
        emitModel(contract);
    }
});

function handleDone() {
    if (!selectedRoom.value || !selectedBed.value || !activeType.value) {
        return;
    }

    const contract =
        contractFor(activeType.value, billingCycle.value) ??
        contractFor(
            activeType.value,
            billingCycle.value === "monthly" ? "yearly" : "monthly",
        );

    if (!contract) return;

    const payload: Reserved = {
        room: selectedRoom.value,
        bed: selectedBed.value,
        contract_id: contract.contract_id,
        billing_cycle: billingCycle.value,
        price: contract.price,
        accommodation_type: normalizeAccommodationType(
            contract.accommodation_type,
        ),
        admitted_at: admittedAt.value,
    };

    emit("confirm", payload);
    emit("close");
}

const todayStr = toLocalDateString(new Date());

const maxDateStr = toLocalDateString(new Date(Date.now() + 7 * 86400000));

function normalizeAccommodationType(type: string): "Common" | "VIP" {
    return type?.toUpperCase() === "VIP" ? "VIP" : "Common";
}
</script>
