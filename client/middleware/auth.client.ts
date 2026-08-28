
import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";
import { authMenuList } from "~/config/authMenu";

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
            ?.filter((p) => p.can_read)
            .map((p) => p.module_name) ?? [];

        const selectedMenu = authMenuList.find((item) => {
            const uuid = item.to.replace("[uuid]", branchUuid);
            return to.path === uuid || to.path.startsWith(uuid);
        });

        const isDashboard =
            selectedMenu?.to?.endsWith("/dashboard");

        if (!isDashboard && selectedMenu?.modules) {
            const hasModuleAccess = selectedMenu.modules.some((m) =>
                readableModules.includes(m)
            );

            if (!hasModuleAccess) {
                return navigateTo("/403");
            }
        }
    }
});