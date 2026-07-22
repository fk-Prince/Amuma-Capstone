<template>
    <div class="w-full max-w-8xl mx-auto p-4 md:p-6 space-y-5">
        <div
            v-if="!addEmployeeTab"
            class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-4 items-start"
        >
            <div>
                <PageHeader
                    title="Manage Employees"
                    subtitle="Human Resources"
                    description="Add, update, and manage employee information."
                />

                <EmployeeDashboard
                    :total-employee="totalEmployee"
                    :on-duty="onDuty"
                    :on-leave="onLeave"
                />

                <ClientOnly>
                    <EmployeeSearch
                        @addEmployee="openAddEmployee"
                        v-model="searchData"
                        v-model:activeTab="activeTab"
                    />
                </ClientOnly>
                <!-- <EmployeeList
                    :employees="employees"
                    :loading="loading"
                    @select="openEditEmployee"
                /> -->

                <EmployeeList
                    :employees="employees"
                    :loading="loading"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :total-items="totalEmployee"
                    @select="updateEmployee"
                    @page-change="handlePageChange"
                />
            </div>

            <div class="space-y-4">
                <Calendar />

                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                    SAMPPLE
                </div>

                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                    SAMPPLE
                </div>
            </div>
        </div>

        <EmployeeForm
            v-else
            :employee="selectedEmployee"
            :mode="employeeMode"
            @edit="employeeMode = 'edit'"
            @back="closeEmployeeForm"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import PageHeader from "~/components/ui/PageHeader.vue";
import { useDebounceFn } from "@vueuse/core";
import { useRoute } from "vue-router";
import EmployeeForm from "~/components/sections/app/Employee/EmployeeForm.vue";
import EmployeeDashboard from "~/components/sections/app/Employee/EmployeeDashboard.vue";
import EmployeeSearch from "~/components/sections/app/Employee/EmployeeSearch.vue";
import EmployeeList from "~/components/sections/app/Employee/EmployeeList.vue";
import Calendar from "~/components/ui/Calendar.vue";
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

useHead({ title: "Staff" });

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

const closeEmployeeForm = () => {
    addEmployeeTab.value = false;
    selectedEmployee.value = null;
    employeeMode.value = "view";
    fetchEmployees();
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
