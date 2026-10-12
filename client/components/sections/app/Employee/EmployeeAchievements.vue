<script setup lang="ts">
import { Award, Plus, X } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import type { EmployeeAchievement } from "~/types/employee";

defineProps<{
    items: EmployeeAchievement[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    add: [];
    remove: [index: number];
    fileSelected: [index: number, event: Event];
}>();

const todayStr = new Date().toISOString().split("T")[0];
</script>

<template>
    <div class="space-y-5 p-8">
        <div class="flex items-center justify-between gap-4">
            <div
                class="flex items-center gap-2 text-gray-700 dark:text-gray-300"
            >
                <Award class="h-4 w-4 text-primary" />
                <p class="text-sm">
                    Certificates and achievements earned by this employee.
                </p>
            </div>

            <button
                v-if="!disabled"
                type="button"
                @click="emit('add')"
                class="inline-flex shrink-0 items-center gap-1.5 text-sm font-medium text-primary hover:underline underline-offset-2"
            >
                <Plus class="h-4 w-4" /> Add certificate / achievement
            </button>
        </div>

        <div
            v-if="!items.length"
            class="rounded-xl border border-dashed border-slate-200 p-10 text-center text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
        >
            No certificates or achievements added.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(item, index) in items"
                :key="index"
                class="grid items-end gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_14rem_14rem_auto] dark:border-white/10 dark:bg-secondary"
            >
                <BaseInput
                    v-model="item.label"
                    label="Label"
                    placeholder="e.g. BLS Certification, Employee of the Month"
                    :disabled="disabled"
                />

                <DatePickerField
                    :model-value="item.date ?? ''"
                    @update:model-value="item.date = $event"
                    label="Date"
                    :max="todayStr"
                    :default-to-today="false"
                    placeholder="Select date"
                    :disabled="disabled"
                />

                <div>
                    <p
                        class="mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        File
                    </p>
                    <div class="flex items-center gap-2">
                        <label
                            class="flex-1 truncate rounded-lg border border-dashed border-slate-300 px-3 py-2.5 text-center text-sm text-slate-500 dark:border-white/20 dark:text-gray-400"
                            :class="
                                disabled
                                    ? 'cursor-default'
                                    : 'cursor-pointer hover:border-primary hover:text-primary'
                            "
                        >
                            {{
                                item.file?.name ||
                                (item.url ? "File attached" : "Choose file")
                            }}
                            <input
                                type="file"
                                class="hidden"
                                :disabled="disabled"
                                @change="emit('fileSelected', index, $event)"
                            />
                        </label>

                        <a
                            v-if="item.url"
                            :href="item.url"
                            target="_blank"
                            rel="noopener"
                            class="shrink-0 text-sm font-medium text-primary hover:underline"
                        >
                            View
                        </a>
                    </div>
                </div>

                <button
                    v-if="!disabled"
                    type="button"
                    @click="emit('remove', index)"
                    class="mb-2.5 shrink-0 text-slate-400 transition hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
