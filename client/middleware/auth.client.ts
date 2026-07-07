import { useAuthUser } from "~/composables/useAuthUser";
import { authMenuList } from "~/config/authMenu";

const AUTH_ROUTES = ["/auth/signin", "/auth/signup"];
const dashboard = ["/app/branches/"];

export default defineNuxtRouteMiddleware((to) => {
    const user = useAuthUser();

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
    if (!branchUuid) return;

    const hasBranchAccess = user.value?.roles?.some(
        (r: any) => r.branch === branchUuid
    );

    if (!hasBranchAccess) {
        return navigateTo("/403");
    }

    const userRoles = user.value?.roles
        ?.filter((r: any) => r.branch === branchUuid)
        .map((r: any) => r.role) ?? [];

    const selectedMenu = authMenuList.find((item) => {
        const uuid = item.to.replace("[uuid]", branchUuid);
        return to.path === uuid || to.path.startsWith(uuid);
    });

    if (selectedMenu) {
        const allowedRoles = selectedMenu.roles;

        const hasRoleAccess = userRoles.some((role: string) =>
            allowedRoles?.includes(role)
        );

        if (!hasRoleAccess) {
            return navigateTo("/403");
        }
    }
});