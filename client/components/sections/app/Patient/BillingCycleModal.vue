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
                    class="w-full max-w-2xl max-h-[88vh] overflow-y-auto rounded-2xl bg-white p-6 md:p-7 shadow-xl"
                >
                    <h3 class="text-lg font-semibold">Extend Stay</h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Choose a billing cycle, or switch accommodation if
                        needed.
                    </p>

                    <div
                        v-if="admission?.end_date"
                        class="mt-4 rounded-xl bg-slate-50 p-3"
                    >
                        <p class="text-xs text-slate-500">
                            Current discharge date
                        </p>
                        <p class="font-semibold">
                            {{ formatDate(admission.end_date) }}
                        </p>
                    </div>

                    <div v-if="loading" class="mt-6 grid sm:grid-cols-2 gap-4">
                        <div
                            v-for="i in 2"
                            :key="i"
                            class="animate-pulse rounded-xl border p-4 space-y-3"
                        >
                            <div class="h-4 w-28 rounded bg-slate-200" />
                            <div class="h-3 w-40 rounded bg-slate-100" />
                        </div>
                    </div>

                    <div
                        v-else-if="!accommodationTypes.length"
                        class="mt-6 rounded-xl border border-dashed py-10 text-center text-sm text-slate-500"
                    >
                        No billing contracts are currently available.
                    </div>

                    <template v-else>
                        <div
                            v-if="hasYearlyOption"
                            class="mt-5 flex items-center justify-center gap-3"
                        >
                            <span
                                class="text-sm font-medium transition-colors"
                                :class="
                                    billingCycle === 'monthly'
                                        ? 'text-slate-900'
                                        : 'text-slate-400'
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
                                        : 'bg-slate-200'
                                "
                            >
                                <span
                                    class="absolute top-0.5 left-0.5 h-6 w-6 rounded-full bg-white shadow transition-transform duration-200"
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
                                        ? 'text-slate-900'
                                        : 'text-slate-400'
                                "
                            >
                                Yearly
                                <span
                                    v-if="activeDiscountPercent !== null"
                                    class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700"
                                >
                                    Save {{ activeDiscountPercent }}%
                                </span>
                            </span>
                        </div>

                        <div class="mt-6 grid sm:grid-cols-2 gap-4">
                            <button
                                v-for="type in accommodationTypes"
                                :key="type.value"
                                type="button"
                                :disabled="
                                    !contractFor(type.value, 'monthly') &&
                                    !contractFor(type.value, 'yearly')
                                "
                                class="text-left rounded-xl border p-4 transition hover:border-primary hover:bg-primary/5 disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="
                                    selectedType === type.value
                                        ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                                        : ''
                                "
                                @click="selectedType = type.value"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="font-semibold capitalize">
                                        {{ type.value.toLowerCase() }}
                                    </p>
                                    <span
                                        v-if="type.value === currentType"
                                        class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600"
                                    >
                                        Current
                                    </span>
                                </div>
                                <p
                                    class="text-sm text-primary font-medium mt-1"
                                >
                                    {{ formatPrice(type.value) }}
                                    <span
                                        class="text-xs font-normal text-slate-400"
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

                        <div
                            v-if="!typeChanged"
                            class="mt-5 rounded-xl border p-4 flex items-center justify-between"
                        >
                            <div>
                                <p class="text-xs text-slate-500">Room & Bed</p>
                                <p class="font-semibold">
                                    Room {{ admission?.room?.room_no ?? "—" }}
                                    <span
                                        v-if="admission?.bed?.bed_no"
                                        class="text-slate-400 font-normal"
                                    >
                                        · Bed {{ admission.bed.bed_no }}
                                    </span>
                                </p>
                            </div>
                            <span
                                class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700"
                            >
                                Unchanged
                            </span>
                        </div>

                        <!-- Room/bed: pick new when switching accommodation type -->
                        <div v-else class="mt-5 space-y-4">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-muted"
                            >
                                Select a room for
                                {{ selectedType?.toLowerCase() }}
                            </p>

                            <div
                                v-if="!roomsForSelection.length"
                                class="rounded-xl border border-dashed py-8 text-center text-sm text-slate-500"
                            >
                                No rooms currently available for this type.
                            </div>

                            <div v-else class="grid sm:grid-cols-2 gap-4">
                                <div
                                    v-for="room in roomsForSelection"
                                    :key="room.room_id"
                                    class="rounded-xl border p-4 transition"
                                    :class="
                                        selectedRoom?.room_id === room.room_id
                                            ? 'border-primary ring-1 ring-primary/30'
                                            : ''
                                    "
                                >
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="font-semibold">
                                                Room {{ room.room_no }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{ room.floor }} Floor
                                            </p>
                                        </div>
                                        <span
                                            class="text-xs h-fit rounded-full px-2.5 py-1 font-medium"
                                            :class="
                                                availableBeds(room).length > 0
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : 'bg-rose-50 text-rose-700'
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
                                        class="mt-4 w-full rounded-lg bg-primary text-white py-2 text-sm font-medium transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
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
                        </div>

                        <!-- Summary -->
                        <div
                            v-if="selectedContract"
                            class="mt-5 rounded-xl bg-primary/5 border border-primary/20 p-4 text-sm"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Price</span>
                                <span class="font-semibold text-primary">
                                    ₱{{
                                        Number(
                                            selectedContract.price,
                                        ).toLocaleString()
                                    }}
                                    <span
                                        class="text-xs font-normal text-slate-400"
                                    >
                                        /
                                        {{
                                            billingCycle === "yearly"
                                                ? "yr"
                                                : "mo"
                                        }}
                                    </span>
                                </span>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-slate-500"
                                    >New discharge date</span
                                >
                                <span class="font-semibold">{{
                                    formatDate(calculatedDischargeDate)
                                }}</span>
                            </div>
                        </div>
                    </template>

                    <button
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white disabled:opacity-50"
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
                        {{ submitting ? "Extending..." : "Confirm" }}
                    </button>
                    <button
                        class="mt-3 w-full rounded-xl border py-2.5 text-sm"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                </div>

                <!-- Bed picker sub-modal -->
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
                            class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl"
                        >
                            <div class="flex justify-between items-center mb-5">
                                <div>
                                    <h3 class="font-semibold text-lg">
                                        Select Bed
                                    </h3>
                                    <p class="text-sm text-slate-500">
                                        Room {{ modalRoom?.room_no }}
                                    </p>
                                </div>
                                <button
                                    class="h-8 w-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-700"
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
                                    class="w-full rounded-xl border p-4 flex justify-between items-center transition"
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
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-rose-50 text-rose-700'
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
const selectedType = ref<string | null>(null);

const selectedRoom = ref<Room | null>(null);
const selectedBed = ref<Bed | null>(null);
const modalRoom = ref<Room | null>(null);
const showBeds = ref(false);

function normalizeType(type?: string | null) {
    return (type ?? "").trim().toUpperCase();
}

const currentType = computed(() =>
    normalizeType(props.admission?.current_contract?.accommodation_type),
);

const typeChanged = computed(
    () => !!selectedType.value && selectedType.value !== currentType.value,
);

const accommodationTypes = computed(() => {
    const seen = new Set<string>();
    const list: { value: string }[] = [];
    for (const c of contracts.value) {
        const t = normalizeType(c.accommodation_type);
        if (seen.has(t)) continue;
        seen.add(t);
        list.push({ value: t });
    }
    return list;
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
    return `₱${Number(contract.price).toLocaleString("en-PH")}`;
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
    if (typeChanged.value) {
        return !!selectedRoom.value && !!selectedBed.value;
    }
    return true;
});

// Reset room/bed pick whenever the accommodation type selection changes,
// since a bed in the old type isn't valid for the new one.
watch(selectedType, () => {
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

    emit("select", {
        contract: selectedContract.value,
        end_date: calculatedDischargeDate.value,
        room: typeChanged.value ? (selectedRoom.value ?? undefined) : undefined,
        bed: typeChanged.value ? (selectedBed.value ?? undefined) : undefined,
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
