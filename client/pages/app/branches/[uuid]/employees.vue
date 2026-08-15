<template>
    <div class="w-full h-full max-w-8xl mx-auto p-4 bg-slate-50 lg:space-y-5">
        <div
            v-if="!addEmployeeTab"
            class="h-full grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_auto] gap-4 items-stretch"
        >
            <div class="flex flex-col min-h-0">
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

                <div class="flex-1 min-h-0 mt-2">
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

            <div class="hidden lg:flex flex-col gap-4 w-[280px]">
                <Calendar />

                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                    UPCOMING SCHEDULES
                </div>

                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                    UPCOMING SCHEDULES
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
