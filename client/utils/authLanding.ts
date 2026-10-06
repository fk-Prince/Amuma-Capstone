import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";

export const AUTH_ROUTES = [
    "/auth/signin",
    "/auth/signup",
    "/auth/forgot-password",
];

export async function authLandingPath(): Promise<string> {
    const user = useAuthUser();
    const branchStore = useBranchStore();

    if (user.value?.isSystemOwner) {
        return "/app/owner/dashboard";
    }

    if (user.value?.isEmployee) {
        if (!branchStore.branches.length) {
            await branchStore.fetchBranches();
        }

        const defaultBranch = branchStore.branches[0];

        return defaultBranch?.uuid
            ? `/app/branches/${defaultBranch.uuid}/dashboard`
            : "/app/branches/dashboard";
    }

    return user.value?.hasBooking || user.value?.hasPatient
        ? "/portal/overview"
        : "/";
}
