
import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";

const AUTH_ROUTES = ["/auth/signin", "/auth/signup"];

export default defineNuxtRouteMiddleware(async (to) => {
    const user = useAuthUser();
    const branchStore = useBranchStore();

    if (import.meta.server) return;

    const isAuthRoute = AUTH_ROUTES.includes(to.path);
    const isAuthenticated = !!user.value;

    if (!isAuthenticated && !isAuthRoute) {
        return navigateTo({
            path: "/auth/signin",
            query: { redirect: to.fullPath },
        });
    }

    if (isAuthenticated && isAuthRoute) {
        return navigateTo("/");
    }

    const branchUuid = to.params.uuid as string;

    if (
        to.path.startsWith("/app/branches/") &&
        branchUuid &&
        !branchStore.loaded
    ) {
        await branchStore.fetchBranches(branchUuid);
    }
});