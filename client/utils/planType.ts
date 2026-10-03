export type PlanType = "sme" | "enterprise";

export const PLAN_TYPES: { value: PlanType; label: string; branchLimit: number }[] = [
    { value: "sme", label: "Small-Medium Enterprise", branchLimit: 1 },
    { value: "enterprise", label: "Enterprise", branchLimit: 10 },
];

export const DEFAULT_PLAN_TYPE: PlanType = "sme";

export const planTypeLabel = (type?: string | null): string =>
    PLAN_TYPES.find((t) => t.value === type)?.label ?? "";

const DEFAULT_BRANCH_LIMIT = 1;

export const planTypeBranchLimit = (type?: string | null): number =>
    PLAN_TYPES.find((t) => t.value === type)?.branchLimit ?? DEFAULT_BRANCH_LIMIT;

export const planPrice = (plan: any): number => Number(plan?.price) || 0;

export const plansOfType = <T extends { type?: string }>(plans: T[], type: PlanType): T[] =>
    plans.filter((plan) => plan.type === type);

export const findPlan = <T extends { plan_code?: string; type?: string }>(
    plans: T[],
    planCode?: string | null,
    type?: string | null,
): T | null =>
    plans.find((plan) => plan.plan_code === planCode && plan.type === type) ?? null;

export const branchLimitText = (type?: string | null): string => {
    const limit = planTypeBranchLimit(type);
    return limit === 1 ? "Covers 1 branch" : `Covers up to ${limit} branches`;
};
