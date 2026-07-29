<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed top-0 left-0 right-0 bottom-0 z-[9999] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
                @click="close"
            />

            <Transition
                appear
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="open"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Assign Nurse"
                    class="relative flex h-[80vh] max-h-[85vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                >
                    <div
                        class="flex items-center justify-between gap-4 border-b border-gray-100 px-6 py-5"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <UsersRound class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <h2
                                    class="text-lg font-semibold leading-tight text-gray-900"
                                >
                                    Assign Nurse
                                </h2>

                                <p
                                    class="mt-0.5 text-xs text-gray-400 max-w-md"
                                >
                                    Select an nurse to handle this service. Only
                                    the assigned nurse will be responsible for
                                    processing requests related to this service.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="close"
                            aria-label="Close dialog"
                            class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div
                        class="mx-6 mt-4 rounded-xl border border-primary/20 bg-primary/5 px-4 py-3"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-primary"
                                >
                                    Selected Service
                                </p>

                                <h3
                                    class="mt-1 truncate text-base font-semibold text-gray-900"
                                >
                                    {{ service?.service_name }}
                                </h3>
                            </div>

                            <span
                                class="shrink-0 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                :class="serviceTypeBadgeClass"
                            >
                                {{ service?.type_formatted ?? "Service" }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 px-6 py-3">
                        <div class="relative flex-1">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            />

                            <BaseInput
                                v-model="searchQuery"
                                mode="text"
                                placeholder="Search nurse..."
                                inputClass="pl-[2.3rem]"
                            />
                        </div>
                    </div>

                    <div
                        v-if="!loading && employeeData.length"
                        class="flex flex-wrap items-center justify-between gap-2 border-y border-gray-100 bg-gray-50/60 px-6 py-2.5"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                            @click="toggleSelectAll"
                        >
                            <CheckCheck class="h-3.5 w-3.5" />
                            {{ allSelected ? "Unselect all" : "Select all" }}
                        </button>

                        <div class="flex items-center gap-2">
                            <span
                                class="hidden sm:inline rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-500 ring-1 ring-gray-200"
                            >
                                {{ employeeGroupLabel }}
                            </span>

                            <div
                                class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-white p-0.5"
                            >
                                <button
                                    type="button"
                                    @click="viewFilter = 'unassigned'"
                                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition"
                                    :class="
                                        viewFilter === 'unassigned'
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-slate-500 hover:text-slate-700'
                                    "
                                >
                                    Unassigned
                                    <span
                                        class="rounded-full px-1.5 text-[10px] font-semibold"
                                        :class="
                                            viewFilter === 'unassigned'
                                                ? 'bg-primary/15'
                                                : 'bg-slate-100'
                                        "
                                    >
                                        {{ unassignedCount }}
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    @click="viewFilter = 'assigned'"
                                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition"
                                    :class="
                                        viewFilter === 'assigned'
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-slate-500 hover:text-slate-700'
                                    "
                                >
                                    Assigned
                                    <span
                                        class="rounded-full px-1.5 text-[10px] font-semibold"
                                        :class="
                                            viewFilter === 'assigned'
                                                ? 'bg-primary/15'
                                                : 'bg-slate-100'
                                        "
                                    >
                                        {{ assignedCount }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        <template v-if="loading">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div
                                    v-for="n in 6"
                                    :key="n"
                                    class="rounded-xl border border-gray-100 p-3.5 animate-pulse"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-11 w-11 shrink-0 rounded-full bg-gray-200"
                                        />
                                        <div class="flex-1 space-y-2">
                                            <div
                                                class="h-3 w-2/3 rounded bg-gray-200"
                                            />
                                            <div
                                                class="h-2 w-1/3 rounded bg-gray-200"
                                            />
                                        </div>
                                    </div>
                                    <div
                                        class="mt-3 h-5 w-20 rounded-full bg-gray-200"
                                    />
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div
                                v-if="filteredEmployees.length"
                                class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                            >
                                <button
                                    v-for="employee in filteredEmployees"
                                    :key="employee.employee_id"
                                    type="button"
                                    class="relative flex flex-col items-start gap-3 rounded-xl border p-3.5 text-left transition-all"
                                    :class="
                                        isSelected(employee.employee_id)
                                            ? 'border-primary/40 bg-primary/5 ring-1 ring-primary/20'
                                            : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                    "
                                    @click="
                                        toggleEmployee(employee.employee_id)
                                    "
                                >
                                    <span
                                        class="absolute right-3 top-3 flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition-colors"
                                        :class="
                                            isSelected(employee.employee_id)
                                                ? 'border-primary bg-primary text-white'
                                                : 'border-gray-300 bg-white'
                                        "
                                    >
                                        <Check
                                            v-if="
                                                isSelected(employee.employee_id)
                                            "
                                            class="h-3.5 w-3.5"
                                        />
                                    </span>

                                    <div
                                        class="flex w-full min-w-0 items-center gap-3 pr-7"
                                    >
                                        <img
                                            v-if="employee.avatar"
                                            :src="employee.avatar"
                                            alt=""
                                            class="h-11 w-11 shrink-0 rounded-full object-cover ring-1 ring-gray-100"
                                        />
                                        <span
                                            v-else
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                                        >
                                            {{ initials(employee) }}
                                        </span>

                                        <div class="min-w-0 text-left">
                                            <p
                                                class="truncate text-sm font-medium leading-tight text-gray-900"
                                            >
                                                {{ employee.first_name }}
                                                {{ employee.last_name }}
                                            </p>

                                            <p
                                                class="mt-0.5 truncate text-xs text-gray-400"
                                            >
                                                {{ employee.role_name }}
                                            </p>
                                        </div>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            assignmentBadgeClass(
                                                employee.formatted_assignment_type,
                                            )
                                        "
                                    >
                                        {{ employee.formatted_assignment_type }}
                                    </span>
                                </button>
                            </div>

                            <div
                                v-else
                                class="flex flex-col items-center justify-center py-14 text-center"
                            >
                                <div
                                    class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-50"
                                >
                                    <UserRoundSearch
                                        class="h-7 w-7 text-gray-300"
                                    />
                                </div>

                                <p class="text-sm font-semibold text-gray-600">
                                    {{ emptyStateTitle }}
                                </p>

                                <p
                                    class="mt-1 max-w-[260px] text-xs text-gray-400"
                                >
                                    {{ emptyStateSubtitle }}
                                </p>
                            </div>
                        </template>
                    </div>

                    <div
                        class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50/60 px-6 py-4"
                    >
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-300"
                            @click="close"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            :disabled="!hasChanges || loading || saving"
                            :class="[
                                'inline-flex min-w-[160px] items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-40',
                                confirmButtonVariant === 'unassign'
                                    ? 'bg-rose-600 hover:bg-rose-600/90 focus-visible:ring-2 focus-visible:ring-rose-400/40'
                                    : 'bg-primary hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-primary/40',
                            ]"
                            @click="confirm"
                        >
                            <LoaderCircle
                                v-if="saving"
                                class="h-4 w-4 animate-spin"
                            />
                            <UserMinus
                                v-else-if="confirmButtonVariant === 'unassign'"
                                class="h-4 w-4"
                            />
                            <Check v-else class="h-4 w-4" />

                            {{ confirmButtonLabel }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import {
    Check,
    CheckCheck,
    LoaderCircle,
    Search,
    UserMinus,
    UserRoundSearch,
    UsersRound,
    X,
} from "lucide-vue-next";
import { employeeService } from "~/api/employee/EmployeeService";
import BaseInput from "~/components/ui/BaseInput.vue";
import type { Employee } from "~/types/employee";
import type { Service } from "~/types/service";
import { useRoute } from "vue-router";
import { serviceService } from "~/api/service/ServiceService";
import { useToast } from "~/composables/useToast";

const { error, success } = useToast();
const props = defineProps<{
    open: boolean;
    service?: Service;
    modelValue?: (number | string)[];
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "assigned"): void;
}>();

const route = useRoute();
const employeeData = ref<Employee[]>([]);
const uuid = computed(() => route.params.uuid as string);
const loading = ref(true);
const saving = ref(false);

function isAssignedOnServer(employee: Employee) {
    return (employee.assigned ?? []).some((a: any) => a.is_assigned);
}

const initialAssignedIds = ref<string[]>([]);

async function fetchEmployees() {
    if (!props.service?.service_id) {
        employeeData.value = [];
        return;
    }

    loading.value = true;

    try {
        const res: any = await employeeService.list({
            per_page: 10,
            branch_uuid: uuid.value,
            type: "service",
            service_id: props.service.service_id,
        });
        employeeData.value = Array.isArray(res) ? res : (res.data ?? []);

        const serverAssignedIds = employeeData.value
            .filter((employee) => isAssignedOnServer(employee))
            .map((employee) => String(employee.employee_id));

        initialAssignedIds.value = serverAssignedIds;

        selectedEmployees.value = [
            ...new Set([...selectedEmployees.value, ...serverAssignedIds]),
        ];
    } catch (err: any) {
        console.error(err);
        employeeData.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    if (props.open) {
        fetchEmployees();
    }

    document.addEventListener("keydown", onKeydown, true);
});

onUnmounted(() => {
    document.removeEventListener("keydown", onKeydown, true);
});

function onKeydown(e: KeyboardEvent) {
    if (e.key === "Escape" && props.open) {
        close();
    }
}

const searchQuery = ref("");
const viewFilter = ref<"unassigned" | "assigned">("unassigned");
const selectedEmployees = ref<string[]>((props.modelValue ?? []).map(String));

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            selectedEmployees.value = (props.modelValue ?? []).map(String);
            searchQuery.value = "";
            viewFilter.value = "unassigned";
            fetchEmployees();
        }
    },
);

const filteredEmployees = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase();

    return employeeData.value.filter((employee) => {
        const name =
            `${employee.first_name} ${employee.last_name}`.toLowerCase();
        const role = (employee.role_name ?? "").toLowerCase();
        const matchesSearch =
            !keyword || name.includes(keyword) || role.includes(keyword);

        const matchesFilter =
            viewFilter.value === "assigned"
                ? isSelected(employee.employee_id)
                : !isSelected(employee.employee_id);

        return matchesSearch && matchesFilter;
    });
});

const assignedCount = computed(
    () => employeeData.value.filter((e) => isSelected(e.employee_id)).length,
);

const unassignedCount = computed(
    () => employeeData.value.length - assignedCount.value,
);

const emptyStateTitle = computed(() => {
    if (searchQuery.value) return "No matching employees";
    return viewFilter.value === "assigned"
        ? "No nurse assigned yet"
        : "No unassigned nurse";
});

const emptyStateSubtitle = computed(() => {
    if (searchQuery.value) return "Try a different name or role.";
    return viewFilter.value === "assigned"
        ? "Select nurse from the Unassigned tab to assign them."
        : "Every available nurse has already been assigned.";
});

const allSelected = computed(
    () =>
        filteredEmployees.value.length > 0 &&
        filteredEmployees.value.every((employee) =>
            selectedEmployees.value.includes(String(employee.employee_id)),
        ),
);

const employeeGroupLabel = computed(() => {
    switch (props.service?.type_formatted) {
        case "Homecare Services":
            return "Homecare Employees";
        case "Inhouse Services":
            return "Inhouse Employees";
        case "Homecare and Inhouse Services":
            return "Homecare + Inhouse Employees";
        default:
            return "Employees";
    }
});

const serviceTypeBadgeClass = computed(() => {
    switch (props.service?.type_formatted) {
        case "Homecare Services":
            return "bg-blue-100 text-blue-700";
        case "Inhouse Services":
            return "bg-emerald-100 text-emerald-700";
        case "Homecare and Inhouse Services":
            return "bg-purple-100 text-purple-700";
        default:
            return "bg-gray-100 text-gray-600";
    }
});

function assignmentBadgeClass(type?: string) {
    switch (type) {
        case "Homecare":
            return "bg-blue-100 text-blue-700";
        case "Inhouse Facility":
            return "bg-emerald-100 text-emerald-700";
        case "Homecare + Inhouse Facility":
            return "bg-purple-100 text-purple-700";
        default:
            return "bg-gray-100 text-gray-500";
    }
}

function initials(employee: Employee) {
    return `${employee.first_name?.[0] ?? ""}${employee.last_name?.[0] ?? ""}`.toUpperCase();
}

function isSelected(id: number | string) {
    return selectedEmployees.value.includes(String(id));
}

function toggleEmployee(id: number | string) {
    const key = String(id);

    if (selectedEmployees.value.includes(key)) {
        selectedEmployees.value = selectedEmployees.value.filter(
            (employeeId) => employeeId !== key,
        );
    } else {
        selectedEmployees.value = [...selectedEmployees.value, key];
    }
}

function toggleSelectAll() {
    const visibleIds = filteredEmployees.value.map((e) =>
        String(e.employee_id),
    );

    if (allSelected.value) {
        selectedEmployees.value = selectedEmployees.value.filter(
            (id) => !visibleIds.includes(id),
        );
    } else {
        selectedEmployees.value = [
            ...new Set([...selectedEmployees.value, ...visibleIds]),
        ];
    }
}

const pendingAssign = computed(() =>
    selectedEmployees.value.filter(
        (id) => !initialAssignedIds.value.includes(id),
    ),
);

const pendingUnassign = computed(() =>
    initialAssignedIds.value.filter(
        (id) => !selectedEmployees.value.includes(id),
    ),
);

const hasChanges = computed(
    () => pendingAssign.value.length > 0 || pendingUnassign.value.length > 0,
);

const confirmButtonVariant = computed<"assign" | "unassign" | "mixed" | "none">(
    () => {
        if (pendingAssign.value.length && pendingUnassign.value.length) {
            return "mixed";
        }
        if (pendingUnassign.value.length) return "unassign";
        if (pendingAssign.value.length) return "assign";
        return "none";
    },
);

const confirmButtonLabel = computed(() => {
    if (saving.value) return "Saving…";

    const parts: string[] = [];
    if (pendingAssign.value.length) {
        parts.push(`Assign ${pendingAssign.value.length}`);
    }
    if (pendingUnassign.value.length) {
        parts.push(`Unassign ${pendingUnassign.value.length}`);
    }

    return parts.length ? parts.join(" · ") : "No changes";
});

async function confirm() {
    if (!hasChanges.value || !props.service?.service_id) return;

    saving.value = true;

    try {
        const payload = [
            ...pendingAssign.value.map((employeeId) => ({
                employee_id: employeeId,
                service_id: props.service!.service_id,
                action: "assign",
            })),
            ...pendingUnassign.value.map((employeeId) => ({
                employee_id: employeeId,
                service_id: props.service!.service_id,
                action: "unassign",
            })),
        ];
        const res = await serviceService.assignEmployeeService({
            branch_uuid: uuid.value,
            employee_service: payload,
        });
        success(res.message);
        emit("assigned");
        close();
    } catch (err: any) {
        console.error(err);
        error(err.message);
    } finally {
        saving.value = false;
    }
}

function close() {
    emit("close");
}
</script>
