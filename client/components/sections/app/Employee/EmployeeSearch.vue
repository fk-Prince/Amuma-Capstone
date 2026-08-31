<script setup lang="ts">
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";
import { computed } from "vue";
import { useBranchPlan } from "~/composables/useBranchPlan";
const { canCreate } = usePermissions();

defineProps<{
    modelValue: string;
    activeTab: string;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: string];
    "update:activeTab": [value: string];
    addEmployee: [];
}>();

const { hasPlan } = useBranchPlan();

const tabs = [
    { label: "All Employees", value: "All Employees" },
    { label: "Homecare Employees", value: "Homecare Employees" },
    { label: "Facility Employees", value: "Facility Employees" },
];

const filteredTabs = computed(() => {
    return tabs.filter((tab) => {
        if (tab.value === "All Employees") return true;
        if (tab.value === "Homecare Employees") return hasPlan("A");
        if (tab.value === "Facility Employees") return hasPlan("B");

        return false;
    });
});
</script>

<template>
    <div class="space-y-4 mt-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="relative flex-1 min-w-[220px] max-w-sm">
                <svg
                    viewBox="0 0 24 24"
                    class="w-4 h-4 text-slate-400 dark:text-gray-500 absolute left-4 top-1/2 -translate-y-1/2"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <circle cx="11" cy="11" r="7" />
                    <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
                </svg>

                <BaseInput
                    :model-value="modelValue"
                    @update:model-value="emit('update:modelValue', $event)"
                    placeholder="Search employee..."
                    input-class="pl-11"
                />
            </div>

            <div class="flex items-center gap-3">
                <Combobox
                    :items="filteredTabs"
                    :model-value="activeTab"
                    @update:model-value="emit('update:activeTab', $event)"
                    placeholder="Select employee type"
                    class="w-56"
                />

                <button
                    v-if="canCreate(Modules.EmployeeManagement)"
                    @click="emit('addEmployee')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-200 hover:bg-primary-600 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-1"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>

                    <span>Add Employee</span>
                </button>
            </div>
        </div>
    </div>
</template>
