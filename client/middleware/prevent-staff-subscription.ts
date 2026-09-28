import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import { useBranchStore } from "~/stores/branch";

export default defineNuxtRouteMiddleware(async (to) => {
    const user = useAuthUser();

    if (!user.value?.isEmployee && !user.value?.isSystemOwner) return;

    if (user.value.isEmployee) {
        const branchStore = useBranchStore();

        if (!branchStore.loaded) {
            await branchStore.refreshBranch();
        }

        const isAgencyOwner = branchStore.branches.some(
            (b) => b.role_name === "agency_owner",
        );

        if (isAgencyOwner) return;
    }

    const { warning } = useToast();
    const role = user.value.isEmployee ? "Employee" : "Platform admin";
    warning("Subscription Access Restricted", `${role} accounts cannot subscribe to a plan, Please use your personal email instead.`);

    if (to.path.endsWith("/subscription-details")) {
        return navigateTo("/product");
    }
});
