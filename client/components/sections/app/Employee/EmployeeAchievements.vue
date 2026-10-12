<script setup lang="ts">
import { Award, Plus, X } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import type { EmployeeAchievement } from "~/types/employee";

const props = defineProps<{
    items: EmployeeAchievement[];
    errors?: Record<string, string>;
    disabled?: boolean;
}>();

const errorFor = (index: number, field: "label" | "date" | "file") =>
    props.errors?.[`achievements.${index}.${field}`] ?? "";

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
                v-if="!disabled && items.length"
                type="button"
                @click="emit('add')"
                class="inline-flex shrink-0 items-center gap-1.5 text-sm font-medium text-primary hover:underline underline-offset-2"
            >
                <Plus class="h-4 w-4" /> Add certificate / achievement
            </button>
        </div>

        <div
            v-if="!items.length"
            class="flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center dark:border-white/15 dark:bg-white/5"
        >
            <span
                class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary"
            >
                <Award class="h-6 w-6" />
            </span>

            <p
                class="mt-4 text-sm font-semibold text-gray-900 dark:text-white"
            >
                No certificates or achievements yet
            </p>
            <p class="mt-1 max-w-sm text-xs text-slate-400 dark:text-gray-500">
                {{
                    disabled
                        ? "This employee has no certificates or achievements on record."
                        : "Add licenses, trainings, or awards along with a copy of the file and the date received."
                }}
            </p>

            <button
                v-if="!disabled"
                type="button"
                @click="emit('add')"
                class="mt-5 inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition hover:opacity-90"
            >
                <Plus class="h-4 w-4" /> Add certificate / achievement
            </button>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(item, index) in items"
                :key="index"
                class="grid items-start gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_14rem_14rem_auto] dark:border-white/10 dark:bg-secondary"
            >
                <BaseInput
                    v-model="item.label"
                    label="Title"
                    placeholder="e.g. BLS Certification, Employee of the Month"
                    :error="errorFor(index, 'label')"
                    :disabled="disabled"
                />

                <DatePickerField
                    :model-value="item.date ?? ''"
                    @update:model-value="item.date = $event"
                    label="Date"
                    :max="todayStr"
                    :default-to-today="false"
                    placeholder="Select date"
                    :error="errorFor(index, 'date')"
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
                            class="flex-1 truncate rounded-lg border border-dashed px-3 py-2.5 text-center text-sm"
                            :class="[
                                errorFor(index, 'file')
                                    ? 'border-red-400 text-red-500 dark:border-red-500/60 dark:text-red-400'
                                    : 'border-slate-300 text-slate-500 dark:border-white/20 dark:text-gray-400',
                                disabled
                                    ? 'cursor-default'
                                    : 'cursor-pointer hover:border-primary hover:text-primary',
                            ]"
                        >
                            <template v-if="item.file">
                                {{ item.file.name }}
                            </template>
                            <a
                                v-else-if="item.url"
                                :href="item.url"
                                target="_blank"
                                rel="noopener"
                                class="font-medium text-primary hover:underline"
                                @click.stop
                            >
                                View
                            </a>
                            <template v-else>Choose file</template>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                class="hidden"
                                :disabled="disabled"
                                @change="emit('fileSelected', index, $event)"
                            />
                        </label>
                    </div>
                    <p
                        v-if="errorFor(index, 'file')"
                        class="mt-1 text-xs text-red-500 dark:text-red-400"
                    >
                        {{ errorFor(index, "file") }}
                    </p>
                    <p
                        v-else-if="!disabled"
                        class="mt-1 text-xs text-slate-400 dark:text-gray-500"
                    >
                        Supports PDF, JPG, JPEG, PNG · Max 10MB
                    </p>
                </div>

                <button
                    v-if="!disabled"
                    type="button"
                    @click="emit('remove', index)"
                    class="shrink-0 justify-self-end text-slate-400 transition hover:text-red-500 md:mt-10 dark:text-gray-500 dark:hover:text-red-400"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <button
                v-if="!disabled"
                type="button"
                @click="emit('add')"
                class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-slate-300 py-3 text-sm font-medium text-primary transition hover:border-primary hover:bg-primary/5 dark:border-white/15"
            >
                <Plus class="h-4 w-4" /> Add another
            </button>
        </div>
    </div>
</template>
