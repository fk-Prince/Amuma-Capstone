import { computed } from "vue";
import { useBranchStore } from "~/stores/branch";

/**
 * The branches of the active branch's agency, as the agency owner sees them.
 * Used by the Executive Overview (all branches together).
 */
export function useAgencyBranches() {
    const branchStore = useBranchStore();

    const activeBranch = computed(() => branchStore.activeBranch);

    const isAgencyOwner = computed(
        () => activeBranch.value?.role_name === "agency_owner",
    );

    const agency = computed(() => activeBranch.value?.agency ?? null);

    const agencyBranches = computed(() => {
        const agencyId = agency.value?.agency_id;
        if (!isAgencyOwner.value || agencyId == null) return [];

        return branchStore.branches.filter(
            (branch) =>
                branch.agency?.agency_id === agencyId &&
                branch.role_name === "agency_owner",
        );
    });

    // Rejected branches aren't a real part of the agency's operations.
    const overviewBranches = computed(() =>
        agencyBranches.value.filter(
            (branch) => branch.subscription_status !== "rejected",
        ),
    );

    const canSeeExecutiveOverview = computed(
        () => isAgencyOwner.value && overviewBranches.value.length > 1,
    );

    return {
        agency,
        isAgencyOwner,
        agencyBranches,
        overviewBranches,
        canSeeExecutiveOverview,
    };
}
