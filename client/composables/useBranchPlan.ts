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

    return {
        planCode,
        hasPlan,
        hasFacilityPlan,
        hasHomecarePlan,
    };
};