<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-5"
            >
                <div
                    class="flex w-full max-w-2xl max-h-[88dvh] flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                >
                    <!-- HEADER -->
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold tracking-tight">
                                Extend Stay
                            </h3>

                            <p
                                class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                            >
                                Add another billing cycle, and switch
                                accommodation at the same time if needed.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                            aria-label="Close"
                            @click="$emit('close')"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <div
                            v-if="admission?.end_date"
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-slate-200 px-4 py-3 dark:border-white/10"
                        >
                            <span
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                Discharge date
                            </span>

                            <span class="text-sm font-semibold">
                                {{ formatDate(admission.end_date) }}
                            </span>

                            <template v-if="selectedContract">
                                <svg
                                    class="h-3.5 w-3.5 shrink-0 text-slate-300 dark:text-gray-600"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M5 12h14" />
                                    <path d="m12 5 7 7-7 7" />
                                </svg>

                                <span class="text-sm font-semibold text-primary">
                                    {{ formatDate(calculatedDischargeDate) }}
                                </span>
                            </template>
                        </div>

                        <div v-if="loading" class="mt-5 space-y-4">
                            <div
                                class="h-10 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                            />
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div
                                    v-for="i in 2"
                                    :key="i"
                                    class="animate-pulse space-y-3 rounded-xl border p-4 dark:border-white/10"
                                >
                                    <div
                                        class="h-4 w-28 rounded bg-slate-200 dark:bg-white/15"
                                    />
                                    <div
                                        class="h-3 w-40 rounded bg-slate-100 dark:bg-white/10"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="!accommodationTypes.length"
                            class="mt-5 rounded-xl border border-dashed py-10 text-center text-sm text-slate-500 dark:border-white/10 dark:text-gray-400"
                        >
                            No billing contracts are currently available.
                        </div>

                        <template v-else>
                            <!-- 1 · BILLING CYCLE -->
                            <section v-if="hasYearlyOption" class="mt-6">
                                <h4
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    1 · Billing cycle
                                </h4>

                                <div
                                    class="mt-2.5 grid grid-cols-2 gap-1 rounded-xl border border-slate-200 p-1 dark:border-white/10"
                                >
                                    <button
                                        v-for="cycle in cycleOptions"
                                        :key="cycle"
                                        type="button"
                                        class="rounded-lg px-3 py-2 text-sm font-medium capitalize transition"
                                        :class="
                                            billingCycle === cycle
                                                ? 'bg-primary text-white shadow-sm'
                                                : 'text-slate-500 hover:bg-slate-50 dark:text-gray-400 dark:hover:bg-white/5'
                                        "
                                        @click="billingCycle = cycle"
                                    >
                                        {{ cycle }}

                                        <span
                                            v-if="
                                                cycle === 'yearly' &&
                                                activeDiscountPercent !== null
                                            "
                                            class="ml-1.5 rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                            :class="
                                                billingCycle === 'yearly'
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                            "
                                        >
                                            Save {{ activeDiscountPercent }}%
                                        </span>
                                    </button>
                                </div>
                            </section>

                            <!-- 2 · ACCOMMODATION -->
                            <section class="mt-6">
                                <h4
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    {{ hasYearlyOption ? "2 · " : "" }}Accommodation
                                </h4>

                                <div
                                    class="mt-2.5 grid gap-3"
                                    :class="
                                        accommodationTypes.length > 1
                                            ? 'sm:grid-cols-2'
                                            : ''
                                    "
                                >
                                    <button
                                        v-for="type in accommodationTypes"
                                        :key="type.value"
                                        type="button"
                                        :disabled="
                                            !contractFor(type.value, 'monthly') &&
                                            !contractFor(type.value, 'yearly')
                                        "
                                        class="rounded-xl border p-4 text-left transition hover:border-primary/60 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10"
                                        :class="
                                            selectedType === type.value
                                                ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                                                : ''
                                        "
                                        @click="selectedType = type.value"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <p class="font-semibold uppercase">
                                                {{ type.value.toLowerCase() }}
                                            </p>

                                            <span
                                                v-if="type.value === currentType"
                                                class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-white/10 dark:text-gray-400"
                                            >
                                                Current
                                            </span>
                                        </div>

                                        <p class="mt-1 text-sm font-semibold text-primary">
                                            {{ formatPrice(type.value) }}
                                            <span
                                                class="text-xs font-normal text-slate-400 dark:text-gray-500"
                                            >
                                                /
                                                {{
                                                    billingCycle === "yearly"
                                                        ? "yr"
                                                        : "mo"
                                                }}
                                            </span>
                                        </p>
                                    </button>
                                </div>
                            </section>

                            <!-- 3 · ROOM & BED -->
                            <section class="mt-6">
                                <h4
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    {{ hasYearlyOption ? "3 · " : "2 · " }}Room &amp; bed
                                </h4>

                                <div
                                    class="mt-2.5 rounded-xl border p-4 dark:border-white/10"
                                >
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p class="font-semibold">
                                                Room
                                                {{ admission?.room?.room_no ?? "—" }}
                                                <span
                                                    v-if="admission?.bed?.bed_no"
                                                    class="font-normal text-slate-400 dark:text-gray-500"
                                                >
                                                    · Bed {{ admission.bed.bed_no }}
                                                </span>
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                                            >
                                                {{
                                                    typeChanged && !keepSameRoomBed
                                                        ? `Pick a room for ${selectedType?.toLowerCase()}.`
                                                        : "The patient keeps this room and bed."
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            v-if="typeChanged"
                                            class="flex shrink-0 rounded-lg border p-0.5 dark:border-white/10"
                                        >
                                            <button
                                                type="button"
                                                class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                                                :class="
                                                    keepSameRoomBed
                                                        ? 'bg-primary text-white'
                                                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400'
                                                "
                                                @click="keepSameRoomBed = true"
                                            >
                                                Keep same
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                                                :class="
                                                    !keepSameRoomBed
                                                        ? 'bg-primary text-white'
                                                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400'
                                                "
                                                @click="keepSameRoomBed = false"
                                            >
                                                Change room
                                            </button>
                                        </div>

                                        <span
                                            v-else
                                            class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                        >
                                            Unchanged
                                        </span>
                                    </div>
                                </div>

                                <template v-if="typeChanged && !keepSameRoomBed">
                                    <div
                                        v-if="!roomsForSelection.length"
                                        class="mt-3 rounded-xl border border-dashed py-8 text-center text-sm text-slate-500 dark:border-white/10 dark:text-gray-400"
                                    >
                                        No rooms currently available for this type.
                                    </div>

                                    <div
                                        v-else
                                        class="mt-3 grid gap-3 sm:grid-cols-2"
                                    >
                                        <div
                                            v-for="room in roomsForSelection"
                                            :key="room.room_id"
                                            class="rounded-xl border p-4 transition dark:border-white/10"
                                            :class="
                                                selectedRoom?.room_id ===
                                                room.room_id
                                                    ? 'border-primary ring-1 ring-primary/30'
                                                    : ''
                                            "
                                        >
                                            <div
                                                class="flex items-start justify-between gap-2"
                                            >
                                                <div class="min-w-0">
                                                    <p class="font-semibold">
                                                        Room {{ room.room_no }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-slate-500 dark:text-gray-400"
                                                    >
                                                        {{ room.floor }} Floor
                                                    </p>
                                                </div>

                                                <span
                                                    class="h-fit shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                                                    :class="
                                                        availableBeds(room).length > 0
                                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
                                                    "
                                                >
                                                    {{
                                                        availableBeds(room).length > 0
                                                            ? "Available"
                                                            : "Fully Reserved"
                                                    }}
                                                </span>
                                            </div>

                                            <button
                                                type="button"
                                                :disabled="
                                                    availableBeds(room).length === 0
                                                "
                                                class="mt-4 w-full rounded-lg bg-primary py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                                                @click="openBeds(room)"
                                            >
                                                {{
                                                    selectedRoom?.room_id ===
                                                        room.room_id && selectedBed
                                                        ? `Bed ${selectedBed.bed_no} selected`
                                                        : "Select Bed"
                                                }}
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </section>
                        </template>
                    </div>

                    <!-- FOOTER -->
                    <div
                        class="border-t border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div
                            v-if="selectedContract"
                            class="mb-3 flex flex-wrap items-end justify-between gap-x-4 gap-y-1"
                        >
                            <div class="min-w-0">
                                <p
                                    class="text-xs text-slate-500 dark:text-gray-400"
                                >
                                    Extends to
                                    {{ formatDate(calculatedDischargeDate) }}
                                </p>
                                <p class="text-lg font-bold tracking-tight">
                                    ₱{{
                                        Number(
                                            selectedContract.price,
                                        ).toLocaleString()
                                    }}
                                    <span
                                        class="text-xs font-normal text-slate-400 dark:text-gray-500"
                                    >
                                        /
                                        {{
                                            billingCycle === "yearly"
                                                ? "yr"
                                                : "mo"
                                        }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                class="flex flex-[2] items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!canConfirm"
                                @click="confirmSelection"
                            >
                                <svg
                                    v-if="submitting"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>
                                {{ submitting ? "Extending..." : "Confirm Extension" }}
                            </button>
                        </div>
                    </div>
                </div>

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
                        class="fixed inset-0 z-[80] bg-black/40 flex items-center justify-center p-5"
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
                                    class="h-8 w-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
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
                                    :disabled="
                                        bed.status.toLowerCase() !== 'available'
                                    "
                                    class="w-full rounded-xl border p-4 flex justify-between items-center transition dark:border-white/10"
                                    :class="
                                        bed.status.toLowerCase() === 'available'
                                            ? 'hover:border-primary hover:bg-primary/5'
                                            : 'opacity-50 cursor-not-allowed'
                                    "
                                    @click="chooseBed(bed)"
                                >
                                    <span class="font-medium"
                                        >Bed {{ bed.bed_no }}</span
                                    >
                                    <span
                                        class="text-xs rounded-full px-3 py-1 font-medium"
                                        :class="
                                            bed.status.toLowerCase() ===
                                            'available'
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
                                        "
                                    >
                                        {{ bed.status }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { useRoute } from "vue-router";

import { admissionService } from "~/api/admission/AdmissionService";
import type { RoomContract } from "~/types/contract";
import type { Admission } from "~/types/patient";
import type { Room } from "~/types/room";
import { formatCurrency } from "~/utils/currency";
import type { Bed } from "~/types/bed";

const route = useRoute();

const props = defineProps<{
    open: boolean;
    admission: Admission | null;
}>();

const emit = defineEmits<{
    (
        e: "select",
        payload: {
            contract: RoomContract;
            end_date: string;
            room?: Room;
            bed?: Bed;
        },
    ): void;
    (e: "close"): void;
}>();

const contracts = ref<RoomContract[]>([]);
const loading = ref(false);
const submitting = ref(false);

const billingCycle = ref<"monthly" | "yearly">("monthly");

const cycleOptions = ["monthly", "yearly"] as const;
const selectedType = ref<string | null>(null);

const selectedRoom = ref<Room | null>(null);
const selectedBed = ref<Bed | null>(null);
const modalRoom = ref<Room | null>(null);
const showBeds = ref(false);

const keepSameRoomBed = ref(true);

function normalizeType(type?: string | null) {
    return (type ?? "").trim().toUpperCase();
}

const currentType = computed(() =>
    normalizeType(props.admission?.current_contract?.accommodation_type),
);

const typeChanged = computed(
    () => !!selectedType.value && selectedType.value !== currentType.value,
);

// const accommodationTypes = computed(() => {
//     const seen = new Set<string>();
//     const list: { value: string }[] = [];
//     for (const c of contracts.value) {
//         const t = normalizeType(c.accommodation_type);
//         if (seen.has(t)) continue;
//         seen.add(t);
//         list.push({ value: t });
//     }
//     return list;
// });

const accommodationTypes = computed(() => {
    if (!currentType.value) return [];

    const currentContracts = contracts.value.filter(
        (contract) =>
            normalizeType(contract.accommodation_type) === currentType.value,
    );

    return currentContracts.length ? [{ value: currentType.value }] : [];
});
function contractFor(type: string | null, interval: "monthly" | "yearly") {
    if (!type) return undefined;
    return contracts.value.find(
        (c) =>
            normalizeType(c.accommodation_type) === type &&
            c.billing_cycle.toLowerCase() === interval,
    );
}

const hasYearlyOption = computed(
    () => !!contractFor(selectedType.value, "yearly"),
);

const selectedContract = computed<RoomContract | null>(
    () =>
        contractFor(selectedType.value, billingCycle.value) ??
        contractFor(
            selectedType.value,
            billingCycle.value === "monthly" ? "yearly" : "monthly",
        ) ??
        null,
);

const activeDiscountPercent = computed(() => {
    const monthly = contractFor(selectedType.value, "monthly");
    const yearly = contractFor(selectedType.value, "yearly");
    if (!monthly || !yearly) return null;

    const monthlyAnnual = Number(monthly.price) * 12;
    const yearlyPrice = Number(yearly.price);
    if (!monthlyAnnual) return null;

    const percent = Math.round((1 - yearlyPrice / monthlyAnnual) * 100);
    return percent > 0 ? percent : null;
});

function formatPrice(type: string) {
    const contract =
        contractFor(type, billingCycle.value) ??
        contractFor(
            type,
            billingCycle.value === "monthly" ? "yearly" : "monthly",
        );
    if (!contract) return "N/A";
    return formatCurrency(contract.price);
}

const roomsForSelection = computed<Room[]>(
    () => selectedContract.value?.rooms ?? [],
);

function availableBeds(room: Room) {
    if (!room.beds?.length) return [];
    return room.beds.filter((b) => b.status?.toLowerCase() === "available");
}

function openBeds(room: Room) {
    modalRoom.value = room;
    showBeds.value = true;
}

function chooseBed(bed: Bed) {
    if (bed.status.toLowerCase() !== "available" || !modalRoom.value) return;
    selectedRoom.value = modalRoom.value;
    selectedBed.value = bed;
    showBeds.value = false;
}

const calculatedDischargeDate = computed<string | null>(() => {
    if (!selectedContract.value || !props.admission?.end_date) return null;

    const date = new Date(props.admission.end_date);

    switch (selectedContract.value.billing_cycle.toLowerCase()) {
        case "monthly":
            date.setMonth(date.getMonth() + 1);
            break;
        case "quarterly":
            date.setMonth(date.getMonth() + 3);
            break;
        case "semi annual":
        case "semi-annually":
        case "semiannual":
            date.setMonth(date.getMonth() + 6);
            break;
        case "annual":
        case "yearly":
            date.setFullYear(date.getFullYear() + 1);
            break;
    }

    return date.toISOString().split("T")[0] ?? null;
});

const canConfirm = computed(() => {
    if (
        submitting.value ||
        !selectedContract.value ||
        !calculatedDischargeDate.value
    ) {
        return false;
    }
    if (typeChanged.value && !keepSameRoomBed.value) {
        return !!selectedRoom.value && !!selectedBed.value;
    }
    return true;
});

watch(selectedType, () => {
    keepSameRoomBed.value = true;
    selectedRoom.value = null;
    selectedBed.value = null;
});

watch(
    [() => props.open, () => props.admission],
    async ([open, admission]) => {
        if (!open || !admission) return;

        selectedType.value = normalizeType(
            admission.current_contract?.accommodation_type,
        );
        billingCycle.value =
            admission.current_contract?.billing_cycle?.toLowerCase() ===
            "yearly"
                ? "yearly"
                : "monthly";
        selectedRoom.value = null;
        selectedBed.value = null;
        keepSameRoomBed.value = true;

        loading.value = true;

        try {
            const res = await admissionService.action({
                branch_uuid: route.params.uuid,
                action: "branch_contract",
            });

            contracts.value = res.data?.data ?? res.data ?? res ?? [];
        } catch (err) {
            console.error("Failed loading contracts", err);
            contracts.value = [];
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);

function confirmSelection() {
    if (
        !canConfirm.value ||
        !selectedContract.value ||
        !calculatedDischargeDate.value
    ) {
        return;
    }
    submitting.value = true;

    const useNewRoomBed = typeChanged.value && !keepSameRoomBed.value;

    emit("select", {
        contract: selectedContract.value,
        end_date: calculatedDischargeDate.value,
        room: useNewRoomBed ? (selectedRoom.value ?? undefined) : undefined,
        bed: useNewRoomBed ? (selectedBed.value ?? undefined) : undefined,
    });
}

function formatDate(date: string | Date | null) {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

watch(
    () => props.open,
    (value) => {
        if (!value) submitting.value = false;
    },
);
</script>
