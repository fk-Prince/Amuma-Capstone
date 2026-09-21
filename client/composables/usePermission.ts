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

    const canApprove = (module_name: string) =>
        can(module_name, PermissionAction.Approve);

    const canReject = (module_name: string) =>
        can(module_name, PermissionAction.Reject);

    const canAssign = (module_name: string) =>
        can(module_name, PermissionAction.Assign);

    const canExport = (module_name: string) =>
        can(module_name, PermissionAction.Export);

    const canAdmit = (module_name: string) =>
        can(module_name, PermissionAction.Admit);

    const canDischarge = (module_name: string) =>
        can(module_name, PermissionAction.Discharge);

    const canForceDischarge = (module_name: string) =>
        can(module_name, PermissionAction.ForceDischarge);

    const canApproveWithdrawal = (module_name: string) =>
        can(module_name, PermissionAction.ApproveWithdrawal);

    const canRenew = (module_name: string) =>
        can(module_name, PermissionAction.Renew);

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
        actionsFor,
        can,
        hasModule,
        canCreate,
        canUpdate,
        canApprove,
        canReject,
        canAssign,
        canExport,
        canAdmit,
        canDischarge,
        canForceDischarge,
        canApproveWithdrawal,
        canRenew,
        role,
        hasRole,
        canChart,
        canLogActivity,
        chartingBlockedReason,
        careTeamBlockedReason,
    };
};
