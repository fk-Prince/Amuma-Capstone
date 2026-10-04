import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";

export const AUTH_ROUTES = [
    "/auth/select",
    "/auth/staff/signin",
    "/auth/client/signin",
    "/auth/signup",
    "/auth/forgot-password",
];

export async function authLandingPath(): Promise<string> {
    const user = useAuthUser();
    const branchStore = useBranchStore();

    if (user.value?.isClient) {
        return user.value.hasBooking || user.value.hasPatient
            ? "/portal/overview"
            : "/";
    }

    if (user.value?.isSystemOwner) {
        return "/app/owner/dashboard";
    }

    if (!branchStore.branches.length) {
        await branchStore.fetchBranches();
    }

    const defaultBranch = branchStore.branches[0];

    return defaultBranch?.uuid
        ? `/app/branches/${defaultBranch.uuid}/dashboard`
        : "/app/branches/dashboard";
}
