<script setup lang="ts">
import { computed, onMounted, ref, readonly } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import {
    createEmployee,
    employeeAssignmentTypes,
    employeePositions,
    employeeSchema,
    type Employee,
    type EmployeePayload,
} from "~/types/employee";
import Combobox from "~/components/ui/Combobox.vue";
import { moduleService } from "~/api/module/ModuleService";
import type { Module } from "~/types/module";
import { useRoute } from "vue-router";
import { employeeService } from "~/api/employee/EmployeeService";
import { useToast } from "~/composables/useToast";
import { useBranchStore } from "~/stores/branch";
import { useAuthUser } from "~/composables/useAuthUser";
const branchStore = useBranchStore();
const user = useAuthUser();
const { success, error } = useToast();
const emit = defineEmits<{ back: [] }>();

const props = defineProps<{
    employee?: Employee | null;
}>();

const isEditMode = computed(() => !!props.employee?.uuid);

const activeTab = ref("information");
const employee = ref<EmployeePayload>(createEmployee());
const fileInput = ref<HTMLInputElement | null>(null);
const errors = ref<Record<string, string>>({});

const initialLoading = ref(false);
const initialLoadError = ref<string | null>(null);
const saving = ref(false);

const tabs = [
    { label: "Employee Information", value: "information" },
    { label: "Permissions", value: "permissions" },
];

type PermissionSet = {
    can_read: boolean;
    can_create: boolean;
    can_update: boolean;
};

const modules = ref<Module[]>([]);
const modulesLoading = ref(false);
const modulesError = ref<string | null>(null);

const permissions = ref<Record<number, PermissionSet>>({});

const allPermissionsEnabled = computed(
    () =>
        modules.value.length > 0 &&
        modules.value.every((m) => permissions.value[m.module_id]?.can_read),
);

const enabledPermissionCount = computed(
    () =>
        modules.value.filter((m) => permissions.value[m.module_id]?.can_read)
            .length,
);

function toggleAllPermissions() {
    const next = !allPermissionsEnabled.value;

    modules.value.forEach((m) => {
        permissions.value[m.module_id] = {
            can_read: next,
            can_create: next,
            can_update: next,
        };
    });
}

function toggleModule(moduleId: number) {
    const next = !(permissions.value[moduleId]?.can_read ?? false);

    permissions.value[moduleId] = {
        can_read: next,
        can_create: next,
        can_update: next,
    };
}

function toggleAction(moduleId: number, action: keyof PermissionSet) {
    if (!permissions.value[moduleId]) {
        permissions.value[moduleId] = {
            can_read: false,
            can_create: false,
            can_update: false,
        };
    }

    permissions.value[moduleId][action] = !permissions.value[moduleId][action];
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
    if (!props.employee) return;

    employee.value = {
        ...createEmployee(),
        ...props.employee,
        phone_number: props.employee.phone_number ?? "",
        location: {
            ...createEmployee().location,
            ...(props.employee.location ?? {}),
        },
    };

    props.employee.permissions?.forEach((p) => {
        const module = modules.value.find(
            (m) => m.module_name === p.module_name,
        );

        if (!module) return;

        permissions.value[module.module_id] = {
            can_read: p.can_read,
            can_create: p.can_create,
            can_update: p.can_update,
        };
    });
}

function openFilePicker() {
    fileInput.value?.click();
}

function onFileSelected(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (file) {
        employee.value.avatar = file;
    }
}

function removePhoto() {
    employee.value.avatar = "";

    if (fileInput.value) {
        fileInput.value.value = "";
    }
}

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

function goToNext() {
    if (validate()) {
        activeTab.value = "permissions";
    }
}

const route = useRoute();
const uuid = route.params.uuid as string;

async function saveEmployee() {
    if (!validate()) {
        activeTab.value = "information";
        return;
    }

    const permissionPayload = Object.entries(permissions.value).map(
        ([moduleId, set]) => ({
            module_id: Number(moduleId),
            can_read: set.can_read,
            can_create: set.can_create,
            can_update: set.can_update,
        }),
    );

    const payload = {
        ...employee.value,
        permissions: permissionPayload,
        type: "employee",
        branch_uuid: uuid,
    };

    saving.value = true;

    try {
        let res: any = null;
        if (isEditMode.value && props.employee?.uuid) {
            res = await employeeService.update(props.employee.uuid, payload);
        } else {
            res = await employeeService.create(payload);
        }
        success(res.message);
        emit("back");
        if (user.value?.uuid === props.employee?.uuid) {
            await branchStore.fetchBranches();
        }
    } catch (err: any) {
        success(err?.data?.message || err?.message || "Internal Server Error");
        console.error(err);
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await loadModules();

    if (isEditMode.value) {
        loadEmployee();
    }
});

const avatarPreview = computed(() => {
    if (!employee.value.avatar) return "";

    if (employee.value.avatar instanceof File) {
        return URL.createObjectURL(employee.value.avatar);
    }

    return employee.value.avatar;
});
import { MoveLeft } from "lucide-vue-next";
</script>

<template>
    <div class="flex w-full flex-col relative">
        <div class="flex justify-between items-center border-b">
            <div class="px-8 py-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ isEditMode ? "Edit Employee" : "Add Employee" }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{
                        isEditMode
                            ? "Update the employee details and system permissions."
                            : "Fill in the employee details and assign system permissions."
                    }}
                </p>
            </div>

            <div>
                <button
                    @click="emit('back')"
                    type="button"
                    class="px-6 py-2.5 font-medium hover:underline flex gap-2 items-center"
                >
                    <MoveLeft class="w-4" /> Back
                </button>
            </div>
        </div>

        <div
            v-if="initialLoading"
            class="flex flex-1 items-center justify-center py-24 text-sm text-slate-400"
        >
            Loading employee...
        </div>

        <div
            v-else-if="initialLoadError"
            class="mx-8 mt-6 flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600"
        >
            {{ initialLoadError }}
            <button
                type="button"
                @click="loadEmployee"
                class="font-medium underline"
            >
                Retry
            </button>
        </div>

        <template v-else>
            <div class="border-b px-8">
                <div class="flex gap-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        @click="activeTab = tab.value"
                        class="relative flex items-center gap-2 py-4 text-sm font-medium transition"
                        :class="
                            activeTab === tab.value
                                ? 'text-primary'
                                : 'text-gray-500 hover:text-gray-700'
                        "
                    >
                        {{ tab.label }}
                        <span
                            v-if="
                                tab.value === 'permissions' &&
                                enabledPermissionCount > 0
                            "
                            class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary"
                        >
                            {{ enabledPermissionCount }}
                        </span>
                        <span
                            v-if="activeTab === tab.value"
                            class="absolute bottom-0 left-0 h-[3px] w-full rounded-full bg-primary"
                        />
                    </button>
                </div>
            </div>

            <div
                v-if="activeTab === 'information'"
                class="grid grid-cols-1 gap-10 p-8 lg:grid-cols-4"
            >
                <div class="flex flex-col items-center gap-3">
                    <button
                        type="button"
                        @click="openFilePicker"
                        class="group relative flex h-40 w-40 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-slate-300 bg-slate-50 transition hover:border-primary hover:bg-primary/5"
                    >
                        <img
                            v-if="avatarPreview"
                            :src="String(avatarPreview)"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-else
                            class="flex flex-col items-center gap-2 text-slate-400 group-hover:text-primary"
                        >
                            <svg
                                class="h-7 w-7"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    d="M4 17.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1.5M12 15V4m0 0 4 4m-4-4-4 4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <span class="text-xs font-medium"
                                >Upload photo</span
                            >
                        </span>
                        <div
                            v-if="employee.avatar"
                            class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition group-hover:opacity-100"
                        >
                            <span class="text-xs font-medium text-white"
                                >Change photo</span
                            >
                        </div>
                    </button>

                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="onFileSelected"
                    />

                    <button
                        v-if="employee.avatar"
                        type="button"
                        @click="removePhoto"
                        class="text-xs font-medium text-slate-400 hover:text-red-500"
                    >
                        Remove photo
                    </button>
                    <p
                        v-else
                        class="max-w-[10rem] text-center text-xs text-slate-400"
                    >
                        PNG or JPG, at least 400×400px
                    </p>
                </div>

                <div class="space-y-10 lg:col-span-3">
                    <section class="space-y-4">
                        <h2
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Personal Details
                        </h2>
                        <div class="grid gap-6 md:grid-cols-3">
                            <BaseInput
                                v-model="employee.first_name"
                                label="First Name"
                                required
                                :schema="employeeSchema.shape.first_name"
                                :error="errors.first_name"
                            />
                            <BaseInput
                                v-model="employee.middle_name"
                                label="Middle Name"
                                :schema="employeeSchema.shape.middle_name"
                                :error="errors.middle_name"
                            />
                            <BaseInput
                                v-model="employee.last_name"
                                label="Last Name"
                                required
                                :schema="employeeSchema.shape.last_name"
                                :error="errors.last_name"
                            />
                        </div>
                        <div class="grid gap-6 md:grid-cols-3">
                            <BaseInput
                                v-model="employee.birth_date"
                                label="Birth Date"
                                mode="date"
                                :schema="employeeSchema.shape.birth_date"
                                :error="errors.birth_date"
                                required
                            />
                            <BaseInput
                                v-model="employee.phone_number"
                                label="Phone Number"
                                required
                                :schema="employeeSchema.shape.phone_number"
                                :error="errors.phone_number"
                            />
                            <BaseInput
                                v-model="employee.email"
                                label="Email"
                                mode="email"
                                required
                                :schema="employeeSchema.shape.email"
                                :error="errors.email"
                            />
                        </div>
                    </section>

                    <section class="space-y-4 border-t pt-8">
                        <h2
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Address
                        </h2>
                        <div class="grid gap-6">
                            <BaseInput
                                v-model="employee.location.street"
                                label="Street Address"
                                required
                                placeholder="House / unit no., building, street"
                                :schema="
                                    employeeSchema.shape.location.shape.street
                                "
                                :error="errors['location.street']"
                            />
                            <div class="grid gap-6 md:grid-cols-3">
                                <BaseInput
                                    v-model="employee.location.city"
                                    label="City"
                                    required
                                    :schema="
                                        employeeSchema.shape.location.shape.city
                                    "
                                    :error="errors['location.city']"
                                />
                                <BaseInput
                                    v-model="employee.location.province"
                                    label="Province"
                                    required
                                    :schema="
                                        employeeSchema.shape.location.shape
                                            .province
                                    "
                                    :error="errors['location.province']"
                                />
                                <BaseInput
                                    v-model="employee.location.country"
                                    label="Country"
                                    required
                                    :schema="
                                        employeeSchema.shape.location.shape
                                            .country
                                    "
                                    :error="errors['location.country']"
                                />
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4 border-t pt-8">
                        <h2
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Employment Details
                        </h2>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <BaseInput
                                    v-if="employee.role_name === 'branch_owner'"
                                    model-value="Branch Owner"
                                    label="Position"
                                    disabled
                                />

                                <Combobox
                                    v-else
                                    label="Position"
                                    v-model="employee.role_name"
                                    :items="employeePositions"
                                />

                                <p
                                    v-if="errors.position"
                                    class="text-xs text-red-500 mt-0.5"
                                >
                                    {{ errors.position }}
                                </p>
                            </div>

                            <div>
                                <Combobox
                                    label="Employee Assignment"
                                    v-model="employee.assignment_type"
                                    :items="employeeAssignmentTypes"
                                />
                                <p
                                    v-if="errors.assignment_type"
                                    class="text-xs text-red-500 mt-0.5"
                                >
                                    {{ errors.assignment_type }}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div v-else class="space-y-6 p-8">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Choose what this employee can access in the system.
                    </p>
                    <button
                        v-if="modules.length"
                        type="button"
                        @click="toggleAllPermissions"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        {{ allPermissionsEnabled ? "Clear all" : "Select all" }}
                    </button>
                </div>

                <div
                    v-if="modulesLoading"
                    class="py-10 text-center text-sm text-slate-400"
                >
                    Loading modules...
                </div>

                <div
                    v-else-if="modulesError"
                    class="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600"
                >
                    {{ modulesError }}
                    <button
                        type="button"
                        @click="loadModules"
                        class="font-medium underline"
                    >
                        Retry
                    </button>
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2">
                    <div
                        v-for="module in modules"
                        :key="module.module_id"
                        class="rounded-xl border p-5 transition"
                        :class="
                            permissions[module.module_id]?.can_read
                                ? 'border-primary/40 bg-primary/5'
                                : 'border-slate-200 hover:border-slate-300'
                        "
                    >
                        <div class="flex items-start justify-between gap-4">
                            <h3 class="font-semibold text-gray-900">
                                {{ module.module_name }}
                                <p
                                    class="text-xs font-normal text-muted mt-0.5"
                                >
                                    Manage access to this module.
                                </p>
                            </h3>
                            <button
                                type="button"
                                @click="toggleModule(module.module_id)"
                                class="relative inline-flex shrink-0 items-center"
                                :aria-label="`Toggle access to ${module.module_name}`"
                            >
                                <span
                                    class="h-6 w-11 rounded-full transition-colors"
                                    :class="
                                        permissions[module.module_id]?.can_read
                                            ? 'bg-primary'
                                            : 'bg-slate-200'
                                    "
                                />
                                <span
                                    class="absolute left-[3px] top-[3px] h-5 w-5 rounded-full bg-white shadow transition-transform"
                                    :class="
                                        permissions[module.module_id]?.can_read
                                            ? 'translate-x-5'
                                            : ''
                                    "
                                />
                            </button>
                        </div>

                        <div
                            v-if="permissions[module.module_id]?.can_read"
                            class="mt-4 flex gap-4 border-t pt-4"
                        >
                            <label
                                class="flex items-center gap-2 text-sm text-gray-600"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        permissions[module.module_id]?.can_read
                                    "
                                    @change="
                                        toggleAction(
                                            module.module_id,
                                            'can_read',
                                        )
                                    "
                                    class="rounded border-slate-300 text-primary focus:ring-primary"
                                />
                                Read
                            </label>
                            <label
                                class="flex items-center gap-2 text-sm text-gray-600"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        permissions[module.module_id]
                                            ?.can_create
                                    "
                                    @change="
                                        toggleAction(
                                            module.module_id,
                                            'can_create',
                                        )
                                    "
                                    class="rounded border-slate-300 text-primary focus:ring-primary"
                                />
                                Create
                            </label>
                            <label
                                class="flex items-center gap-2 text-sm text-gray-600"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        permissions[module.module_id]
                                            ?.can_update
                                    "
                                    @change="
                                        toggleAction(
                                            module.module_id,
                                            'can_update',
                                        )
                                    "
                                    class="rounded border-slate-300 text-primary focus:ring-primary"
                                />
                                Update
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="mt-auto flex items-center justify-between gap-3 border-t px-8 py-5"
            >
                <button
                    v-if="activeTab === 'permissions'"
                    type="button"
                    @click="activeTab = 'information'"
                    class="rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-medium hover:bg-slate-50"
                >
                    Back
                </button>
                <span v-else />

                <div class="flex gap-3">
                    <button
                        @click="emit('back')"
                        type="button"
                        class="rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-medium hover:bg-slate-50"
                    >
                        Cancel
                    </button>

                    <button
                        v-if="activeTab === 'information'"
                        type="button"
                        @click="goToNext"
                        class="rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Next
                    </button>

                    <button
                        v-else
                        type="button"
                        :disabled="saving"
                        @click="saveEmployee"
                        class="rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{
                            saving
                                ? "Saving..."
                                : isEditMode
                                  ? "Save Changes"
                                  : "Save Employee"
                        }}
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>
