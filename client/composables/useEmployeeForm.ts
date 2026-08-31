import { computed, ref, watch } from "vue";
import { useRoute } from "vue-router";
import {
    createEmployee,
    type Employee,
    employeeSchema,
    type EmployeePayload,
} from "~/types/employee";
import { moduleService } from "~/api/module/ModuleService";
import { Modules, type Module } from "~/types/module";
import { employeeService } from "~/api/employee/EmployeeService";
import { useToast } from "~/composables/useToast";
import { fetchAuthUser } from "~/composables/useAuthUser";

export type PermissionSet = {
    can_read: boolean;
    can_create: boolean;
    can_update: boolean;
    can_approve: boolean;
    can_assign: boolean;
};

type ActionKey = keyof PermissionSet;

const ALL_ACTIONS: ActionKey[] = [
    "can_read",
    "can_create",
    "can_update",
    "can_approve",
    "can_assign",
];
const ADD_UPDATE_READ: ActionKey[] = ["can_read", "can_create", "can_update"];
const READ_ONLY: ActionKey[] = ["can_read"];

// Default module access checked in when a position is picked for a new
// employee — matches the org's standing role/permission policy. Only
// applied while creating a new employee; an existing employee's saved
// permissions are never overwritten by this.
const ROLE_DEFAULT_PERMISSIONS: Record<
    string,
    Partial<Record<Modules, ActionKey[]>>
> = {
    administrator: {
        [Modules.RoomsAndBeds]: ADD_UPDATE_READ,
        [Modules.Contracts]: ADD_UPDATE_READ,
        [Modules.Services]: ADD_UPDATE_READ,
        [Modules.ManageBranches]: READ_ONLY,
        [Modules.BranchSettings]: READ_ONLY,
        [Modules.EmployeeManagement]: READ_ONLY,
    },
    admission: {
        [Modules.Patients]: ALL_ACTIONS,
        [Modules.Admissions]: ALL_ACTIONS,
        [Modules.Bookings]: ALL_ACTIONS,
        [Modules.Schedules]: ALL_ACTIONS,
        [Modules.Services]: READ_ONLY,
        [Modules.Contracts]: READ_ONLY,
        [Modules.RoomsAndBeds]: READ_ONLY,
        [Modules.EmployeeManagement]: READ_ONLY,
    },
    accounting: {
        [Modules.BillingAndInvoices]: ALL_ACTIONS,
        [Modules.Patients]: READ_ONLY,
    },
    nurse: {
        [Modules.Patients]: ADD_UPDATE_READ,
        [Modules.Schedules]: READ_ONLY,
    },
    caregiver: {
        [Modules.Patients]: ADD_UPDATE_READ,
        [Modules.Schedules]: READ_ONLY,
    },
};

export interface UseEmployeeFormOptions {
    employee: () => Employee | null | undefined;
    mode: () => "view" | "edit" | undefined;
    onSaved?: () => void;
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

    const filteredModules = computed(() => {
        const query = moduleSearch.value.trim().toLowerCase();
        if (!query) return modules.value;
        return modules.value.filter((m) =>
            m.module_name.toLowerCase().includes(query),
        );
    });

    const allPermissionsEnabled = computed(
        () =>
            modules.value.length > 0 &&
            modules.value.every(
                (m) => permissions.value[m.module_id]?.can_read,
            ),
    );

    const enabledPermissionCount = computed(
        () =>
            modules.value.filter(
                (m) => permissions.value[m.module_id]?.can_read,
            ).length,
    );

    function toggleAllPermissions() {
        const next = !allPermissionsEnabled.value;

        modules.value.forEach((m) => {
            permissions.value[m.module_id] = {
                can_read: next && !!m.has_read,
                can_create: next && !!m.has_create,
                can_update: next && !!m.has_update,
                can_approve: next && !!m.has_approve,
                can_assign: next && !!m.has_assign,
            };
        });
    }

    function toggleModule(moduleId: number) {
        const module = modules.value.find((m) => m.module_id === moduleId);
        const next = !(permissions.value[moduleId]?.can_read ?? false);

        permissions.value[moduleId] = {
            can_read: next,
            can_create: next && !!module?.has_create,
            can_update: next && !!module?.has_update,
            can_approve: next && !!module?.has_approve,
            can_assign: next && !!module?.has_assign,
        };
    }

    function applyRoleDefaults(role: string) {
        if (!modules.value.length) return;

        const roleDefaults = ROLE_DEFAULT_PERMISSIONS[role] ?? {};

        modules.value.forEach((module) => {
            const actions =
                roleDefaults[module.module_name as Modules] ?? [];

            permissions.value[module.module_id] = {
                can_read: actions.includes("can_read") && !!module.has_read,
                can_create:
                    actions.includes("can_create") && !!module.has_create,
                can_update:
                    actions.includes("can_update") && !!module.has_update,
                can_approve:
                    actions.includes("can_approve") && !!module.has_approve,
                can_assign:
                    actions.includes("can_assign") && !!module.has_assign,
            };
        });
    }

    watch(
        () => employee.value.role_name,
        (role) => {
            if (hasExistingEmployee.value || !role) return;
            applyRoleDefaults(role);
        },
    );

    function toggleAction(moduleId: number, action: keyof PermissionSet) {
        if (!permissions.value[moduleId]) {
            permissions.value[moduleId] = {
                can_read: false,
                can_create: false,
                can_update: false,
                can_approve: false,
                can_assign: false,
            };
        }

        permissions.value[moduleId][action] =
            !permissions.value[moduleId][action];
    }

    async function loadModules() {
        modulesLoading.value = true;
        modulesError.value = null;

        try {
            const res = await moduleService.list();
            modules.value = res.data ?? res;

            modules.value.forEach((m) => {
                permissions.value[m.module_id] = {
                    can_read: false,
                    can_create: false,
                    can_update: false,
                    can_approve: false,
                    can_assign: false,
                };
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

        employee.value = {
            ...createEmployee(),
            ...current,
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

            permissions.value[module.module_id] = {
                can_read: p.can_read,
                can_create: p.can_create,
                can_update: p.can_update,
                can_approve: p.can_approve ?? false,
                can_assign: p.can_assign ?? false,
            };
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

        if (file) {
            employee.value.documents[index].file = file;
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

        const permissionPayload = Object.entries(permissions.value).map(
            ([moduleId, set]) => ({
                module_id: Number(moduleId),
                can_read: set.can_read,
                can_create: set.can_create,
                can_update: set.can_update,
                can_approve: set.can_approve,
                can_assign: set.can_assign,
            }),
        );

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
            options.onSaved?.();

            if (user.value?.uuid === current?.uuid) {
                await fetchAuthUser();
            }
            return true;
        } catch (err: any) {
            error(err?.data?.message || err?.message || "Internal Server Error");
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
        init,
        pageTitle,
        pageSubtitle,
    };
}