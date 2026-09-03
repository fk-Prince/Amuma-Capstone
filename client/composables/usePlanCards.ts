import { computed, onMounted, ref, watch, type Ref } from "vue";
import { planService } from "@/api/plan/PlanService";
import { useSubscriptionCheckout } from "~/stores/subscription";

const PLAN_LABELS: Record<string, string> = {
    A: "Plan A",
    B: "Plan B",
    C: "Plan C",
};

const MODULE_FEATURES: Record<string, string[]> = {
    A: [
        "Home visit booking & scheduling",
        "Caregiver assignment with QR clock-in",
        "eMAR & vital signs charting",
        "Family portal & messaging",
        "Billing, invoices & online payments",
    ],
    B: [
        "Admission & discharge management",
        "Room, bed & occupancy tracking",
        "VIP & Common room contracts",
        "VIP room CCTV access",
        "eMAR & vital signs charting",
        "Family portal & messaging",
        "Billing, invoices & online payments",
    ],
    C: [
        "Everything in the Homecare module",
        "Everything in the Facility module",
        "One subscription covering both modules",
    ],
};

export function usePlanCards(billingCycle: Ref<"monthly" | "yearly">) {
    const checkout = useSubscriptionCheckout();
    const loading = ref(true);

    watch(billingCycle, (value) => (checkout.selectedInterval = value), {
        immediate: true,
    });

    onMounted(async () => {
        try {
            checkout.setPlans(await planService.list());
        } finally {
            loading.value = false;
        }
    });

    const formattedPlans = computed(() =>
        checkout.plans.map((plan: any, index: number) => {
            const limit = Number(plan.branch_limit) || 5;

            return {
                ...plan,
                planLabel: PLAN_LABELS[plan.plan_code] ?? `Plan ${index + 1}`,
                title: plan.name,
                description: plan.description,
                price:
                    billingCycle.value === "yearly"
                        ? plan.yearly_price
                        : plan.monthly_price,
                monthly_price: Number(plan.monthly_price),
                yearly_price: Number(plan.yearly_price),
                billing_interval: billingCycle.value,
                ctaText: `Subscribe to ${plan.name}`,
                featured: plan.plan_code === "C",
                features: [
                    ...(MODULE_FEATURES[plan.plan_code] ?? []),
                    `Cover up to ${limit} branches on one subscription`,
                ],
            };
        }),
    );

    return { checkout, loading, formattedPlans };
}
