import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";
import { authMenuList } from "~/config/authMenu";
import { PermissionAction } from "~/utils/permissions";
import { AUTH_ROUTES, authLandingPath } from "~/utils/authLanding";

export default defineNuxtRouteMiddleware(async (to) => {
    const user = useAuthUser();
    const branchStore = useBranchStore();

    if (import.meta.server) return;

    const isAuthRoute = AUTH_ROUTES.includes(to.path);
    const isAuthenticated = !!user.value;

    if (!isAuthenticated && !isAuthRoute) {
        const target = to.path.startsWith("/product/subscription-details")
            ? "/auth/agency/signup"
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

    // if (isAuthenticated && isAuthRoute) {
    //     if (useNuxtApp().isHydrating) return;
    //     return navigateTo(await authLandingPath());
    // }

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