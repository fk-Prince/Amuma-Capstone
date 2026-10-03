import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";
import { authMenuList } from "~/config/authMenu";
import { PermissionAction } from "~/utils/permissions";

const AUTH_ROUTES = [
    "/auth/select",
    "/auth/staff/signin",
    "/auth/client/signin",
    "/auth/signup",
    "/auth/forgot-password",
];

export default defineNuxtRouteMiddleware(async (to) => {
    const user = useAuthUser();
    const branchStore = useBranchStore();

    if (import.meta.server) return;

    const isAuthRoute = AUTH_ROUTES.includes(to.path);
    const isAuthenticated = !!user.value;

    if (!isAuthenticated && !isAuthRoute) {
        // Someone heading to checkout with no account is a new subscriber,
        // so send them to the agency sign-up instead of the portal chooser.
        const target = to.path.startsWith("/product/subscription-details")
            ? "/auth/signup"
            : to.path.startsWith("/portal") || to.path.startsWith("/booking")
              ? "/auth/client/signin"
              : to.path.startsWith("/app")
                ? "/auth/staff/signin"
                : "/auth/select";

        saveAuthRedirect(to.fullPath);

        return navigateTo({
            path: target,
            query: { redirect: to.fullPath },
        });
    }

    if (isAuthenticated && isAuthRoute) {
        if (user.value?.isClient) {
            return navigateTo(
                user.value.hasBooking || user.value.hasPatient
                    ? "/portal/overview"
                    : "/",
            );
        }

        if (user.value?.isSystemOwner) {
            return navigateTo("/app/owner/dashboard");
        }

        if (!branchStore.branches.length) {
            await branchStore.fetchBranches();
            return;
        }

        const defaultBranch = branchStore.branches[0];

        return navigateTo(
            defaultBranch?.uuid
                ? `/app/branches/${defaultBranch.uuid}/dashboard`
                : "/app/branches/dashboard",
        );
    }

    if (to.path.startsWith("/app/branches/")) {
        const branchUuid = to.params.uuid as string;
        if (!branchUuid) return;

        if (!branchStore.branches.length) {
            await branchStore.fetchBranches(branchUuid);
        }

        const branch = branchStore.branches.find((b) => b?.uuid === branchUuid);
        if (!branch) {
            return navigateTo("/403");
        }

        const readableModules = branch.permissions
            ?.filter((p) => p.actions?.includes(PermissionAction.Read))
            .map((p) => p.module_name) ?? [];

        const selectedMenu = authMenuList.find((item) => {
            const uuid = item.to.replace("[uuid]", branchUuid);
            return to.path === uuid || to.path.startsWith(uuid);
        });

        const isDashboard = selectedMenu?.to?.endsWith("/dashboard");

        if (!isDashboard && selectedMenu?.modules) {
            const hasModuleAccess = selectedMenu.modules.some((m) =>
                readableModules.includes(m),
            );

            if (!hasModuleAccess) {
                return navigateTo("/403");
            }
        }
    }
});