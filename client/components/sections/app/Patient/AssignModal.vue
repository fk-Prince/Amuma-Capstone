<template>
    <Teleport to="body">
        <Transition name="assign-modal-backdrop">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
                @click.self="handleCancel"
            >
                <Transition name="assign-modal-panel" appear>
                    <div
                        v-if="modelValue"
                        class="flex max-h-[85vh] w-full max-w-lg flex-col rounded-2xl border border-slate-100 bg-white shadow-xl dark:border-white/10 dark:bg-secondary"
                    >
                        <div
                            class="flex items-start justify-between border-b border-slate-100 px-5 py-4 dark:border-white/10"
                        >
                            <div>
                                <h2
                                    class="text-sm font-semibold text-slate-800 dark:text-white"
                                >
                                    Assign Now
                                </h2>
                                <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                                    {{ headerSubtitle }}
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-50 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-400"
                                @click="handleCancel"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="flex-1 space-y-3 overflow-y-auto px-5 py-4">
                            <div
                                v-for="row in serviceRows"
                                :key="row.block.scheduleServiceId"
                                class="rounded-xl border border-slate-100 p-3 dark:border-white/10"
                            >
                                <div
                                    class="mb-2.5 flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-xs font-semibold text-slate-800 dark:text-white"
                                        >
                                            {{ row.block.serviceName }}
                                        </p>
                                        <p class="text-[11px] text-slate-400 dark:text-gray-500">
                                            {{ row.block.patientName }} ·
                                            {{ row.block.startLabel }} -
                                            {{ row.block.endLabel }}
                                        </p>
                                    </div>

                                    <span
                                        v-if="row.employeeId"
                                        class="flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                                    >
                                        <Check class="h-3 w-3" />
                                        Selected
                                    </span>
                                </div>

                                <div class="relative">
                                    <select
                                        v-model="row.employeeId"
                                        class="w-full appearance-none rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 outline-none transition focus:border-sky-400 focus:bg-white focus:ring-2 focus:ring-sky-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:focus:bg-secondary dark:focus:ring-sky-500/20 dark:focus:bg-white/10"
                                    >
                                        <option :value="null" disabled>
                                            Select employee
                                        </option>
                                        <option
                                            v-for="employee in employees"
                                            :key="employee.employee_id"
                                            :value="employee.employee_id"
                                        >
                                            {{ employee.employee_name }}
                                        </option>
                                    </select>
                                    <ChevronDown
                                        class="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 dark:text-gray-500"
                                    />
                                </div>
                            </div>

                            <p
                                v-if="!serviceRows.length"
                                class="py-8 text-center text-xs text-slate-400 dark:text-gray-500"
                            >
                                No services to assign.
                            </p>
                        </div>

                        <div
                            class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 dark:border-white/10"
                        >
                            <p class="text-[11px] text-slate-400 dark:text-gray-500">
                                {{ assignedCount }} of
                                {{ serviceRows.length }} assigned
                            </p>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg px-3.5 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 dark:text-gray-400 dark:hover:bg-white/5"
                                    @click="handleCancel"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    :disabled="!canSubmit || submitting"
                                    class="rounded-lg bg-sky-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 dark:disabled:bg-white/15 dark:disabled:text-gray-500"
                                    @click="handleSubmit"
                                >
                                    {{
                                        submitting
                                            ? "Assigning..."
                                            : "Confirm Assignment"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from "vue";
import { Check, ChevronDown, X } from "lucide-vue-next";
import type { Block } from "~/types/schedule";

export interface AssignEmployeeOption {
    employee_id: number;
    employee_name: string | null;
}

export interface AssignmentResult {
    scheduleId: number;
    scheduleServiceId: number;
    employeeId: number;
    employeeName: string | null;
}

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        blocks: Block[];
        employees?: AssignEmployeeOption[];
        submitting?: boolean;
    }>(),
    {
        employees: () => [],
        submitting: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "cancel"): void;
    (e: "submit", assignments: AssignmentResult[]): void;
}>();

interface ServiceRow {
    block: Block;
    employeeId: number | null;
}

const serviceRows = reactive<ServiceRow[]>([]);

watch(
    () => [props.modelValue, props.blocks] as const,
    ([open, blocks]) => {
        if (!open) return;
        serviceRows.splice(
            0,
            serviceRows.length,
            ...blocks.map((block) => ({ block, employeeId: null })),
        );
    },
    { immediate: true },
);

const assignedCount = computed(
    () => serviceRows.filter((row) => row.employeeId !== null).length,
);

const canSubmit = computed(
    () => serviceRows.length > 0 && assignedCount.value === serviceRows.length,
);

const headerSubtitle = computed(() => {
    if (!serviceRows.length) return "";
    const patientName = serviceRows[0]!.block.patientName;
    const count = serviceRows.length;
    return `${patientName} · ${count} service${count === 1 ? "" : "s"}`;
});

function handleCancel() {
    emit("update:modelValue", false);
    emit("cancel");
}

function handleSubmit() {
    if (!canSubmit.value || props.submitting) return;

    const assignments: AssignmentResult[] = serviceRows.map((row) => {
        const employee = props.employees.find(
            (e) => e.employee_id === row.employeeId,
        );
        return {
            scheduleId: row.block.scheduleId,
            scheduleServiceId: row.block.scheduleServiceId,
            employeeId: row.employeeId!,
            employeeName: employee?.employee_name ?? null,
        };
    });

    emit("submit", assignments);
}
</script>

<style scoped>
.assign-modal-backdrop-enter-active,
.assign-modal-backdrop-leave-active {
    transition: opacity 0.15s ease;
}
.assign-modal-backdrop-enter-from,
.assign-modal-backdrop-leave-to {
    opacity: 0;
}

.assign-modal-panel-enter-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}
.assign-modal-panel-leave-active {
    transition:
        opacity 0.1s ease,
        transform 0.1s ease;
}
.assign-modal-panel-enter-from,
.assign-modal-panel-leave-to {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
}
</style>
