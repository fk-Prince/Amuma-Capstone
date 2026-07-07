<template>
    <section class="rounded-2xl p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">01</span>
            <div>
                <h2 class="text-xl text-primary">Booking Request</h2>
                <p class="text-[13px] text-muted">
                    Select your admission type and schedule.
                </p>
            </div>
        </div>

        <div class="space-y-8">
            <div>
                <label class="text-sm font-semibold text-slate-700 mb-3 block">
                    Admission Type
                    <span class="text-danger">*</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        @click="update('type', 'Pre-Admission')"
                        class="group flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                        :class="
                            model.type === 'Pre-Admission'
                                ? 'border-primary bg-primary/5 shadow-sm'
                                : 'border-slate-200'
                        "
                    >
                        <div
                            class="text-xl w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 group-hover:bg-primary/10 transition"
                        >
                            📝
                        </div>
                        <div>
                            <p class="font-semibold group-hover:text-primary">
                                Pre-Admission
                            </p>
                            <p class="text-[13px] text-muted">
                                Submit requirements only.
                            </p>
                        </div>
                    </div>

                    <div
                        @click="update('type', 'Complete')"
                        class="group flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                        :class="
                            model.type === 'Complete'
                                ? 'border-primary bg-primary/5 shadow-sm'
                                : 'border-slate-200'
                        "
                    >
                        <div
                            class="text-xl w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 group-hover:bg-primary/10 transition"
                        >
                            🏥
                        </div>
                        <div>
                            <p class="font-semibold group-hover:text-primary">
                                Complete Admission
                            </p>
                            <p class="text-[13px] text-muted">
                                Select room, plan, and complete payment in one
                                step
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="model.type === 'Complete'" class="space-y-8">
                <div>
                    <label
                        class="text-sm font-semibold text-slate-700 mb-3 block"
                    >
                        Room Type
                        <span class="text-danger">*</span>
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="room in roomTypes"
                            :key="room.value"
                            @click="update('plan', room.value)"
                            class="group flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                            :class="
                                model.plan === room.value
                                    ? 'border-primary bg-primary/5 shadow-sm'
                                    : 'border-slate-200'
                            "
                        >
                            <div
                                class="text-xl text-primary w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 group-hover:bg-primary/10 transition"
                            >
                                <component
                                    v-if="typeof room.icon !== 'string'"
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
                            </div>
                        </div>
                    </div>
                </div>

                <div>
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
                            @click="update('billing_interval', plan.value)"
                            class="group flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                            :class="
                                model.billing_interval === plan.value
                                    ? 'border-primary bg-primary/5 shadow-sm'
                                    : 'border-slate-200'
                            "
                        >
                            <div
                                class="text-xl text-primary w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 group-hover:bg-primary/10 transition"
                            >
                                <component
                                    v-if="typeof plan.icon !== 'string'"
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
                </div>
            </div>

            <div class="h-px bg-[#E4E0D6]" />

            <div>
                <label class="text-sm font-medium">Admission Date</label>
                <input
                    type="date"
                    :value="model.admission_date"
                    @input="
                        update(
                            'admission_date',
                            ($event.target as HTMLInputElement).value,
                        )
                    "
                    :min="todayStr"
                    class="w-full border rounded-lg p-2 mt-1"
                />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { watch, computed } from "vue";
import { getLocalDateStr } from "~/utils/time-slot";
import type { FacilityBooking } from "~/types/booking";
import { Star, CalendarDays, CalendarRange, Users } from "lucide-vue-next";
const props = defineProps<{
    model: FacilityBooking;
}>();

const emit = defineEmits<{
    (e: "update:model", value: FacilityBooking): void;
}>();

function update<K extends keyof FacilityBooking>(
    key: K,
    value: FacilityBooking[K],
) {
    emit("update:model", {
        ...props.model,
        [key]: value,
    });
}

const roomTypes: {
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

const pricingMatrix: Record<string, Record<string, number>> = {
    Common: {
        Monthly: 8000,
        Yearly: 85000,
    },
    VIP: {
        Monthly: 15000,
        Yearly: 160000,
    },
};

function getPrice(plan: "Monthly" | "Yearly") {
    const room = props.model.plan || "Common";
    return pricingMatrix[room]?.[plan] ?? 0;
}

const availablePlans = computed(() => {
    const room = props.model.plan || "Common";

    return admissionPlans.filter((plan) => {
        const price = pricingMatrix?.[room]?.[plan.value] ?? 0;
        return price > 0;
    });
});

watch(
    () => props.model.type,
    (type) => {
        if (type === "Pre-Admission") {
            emit("update:model", {
                ...props.model,
                plan: "",
                billing_interval: "",
            });
        }
    },
);

const todayStr = getLocalDateStr(new Date());
</script>
