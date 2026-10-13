<script setup lang="ts">
import { computed } from "vue";
import { BedDouble, Building2, Crown, Users } from "lucide-vue-next";
import ClampedText from "~/components/ui/ClampedText.vue";
import type { BranchFacility, BranchRetrieve } from "~/types/branch";
import { formatCurrency } from "~/utils/currency";

const props = defineProps<{
    branch: BranchRetrieve | null;
}>();

const emit = defineEmits<{
    facility: [];
}>();

const TYPE_META = {
    COMMON: {
        label: "Common Room",
        blurb: "Shared accommodation with round-the-clock care.",
        icon: Users,
        accent: "from-sky-400 to-blue-500 shadow-sky-500/20",
    },
    VIP: {
        label: "VIP Room",
        blurb: "Private room with more space and privacy.",
        icon: Crown,
        accent: "from-amber-400 to-orange-500 shadow-amber-500/20",
    },
} as const;

const CYCLE_LABELS: Record<string, string> = {
    HOURLY: "per hour",
    MONTHLY: "per month",
    YEARLY: "per year",
};

const CYCLE_ORDER = ["HOURLY", "MONTHLY", "YEARLY"];

const accommodations = computed(() => {
    const groups = new Map<string, BranchFacility[]>();

    (props.branch?.facility ?? []).forEach((plan) => {
        const type = String(plan.accommodation_type).toUpperCase();

        if (!groups.has(type)) groups.set(type, []);
        groups.get(type)!.push(plan);
    });

    return [...groups.entries()].map(([type, plans]) => {
        const meta = TYPE_META[type as keyof typeof TYPE_META];
        const sorted = [...plans].sort(
            (a, b) =>
                CYCLE_ORDER.indexOf(String(a.billing_cycle).toUpperCase()) -
                CYCLE_ORDER.indexOf(String(b.billing_cycle).toUpperCase()),
        );

        const distinct = [
            ...new Set(
                sorted
                    .map((plan) => (plan.description ?? "").trim())
                    .filter(Boolean),
            ),
        ];

        return {
            type,
            label: meta?.label ?? type,
            blurb: meta?.blurb ?? "",
            icon: meta?.icon ?? BedDouble,
            accent: meta?.accent ?? "from-sky-400 to-blue-500 shadow-sky-500/20",
            description: distinct.length === 1 ? distinct[0] : null,
            perPlanDescriptions: distinct.length > 1,
            slots: Math.max(...sorted.map((plan) => Number(plan.available_slot ?? 0))),
            plans: sorted,
        };
    });
});

const peso = (value: number | string) =>
    formatCurrency(value, { minimumFractionDigits: 0 });
</script>

<template>
    <section
        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-400 to-blue-500 text-white shadow-md shadow-sky-500/20"
                >
                    <Building2 class="h-5 w-5" />
                </span>
                <div>
                    <h2
                        class="text-xl font-bold text-secondary dark:text-white"
                    >
                        Facility Admission
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        In-house, facility-based care at {{ branch?.name }}.
                    </p>
                </div>
            </div>

            <span
                v-if="accommodations.length"
                class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-500/10 dark:text-sky-300"
            >
                {{ accommodations.length }}
                {{ accommodations.length === 1 ? "accommodation" : "accommodations" }}
            </span>
        </div>

        <div
            v-if="!accommodations.length"
            class="mt-6 flex flex-col items-center rounded-2xl border border-dashed border-gray-200 px-6 py-12 text-center dark:border-white/10"
        >
            <span
                class="flex h-12 w-12 items-center justify-center rounded-full bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300"
            >
                <Building2 class="h-6 w-6" />
            </span>
            <p
                class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200"
            >
                No room accommodations offered yet
            </p>
        </div>

        <div v-else class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
            <article
                v-for="(room, index) in accommodations"
                :key="room.type"
                class="facility-card flex flex-col overflow-hidden rounded-2xl border border-gray-100 dark:border-white/10"
                :style="{ animationDelay: `${index * 60}ms` }"
            >
                <div
                    class="flex items-start justify-between gap-3 border-b border-gray-100 bg-gradient-to-r from-primary-50 to-transparent px-4 py-4 dark:border-white/10 dark:from-primary/10"
                >
                    <div class="flex min-w-0 items-start gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-md"
                            :class="room.accent"
                        >
                            <component :is="room.icon" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-bold text-secondary dark:text-white"
                            >
                                {{ room.label }}
                            </p>
                            <p
                                class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ room.blurb }}
                            </p>
                        </div>
                    </div>

                    <span
                        class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                        :class="
                            room.slots > 0
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                : 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400'
                        "
                    >
                        {{
                            room.slots > 0
                                ? `${room.slots} ${room.slots === 1 ? "slot" : "slots"} available`
                                : "Fully booked"
                        }}
                    </span>
                </div>

                <div class="flex flex-1 flex-col gap-4 p-4">
                    <ClampedText
                        v-if="room.description"
                        :text="room.description"
                        class="text-sm leading-6 text-gray-600 dark:text-gray-300"
                    />

                    <ul class="mt-auto flex flex-col gap-2">
                        <li
                            v-for="plan in room.plans"
                            :key="plan.billing_cycle"
                            class="rounded-xl bg-gray-50 px-3.5 py-2.5 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span
                                    class="text-sm font-medium capitalize text-gray-700 dark:text-gray-200"
                                >
                                    {{ String(plan.billing_cycle).toLowerCase() }}
                                </span>
                                <span
                                    class="text-sm font-bold text-secondary dark:text-white"
                                >
                                    {{ peso(plan.price) }}
                                    <span
                                        class="text-xs font-medium text-gray-400"
                                        >{{
                                            CYCLE_LABELS[
                                                String(plan.billing_cycle).toUpperCase()
                                            ]
                                        }}</span
                                    >
                                </span>
                            </div>

                            <ClampedText
                                v-if="room.perPlanDescriptions && plan.description"
                                :text="plan.description"
                                class="mt-1.5 text-xs leading-5 text-gray-500 dark:text-gray-400"
                            />
                        </li>
                    </ul>

                    <button
                        type="button"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="room.slots <= 0"
                        @click="emit('facility')"
                    >
                        Book Facility Admission
                    </button>
                </div>
            </article>
        </div>
    </section>
</template>

<style scoped>
.facility-card {
    animation: facility-in 0.4s ease-out backwards;
}

@keyframes facility-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
