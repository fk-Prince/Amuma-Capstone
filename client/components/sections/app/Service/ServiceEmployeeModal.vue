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
                    class="relative bg-white w-full max-w-3xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh] h-[80vh]"
                >
                    <div
                        class="flex justify-between items-center px-6 py-4 border-b border-gray-100"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Assign Employee
                            </h2>

                            <p class="text-xs text-gray-400 mt-1 max-w-md">
                                Select an employee to handle this service. Only
                                the assigned employee will be responsible for
                                processing requests related to this service.
                            </p>
                        </div>

                        <button
                            @click="close"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition"
                        >
                            ✕
                        </button>
                    </div>
                    <div
                        class="mx-6 mt-4 rounded-xl border border-primary/20 bg-primary/5 px-4 py-3"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-primary"
                                >
                                    Selected Service
                                </p>

                                <h3
                                    class="mt-1 text-base font-semibold text-gray-900"
                                >
                                    {{ service?.service_name }}
                                </h3>
                            </div>

                            <span
                                class="shrink-0 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                :class="{
                                    'bg-blue-100 text-blue-700':
                                        service?.type_formatted ===
                                        'Homecare Services',
                                    'bg-emerald-100 text-emerald-700':
                                        service?.type_formatted ===
                                        'Inhouse Services',
                                    'bg-purple-100 text-purple-700':
                                        service?.type_formatted ===
                                        'Homecare and Inhouse Services',
                                }"
                            >
                                {{ service?.type_formatted }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 px-6 py-3 border-b border-gray-100"
                    >
                        <div class="relative flex-1">
                            <svg
                                class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="11" cy="11" r="7" />
                                <path d="m21 21-4.3-4.3" />
                            </svg>

                            <BaseInput
                                v-model="searchQuery"
                                mode="text"
                                placeholder="Search employees..."
                                inputClass="pl-[2.3rem]"
                            />
                        </div>
                    </div>

                    <div
                        v-if="!loading && employeeData.length"
                        class="flex items-center justify-between px-6 py-2.5 border-b border-gray-100 bg-gray-50/60"
                    >
                        <button
                            type="button"
                            class="text-sm font-medium text-primary hover:underline"
                            @click="toggleSelectAll"
                        >
                            {{ allSelected ? "Unselect all" : "Select all" }}
                        </button>

                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs font-medium text-muted rounded-full px-3 py-1.5"
                            >
                                {{
                                    service?.type_formatted ===
                                    "Homecare Services"
                                        ? "Homecare Employees"
                                        : service?.type_formatted ===
                                            "Inhouse Services"
                                          ? "Inhouse Employees"
                                          : service?.type_formatted ===
                                              "Homecare and Inhouse Services"
                                            ? "Homecare + Inhouse Employees"
                                            : "Employees"
                                }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ selectedEmployees.length }} selected
                            </span>
                        </div>
                    </div>

                    <div class="overflow-y-auto px-6 py-4 space-y-2 flex-1">
                        <template v-if="loading">
                            <div
                                v-for="n in 5"
                                :key="n"
                                class="flex items-center gap-3 rounded-xl p-3 border border-gray-100 animate-pulse"
                            >
                                <div
                                    class="w-10 h-10 rounded-full bg-gray-200"
                                />

                                <div class="space-y-2 flex-1">
                                    <div class="h-3 bg-gray-200 rounded w-32" />
                                    <div class="h-2 bg-gray-200 rounded w-20" />
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <button
                                v-for="employee in filteredEmployees"
                                :key="employee.id"
                                type="button"
                                class="flex items-center justify-between rounded-xl p-3 cursor-pointer border transition-all w-full"
                                :class="
                                    isSelected(employee.id)
                                        ? 'border-primary/40 bg-primary/5 ring-1 ring-primary/20'
                                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                "
                                @click="toggleEmployee(employee.id)"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected(employee.id)"
                                        class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/40 focus:ring-offset-0 cursor-pointer pointer-events-none"
                                        tabindex="-1"
                                    />

                                    <!-- <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary text-xs font-semibold"
                                    >
                                        {{ initials(employee) }}
                                    </span> -->
                                    <img
                                        :src="employee.avatar"
                                        alt=""
                                        class="h-9 w-9 rounded-full"
                                    />

                                    <div class="min-w-0 text-left">
                                        <p
                                            class="font-medium text-sm text-gray-900 leading-tight truncate"
                                        >
                                            {{ employee.first_name }}
                                            {{ employee.last_name }}
                                        </p>

                                        <p
                                            class="text-xs text-gray-400 mt-0.5 truncate"
                                        >
                                            {{ employee.role_name }}
                                        </p>
                                    </div>
                                </div>

                                <span
                                    class="shrink-0 px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="
                                        employee.assignment_type === 'Homecare'
                                            ? 'bg-blue-100 text-blue-700'
                                            : employee.assignment_type ===
                                                'Inhouse Facility'
                                              ? 'bg-emerald-100 text-emerald-700'
                                              : employee.assignment_type ===
                                                  'Homecare + Inhouse Facility'
                                                ? 'bg-purple-100 text-purple-700'
                                                : 'bg-gray-100 text-gray-500'
                                    "
                                >
                                    {{ employee.assignment_type }}
                                </span>
                            </button>
                            <div
                                v-if="!filteredEmployees.length"
                                class="text-center text-sm text-gray-400 py-10"
                            >
                                Currently there is no employee needed to be
                                assigned.
                            </div>
                        </template>
                    </div>

                    <div
                        class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl"
                    >
                        <button
                            class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-100"
                            @click="close"
                        >
                            Cancel
                        </button>

                        <button
                            :disabled="
                                !selectedEmployees.length || loading || saving
                            "
                            class="px-4 py-2 text-sm font-medium bg-primary text-white rounded-lg disabled:opacity-40"
                            @click="confirm"
                        >
                            {{
                                saving
                                    ? "Assigning…"
                                    : `Assign Employee${selectedEmployees.length > 1 ? "s" : ""}`
                            }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { employeeService } from "~/api/employee/EmployeeService";
import BaseInput from "~/components/ui/BaseInput.vue";
import type { Employee } from "~/types/employee";
import type { Service } from "~/types/service";
import { useRoute } from "vue-router";
import { serviceService } from "~/api/service/ServiceService";

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

async function fetchEmployees() {
    if (!props.service?.service_id) {
        employeeData.value = [];
        return;
    }

    loading.value = true;

    try {
        const res: any = await employeeService.list({
            per_page: 5,
            branch_uuid: uuid.value,
            type: "service",
            service_id: props.service.service_id,
        });
        employeeData.value = Array.isArray(res) ? res : (res.data ?? []);
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
});

const searchQuery = ref("");
const selectedEmployees = ref<string[]>((props.modelValue ?? []).map(String));

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            selectedEmployees.value = [];
            searchQuery.value = "";
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

        return !keyword || name.includes(keyword) || role.includes(keyword);
    });
});

const allSelected = computed(
    () =>
        filteredEmployees.value.length > 0 &&
        filteredEmployees.value.every((employee) =>
            selectedEmployees.value.includes(String(employee.id)),
        ),
);

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
    const visibleIds = filteredEmployees.value.map((e) => String(e.id));

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

async function confirm() {
    if (!selectedEmployees.value.length || !props.service?.service_id) return;

    const payload = selectedEmployees.value.map((employeeId) => ({
        service_id: props.service!.service_id,
        employee_id: employeeId,
    }));

    saving.value = true;

    try {
        const res = await serviceService.assignEmployeeService({
            branch_uuid: uuid.value,
            employee_service: payload,
        });
        console.log(res);
        emit("assigned");
        close();
    } catch (err: any) {
        console.error(err);
    } finally {
        saving.value = false;
    }
}

function close() {
    emit("close");
}
</script>
