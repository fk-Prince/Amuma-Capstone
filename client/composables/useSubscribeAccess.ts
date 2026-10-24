import { computed } from "vue";
import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";

export const SUBSCRIBE_ONCE_TITLE = "Your agency is already subscribed";
export const SUBSCRIBE_ONCE_MESSAGE =
    "An agency can subscribe only once. Add a branch, renew or pay for your plan from Manage Subscription.";
export const MANAGE_SUBSCRIPTION_PATH = "/app/owner/subscriptions";

export function useSubscribeAccess() {
    const user = useAuthUser();
    const branchStore = useBranchStore();

    const isAgencyOwner = computed(
        () =>
            !!user.value?.isEmployee &&
            branchStore.branches.some((b) => b.role_name === "agency_owner"),
    );

    async function ensureLoaded() {
        if (user.value?.isEmployee && !branchStore.loaded) {
            await branchStore.refreshBranch();
        }
    }

    return { isAgencyOwner, ensureLoaded };
}
