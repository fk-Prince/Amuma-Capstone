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
                <div class="h-6 w-24 rounded-full bg-slate-200 shrink-0" />
            </template>
            <template v-else>
                <span class="text-2xl text-primary">01</span>
                <div class="flex-1">
                    <h2 class="text-xl text-primary">Booking Request</h2>
                    <p class="text-[13px] text-muted">
                        Select your admission type and schedule.
                    </p>
                </div>

                <span
                    v-if="maxAvailableSlots > 0"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/10"
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
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500 ring-1 ring-gray-200"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    No slots available
                </span>
            </template>
        </div>

        <div class="space-y-8">
            <div>
                <template v-if="loading">
                    <div class="h-4 w-32 rounded bg-slate-200 mb-3" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="n in 2"
                            :key="n"
                            class="border border-slate-200 rounded-xl p-4"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-10 w-10 rounded-lg bg-slate-200 shrink-0"
                                />
                                <div class="flex-1 space-y-2">
                                    <div
                                        class="h-4 w-28 rounded bg-slate-200"
                                    />
                                    <div
                                        class="h-3 w-36 rounded bg-slate-200"
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
                        Admission Type
                        <span class="text-danger">*</span>
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            @click="update('type', 'Pre-Admission')"
                            class="group relative flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                            :class="
                                model.type === 'Pre-Admission'
                                    ? 'border-primary bg-primary/5 shadow-sm'
                                    : 'border-slate-200'
                            "
                        >
                            <span
                                v-if="model.type === 'Pre-Admission'"
                                class="absolute top-3 right-3 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white"
                            >
                                <Check class="h-3 w-3" />
                            </span>

                            <div
                                class="text-primary w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 group-hover:bg-primary/10 transition shrink-0"
                            >
                                <ClipboardCheck class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="font-semibold group-hover:text-primary"
                                >
                                    Pre-Admission
                                </p>
                                <p class="text-[13px] text-muted">
                                    Submit requirements only.
                                </p>
                            </div>
                        </div>

                        <div
                            @click="update('type', 'Complete')"
                            class="group relative flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                            :class="
                                model.type === 'Complete'
                                    ? 'border-primary bg-primary/5 shadow-sm'
                                    : 'border-slate-200'
                            "
                        >
                            <span
                                v-if="model.type === 'Complete'"
                                class="absolute top-3 right-3 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white"
                            >
                                <Check class="h-3 w-3" />
                            </span>

                            <div
                                class="text-primary w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 group-hover:bg-primary/10 transition shrink-0"
                            >
                                <Building2 class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="font-semibold group-hover:text-primary"
                                >
                                    Complete Admission
                                </p>
                                <p class="text-[13px] text-muted">
                                    Select room, plan, and complete payment in
                                    one step
                                </p>
                            </div>
                        </div>
                    </div>

                    <p v-if="errors?.type" class="text-xs text-red-500 mt-2">
                        {{ errors.type }}
                    </p>
                </template>
            </div>

            <div v-if="loading || model.type === 'Complete'" class="space-y-8">
                <div>
                    <template v-if="!loading">
                        <label
                            class="text-sm font-semibold text-slate-700 mb-3 block"
                        >
                            Accommodation Type
                            <span class="text-danger">*</span>
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="room in roomTypes"
                                :key="room.value"
                                @click="update('plan', room.value)"
                                class="group relative flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                                :class="
                                    model.plan === room.value
                                        ? 'border-primary bg-primary/5 shadow-sm'
                                        : 'border-slate-200'
                                "
                            >
                                <span
                                    v-if="model.plan === room.value"
                                    class="absolute top-3 right-3 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white"
                                >
                                    <Check class="h-3 w-3" />
                                </span>

                                <div
                                    class="text-xl text-primary w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 group-hover:bg-primary/10 transition shrink-0"
                                >
                                    <component
                                        :is="room.icon"
                                        class="h-6 w-6"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="font-semibold group-hover:text-primary"
                                    >
                                        {{ room.title }}
                                    </p>
                                    <p class="text-[13px] text-muted">
                                        {{ room.description }}
                                    </p>
                                    <p
                                        class="text-xs font-medium mt-1"
                                        :class="
                                            room.slots > 0
                                                ? 'text-emerald-600'
                                                : 'text-gray-400'
                                        "
                                    >
                                        {{
                                            room.slots > 0
                                                ? `${room.slots} slot${room.slots === 1 ? "" : "s"} available`
                                                : "Fully booked"
                                        }}
                                    </p>
                                </div>
                            </div>
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
                    <template v-if="!loading">
                        <label
                            class="text-sm font-semibold text-slate-700 mb-3 block"
                        >
                            Admission Plan
                            <span class="text-danger">*</span>
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="plan in availablePlans"
                                :key="plan.value"
                                @click="update('billing_cycle', plan.value)"
                                class="group relative flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                                :class="
                                    model.billing_cycle === plan.value
                                        ? 'border-primary bg-primary/5 shadow-sm'
                                        : 'border-slate-200'
                                "
                            >
                                <span
                                    v-if="model.billing_cycle === plan.value"
                                    class="absolute top-3 right-3 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white"
                                >
                                    <Check class="h-3 w-3" />
                                </span>

                                <div
                                    class="text-xl text-primary w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 group-hover:bg-primary/10 transition shrink-0"
                                >
                                    <component
                                        :is="plan.icon"
                                        class="h-6 w-6"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="font-semibold group-hover:text-primary"
                                    >
                                        {{ plan.title }}
                                    </p>
                                    <p class="text-[13px] text-muted">
                                        {{ plan.description }}
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-primary mt-1"
                                    >
                                        ₱{{ getPrice(plan.value) }}
                                    </p>
                                </div>
                            </div>
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

            <div class="h-px bg-[#E4E0D6]" />

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
import type { BranchRetrieve } from "~/types/branch";
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
    roomTypeDefs.map((room) => {
        const data: RoomTypeStat | undefined =
            slotsByRoomType.value[room.value.toUpperCase()];

        return {
            ...room,
            description: data?.description || room.description,
            slots: data?.slots ?? 0,
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
