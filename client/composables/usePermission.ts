import { useBranchStore } from "~/stores/branch";
import { useBranchPlan } from "~/composables/useBranchPlan";
import { Modules } from "~/types/module";
import { PermissionAction, type PermissionActionKey } from "~/utils/permissions";

const FACILITY_MODULES: string[] = [Modules.Admissions, Modules.RoomsAndBeds];

export const usePermissions = () => {
    const branchStore = useBranchStore();
    const { hasFacilityPlan } = useBranchPlan();

    const actionsFor = (module_name: string): PermissionActionKey[] =>
        (branchStore.activeBranch?.permissions ?? []).find(
            (p) => p.module_name === module_name,
        )?.actions ?? [];

    const can = (module_name: string, action: PermissionActionKey) => {
        if (
            action !== PermissionAction.Read &&
            FACILITY_MODULES.includes(module_name) &&
            !hasFacilityPlan.value
        ) {
            return false;
        }

        return actionsFor(module_name).includes(action);
    };

    const hasModule = (...modules: string[]) =>
        modules.some((module_name) => can(module_name, PermissionAction.Read));

    const canCreate = (module_name: string) =>
        can(module_name, PermissionAction.Create);

    const canUpdate = (module_name: string) =>
        can(module_name, PermissionAction.Update);

    const canAssign = (module_name: string) =>
        can(module_name, PermissionAction.Assign);

    const canExport = (module_name: string) =>
        can(module_name, PermissionAction.Export);

    const canForceDischarge = (module_name: string) =>
        can(module_name, PermissionAction.ForceDischarge);

    const role = computed(() =>
        (branchStore.activeBranch?.role_name ?? "").toLowerCase(),
    );

    const hasRole = (...roles: string[]) =>
        roles.map((r) => r.toLowerCase()).includes(role.value);

    const canChart = computed(() => canUpdate(Modules.Patients));

    const canLogActivity = computed(() => canUpdate(Modules.Patients));

    const chartingBlockedReason =
        "You need permission to update patients to record this.";

    const careTeamBlockedReason =
        "You need permission to update patients to record this.";

    return {
        actionsFor,
        can,
        hasModule,
        canCreate,
        canUpdate,
        canAssign,
        canExport,
        canForceDischarge,
        role,
        hasRole,
        canChart,
        canLogActivity,
        chartingBlockedReason,
        careTeamBlockedReason,
    };
};
