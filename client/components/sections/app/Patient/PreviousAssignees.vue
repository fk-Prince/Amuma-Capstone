<template>
    <div v-if="assignees.length">
        <p
            class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
        >
            Previously Assigned
        </p>

        <div class="mt-2 space-y-2">
            <div
                v-for="assignee in assignees"
                :key="assignee.employee_id"
                class="flex items-center justify-between gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-3 py-2 dark:border-white/10 dark:bg-white/5"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-[11px] font-semibold text-slate-500 dark:bg-white/10 dark:text-gray-400"
                    >
                        <img
                            v-if="assignee.avatar"
                            :src="assignee.avatar"
                            class="h-full w-full object-cover grayscale"
                        />

                        <template v-else>
                            {{ initials(assignee.full_name) }}
                        </template>
                    </span>

                    <div class="min-w-0">
                        <p
                            class="truncate text-sm font-medium text-slate-500 dark:text-gray-400"
                        >
                            {{ assignee.full_name || "Unknown employee" }}
                        </p>

                        <p
                            class="truncate text-xs capitalize text-slate-400 dark:text-gray-500"
                        >
                            {{ assignee.employee_role ?? "Staff" }}
                            <template v-if="assignee.note">
                                · {{ assignee.note }}
                            </template>
                        </p>
                    </div>
                </div>

                <span
                    class="shrink-0 rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-500 dark:bg-white/10 dark:text-gray-400"
                >
                    Not active
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { initials } from "~/utils/user";
import type { PreviousAssignee } from "~/types/schedule";

defineProps<{
    assignees: PreviousAssignee[];
}>();
</script>
