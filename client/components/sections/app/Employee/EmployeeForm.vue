<script setup lang="ts">
import { ref } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import PhoneInput from "~/components/ui/PhoneInput.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import {
    employeeAssignmentTypes,
    employeePositions,
    employeeSchema,
    type Employee,
} from "~/types/employee";
import Combobox from "~/components/ui/Combobox.vue";
import { useEmployeeForm } from "~/composables/useEmployeeForm";
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

const props = defineProps<{
    employee?: Employee | null;
    mode?: "view" | "edit";
}>();

const emit = defineEmits<{
    back: [];
    edit: [];
}>();

const {
    isViewMode,
    isEditMode,
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
    removePhoto: removePhotoState,
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
} = useEmployeeForm({
    employee: () => props.employee,
    mode: () => props.mode,
    onSaved: () => emit("back"),
});

const activeTab = ref("information");
const fileInput = ref<HTMLInputElement | null>(null);
const todayStr = new Date().toISOString().split("T")[0];

const tabs = [
    { label: "Employee Information", value: "information" },
    { label: "Permissions", value: "permissions" },
];

function openFilePicker() {
    if (isViewMode.value) return;
    fileInput.value?.click();
}

function removePhoto() {
    removePhotoState(fileInput.value);
}

function goToNext() {
    if (validate()) {
        activeTab.value = "permissions";
    }
}

async function handleSave() {
    const ok = await saveEmployee();
    if (!ok) {
        activeTab.value = "information";
    }
}

init();
</script>

<template>
    <div class="flex w-full flex-col relative bg-slate-50/40 dark:bg-secondary">
        <div
            class="flex flex-wrap items-center justify-between gap-4 border-b bg-white px-8 py-6 dark:border-white/10 dark:bg-secondary"
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
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ pageTitle }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
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
                    class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-slate-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white"
                >
                    <MoveLeft class="w-4 h-4" /> Back
                </button>
            </div>
        </div>

        <div
            v-if="initialLoading"
            class="flex flex-1 items-center justify-center py-24 text-sm text-slate-400 dark:text-gray-500"
        >
            Loading employee...
        </div>

        <div
            v-else-if="initialLoadError"
            class="mx-8 mt-6 flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400"
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
            <div class="border-b bg-white px-8 dark:border-white/10 dark:bg-secondary">
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
                                      : 'border-slate-300 bg-white text-slate-400 group-hover:border-slate-400 dark:border-white/20 dark:bg-secondary dark:text-gray-500 dark:group-hover:border-white/40',
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
                                    ? 'text-gray-900 dark:text-white'
                                    : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-200'
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
                            class="group relative flex h-36 w-36 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-slate-300 bg-white transition hover:border-primary hover:bg-primary/5 disabled:hover:border-slate-300 disabled:hover:bg-white dark:border-white/20 dark:bg-secondary dark:hover:bg-primary-500/10 dark:disabled:hover:border-white/20 dark:disabled:hover:bg-secondary"
                        >
                            <img
                                v-if="avatarPreview"
                                :src="String(avatarPreview)"
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="flex flex-col items-center gap-2 text-slate-400 group-hover:text-primary dark:text-gray-500"
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
                            class="flex items-center gap-1 text-xs font-medium text-slate-400 transition hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"
                        >
                            <X class="h-3 w-3" /> Remove photo
                        </button>
                        <p
                            v-else-if="!isViewMode"
                            class="max-w-[10rem] text-center text-xs text-slate-400 dark:text-gray-500"
                        >
                            PNG or JPG, at least 400×400px
                        </p>

                        <div class="w-full space-y-2 border-t pt-4 dark:border-white/10">
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                >
                                    Documents
                                </p>

                                <button
                                    v-if="!isViewMode"
                                    type="button"
                                    @click="addDocument"
                                    class="text-xs font-medium text-primary hover:underline"
                                >
                                    + Add file
                                </button>
                            </div>

                            <p
                                v-if="!employee.documents.length"
                                class="text-xs text-slate-400 dark:text-gray-500"
                            >
                                No files attached.
                            </p>

                            <div
                                v-for="(doc, index) in employee.documents"
                                :key="index"
                                class="space-y-1.5 rounded-lg border border-slate-200 p-2.5 dark:border-white/10"
                            >
                                <BaseInput
                                    v-model="doc.label"
                                    placeholder="Label (e.g. Resume, Valid ID)"
                                    :disabled="isViewMode"
                                />

                                <div class="flex items-center gap-2">
                                    <label
                                        class="flex-1 truncate rounded-md border border-dashed border-slate-300 px-2 py-1.5 text-center text-xs text-slate-500 dark:border-white/20 dark:text-gray-400"
                                        :class="
                                            isViewMode
                                                ? 'cursor-default'
                                                : 'cursor-pointer hover:border-primary hover:text-primary'
                                        "
                                    >
                                        {{
                                            doc.file?.name ||
                                            (doc.url
                                                ? "File attached"
                                                : "Choose file")
                                        }}
                                        <input
                                            type="file"
                                            class="hidden"
                                            :disabled="isViewMode"
                                            @change="
                                                onDocumentFileSelected(
                                                    index,
                                                    $event,
                                                )
                                            "
                                        />
                                    </label>

                                    <a
                                        v-if="doc.url"
                                        :href="doc.url"
                                        target="_blank"
                                        rel="noopener"
                                        class="shrink-0 text-xs font-medium text-primary hover:underline"
                                    >
                                        View
                                    </a>

                                    <button
                                        v-if="!isViewMode"
                                        type="button"
                                        @click="removeDocument(index)"
                                        class="shrink-0 text-slate-400 transition hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-10 lg:col-span-3">
                        <section class="space-y-4">
                            <h2
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
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
                                <DatePickerField
                                    v-model="employee.birth_date"
                                    label="Birth Date"
                                    :max="todayStr"
                                    :default-to-today="false"
                                    placeholder="Select date of birth"
                                    :error="errors.birth_date"
                                    :disabled="isViewMode"
                                    required
                                />
                                <PhoneInput
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

                        <section class="space-y-4 border-t pt-8 dark:border-white/10">
                            <h2
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
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

                        <section class="space-y-4 border-t pt-8 dark:border-white/10">
                            <h2
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
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
                                        class="mt-1 text-xs text-red-500 dark:text-red-400"
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
                                        class="mt-1 text-xs text-red-500 dark:text-red-400"
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
                        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <ShieldCheck class="h-4 w-4 text-primary" />
                            <p class="text-sm">
                                Choose what this employee can access in the
                                system.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
                                />
                                <input
                                    v-model="moduleSearch"
                                    type="text"
                                    placeholder="Search modules..."
                                    class="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 sm:w-56 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
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
                        class="py-10 text-center text-sm text-slate-400 dark:text-gray-500"
                    >
                        Loading modules...
                    </div>

                    <div
                        v-else-if="modulesError"
                        class="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400"
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
                        class="rounded-xl border border-dashed border-slate-200 p-10 text-center text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
                    >
                        No modules match “{{ moduleSearch }}”.
                    </div>

                    <div
                        v-else
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-white/10 dark:bg-secondary"
                    >
                        <div
                            v-for="(module, i) in filteredModules"
                            :key="module.module_id"
                            class="flex flex-col gap-4 px-5 py-4 transition sm:flex-row sm:items-center sm:justify-between"
                            :class="[
                                i !== filteredModules.length - 1
                                    ? 'border-b border-slate-100 dark:border-white/10'
                                    : '',
                                permissions[module.module_id]?.can_read
                                    ? 'bg-primary/5'
                                    : 'hover:bg-slate-50 dark:hover:bg-white/5',
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
                                                : 'bg-slate-200 dark:bg-white/10'
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
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ module.module_name }}
                                    </h3>
                                    <p class="text-xs text-slate-400 dark:text-gray-500">
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
                                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
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
                                        class="rounded border-slate-300 text-primary focus:ring-primary dark:border-white/20 dark:bg-white/5"
                                    />
                                    Read
                                </label>
                                <label
                                    v-if="module.has_create"
                                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
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
                                        class="rounded border-slate-300 text-primary focus:ring-primary dark:border-white/20 dark:bg-white/5"
                                    />
                                    Create
                                </label>
                                <label
                                    v-if="module.has_update"
                                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
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
                                        class="rounded border-slate-300 text-primary focus:ring-primary dark:border-white/20 dark:bg-white/5"
                                    />
                                    Update
                                </label>
                                <label
                                    v-if="module.has_approve"
                                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
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
                                        class="rounded border-slate-300 text-primary focus:ring-primary dark:border-white/20 dark:bg-white/5"
                                    />
                                    Approve
                                </label>
                                <label
                                    v-if="module.has_assign"
                                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    <input
                                        :disabled="isViewMode"
                                        type="checkbox"
                                        :checked="
                                            permissions[module.module_id]
                                                ?.can_assign
                                        "
                                        @change="
                                            toggleAction(
                                                module.module_id,
                                                'can_assign',
                                            )
                                        "
                                        class="rounded border-slate-300 text-primary focus:ring-primary dark:border-white/20 dark:bg-white/5"
                                    />
                                    Assign
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <div
                class="sticky bottom-0 mt-auto flex items-center justify-between gap-3 border-t bg-white/95 px-8 py-5 shadow-[0_-4px_12px_-8px_rgba(0,0,0,0.15)] backdrop-blur dark:border-white/10 dark:bg-secondary/95"
            >
                <button
                    v-if="activeTab === 'permissions'"
                    type="button"
                    @click="activeTab = 'information'"
                    class="rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                >
                    Back
                </button>
                <span v-else class="text-xs text-slate-400 dark:text-gray-500">
                    Step {{ activeTab === "information" ? 1 : 2 }} of 2
                </span>

                <div class="flex gap-3">
                    <button
                        @click="emit('back')"
                        type="button"
                        class="rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
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
                        @click="handleSave"
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
