import type { RouteLocationNormalizedLoaded } from "vue-router";
import { useBranchStore } from "~/stores/branch";
import { authMenuList } from "~/config/authMenu";
import { PermissionAction } from "~/utils/permissions";

export type RouteAccess =
    | "allowed"
    | "no-branch"
    | "no-module"
    | "inactive-branch";

export const routeAccess = (
    route: Pick<RouteLocationNormalizedLoaded, "path" | "params">,
): RouteAccess => {
    if (!route.path.startsWith("/app/branches/")) return "allowed";

    const uuidParam = route.params.uuid;
    const branchUuid = Array.isArray(uuidParam) ? uuidParam[0] : uuidParam;
    if (!branchUuid) return "allowed";

    const branchStore = useBranchStore();
    if (!branchStore.loaded) return "allowed";

    const branch = branchStore.branches.find((b) => b?.uuid === branchUuid);
    if (!branch) return "no-branch";

    if (branch.employee_status === "inactive") return "inactive-branch";

    const menu = authMenuList.find((item) => {
        const base = item.to.replace("[uuid]", branchUuid);
        return route.path === base || route.path.startsWith(`${base}/`);
    });

    if (!menu?.modules?.length) return "allowed";

    const readable =
        branch.permissions
            ?.filter((p) => p.actions?.includes(PermissionAction.Read))
            .map((p) => p.module_name) ?? [];

    return menu.modules.some((m) => readable.includes(m))
        ? "allowed"
        : "no-module";
};

export const useRouteAccess = () => {
    const route = useRoute();
    return computed(() => routeAccess(route));
};
