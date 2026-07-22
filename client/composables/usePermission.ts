import { useBranchStore } from "~/stores/branch";

export const usePermissions = () => {
    const branchStore = useBranchStore();

    const hasModule = (...modules: string[]) => {
        const permissions = branchStore.activeBranch?.permissions ?? [];

        return permissions.some(
            (p) => modules.includes(p.module_name) && p.can_read
        );
    };

    const canCreate = (module_name: string) => {
        const permissions = branchStore.activeBranch?.permissions ?? [];
        return permissions.some((p) => p.module_name === module_name && p.can_create);
    };

    const canUpdate = (module_name: string) => {
        const permissions = branchStore.activeBranch?.permissions ?? [];
        return permissions.some((p) => p.module_name === module_name && p.can_update);
    };


    const canApprove = (module_name: string) => {
        const permissions = branchStore.activeBranch?.permissions ?? [];
        return permissions.some((p) => p.module_name === module_name && p.can_approve);
    };

    return {
        hasModule,
        canCreate,
        canUpdate,
        canApprove
    };
};