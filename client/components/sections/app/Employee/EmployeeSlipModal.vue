<template>
    <Teleport to="body">
        <div
            v-if="slip"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-secondary/50 p-4 backdrop-blur-sm no-print dark:bg-white/10"
            @click.self="emit('close')"
        >
            <div
                class="flex max-h-[88vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-secondary"
            >
                <div
                    class="flex items-start justify-between gap-3 border-b border-primary-100 px-6 py-4 dark:border-white/10"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-primary-600 dark:text-primary-300"
                        >
                            New employee
                        </p>

                        <h3
                            class="mt-1 text-lg font-semibold text-secondary dark:text-white"
                        >
                            Employee slip
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        @click="emit('close')"
                    >
                        <AppIcon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <EmployeeSlipSheet :slip="slip" />
                </div>

                <div
                    class="flex items-center justify-end gap-2 border-t border-primary-100 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2.5 text-sm font-medium text-muted transition hover:bg-slate-100 hover:text-secondary dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        @click="emit('close')"
                    >
                        Done
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600"
                        @click="handlePrint"
                    >
                        <AppIcon name="printer" class="h-4 w-4" />
                        Print slip
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Lives outside the dialog so the print stylesheet can keep this alone. -->
    <Teleport to="body">
        <div v-if="slip" class="print-slip-sheet hidden print:block">
            <EmployeeSlipSheet :slip="slip" print />
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { nextTick } from "vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import EmployeeSlipSheet from "./EmployeeSlipSheet.vue";
import type { EmployeeSlip } from "~/types/employee-slip";

defineProps<{
    slip: EmployeeSlip | null;
}>();

const emit = defineEmits<{
    (event: "close"): void;
}>();

async function handlePrint() {
    document.body.classList.add("printing-slip");

    await nextTick();

    await new Promise((resolve) =>
        requestAnimationFrame(() => requestAnimationFrame(resolve)),
    );

    try {
        window.print();
    } finally {
        document.body.classList.remove("printing-slip");
    }
}
</script>
