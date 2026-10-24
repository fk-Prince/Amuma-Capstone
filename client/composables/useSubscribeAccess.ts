import { computed } from "vue";
import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";

export const SUBSCRIBE_ONCE_TITLE = "Your agency is already subscribed";
export const SUBSCRIBE_ONCE_MESSAGE =
    "An agency can subscribe only once. Add a branch, renew or pay for your plan from Dashboard.";

export function useSubscribeAccess() {
    const user = useAuthUser();
    const branchStore = useBranchStore();

    const isAgencyOwner = computed(
        () =>
            !!user.value?.isEmployee &&
            branchStore.branches.some((b) => b.role_name === "agency_owner"),
    );

    const manageSubscriptionPath = computed(() => {
        const ownerBranches = branchStore.branches.filter(
            (branch) => branch.role_name === "agency_owner" && branch.uuid,
        );
        const branch =
            ownerBranches.find((item) => item.uuid === branchStore.activeBranch?.uuid) ??
            ownerBranches.find((item) => item.uuid === branchStore.defaultBranchUuid) ??
            ownerBranches[0];

        return branch?.uuid
            ? `/app/branches/${branch.uuid}/dashboard`
            : "/app/dashboard";
    });

    async function ensureLoaded() {
        if (user.value?.isEmployee && !branchStore.loaded) {
            await branchStore.refreshBranch();
        }
    }

    return { isAgencyOwner, manageSubscriptionPath, ensureLoaded };
}
