import { computed, ref, watch } from "vue";
import { useRoute } from "vue-router";
import {
    createEmployee,
    type Employee,
    employeeSchema,
    type EmployeePayload,
    type EmployeeStatus,
    isActiveStatus,
} from "~/types/employee";
import { moduleService } from "~/api/module/ModuleService";
import { type Module } from "~/types/module";
import {
    moduleActions,
    PermissionAction,
    ROLE_DEFAULT_PERMISSIONS,
    type PermissionActionKey,
} from "~/utils/permissions";
import { employeeService } from "~/api/employee/EmployeeService";
import { useToast } from "~/composables/useToast";
import { fetchAuthUser } from "~/composables/useAuthUser";
import type { EmployeeSlip } from "~/types/employee-slip";

export type PermissionSet = PermissionActionKey[];

export interface UseEmployeeFormOptions {
    employee: () => Employee | null | undefined;
    mode: () => "view" | "edit" | undefined;
    onSaved?: (employee?: Employee) => void;
}

export function useEmployeeForm(options: UseEmployeeFormOptions) {
    const user = useAuthUser();
    const { success, error } = useToast();
    const route = useRoute();
    const uuid = route.params.uuid as string;

    const isViewMode = computed(() => options.mode() === "view");
    const hasExistingEmployee = computed(() => !!options.employee()?.uuid);
    const isEditMode = computed(
        () => hasExistingEmployee.value && !isViewMode.value,
    );

    const employee = ref<EmployeePayload>(createEmployee());
    const errors = ref<Record<string, string>>({});

    const initialLoading = ref(false);
    const initialLoadError = ref<string | null>(null);
    const saving = ref(false);

    const modules = ref<Module[]>([]);
    const modulesLoading = ref(false);
    const modulesError = ref<string | null>(null);
    const moduleSearch = ref("");

    const permissions = ref<Record<number, PermissionSet>>({});

    const employeeSlip = ref<EmployeeSlip | null>(null);
    const savedEmployee = ref<Employee | null>(null);

    const loadedStatus = ref<EmployeeStatus>("active");

    const isActive = computed({
        get: () => isActiveStatus(employee.value.status),
        set: (next: boolean) => {
            employee.value.status = next
                ? loadedStatus.value === "inactive"
                    ? "active"
                    : loadedStatus.value
                : "inactive";
        },
    });

    function dismissSlip() {
        employeeSlip.value = null;
        options.onSaved?.(savedEmployee.value ?? undefined);
    }

    const filteredModules = computed(() => {
        const query = moduleSearch.value.trim().toLowerCase();
        if (!query) return modules.value;
        return modules.value.filter((m) =>
            m.module_name.toLowerCase().includes(query),
        );
    });

    function grantedActions(moduleId: number): PermissionActionKey[] {
        return permissions.value[moduleId] ?? [];
    }

    function hasAction(moduleId: number, action: PermissionActionKey) {
        return grantedActions(moduleId).includes(action);
    }

    const allPermissionsEnabled = computed(
        () =>
            modules.value.length > 0 &&
            modules.value.every((m) =>
                hasAction(m.module_id, PermissionAction.Read),
            ),
    );

    const enabledPermissionCount = computed(
        () =>
            modules.value.filter((m) =>
                hasAction(m.module_id, PermissionAction.Read),
            ).length,
    );

    function toggleAllPermissions() {
        const next = !allPermissionsEnabled.value;

        modules.value.forEach((m) => {
            permissions.value[m.module_id] = next
                ? [...moduleActions(m.module_name)]
                : [];
        });
    }

    function toggleModule(moduleId: number) {
        const module = modules.value.find((m) => m.module_id === moduleId);

        permissions.value[moduleId] = hasAction(moduleId, PermissionAction.Read)
            ? []
            : [...moduleActions(module?.module_name ?? "")];
    }

    function applyRoleDefaults(role: string) {
        if (!modules.value.length) return;

        const roleDefaults = ROLE_DEFAULT_PERMISSIONS[role] ?? {};

        modules.value.forEach((module) => {
            const actions = (roleDefaults as Record<string, PermissionActionKey[]>)[
                module.module_name
            ] ?? [];

            permissions.value[module.module_id] = actions.filter((action) =>
                moduleActions(module.module_name).includes(action),
            );
        });
    }

    watch(
        () => employee.value.role_name,
        (role) => {
            if (hasExistingEmployee.value || !role) return;
            applyRoleDefaults(role);
        },
    );

    function toggleAction(moduleId: number, action: PermissionActionKey) {
        const current = grantedActions(moduleId);

        if (current.includes(action)) {
            permissions.value[moduleId] = current.filter(
                (granted) => granted !== action,
            );

            // Read is the switch for the whole module: without it nothing else applies.
            if (action === PermissionAction.Read) {
                permissions.value[moduleId] = [];
            }

            return;
        }

        permissions.value[moduleId] = [...current, action];
    }

    async function loadModules() {
        modulesLoading.value = true;
        modulesError.value = null;

        try {
            const res = await moduleService.list();
            modules.value = res.data ?? res;

            modules.value.forEach((m) => {
                permissions.value[m.module_id] = [];
            });
        } catch (err) {
            modulesError.value = "Failed to load modules. Please try again.";
            console.error(err);
        } finally {
            modulesLoading.value = false;
        }
    }

    function loadEmployee() {
        const current = options.employee();
        if (!current) return;

        const status = (current.status as EmployeeStatus) || "active";

        loadedStatus.value = status;

        employee.value = {
            ...createEmployee(),
            ...current,
            status,
            phone_number: current.phone_number ?? "",
            documents: (current.documents ?? []).map((doc) => ({
                label: doc.label,
                url: doc.url ?? null,
                file: null,
            })),
            location: {
                ...createEmployee().location,
                ...(current.location ?? {}),
            },
        };

        current.permissions?.forEach((p) => {
            const module = modules.value.find(
                (m) => m.module_name === p.module_name,
            );

            if (!module) return;

            permissions.value[module.module_id] = [...(p.actions ?? [])];
        });
    }

    function onFileSelected(e: Event) {
        const file = (e.target as HTMLInputElement).files?.[0];

        if (file) {
            employee.value.avatar = file;
        }
    }

    function removePhoto(fileInput?: HTMLInputElement | null) {
        employee.value.avatar = "";

        if (fileInput) {
            fileInput.value = "";
        }
    }

    function addDocument() {
        employee.value.documents.push({ label: "", file: null, url: null });
    }

    function removeDocument(index: number) {
        employee.value.documents.splice(index, 1);
    }

    function onDocumentFileSelected(index: number, e: Event) {
        const file = (e.target as HTMLInputElement).files?.[0];
        const document = employee.value.documents[index];

        if (file && document) {
            document.file = file;
        }
    }

    const avatarPreview = computed(() => {
        if (!employee.value.avatar) return "";

        if (employee.value.avatar instanceof File) {
            return URL.createObjectURL(employee.value.avatar);
        }

        return employee.value.avatar;
    });

    const initials = computed(() => {
        const first = employee.value.first_name?.[0] ?? "";
        const last = employee.value.last_name?.[0] ?? "";
        return (first + last).toUpperCase();
    });

    function validate() {
        const result = employeeSchema.safeParse(employee.value);

        if (result.success) {
            errors.value = {};
            return true;
        }

        const fieldErrors: Record<string, string> = {};

        result.error.issues.forEach((issue) => {
            const path = issue.path.join(".");

            if (!fieldErrors[path]) {
                fieldErrors[path] = issue.message;
            }
        });

        errors.value = fieldErrors;

        return false;
    }

    async function saveEmployee() {
        if (!validate()) {
            return false;
        }

        const permissionPayload = Object.entries(permissions.value)
            .filter(([, actions]) => actions.length)
            .map(([moduleId, actions]) => ({
                module_id: Number(moduleId),
                actions,
            }));

        const payload = {
            ...employee.value,
            documents: employee.value.documents.filter(
                (doc) => doc.label.trim() && (doc.file || doc.url),
            ),
            permissions: permissionPayload,
            type: "employee",
            branch_uuid: uuid,
        };

        saving.value = true;

        try {
            let res: any = null;
            const current = options.employee();

            if (isEditMode.value && current?.uuid) {
                res = await employeeService.update(current.uuid, payload);
            } else {
                res = await employeeService.create(payload);
            }

            success(res.message);

            if (user.value?.uuid === current?.uuid) {
                await fetchAuthUser();
            }

            savedEmployee.value = (res?.employee?.data ??
                res?.employee ??
                null) as Employee | null;

            const slip = res?.data?.access ? (res.data as EmployeeSlip) : null;

            if (slip) {
                employeeSlip.value = slip;
            } else {
                options.onSaved?.(savedEmployee.value ?? undefined);
            }

            return true;
        } catch (err: any) {
            error(
                err?.data?.message || err?.message || "Internal Server Error",
            );
            console.error(err);
            return false;
        } finally {
            saving.value = false;
        }
    }

    async function init() {
        await loadModules();

        if (hasExistingEmployee.value) {
            loadEmployee();
        } else {
            applyRoleDefaults(employee.value.role_name);
        }
    }

    const pageTitle = computed(() => {
        if (isViewMode.value) return "View Employee";
        if (isEditMode.value) return "Edit Employee";
        return "Add Employee";
    });

    const pageSubtitle = computed(() => {
        if (isViewMode.value)
            return "Review this employee's details and system permissions.";
        if (isEditMode.value)
            return "Update the employee details and system permissions.";
        return "Fill in the employee details and assign system permissions.";
    });

    return {
        isViewMode,
        isEditMode,
        hasExistingEmployee,
        employee,
        errors,
        initialLoading,
        initialLoadError,
        saving,
        modules,
        modulesLoading,
        modulesError,
        moduleSearch,
        filteredModules,
        permissions,
        hasAction,
        grantedActions,
        allPermissionsEnabled,
        enabledPermissionCount,
        toggleAllPermissions,
        toggleModule,
        toggleAction,
        loadModules,
        loadEmployee,
        onFileSelected,
        removePhoto,
        addDocument,
        removeDocument,
        onDocumentFileSelected,
        avatarPreview,
        initials,
        validate,
        saveEmployee,
        employeeSlip,
        dismissSlip,
        isActive,
        loadedStatus,
        init,
        pageTitle,
        pageSubtitle,
    };
}
