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
                class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                @click="close"
            />

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="open"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-secondary/40 backdrop-blur-[2px]"
                    @mousedown.self="close"
                >
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="assign-employee-title"
                        class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[85vh] h-[80vh] flex flex-col"
                    >
                        <div
                            class="flex items-start justify-between gap-4 px-7 py-6 border-b border-muted-light"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                        />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M19 8v6" />
                                        <path d="M22 11h-6" />
                                    </svg>
                                </div>

                                <div>
                                    <h2
                                        id="assign-employee-title"
                                        class="text-lg font-semibold text-secondary leading-tight"
                                    >
                                        Assign Employee
                                    </h2>
                                    <div class="text-sm mt-1 text-muted">
                                        Booking ID:
                                        <span
                                            class="font-medium bg-[#EAF4F2] text-[#0E7C7B] px-2 py-1 rounded-md"
                                        >
                                            #{{ referenceId }}
                                        </span>
                                    </div>
                                    <p
                                        class="mt-1 text-sm text-muted gap-1 flex"
                                    >
                                        Select staff members for
                                        <span
                                            class="font-medium text-secondary"
                                        >
                                            {{ services.length }}

                                            service{{
                                                services.length === 1 ? "" : "s"
                                            }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="text-muted hover:text-secondary transition rounded-md p-1 -mr-1 -mt-1"
                                @click="close"
                            >
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                >
                                    <path
                                        d="M5 5L15 15M15 5L5 15"
                                        stroke="currentColor"
                                        stroke-width="1.75"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </button>
                        </div>

                        <div
                            class="grid grid-cols-1 lg:grid-cols-[1fr_400px] flex-1 min-h-0"
                        >
                            <div class="px-7 py-6 space-y-5 overflow-y-auto">
                                <template v-if="loading">
                                    <div
                                        v-for="n in Math.max(
                                            services.length,
                                            2,
                                        )"
                                        :key="n"
                                        class="space-y-1.5"
                                    >
                                        <div
                                            class="h-3.5 w-32 rounded bg-slate-200"
                                        />
                                        <div
                                            class="h-[38px] w-full rounded-lg bg-slate-200"
                                        />
                                    </div>
                                </template>

                                <template v-else>
                                    <label
                                        v-if="services.length > 1"
                                        class="flex items-center gap-2.5 text-sm font-medium text-secondary cursor-pointer select-none"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="sameForAll"
                                            class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/40"
                                        />
                                        Assign one employee to all services
                                    </label>

                                    <div v-if="sameForAll" class="space-y-1.5">
                                        <Combobox
                                            :model-value="sharedEmployeeId"
                                            @update:model-value="
                                                setSharedEmployee
                                            "
                                            label="Employee"
                                            placeholder="Select an employee"
                                            required
                                            :items="employeeOptions"
                                        />
                                        <p
                                            v-if="unavailableWarning"
                                            class="text-xs text-danger"
                                        >
                                            {{ unavailableWarning }}
                                        </p>
                                    </div>

                                    <div v-else class="space-y-4">
                                        <div
                                            v-for="service in services"
                                            :key="service.service_id"
                                            class="rounded-xl border p-4 cursor-pointer transition-all"
                                            :class="
                                                activeServiceId ===
                                                service.service_id
                                                    ? 'border-primary bg-primary/5 shadow-sm'
                                                    : 'border-muted-light bg-white hover:border-primary/30'
                                            "
                                            @click="
                                                activeServiceId =
                                                    service.service_id
                                            "
                                        >
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <div class="min-w-0">
                                                    <p
                                                        class="text-sm font-semibold text-secondary truncate"
                                                    >
                                                        {{
                                                            service.service_name
                                                        }}
                                                    </p>
                                                </div>

                                                <span
                                                    class="shrink-0 text-sm font-semibold text-primary"
                                                >
                                                    ₱{{
                                                        Number(
                                                            service.price,
                                                        ).toLocaleString()
                                                    }}
                                                </span>
                                            </div>

                                            <div class="mt-3">
                                                <label
                                                    class="mb-1.5 block text-xs font-medium text-muted"
                                                >
                                                    Assigned Employee
                                                </label>

                                                <Combobox
                                                    :model-value="
                                                        assignments[
                                                            service.service_id ??
                                                                ''
                                                        ] ?? ''
                                                    "
                                                    @update:model-value="
                                                        setAssignment(
                                                            service.service_id!,
                                                            $event,
                                                        )
                                                    "
                                                    placeholder="Select an employee"
                                                    :items="employeeOptions"
                                                    @focus="
                                                        activeServiceId =
                                                            service.service_id ??
                                                            null
                                                    "
                                                />
                                            </div>

                                            <p
                                                v-if="
                                                    service.is_available ===
                                                    false
                                                "
                                                class="mt-2 text-xs text-danger"
                                            >
                                                This service is currently
                                                unavailable.
                                            </p>
                                        </div>
                                    </div>
                                    <p
                                        v-if="availableEmployees.length === 0"
                                        class="text-sm text-muted text-center py-6"
                                    >
                                        No employees available to assign right
                                        now.
                                    </p>
                                </template>
                            </div>

                            <div
                                class="border-t lg:border-t-0 lg:border-l border-muted-light bg-light/30 px-5 py-6 overflow-y-auto"
                            >
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-muted mb-4"
                                >
                                    <span
                                        v-if="props.type"
                                        class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                                    >
                                        {{ props.type }}
                                    </span>
                                    Employees
                                </p>

                                <div class="space-y-2">
                                    <button
                                        v-for="employee in sortedEmployees"
                                        :key="employee.id"
                                        type="button"
                                        class="group w-full flex items-center gap-3 rounded-xl border px-3.5 py-3 text-left transition-all"
                                        :class="
                                            isAssignedToActive(employee.id)
                                                ? 'border-primary bg-primary/5 shadow-sm'
                                                : 'border-muted-light bg-white hover:border-primary/30 hover:bg-primary/5'
                                        "
                                        @click="assignFromRoster(employee.id)"
                                    >
                                        <div class="relative shrink-0">
                                            <img
                                                :src="employee.avatar"
                                                :alt="`${employee.first_name} ${employee.last_name}`"
                                                class="h-10 w-10 rounded-full object-cover border border-white shadow-sm"
                                            />

                                            <span
                                                class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white"
                                                :class="
                                                    statusDotClass(
                                                        employee.status,
                                                    )
                                                "
                                            />
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-center justify-between gap-2"
                                            >
                                                <p
                                                    class="text-sm font-semibold text-secondary truncate"
                                                >
                                                    {{
                                                        fullName(
                                                            employee.first_name,
                                                            "",
                                                            employee.last_name,
                                                        )
                                                    }}
                                                </p>

                                                <span
                                                    class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium"
                                                    :class="
                                                        statusTextClass(
                                                            employee.status,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        statusLabel(
                                                            employee.status,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <p
                                                class="mt-0.5 text-xs text-muted truncate"
                                            >
                                                {{
                                                    employee.role_name ||
                                                    "Staff"
                                                }}
                                            </p>

                                            <div
                                                class="mt-1 flex items-center gap-1.5 text-[11px] text-muted"
                                            >
                                                <svg
                                                    class="h-3.5 w-3.5"
                                                    viewBox="0 0 20 20"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.5"
                                                >
                                                    <path
                                                        d="M4 5h12M4 10h12M4 15h12"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>

                                                <span class="truncate">
                                                    {{
                                                        employee.assignment_type ||
                                                        "No assignment type"
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </button>

                                    <p
                                        v-if="availableEmployees.length === 0"
                                        class="text-sm text-muted text-center py-6"
                                    >
                                        No employees found.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-3 px-7 py-5 border-t border-muted-light bg-light/40"
                        >
                            <p class="text-xs text-muted">
                                {{ assignedCount }} /
                                {{ services.length }} assigned
                            </p>

                            <div class="flex items-center gap-2.5">
                                <button
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-muted hover:text-secondary transition"
                                    @click="close"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    :disabled="assignedCount === 0 || saving"
                                    class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-primary transition disabled:opacity-40 disabled:cursor-not-allowed hover:bg-primary/90 flex items-center gap-2"
                                    @click="confirm"
                                >
                                    <svg
                                        v-if="saving"
                                        class="h-4 w-4 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"
                                        />
                                    </svg>
                                    {{ saving ? "Assigning…" : "Assign" }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import Combobox from "~/components/ui/Combobox.vue";
import type { Employee } from "~/types/employee";
import { fullName } from "~/utils/user";
import { useRoute } from "vue-router";
import { employeeService } from "~/api/employee/EmployeeService";

const props = defineProps<{
    open: boolean;
    referenceId?: string | number;
    services: any[];
    modelValue: Record<string, string>;
    loading?: boolean;
    saving?: boolean;
    type?: string;
}>();

const availableEmployees = ref<Employee[]>([]);
const isEmployeesLoading = ref(true);
const route = useRoute();

onMounted(async () => {
    isEmployeesLoading.value = true;
    try {
        const employeeRes: any = await employeeService.list({
            branch_uuid: route.params.uuid as string,
            type: "schedule",
            per_page: 10,
        });
        availableEmployees.value = employeeRes.data.map((item: any) => item);
    } catch (err: any) {
        console.error(err);
        availableEmployees.value = [];
    } finally {
        isEmployeesLoading.value = false;
    }
});
const emit = defineEmits<{
    (e: "update:modelValue", value: Record<string, string>): void;
    (e: "close"): void;
    (e: "confirm", value: Record<string, string>): void;
}>();

const assignments = ref<Record<string, string>>({ ...props.modelValue });

watch(
    () => props.modelValue,
    (val) => {
        assignments.value = { ...val };
    },
);

const sameForAll = ref(false);
const activeServiceId = ref<string | number | null>(
    props.services[0]?.service_id ?? null,
);

watch(
    () => props.services,
    (services) => {
        if (!services.some((s) => s.service_id === activeServiceId.value)) {
            activeServiceId.value = services[0]?.service_id ?? null;
        }
    },
);

const sharedEmployeeId = computed(() => {
    const values = props.services.map((s) => assignments.value[s.service_id]);
    const allSame = values.every((v) => v && v === values[0]);
    return allSame ? (values[0] ?? "") : "";
});

const sortedEmployees = computed(() =>
    [...availableEmployees.value].sort((a, b) => {
        const rank = { available: 0, busy: 1, off: 2 } as const;
        const aStatus = (a.status ?? "available") as keyof typeof rank;
        const bStatus = (b.status ?? "available") as keyof typeof rank;
        return rank[aStatus] - rank[bStatus];
    }),
);

const employeeOptions = computed(() =>
    sortedEmployees.value.map((e) => ({
        value: e.id,
        label:
            e.status && e.status !== "available"
                ? `${e.last_name}${e.role_name ? ` · ${e.role_name}` : ""} (${statusLabel(e.status)})`
                : `${e.last_name}${e.role_name ? ` · ${e.role_name}` : ""}`,
    })),
);

function statusLabel(status: Employee["status"]) {
    if (status === "busy") return "Busy";
    if (status === "off") return "Off duty";
    return "Available";
}

function statusDotClass(status: Employee["status"]) {
    if (status === "busy") return "bg-yellow-400";
    if (status === "off") return "bg-slate-300";
    return "bg-accent";
}

function statusTextClass(status: Employee["status"]) {
    if (status === "busy") return "text-yellow-600";
    if (status === "off") return "text-slate-400";
    return "text-accent";
}

const unavailableWarning = computed(() => {
    const id = sharedEmployeeId.value;
    if (!id) return "";
    const emp = availableEmployees.value.find((e) => e.id === id);
    if (emp && emp.status && emp.status !== "available") {
        return `${emp.first_name} ${emp.last_name} is currently marked ${statusLabel(emp.status).toLowerCase()}.`;
    }
    return "";
});

const assignedCount = computed(
    () =>
        props.services.filter((s) => !!assignments.value[s.service_id]).length,
);

function isAssignedToActive(employeeId: string) {
    if (sameForAll.value) return sharedEmployeeId.value === employeeId;
    if (activeServiceId.value === null) return false;
    return assignments.value[activeServiceId.value] === employeeId;
}

function setAssignment(serviceId: string | number, employeeId: string) {
    assignments.value = { ...assignments.value, [serviceId]: employeeId };
    emit("update:modelValue", assignments.value);
}

function setSharedEmployee(employeeId: string) {
    const next: Record<string, string> = {};
    for (const s of props.services) {
        next[s.service_id] = employeeId;
    }
    assignments.value = next;
    emit("update:modelValue", assignments.value);
}

function assignFromRoster(employeeId: string) {
    if (sameForAll.value) {
        setSharedEmployee(employeeId);
        return;
    }
    if (activeServiceId.value === null) return;
    setAssignment(activeServiceId.value, employeeId);
}

function close() {
    emit("close");
}

function confirm() {
    emit("confirm", assignments.value);
}
</script>
