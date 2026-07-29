<template>
    <div class="overflow-x-auto rounded-xl">
        <div v-if="loading" class="bg-white rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th v-for="header in 6" :key="header" class="py-3 px-6">
                            <div
                                class="h-3 w-20 bg-gray-200 rounded animate-pulse"
                            />
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="n in 4"
                        :key="n"
                        class="border-b last:border-none"
                    >
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-9 w-9 rounded-full bg-gray-200 animate-pulse"
                                />

                                <div class="space-y-2">
                                    <div
                                        class="h-3.5 w-32 bg-gray-200 rounded animate-pulse"
                                    />

                                    <div
                                        class="h-3 w-20 bg-gray-100 rounded animate-pulse"
                                    />
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div
                                class="h-3.5 w-40 bg-gray-200 rounded animate-pulse"
                            />
                        </td>

                        <td class="px-6 py-4">
                            <div
                                class="h-3.5 w-28 bg-gray-200 rounded animate-pulse"
                            />
                        </td>

                        <td class="px-6 py-4">
                            <div
                                class="h-3.5 w-24 bg-gray-200 rounded animate-pulse"
                            />
                        </td>

                        <td class="px-6 py-4">
                            <div
                                class="h-6 w-16 rounded-full bg-gray-200 animate-pulse"
                            />
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-3">
                                <div
                                    class="h-8 w-8 rounded-lg bg-gray-200 animate-pulse"
                                />

                                <div
                                    class="h-8 w-8 rounded-lg bg-gray-200 animate-pulse"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-else-if="employees.length === 0"
            class="flex flex-col items-center justify-center text-center"
        >
            <svg
                viewBox="0 0 24 24"
                class="w-10 h-10 text-gray-300 mb-3"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>

            <p class="text-sm font-medium text-gray-500">No employees found</p>

            <p class="text-xs text-gray-400 mt-1">
                Try adjusting your search or filters, or add a new employee.
            </p>
        </div>

        <template v-else>
            <table class="w-full text-sm bg-white mt-2">
                <thead>
                    <tr class="border-b text-left text-xs text-gray-500">
                        <th
                            class="py-3 pl-6 pr-3 text-xs font-semibold text-muted uppercase tracking-wide"
                        >
                            Name ↕
                        </th>
                        <th
                            class="py-3 pl-6 pr-3 text-xs font-semibold text-muted uppercase tracking-wide"
                        >
                            Email ↕
                        </th>
                        <th
                            class="py-3 pl-6 pr-3 text-xs font-semibold text-muted uppercase tracking-wide"
                        >
                            Phone Number
                        </th>
                        <th
                            class="py-3 pl-6 pr-3 text-xs font-semibold text-muted uppercase tracking-wide"
                        >
                            Assignment
                        </th>
                        <th
                            class="py-3 pl-6 pr-3 text-xs font-semibold text-muted uppercase tracking-wide"
                        >
                            Status Label ↕
                        </th>
                        <th
                            class="py-3 pl-6 pr-5 text-xs font-semibold text-muted text-right uppercase tracking-wide"
                        >
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="employee in employees"
                        :key="employee.uuid"
                        class="border-b last:border-none hover:bg-gray-50"
                    >
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img
                                    :src="
                                        employee.avatar ||
                                        `https://ui-avatars.com/api/?name=${employee.first_name}+${employee.last_name}`
                                    "
                                    class="h-9 w-9 rounded-full object-cover"
                                />

                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ employee.first_name }}
                                        {{ employee.last_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ formatRole(employee.role_name) }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            {{ employee.email }}
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            {{ employee.phone_number || "-" }}
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            {{ formatAssignmentType(employee.assignment_type) }}
                        </td>

                        <td class="px-6 py-4">
                            <span
                                class="rounded-full px-3 py-1 text-xs font-medium"
                                :class="statusColor(employee.status)"
                            >
                                {{ formatStatus(employee.status) }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex justify-end items-center gap-3">
                                <button
                                    class="text-gray-500 hover:text-gray-900"
                                    @click="updateEmployee(employee)"
                                    v-if="canUpdate(Modules.EmployeeManagement)"
                                >
                                    <Pencil class="w-4 h-4" />
                                </button>

                                <button
                                    class="text-gray-500 hover:text-gray-900"
                                >
                                    <Calendar class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div
                v-if="totalPages > 1"
                class="flex items-center justify-between px-6 py-4 border-t"
            >
                <p class="text-xs text-gray-500">
                    Page {{ currentPage }} of {{ totalPages }}
                    <span v-if="totalItems"> · {{ totalItems }} total</span>
                </p>

                <div class="flex items-center gap-1">
                    <button
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="currentPage <= 1"
                        @click="goToPage(currentPage - 1)"
                    >
                        Previous
                    </button>

                    <button
                        v-for="page in visiblePages"
                        :key="page"
                        class="min-w-[32px] px-2 py-1.5 text-xs font-medium rounded-lg"
                        :class="
                            page === currentPage
                                ? 'bg-primary text-white'
                                : 'text-gray-600 hover:bg-gray-50'
                        "
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </button>

                    <button
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="currentPage >= totalPages"
                        @click="goToPage(currentPage + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { formatRole } from "~/utils/user";
import { type Employee, formatAssignmentType } from "~/types/employee";
import { Pencil } from "lucide-vue-next";
import { Calendar } from "lucide-vue-next";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";

const { canUpdate } = usePermissions();
const props = withDefaults(
    defineProps<{
        employees: Employee[];
        loading: boolean;
        currentPage?: number;
        totalPages?: number;
        totalItems?: number;
    }>(),
    {
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
    },
);

const emit = defineEmits<{
    (e: "select", employee: Employee): void;
    (e: "page-change", page: number): void;
}>();

const updateEmployee = (employee: Employee) => {
    emit("select", employee);
};

const goToPage = (page: number) => {
    if (page < 1 || page > props.totalPages || page === props.currentPage)
        return;
    emit("page-change", page);
};

const visiblePages = computed(() => {
    const total = props.totalPages;
    const current = props.currentPage;
    const maxVisible = 5;

    if (total <= maxVisible) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    let start = Math.max(1, current - Math.floor(maxVisible / 2));
    let end = start + maxVisible - 1;

    if (end > total) {
        end = total;
        start = end - maxVisible + 1;
    }

    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const statusColor = (status: string | null) => {
    const colors: Record<string, string> = {
        active: "bg-green-100 text-green-700",
        inactive: "bg-red-100 text-red-700",
        on_call: "bg-blue-100 text-blue-700",
        on_leave: "bg-yellow-100 text-yellow-700",
    };

    return colors[status ?? ""] ?? "bg-gray-100 text-gray-700";
};

const formatStatus = (status: string | null | undefined) => {
    if (!status) return "No Status";

    return status
        .replace("_", " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
};
</script>
