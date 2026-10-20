import { computed, onMounted, ref } from "vue";
import { planService } from "@/api/plan/PlanService";
import { subscriptionService } from "@/api/subscription/SubscriptionService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { branchLimitText, planPrice } from "~/utils/planType";

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

export function usePlanCards() {
    const checkout = useSubscriptionCheckout();
    const user = useAuthUser();
    const loading = ref(true);
    const trialAvailable = ref(true);

    async function loadTrialStatus() {
        if (!user.value) return;

        try {
            const res: any = await subscriptionService.trialStatus();
            trialAvailable.value = (res?.data ?? res)?.trial_available !== false;
        } catch {
            trialAvailable.value = true;
        }
    }

    onMounted(async () => {
        try {
            await Promise.all([
                planService
                    .list()
                    .then((plans: any) => checkout.setPlans(plans)),
                loadTrialStatus(),
            ]);
        } finally {
            loading.value = false;
        }
    });

    const hybridSavePercent = (type: string) => {
        const price = (code: string) =>
            planPrice(
                checkout.plans.find(
                    (p: any) => p.plan_code === code && p.type === type,
                ),
            );

        const separate = price("A") + price("B");
        const hybrid = price("C");

        return separate > 0 && hybrid > 0 && hybrid < separate
            ? Math.round(((separate - hybrid) / separate) * 100)
            : 0;
    };

    const formattedPlans = computed(() =>
        checkout.typedPlans.map((plan: any, index: number) => {
            return {
                ...plan,
                planLabel: PLAN_LABELS[plan.plan_code] ?? `Plan ${index + 1}`,
                title: plan.name,
                description: plan.description,
                price: planPrice(plan),
                branchNote: branchLimitText(plan.type),
                ctaText: `Subscribe to ${plan.name}`,
                featured: plan.plan_code === "C",
                showTrial: trialAvailable.value,
                savePercent: plan.plan_code === "C" ? hybridSavePercent(plan.type) : 0,
                features: MODULE_FEATURES[plan.plan_code] ?? [],
            };
        }),
    );

    return { checkout, loading, formattedPlans, trialAvailable };
}