<template>
    <div class="min-h-screen bg-white dark:bg-secondary">
        <main class="max-w-6xl mx-auto px-6 py-16">
            <div class="text-center mb-10">
                <p
                    class="text-xs font-bold tracking-[0.2em] text-primary uppercase mb-4"
                >
                    Pricing
                </p>

                <h1
                    class="font-display font-extrabold text-4xl md:text-5xl text-gray-900 leading-tight dark:text-white"
                >
                    One calm price. All the
                    <span class="text-primary">features</span> you need.
                </h1>
            </div>

            <div class="flex justify-center mb-12">
                <BillingToggle v-model="billingCycle" />
            </div>

            <div
                v-if="loading"
                class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch"
            >
                <div
                    v-for="i in 3"
                    :key="i"
                    class="rounded-3xl border border-gray-200 p-8 animate-pulse dark:border-white/10"
                >
                    <div class="h-4 w-20 bg-gray-200 rounded mb-6"></div>

                    <div class="h-8 w-40 bg-gray-200 rounded mb-4"></div>

                    <div class="space-y-2 mb-8">
                        <div class="h-4 bg-gray-200 rounded"></div>
                        <div class="h-4 w-5/6 bg-gray-200 rounded"></div>
                    </div>

                    <div class="h-10 w-28 bg-gray-200 rounded mb-8"></div>

                    <div class="h-12 bg-gray-200 rounded-xl mb-8"></div>

                    <div class="space-y-4">
                        <div
                            v-for="j in 5"
                            :key="j"
                            class="flex items-center gap-3"
                        >
                            <div
                                class="w-5 h-5 rounded-full bg-gray-200 flex-shrink-0"
                            ></div>
                            <div class="h-4 flex-1 bg-gray-200 rounded"></div>
                        </div>
                    </div>
                </div>
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
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import BillingToggle from "~/components/ui/BillingToggle.vue";
import PricingCard from "~/components/ui/PricingCard.vue";
import { planService } from "@/api/plan/PlanService";
import { useSubscriptionCheckout } from "~/stores/subscription";

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
                ? plan.yearly_price
                : plan.monthly_price,
        monthy_price: Number(plan.monthly_price),
        yearly_price: Number(plan.yearly_price),
        billing_interval: billingCycle.value,
        ctaText: `Subscribe to ${plan.name}`,
        featured: index === checkout.plans.length - 1,
        features: plan.name.includes("Home")
            ? [
                  "Homecare visits scheduling",
                  "Caregiver & nurse assignment with QR clock-in",
                  "eMAR & vital signs tracking",
                  "Family portal & messaging",
                  "Billing, invoices & online payments",
              ]
            : plan.name.includes("In-house")
              ? [
                    "Admissions & discharge management",
                    "VIP & Common room contracts",
                    "eMAR & vital signs tracking",
                    "VIP CCTV access",
                    "Family portal & messaging",
                    "Billing, invoices & online payments",
                ]
              : [
                    "All Homecare features",
                    "All Facility features",
                    "One subscription for both services",
                ],
    })),
);
</script>
