<template>
    <div class="h-full w-full mx-auto lg:space-y-5 rounded-lg">
        <div v-if="!addEmployeeTab" class="h-full flex flex-col min-h-0">
            <EmployeeDashboard
                :total-employee="totalEmployee"
                :on-duty="onDuty"
                :on-leave="onLeave"
            />

            <div
                class="overflow-hidden rounded-lg mt-2 border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="border-b border-slate-100 py-5 px-3 dark:border-white/10"
                >
                    <ClientOnly>
                        <EmployeeSearch
                            @addEmployee="openAddEmployee"
                            v-model="searchData"
                            v-model:activeTab="activeTab"
                        />
                    </ClientOnly>
                </div>

                <div class="flex-1 min-h-0">
                    <EmployeeList
                        class="h-full"
                        :employees="employees"
                        :loading="loading"
                        :current-page="currentPage"
                        :total-pages="totalPages"
                        :total-items="totalEmployee"
                        @select="updateEmployee"
                        @page-change="handlePageChange"
                    />
                </div>
            </div>
        </div>

        <EmployeeForm
            v-else
            :employee="selectedEmployee"
            :mode="employeeMode"
            @edit="employeeMode = 'edit'"
            @saved="applyEmployee"
            @back="closeEmployeeForm"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useDebounceFn } from "@vueuse/core";
import { useRoute } from "vue-router";
import EmployeeForm from "~/components/sections/app/Employee/EmployeeForm.vue";
import EmployeeDashboard from "~/components/sections/app/Employee/EmployeeDashboard.vue";
import EmployeeSearch from "~/components/sections/app/Employee/EmployeeSearch.vue";
import EmployeeList from "~/components/sections/app/Employee/EmployeeList.vue";
import type { Employee } from "~/types/employee";
import { employeeService } from "~/api/employee/EmployeeService";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

const route = useRoute();
const uuid = route.params.uuid as string;

const employees = ref<Employee[]>([]);
const loading = ref(false);

const searchData = ref("");
const activeTab = ref("All Employees");
const addEmployeeTab = ref(false);
const totalEmployee = ref(0);
const onDuty = ref(0);
const onLeave = ref(0);
const totalPages = ref(1);
const currentPage = ref(1);
const selectedEmployee = ref<Employee | null>(null);
const employeeMode = ref<"view" | "edit">("view");

useHead({ title: "Employees" });

const fetchEmployees = async () => {
    try {
        loading.value = true;

        const res: any = await employeeService.list({
            per_page: 15,
            branch_uuid: uuid,
            search: searchData.value,
        });

        employees.value = res.data;
        totalEmployee.value = res.total_employee;
        totalPages.value =
            res.last_page ??
            Math.ceil((res.total ?? res.total_employee ?? 0) / 15) ??
            1;
        onDuty.value = res.status_counts?.active ?? 0;
        onLeave.value = res.status_counts?.on_leave ?? 0;
    } catch (err) {
        console.error(err);
        employees.value = [];
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
    fetchEmployees();
};

const debouncedFetchEmployees = useDebounceFn(() => {
    currentPage.value = 1;
    fetchEmployees();
}, 500);

const openAddEmployee = () => {
    selectedEmployee.value = null;
    employeeMode.value = "edit";
    addEmployeeTab.value = true;
};

const updateEmployee = (employee: Employee) => {
    selectedEmployee.value = employee;
    employeeMode.value = "view";
    addEmployeeTab.value = true;
};

const statusCount = (status?: string) => {
    const value = (status ?? "").toLowerCase();

    if (value === "active") return onDuty;
    if (value === "on_leave") return onLeave;

    return null;
};

const applyEmployee = (employee: Employee) => {
    const index = employees.value.findIndex(
        (row) => row.uuid === employee.uuid,
    );

    if (index === -1) {
        employees.value = [employee, ...employees.value];
        totalEmployee.value += 1;

        const added = statusCount(employee.status);

        if (added) added.value += 1;

        return;
    }

    const previous = employees.value[index]!;

    employees.value.splice(index, 1, employee);

    if (previous.status !== employee.status) {
        const before = statusCount(previous.status);
        const after = statusCount(employee.status);

        if (before) before.value = Math.max(0, before.value - 1);
        if (after) after.value += 1;
    }
};

const closeEmployeeForm = () => {
    addEmployeeTab.value = false;
    selectedEmployee.value = null;
    employeeMode.value = "view";
};

onMounted(() => {
    fetchEmployees();
});

watch(searchData, () => {
    debouncedFetchEmployees();
});

watch(activeTab, () => {
    fetchEmployees();
});
</script>
