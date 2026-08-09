<template>
    <section
        class="rounded-2xl bg-white p-6 md:p-8"
        :class="{ 'animate-pulse': loading }"
    >
        <div
            class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-5"
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
                            Select your admission type and schedule.
                        </p>
                    </div>
                </template>
            </div>

            <div v-if="!loading" class="shrink-0">
                <span
                    v-if="maxAvailableSlots > 0"
                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                    ></span>
                    {{ maxAvailableSlots }} slot{{
                        maxAvailableSlots === 1 ? "" : "s"
                    }}
                    available
                </span>
                <span
                    v-else
                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    No slots available
                </span>
            </div>
        </div>

        <div class="space-y-6">
            <div>
                <div v-if="loading" class="grid md:grid-cols-2 gap-3">
                    <div
                        v-for="i in 2"
                        :key="i"
                        class="rounded-xl border p-4 animate-pulse"
                    >
                        <div class="flex items-center justify-between">
                            <div class="space-y-2">
                                <div
                                    class="h-4 w-28 bg-slate-200 rounded"
                                ></div>
                                <div
                                    class="h-3 w-40 bg-slate-100 rounded"
                                ></div>
                            </div>
                            <div class="h-9 w-9 rounded-lg bg-slate-200"></div>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <h3 class="font-semibold text-sm text-slate-900 mb-3">
                        Admission Type <span class="text-danger">*</span>
                    </h3>

                    <div class="grid md:grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="update('type', 'Pre-Admission')"
                            class="text-left rounded-xl border p-4 transition hover:border-primary hover:bg-primary/5 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            :class="
                                model.type === 'Pre-Admission'
                                    ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                                    : ''
                            "
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-sm">
                                        Pre-Admission
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Submit requirements only.
                                    </p>
                                </div>

                                <div
                                    class="h-9 w-9 shrink-0 rounded-lg bg-primary/10 flex items-center justify-center text-primary"
                                >
                                    <ClipboardCheck class="h-4 w-4" />
                                </div>
                            </div>

                            <p
                                v-if="model.type === 'Pre-Admission'"
                                class="mt-3 text-[11px] font-medium text-primary"
                            >
                                Currently selected
                            </p>
                        </button>

                        <button
                            type="button"
                            @click="update('type', 'Complete')"
                            class="text-left rounded-xl border p-4 transition hover:border-primary hover:bg-primary/5 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            :class="
                                model.type === 'Complete'
                                    ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                                    : ''
                            "
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-sm">
                                        Complete Admission
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Select room, plan, and complete payment
                                        in one step
                                    </p>
                                </div>

                                <div
                                    class="h-9 w-9 shrink-0 rounded-lg bg-primary/10 flex items-center justify-center text-primary"
                                >
                                    <Building2 class="h-4 w-4" />
                                </div>
                            </div>

                            <p
                                v-if="model.type === 'Complete'"
                                class="mt-3 text-[11px] font-medium text-primary"
                            >
                                Currently selected
                            </p>
                        </button>
                    </div>

                    <p v-if="errors?.type" class="text-xs text-red-500 mt-2">
                        {{ errors.type }}
                    </p>
                </template>
            </div>

            <!-- Accommodation + Plan (only for Complete) -->
            <div v-if="loading || model.type === 'Complete'" class="space-y-6">
                <div>
                    <div v-if="loading" class="grid md:grid-cols-2 gap-3">
                        <div
                            v-for="i in 2"
                            :key="i"
                            class="rounded-xl border p-4 animate-pulse"
                        >
                            <div class="flex items-center justify-between">
                                <div class="space-y-2">
                                    <div
                                        class="h-4 w-28 bg-slate-200 rounded"
                                    ></div>
                                    <div
                                        class="h-3 w-40 bg-slate-100 rounded"
                                    ></div>
                                </div>
                                <div
                                    class="h-9 w-9 rounded-lg bg-slate-200"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <template v-else>
                        <h3 class="font-semibold text-sm text-slate-900 mb-3">
                            Accommodation Type
                            <span class="text-danger">*</span>
                        </h3>

                        <div class="grid md:grid-cols-2 gap-3">
                            <button
                                v-for="room in roomTypes"
                                :key="room.value"
                                type="button"
                                :disabled="room.slots === 0"
                                @click="update('plan', room.value)"
                                class="text-left rounded-xl border p-4 transition hover:border-primary hover:bg-primary/5 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:border-inherit disabled:hover:bg-transparent disabled:hover:shadow-none"
                                :class="
                                    model.plan === room.value
                                        ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                                        : ''
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-sm">
                                            {{ room.title }}
                                        </h3>
                                        <p
                                            class="text-xs text-slate-500 mt-0.5"
                                        >
                                            {{ room.description }}
                                        </p>
                                    </div>

                                    <img
                                        v-if="room.image"
                                        :src="room.image"
                                        :alt="room.title"
                                        class="h-16 w-16 shrink-0 rounded-lg object-cover"
                                    />
                                    <div
                                        v-else
                                        class="h-9 w-9 shrink-0 rounded-lg bg-primary/10 flex items-center justify-center text-primary"
                                    >
                                        <component
                                            :is="room.icon"
                                            class="h-7 w-7"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex items-end justify-between"
                                >
                                    <span
                                        class="text-xs"
                                        :class="
                                            room.slots === 0
                                                ? 'text-rose-500 font-medium'
                                                : 'text-slate-500'
                                        "
                                    >
                                        {{
                                            room.slots > 0
                                                ? `${room.slots} slot${room.slots === 1 ? "" : "s"} available`
                                                : "Fully booked"
                                        }}
                                    </span>

                                    <p
                                        v-if="model.plan === room.value"
                                        class="text-[11px] font-medium text-primary"
                                    >
                                        Currently selected
                                    </p>
                                </div>
                            </button>
                        </div>
                        <p
                            v-if="errors?.plan"
                            class="text-xs text-red-500 mt-2"
                        >
                            {{ errors.plan }}
                        </p>
                    </template>
                </div>

                <div>
                    <div v-if="loading" class="grid md:grid-cols-2 gap-3">
                        <div
                            v-for="i in 2"
                            :key="i"
                            class="rounded-xl border p-4 animate-pulse"
                        >
                            <div class="flex items-center justify-between">
                                <div class="space-y-2">
                                    <div
                                        class="h-4 w-28 bg-slate-200 rounded"
                                    ></div>
                                    <div
                                        class="h-3 w-40 bg-slate-100 rounded"
                                    ></div>
                                </div>
                                <div
                                    class="h-9 w-9 rounded-lg bg-slate-200"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <template v-else>
                        <h3 class="font-semibold text-sm text-slate-900 mb-3">
                            Admission Plan <span class="text-danger">*</span>
                        </h3>

                        <div class="grid md:grid-cols-2 gap-3">
                            <button
                                v-for="plan in availablePlans"
                                :key="plan.value"
                                type="button"
                                @click="update('billing_cycle', plan.value)"
                                class="text-left rounded-xl border p-4 transition hover:border-primary hover:bg-primary/5 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                :class="
                                    model.billing_cycle === plan.value
                                        ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                                        : ''
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-sm">
                                            {{ plan.title }}
                                        </h3>
                                        <p
                                            class="text-xs text-slate-500 mt-0.5"
                                        >
                                            {{ plan.description }}
                                        </p>
                                    </div>

                                    <div
                                        class="h-9 w-9 shrink-0 rounded-lg bg-primary/10 flex items-center justify-center text-primary"
                                    >
                                        <component
                                            :is="plan.icon"
                                            class="h-5 w-5"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex items-end justify-between"
                                >
                                    <span
                                        class="text-sm font-semibold text-primary"
                                    >
                                        ₱{{ getPrice(plan.value) }}
                                    </span>

                                    <p
                                        v-if="
                                            model.billing_cycle === plan.value
                                        "
                                        class="text-[11px] font-medium text-primary"
                                    >
                                        Currently selected
                                    </p>
                                </div>
                            </button>
                        </div>

                        <p
                            v-if="!availablePlans.length"
                            class="text-xs text-amber-600 mt-2"
                        >
                            No billing plans are configured for this room type
                            yet.
                        </p>

                        <p
                            v-if="errors?.billing_cycle"
                            class="text-xs text-red-500 mt-2"
                        >
                            {{ errors.billing_cycle }}
                        </p>
                    </template>
                </div>
            </div>

            <div class="h-px bg-slate-200" />

            <div v-if="model.type === 'Complete' && !loading" class="max-w-xs">
                <BaseInput
                    label="Admission Date"
                    :model-value="model.admission_date"
                    @update:model-value="update('admission_date', $event)"
                    mode="date"
                    :min="todayStr"
                    :max="maxDateStr"
                    :error="errors?.admission_date"
                    required
                />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, watch } from "vue";
import type { Component } from "vue";
import { getLocalDateStr } from "~/utils/time";
import type { FacilityBooking } from "~/types/booking";
import type { BranchImage, BranchRetrieve } from "~/types/branch";
import BaseInput from "~/components/ui/BaseInput.vue";
import {
    Star,
    CalendarDays,
    CalendarRange,
    Users,
    ClipboardCheck,
    Building2,
    Check,
} from "lucide-vue-next";

interface RoomTypeStat {
    slots: number;
    description: string | null;
}

function getRoomImage(roomValue: "VIP" | "Common"): string | null {
    const typeMap: Record<"VIP" | "Common", BranchImage["type"]> = {
        VIP: "vip_room",
        Common: "common_room",
    };

    const match = (props.branch?.images ?? []).find(
        (img) => img.type === typeMap[roomValue],
    );

    return match?.image_url ?? null;
}

const props = defineProps<{
    model: FacilityBooking;
    errors?: Record<string, string> | null;
    branch?: BranchRetrieve | null;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:model", value: FacilityBooking): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

function update<K extends keyof FacilityBooking>(
    key: K,
    value: FacilityBooking[K],
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

const facilityList = computed(() => props.branch?.facility ?? []);

const maxAvailableSlots = computed(() => {
    const slots = facilityList.value
        .map((room) => Number(room.available_slot))
        .filter((n) => !isNaN(n));

    return slots.length ? Math.max(...slots) : 0;
});

const slotsByRoomType = computed(() => {
    const map: Record<string, RoomTypeStat> = {};

    for (const room of facilityList.value) {
        const type = (room.accommodation_type || "").toUpperCase();
        const slot = Number(room.available_slot);
        if (isNaN(slot)) continue;

        const existing = map[type];
        const isHigher = !existing || slot > existing.slots;

        map[type] = {
            slots: isHigher ? slot : existing.slots,
            description: room.description ?? existing?.description ?? null,
        };
    }

    return map;
});

const roomTypeDefs: {
    value: "VIP" | "Common";
    title: string;
    description: string;
    icon: Component;
}[] = [
    {
        value: "Common",
        title: "Common Room",
        description: "Shared ward",
        icon: Users,
    },
    {
        value: "VIP",
        title: "VIP Room",
        description: "Private premium room",
        icon: Star,
    },
];

const roomTypes = computed(() =>
    roomTypeDefs
        .filter((room) => slotsByRoomType.value[room.value.toUpperCase()])
        .map((room) => {
            const data = slotsByRoomType.value[room.value.toUpperCase()];

            return {
                ...room,
                description: data?.description || room.description,
                slots: data?.slots ?? 0,
                image: getRoomImage(room.value),
            };
        }),
);

const admissionPlans: {
    value: "Monthly" | "Yearly";
    title: string;
    description: string;
    icon: Component;
}[] = [
    {
        value: "Monthly",
        title: "Monthly",
        description: "Monthly billing cycle",
        icon: CalendarDays,
    },
    {
        value: "Yearly",
        title: "Yearly",
        description: "Annual billing cycle",
        icon: CalendarRange,
    },
];

const availablePlans = computed(() => {
    const room = props.model.plan || "Common";

    return admissionPlans.filter((plan) =>
        facilityList.value.some(
            (item) =>
                (item.accommodation_type || "").toUpperCase() ===
                    room.toUpperCase() &&
                (item.billing_cycle || "").toUpperCase() ===
                    plan.value.toUpperCase(),
        ),
    );
});

function getPrice(plan: "Monthly" | "Yearly") {
    const room = props.model.plan || "Common";

    const facility = facilityList.value.find(
        (item) =>
            (item.accommodation_type || "").toUpperCase() ===
                room.toUpperCase() &&
            (item.billing_cycle || "").toUpperCase() === plan.toUpperCase(),
    );

    const price = Number(facility?.price ?? 0);
    return isNaN(price) ? "0" : price.toLocaleString();
}

watch(
    () => props.model.type,
    (type) => {
        if (type === "Pre-Admission") {
            emit("update:model", {
                ...props.model,
                plan: "",
                billing_cycle: "",
            });
        }

        if (props.errors) {
            emit("update:errors", {});
        }
    },
);

const todayStr = getLocalDateStr(new Date());
const maxDateStr = getLocalDateStr(new Date(Date.now() + 7 * 86400000));
</script>
