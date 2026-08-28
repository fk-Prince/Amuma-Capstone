import { useBranch } from "~/composables/useBranchProvider";

export default defineNuxtRouteMiddleware(async (to) => {
    const uuid = to.params.branch_uuid as string;

    if (!uuid) {
        return;
    }

    const {
        branch,
        fetchBranch,
        canUseHomecare,
        canUseFacility,
    } = useBranch();

    if (!branch.value) {
        try {
            await fetchBranch(uuid);
        } catch (error) {
            return navigateTo("/404");
        }
    }

    const category = to.query.category as "homecare" | "facility";
    if (!category) {
        return;
    }

    if (category === "homecare" && !canUseHomecare.value) {
        return navigateTo("/");
    }

    if (category === "facility" && !canUseFacility.value) {
        return navigateTo("/");
    }
});