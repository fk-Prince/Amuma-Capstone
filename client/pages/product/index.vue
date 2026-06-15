<template>
    <div class="min-h-screen bg-white">
        <main class="max-w-6xl mx-auto px-6 py-16">
            <div class="text-center mb-10">
                <p
                    class="text-xs font-bold tracking-[0.2em] text-gray-400 uppercase mb-4"
                >
                    Pricing
                </p>

                <h1
                    class="font-display font-extrabold text-4xl md:text-5xl text-gray-900 leading-tight"
                >
                    One calm price. All the
                    <span class="text-primary">features</span> you need.
                </h1>
            </div>

            <div class="flex justify-center mb-12">
                <BillingToggle v-model="billingCycle" />
            </div>

            <div v-if="loading" class="flex justify-center py-20">
                <div
                    class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
                />
            </div>

            <div
                v-else
                class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch"
            >
                <PricingCard
                    v-for="plan in formattedPlans"
                    :key="plan.title"
                    v-bind="plan"
                    :billingInterval="billingCycle"
                    @select="checkout.setSelectedPlan(plan)"
                />
            </div>

            <ComparableTable />
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import BillingToggle from "~/components/ui/BillingToggle.vue";
import PricingCard from "~/components/ui/PricingCard.vue";
import { planService } from "@/api/plan/PlanService";
import ComparableTable from "~/components/ui/ComparableTable.vue";
import { useSubscriptionCheckout } from "~/stores/subscription";

useHead({ title: "Product" });

definePageMeta({
    navVariant: 1,
});

const billingCycle = ref<"monthly" | "yearly">("monthly");
const loading = ref(true);
const checkout = useSubscriptionCheckout();
checkout.selectedInterval = billingCycle.value;

watch(billingCycle, (val) => {
    checkout.selectedInterval = val;
});

onMounted(async () => {
    try {
        const plans = await planService.list();
        checkout.setPlans(plans);
    } finally {
        loading.value = false;
    }
});

const PLAN_LABELS = ["Plan A", "Plan B", "Plan C"];

const formattedPlans = computed(() =>
    checkout.plans.map((plan: any, index: number) => ({
        planLabel: PLAN_LABELS[index] ?? `Plan ${index + 1}`,
        ...plan,
        title: plan.name,
        description: plan.description,
        price:
            billingCycle.value === "yearly"
                ? plan.yearly_price?.price
                : plan.monthly_price?.price,
        billing_interval: billingCycle.value,
        ctaText: `Subscribe to ${plan.name}`,
        featured: index === checkout.plans.length - 1,
        features: plan.name.includes("Home")
            ? ["Daily home care visits", "Live-in caregivers", "Family portal"]
            : plan.name.includes("In-house")
              ? [
                    "Facility Management",
                    "Admission & rooms",
                    "eMAR & vitals",
                    "VIP CCTV access",
                    "Family Portal",
                    "VIP & Common Rooms",
                ]
              : [
                    "All Home Care features",
                    "All Facility features",
                    "All in one solution",
                ],
    })),
);
</script>
