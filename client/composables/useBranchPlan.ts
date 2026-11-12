import { computed } from "vue";
import { useBranchStore } from "~/stores/branch";

export const useBranchPlan = () => {
    const branchStore = useBranchStore();

    const planCode = computed(
        () => branchStore.activeBranch?.plan?.[0]?.plan_code ?? null
    );

    const hasPlan = (code: "A" | "B" | "C") => {
        switch (planCode.value) {
            case "A":
                return code === "A";

            case "B":
                return code === "B";

            case "C":
                return ["A", "B", "C"].includes(code);

            default:
                return false;
        }
    };

    const hasFacilityPlan = computed(() => hasPlan("B"));
    const hasHomecarePlan = computed(() => hasPlan("A"));

    const isLocationLocked = (value: string) => {
        if (!planCode.value) return false;
        if (value === "online") return !hasHomecarePlan.value;
        if (value === "facility") return !hasFacilityPlan.value;
        return false;
    };

    const withPlanLocks = <T extends { value: string }>(items: T[]) =>
        items.map((item) => ({
            ...item,
            disabled: isLocationLocked(item.value),
            hint: isLocationLocked(item.value)
                ? "Not in this branch's plan"
                : undefined,
        }));

    return {
        planCode,
        hasPlan,
        hasFacilityPlan,
        hasHomecarePlan,
        isLocationLocked,
        withPlanLocks,
    };
};