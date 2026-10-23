<template>
    <Teleport to="body">
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
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
                @click.self="!saving && emit('close')"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Add care note"
                    class="w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
                >
                    <div
                        class="flex items-center justify-between gap-4 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/20 text-primary"
                            >
                                <NotebookPen class="h-5 w-5" />
                            </div>

                            <div>
                                <h2
                                    class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                                >
                                    Add care note
                                </h2>
                                <p
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    A note about this patient.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            :disabled="saving"
                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 disabled:opacity-50 dark:hover:bg-white/10"
                            @click="emit('close')"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="px-6 py-5">
                        <EmployeeNoteForm
                            ref="form"
                            :saving="saving"
                            :rows="5"
                            show-cancel
                            placeholder="Write a note about this patient..."
                            @submit="emit('submit', $event)"
                            @cancel="emit('close')"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { NotebookPen, X } from "lucide-vue-next";
import EmployeeNoteForm from "~/components/sections/app/Employee/EmployeeNoteForm.vue";

defineProps<{
    open: boolean;
    saving?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", text: string): void;
}>();

const form = ref<{ clear: () => void } | null>(null);

defineExpose({ clear: () => form.value?.clear() });
</script>
