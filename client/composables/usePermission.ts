import { useBranchStore } from "~/stores/branch";
import { useBranchPlan } from "~/composables/useBranchPlan";
import { useSandbox } from "~/composables/useSandbox";
import { Modules } from "~/types/module";
import { PermissionAction, type PermissionActionKey } from "~/utils/permissions";

const FACILITY_MODULES: string[] = [Modules.Admissions, Modules.RoomsAndBeds];

export const usePermissions = () => {
    const branchStore = useBranchStore();
    const { hasFacilityPlan } = useBranchPlan();
    const { isSandbox } = useSandbox();

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

        const changes =
            action !== PermissionAction.Read &&
            action !== PermissionAction.Export;
        const isSubscriptionModule = module_name === Modules.ManageSubscription;

        if (changes && isSubscriptionModule && isSandbox.value) {
            return false;
        }

        if (
            changes &&
            !isSubscriptionModule &&
            branchStore.activeBranch?.subscription_locked
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

    const canReview = (module_name: string) =>
        can(module_name, PermissionAction.Review);

    const canExport = (module_name: string) =>
        can(module_name, PermissionAction.Export);

    const role = computed(() =>
        (branchStore.activeBranch?.role_name ?? "").toLowerCase(),
    );

    const hasRole = (...roles: string[]) =>
        roles.map((r) => r.toLowerCase()).includes(role.value);

    const canChart = computed(() => canUpdate(Modules.Patients));

    const canAddChart = computed(() => canCreate(Modules.Patients));

    const canLogActivity = computed(() => canCreate(Modules.Patients));

    const chartingBlockedReason =
        "You need permission to update patients to record this.";

    const addChartBlockedReason =
        "You need permission to create patients to record this.";

    const careTeamBlockedReason =
        "You need permission to create patients to record this.";

    const canViewMedications = computed(() => hasModule(Modules.Medications));

    const canAddMedication = computed(() => canCreate(Modules.Medications));

    const canRecordMedication = computed(() =>
        canUpdate(Modules.Medications),
    );

    const addMedicationBlockedReason =
        "You need permission to create medications to record this.";

    const recordMedicationBlockedReason =
        "You need permission to update medications to record this.";

    return {
        actionsFor,
        can,
        hasModule,
        canCreate,
        canUpdate,
        canAssign,
        canReview,
        canExport,
        role,
        hasRole,
        canChart,
        canAddChart,
        canLogActivity,
        chartingBlockedReason,
        addChartBlockedReason,
        careTeamBlockedReason,
        canViewMedications,
        canAddMedication,
        canRecordMedication,
        addMedicationBlockedReason,
        recordMedicationBlockedReason,
    };
};
