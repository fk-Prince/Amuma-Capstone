import { useBranchStore } from "~/stores/branch";

export const usePermissions = () => {
    const branchStore = useBranchStore();

    const hasModule = (...modules: string[]) => {
        const permissions = branchStore.activeBranch?.permissions ?? [];

        return permissions.some(
            (p) => modules.includes(p.module_name) && p.can_read,
        );
    };

    const canCreate = (module_name: string) => {
        const permissions = branchStore.activeBranch?.permissions ?? [];
        return permissions.some(
            (p) => p.module_name === module_name && p.can_create,
        );
    };

    const canUpdate = (module_name: string) => {
        const permissions = branchStore.activeBranch?.permissions ?? [];
        return permissions.some(
            (p) => p.module_name === module_name && p.can_update,
        );
    };

    const canApprove = (module_name: string) => {
        const permissions = branchStore.activeBranch?.permissions ?? [];
        return permissions.some(
            (p) => p.module_name === module_name && p.can_approve,
        );
    };

    const canAssign = (module_name: string) => {
        const permissions = branchStore.activeBranch?.permissions ?? [];
        return permissions.some(
            (p) => p.module_name === module_name && p.can_assign,
        );
    };

    const role = computed(() =>
        (branchStore.activeBranch?.role_name ?? "").toLowerCase(),
    );

    const hasRole = (...roles: string[]) =>
        roles.map((r) => r.toLowerCase()).includes(role.value);

    const canChart = computed(() => hasRole("nurse", "caregiver"));

    const canLogActivity = computed(() =>
        hasRole("nurse", "caregiver", "admission"),
    );

    const chartingBlockedReason = "Only a nurse or caregiver can record this.";

    const careTeamBlockedReason =
        "Only admission staff, a nurse or a caregiver can record this.";

    return {
        hasModule,
        canCreate,
        canUpdate,
        canApprove,
        canAssign,
        role,
        hasRole,
        canChart,
        canLogActivity,
        chartingBlockedReason,
        careTeamBlockedReason,
    };
};
