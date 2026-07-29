<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
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
import { employeeService } from "~/api/employee/EmployeeService";
import { useBranchStore } from "~/stores/branch";
import { useToast } from "~/composables/useToast";
import { useAuthUser } from "~/composables/useAuthUser";
import { useRoute } from "vue-router";
import {
    MoveLeft,
    Camera,
    X,
    Check,
    User,
    MapPin,
    Briefcase,
    ShieldCheck,
    Search,
} from "lucide-vue-next";

const branchStore = useBranchStore();
const user = useAuthUser();
const { success, error } = useToast();
const props = defineProps<{
    employee?: Employee | null;
    mode?: "view" | "edit";
}>();

const emit = defineEmits<{
    back: [];
    edit: [];
}>();

const isViewMode = computed(() => props.mode === "view");
const hasExistingEmployee = computed(() => !!props.employee?.uuid);
const isEditMode = computed(
    () => hasExistingEmployee.value && !isViewMode.value,
);

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
    can_approve: boolean;
};

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
            can_read: next && !!m.has_read,
            can_create: next && !!m.has_create,
            can_update: next && !!m.has_update,
            can_approve: next && !!m.has_approve,
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
    };
}

function toggleAction(moduleId: number, action: keyof PermissionSet) {
    if (!permissions.value[moduleId]) {
        permissions.value[moduleId] = {
            can_read: false,
            can_create: false,
            can_update: false,
            can_approve: false,
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
                can_approve: false,
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
            can_approve: p.can_approve ?? false,
        };
    });
}

function openFilePicker() {
    if (isViewMode.value) return;
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
            can_approve: set.can_approve,
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
        error(err?.data?.message || err?.message || "Internal Server Error");
        console.error(err);
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await loadModules();

    if (hasExistingEmployee.value) {
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

const initials = computed(() => {
    const first = employee.value.first_name?.[0] ?? "";
    const last = employee.value.last_name?.[0] ?? "";
    return (first + last).toUpperCase();
});

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
</script>

<template>
    <div class="flex w-full flex-col relative bg-slate-50/40">
        <div
            class="flex flex-wrap items-center justify-between gap-4 border-b bg-white px-8 py-6"
        >
            <div class="flex items-center gap-4">
                <div
                    v-if="isEditMode || isViewMode"
                    class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-sm font-semibold text-primary"
                >
                    <img
                        v-if="avatarPreview"
                        :src="String(avatarPreview)"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ initials || "—" }}</span>
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ pageTitle }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ pageSubtitle }}
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <button
                    v-if="isViewMode"
                    type="button"
                    @click="emit('edit')"
                    class="rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-white transition hover:opacity-90"
                >
                    Update Employee
                </button>

                <button
                    @click="emit('back')"
                    type="button"
                    class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-slate-100 hover:text-gray-900"
                >
                    <MoveLeft class="w-4 h-4" /> Back
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
                class="font-medium underline underline-offset-2"
            >
                Retry
            </button>
        </div>

        <template v-else>
            <div class="border-b bg-white px-8">
                <div class="flex gap-10">
                    <button
                        v-for="(tab, index) in tabs"
                        :key="tab.value"
                        type="button"
                        @click="activeTab = tab.value"
                        class="group relative flex items-center gap-3 py-4 text-sm font-medium transition"
                    >
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold transition-colors"
                            :class="[
                                activeTab === tab.value
                                    ? 'border-primary bg-primary text-white'
                                    : index === 0 && activeTab === 'permissions'
                                      ? 'border-primary bg-primary text-white'
                                      : 'border-slate-300 bg-white text-slate-400 group-hover:border-slate-400',
                            ]"
                        >
                            <Check
                                v-if="
                                    index === 0 && activeTab === 'permissions'
                                "
                                class="h-3.5 w-3.5"
                            />
                            <template v-else>{{ index + 1 }}</template>
                        </span>

                        <span
                            :class="
                                activeTab === tab.value
                                    ? 'text-gray-900'
                                    : 'text-gray-500 group-hover:text-gray-700'
                            "
                        >
                            {{ tab.label }}
                        </span>

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
                            class="absolute bottom-0 left-0 h-[2px] w-full rounded-full bg-primary"
                        />
                    </button>
                </div>
            </div>

            <Transition name="fade-slide" mode="out-in">
                <div
                    v-if="activeTab === 'information'"
                    key="information"
                    class="grid grid-cols-1 gap-10 p-8 lg:grid-cols-4"
                >
                    <div class="flex flex-col items-center gap-3">
                        <button
                            type="button"
                            @click="openFilePicker"
                            :disabled="isViewMode"
                            class="group relative flex h-36 w-36 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-slate-300 bg-white transition hover:border-primary hover:bg-primary/5 disabled:hover:border-slate-300 disabled:hover:bg-white"
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
                                <Camera class="h-6 w-6" />
                                <span class="text-xs font-medium"
                                    >Upload photo</span
                                >
                            </span>

                            <span
                                v-if="!isViewMode"
                                class="absolute bottom-1 right-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-white shadow-sm ring-2 ring-white transition group-hover:opacity-100"
                                :class="
                                    avatarPreview ? 'opacity-90' : 'opacity-0'
                                "
                            >
                                <Camera class="h-4 w-4" />
                            </span>
                        </button>

                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onFileSelected"
                        />

                        <button
                            v-if="employee.avatar && !isViewMode"
                            type="button"
                            @click="removePhoto"
                            class="flex items-center gap-1 text-xs font-medium text-slate-400 transition hover:text-red-500"
                        >
                            <X class="h-3 w-3" /> Remove photo
                        </button>
                        <p
                            v-else-if="!isViewMode"
                            class="max-w-[10rem] text-center text-xs text-slate-400"
                        >
                            PNG or JPG, at least 400×400px
                        </p>
                    </div>

                    <div class="space-y-10 lg:col-span-3">
                        <section class="space-y-4">
                            <h2
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                <User class="h-3.5 w-3.5" />
                                Personal Details
                            </h2>
                            <div class="grid gap-6 md:grid-cols-3">
                                <BaseInput
                                    v-model="employee.first_name"
                                    label="First Name"
                                    required
                                    :schema="employeeSchema.shape.first_name"
                                    :error="errors.first_name"
                                    :disabled="isViewMode"
                                />
                                <BaseInput
                                    v-model="employee.middle_name"
                                    label="Middle Name"
                                    :schema="employeeSchema.shape.middle_name"
                                    :error="errors.middle_name"
                                    :disabled="isViewMode"
                                />
                                <BaseInput
                                    v-model="employee.last_name"
                                    label="Last Name"
                                    required
                                    :schema="employeeSchema.shape.last_name"
                                    :error="errors.last_name"
                                    :disabled="isViewMode"
                                />
                            </div>

                            <div class="grid gap-6 md:grid-cols-3">
                                <BaseInput
                                    v-model="employee.birth_date"
                                    label="Birth Date"
                                    mode="date"
                                    :schema="employeeSchema.shape.birth_date"
                                    :error="errors.birth_date"
                                    :disabled="isViewMode"
                                    required
                                />
                                <BaseInput
                                    v-model="employee.phone_number"
                                    label="Phone Number"
                                    required
                                    :schema="employeeSchema.shape.phone_number"
                                    :disabled="isViewMode"
                                    :error="errors.phone_number"
                                />
                                <BaseInput
                                    v-model="employee.email"
                                    label="Email"
                                    mode="email"
                                    required
                                    :schema="employeeSchema.shape.email"
                                    :disabled="isViewMode"
                                    :error="errors.email"
                                />
                            </div>
                        </section>

                        <section class="space-y-4 border-t pt-8">
                            <h2
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                <MapPin class="h-3.5 w-3.5" />
                                Address
                            </h2>
                            <div class="grid gap-6">
                                <BaseInput
                                    v-model="employee.location.street"
                                    :disabled="isViewMode"
                                    label="Street Address"
                                    required
                                    placeholder="House / unit no., building, street"
                                    :schema="
                                        employeeSchema.shape.location.shape
                                            .street
                                    "
                                    :error="errors['location.street']"
                                />
                                <div class="grid gap-6 md:grid-cols-3">
                                    <BaseInput
                                        v-model="employee.location.city"
                                        :disabled="isViewMode"
                                        label="City"
                                        required
                                        :schema="
                                            employeeSchema.shape.location.shape
                                                .city
                                        "
                                        :error="errors['location.city']"
                                    />
                                    <BaseInput
                                        v-model="employee.location.province"
                                        :disabled="isViewMode"
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
                                        :disabled="isViewMode"
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
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                <Briefcase class="h-3.5 w-3.5" />
                                Employment Details
                            </h2>
                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <BaseInput
                                        v-if="
                                            employee.role_name ===
                                                'branch_owner' ||
                                            employee.role_name ===
                                                'Branch Owner'
                                        "
                                        model-value="Branch Owner"
                                        label="Position"
                                        disabled
                                    />

                                    <Combobox
                                        v-else
                                        position="top"
                                        :disabled="isViewMode"
                                        label="Position"
                                        v-model="employee.role_name"
                                        :items="employeePositions"
                                    />

                                    <p
                                        v-if="errors.position"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ errors.position }}
                                    </p>
                                </div>

                                <div>
                                    <Combobox
                                        label="Employee Assignment"
                                        position="top"
                                        v-model="employee.assignment_type"
                                        :disabled="isViewMode"
                                        :items="employeeAssignmentTypes"
                                    />
                                    <p
                                        v-if="errors.assignment_type"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ errors.assignment_type }}
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div v-else key="permissions" class="space-y-5 p-8">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-2 text-gray-700">
                            <ShieldCheck class="h-4 w-4 text-primary" />
                            <p class="text-sm">
                                Choose what this employee can access in the
                                system.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                />
                                <input
                                    v-model="moduleSearch"
                                    type="text"
                                    placeholder="Search modules..."
                                    class="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 sm:w-56"
                                />
                            </div>

                            <button
                                v-if="modules.length && !isViewMode"
                                type="button"
                                @click="toggleAllPermissions"
                                class="shrink-0 whitespace-nowrap text-sm font-medium text-primary hover:underline underline-offset-2"
                            >
                                {{
                                    allPermissionsEnabled
                                        ? "Clear all"
                                        : "Select all"
                                }}
                            </button>
                        </div>
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
                            class="font-medium underline underline-offset-2"
                        >
                            Retry
                        </button>
                    </div>

                    <div
                        v-else-if="!filteredModules.length"
                        class="rounded-xl border border-dashed border-slate-200 p-10 text-center text-sm text-slate-400"
                    >
                        No modules match “{{ moduleSearch }}”.
                    </div>

                    <div
                        v-else
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white"
                    >
                        <div
                            v-for="(module, i) in filteredModules"
                            :key="module.module_id"
                            class="flex flex-col gap-4 px-5 py-4 transition sm:flex-row sm:items-center sm:justify-between"
                            :class="[
                                i !== filteredModules.length - 1
                                    ? 'border-b border-slate-100'
                                    : '',
                                permissions[module.module_id]?.can_read
                                    ? 'bg-primary/5'
                                    : 'hover:bg-slate-50',
                            ]"
                        >
                            <div class="flex items-center gap-4">
                                <button
                                    :disabled="isViewMode"
                                    type="button"
                                    @click="toggleModule(module.module_id)"
                                    class="relative inline-flex shrink-0 items-center disabled:cursor-not-allowed"
                                    :aria-label="`Toggle access to ${module.module_name}`"
                                >
                                    <span
                                        class="h-6 w-11 rounded-full transition-colors"
                                        :class="
                                            permissions[module.module_id]
                                                ?.can_read
                                                ? 'bg-primary'
                                                : 'bg-slate-200'
                                        "
                                    />
                                    <span
                                        class="absolute left-[3px] top-[3px] h-5 w-5 rounded-full bg-white shadow transition-transform"
                                        :class="
                                            permissions[module.module_id]
                                                ?.can_read
                                                ? 'translate-x-5'
                                                : ''
                                        "
                                    />
                                </button>

                                <div>
                                    <h3
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        {{ module.module_name }}
                                    </h3>
                                    <p class="text-xs text-slate-400">
                                        {{
                                            module.description ??
                                            "Manage access to this module."
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="permissions[module.module_id]?.can_read"
                                class="flex flex-wrap gap-x-5 gap-y-2 pl-14 sm:pl-0"
                            >
                                <label
                                    v-if="module.has_read"
                                    class="flex items-center gap-2 text-sm text-gray-600"
                                >
                                    <input
                                        :disabled="isViewMode"
                                        type="checkbox"
                                        :checked="
                                            permissions[module.module_id]
                                                ?.can_read
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
                                    v-if="module.has_create"
                                    class="flex items-center gap-2 text-sm text-gray-600"
                                >
                                    <input
                                        :disabled="isViewMode"
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
                                    v-if="module.has_update"
                                    class="flex items-center gap-2 text-sm text-gray-600"
                                >
                                    <input
                                        :disabled="isViewMode"
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
                                <label
                                    v-if="module.has_approve"
                                    class="flex items-center gap-2 text-sm text-gray-600"
                                >
                                    <input
                                        :disabled="isViewMode"
                                        type="checkbox"
                                        :checked="
                                            permissions[module.module_id]
                                                ?.can_approve
                                        "
                                        @change="
                                            toggleAction(
                                                module.module_id,
                                                'can_approve',
                                            )
                                        "
                                        class="rounded border-slate-300 text-primary focus:ring-primary"
                                    />
                                    Approve
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <div
                class="sticky bottom-0 mt-auto flex items-center justify-between gap-3 border-t bg-white/95 px-8 py-5 shadow-[0_-4px_12px_-8px_rgba(0,0,0,0.15)] backdrop-blur"
            >
                <button
                    v-if="activeTab === 'permissions'"
                    type="button"
                    @click="activeTab = 'information'"
                    class="rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-medium transition hover:bg-slate-50"
                >
                    Back
                </button>
                <span v-else class="text-xs text-slate-400">
                    Step {{ activeTab === "information" ? 1 : 2 }} of 2
                </span>

                <div class="flex gap-3">
                    <button
                        @click="emit('back')"
                        type="button"
                        class="rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-medium transition hover:bg-slate-50"
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

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}
.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(4px);
}
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
