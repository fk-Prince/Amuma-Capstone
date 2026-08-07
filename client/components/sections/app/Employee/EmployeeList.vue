<script setup lang="ts">
import { computed } from "vue";
import { formatRole } from "~/utils/user";
import { type Employee, formatAssignmentType } from "~/types/employee";
import { Pencil, Calendar } from "lucide-vue-next";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";
import { usePagination } from "~/composables/usePagination";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";

const { canUpdate } = usePermissions();

const props = withDefaults(
    defineProps<{
        employees: Employee[];
        loading: boolean;
        currentPage?: number;
        totalPages?: number;
        totalItems?: number;
        pageSize?: number;
    }>(),
    {
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
        pageSize: 10,
    },
);

const emit = defineEmits<{
    (e: "select", employee: Employee): void;
    (e: "page-change", page: number): void;
}>();

const pagination = usePagination({ pageSize: props.pageSize });

watch(
    () => props.totalItems,
    (value) => {
        pagination.totalItems.value = value;
    },
    { immediate: true },
);

watch(
    () => props.currentPage,
    (value) => {
        pagination.currentPage.value = value;
    },
    { immediate: true },
);

const columns: DataTableColumn[] = [
    { key: "name", label: "Name", sortable: true },
    { key: "email", label: "Email", sortable: true },
    { key: "phone", label: "Phone Number" },
    { key: "assignment", label: "Assignment" },
    { key: "status", label: "Status Label", sortable: true },
    { key: "actions", label: "Action", align: "right" },
];

const rows = computed(() =>
    props.employees.map((employee) => ({
        id: employee.uuid,
        name: `${employee.first_name} ${employee.last_name}`,
        role: formatRole(employee.role_name),
        avatar:
            employee.avatar ||
            `https://ui-avatars.com/api/?name=${employee.first_name}+${employee.last_name}`,
        email: employee.email,
        phone: employee.phone_number || "-",
        assignment: formatAssignmentType(employee.assignment_type),
        status: employee.status,
        raw: employee,
    })),
);

const updateEmployee = (employee: Employee) => {
    emit("select", employee);
};

const handlePageChange = (page: number) => {
    emit("page-change", page);
};

const statusColor = (status: string | null) => {
    const colors: Record<string, string> = {
        active: "bg-emerald-100 text-emerald-700",
        inactive: "bg-rose-100 text-rose-700",
        on_call: "bg-primary-100 text-primary-700",
        on_leave: "bg-amber-100 text-amber-700",
    };

    return colors[status ?? ""] ?? "bg-slate-100 text-slate-600";
};

const formatStatus = (status: string | null | undefined) => {
    if (!status) return "No Status";

    return status
        .replace("_", " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
};
</script>

<template>
    <div class="flex flex-col h-full min-h-0">
        <DataTable
            class="flex-1 min-h-0"
            :columns="columns"
            :rows="rows"
            :pagination="pagination"
            :loading="loading"
            :searchable="false"
            empty-title="No employees found"
            empty-description="Try adjusting your search or filters, or add a new employee."
            :row-key="(row) => row.id"
            @page-change="handlePageChange"
        >
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <img
                        :src="row.avatar"
                        class="h-9 w-9 rounded-full object-cover"
                    />

                    <div>
                        <p class="font-medium text-slate-800">{{ row.name }}</p>
                        <p class="text-xs text-slate-400">{{ row.role }}</p>
                    </div>
                </div>
            </template>

            <template #cell-status="{ row }">
                <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="statusColor(row.status)"
                >
                    {{ formatStatus(row.status) }}
                </span>
            </template>

            <template #cell-actions="{ row }">
                <div class="flex justify-end items-center gap-3">
                    <button
                        v-if="canUpdate(Modules.EmployeeManagement)"
                        class="text-slate-400 hover:text-slate-700"
                        @click="updateEmployee(row.raw)"
                    >
                        <Pencil class="w-4 h-4" />
                    </button>

                    <button class="text-slate-400 hover:text-slate-700">
                        <Calendar class="w-4 h-4" />
                    </button>
                </div>
            </template>
        </DataTable>
    </div>
</template>
