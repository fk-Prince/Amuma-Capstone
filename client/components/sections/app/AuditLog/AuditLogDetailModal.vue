<script setup lang="ts">
import { computed } from "vue";
import { LoaderCircle, X } from "lucide-vue-next";
import {
    AUDIT_ACTION_CLASSES,
    auditActionLabel,
    auditModuleLabel,
    auditSummary,
    auditValue,
    humanize,
    type AuditLog,
    type AuditLogDetail,
} from "~/types/audit-log";
import { stringToDateTime } from "~/utils/time";

const props = defineProps<{
    log: AuditLog | null;
    detail: AuditLogDetail | null;
    loading: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const changes = computed(() => {
    const before = props.detail?.old_values ?? {};
    const after = props.detail?.new_values ?? {};

    return [...new Set([...Object.keys(before), ...Object.keys(after)])].map(
        (field) => ({
            field: humanize(field),
            before: field in before ? auditValue(before[field]) : "—",
            after: field in after ? auditValue(after[field]) : "—",
        }),
    );
});

const actor = computed(
    () => props.log?.actor_name || props.log?.actor_email || "System",
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="log"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Audit log details"
                    class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h2
                                class="truncate text-base font-semibold text-slate-900 dark:text-white"
                            >
                                {{ auditSummary(log) }}
                            </h2>
                            <p class="text-xs text-slate-400 dark:text-gray-500">
                                {{ stringToDateTime(log.created_at) }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="rounded-full px-3 py-1 text-xs font-medium"
                                :class="
                                    AUDIT_ACTION_CLASSES[log.action] ??
                                    'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300'
                                "
                            >
                                {{ auditActionLabel(log.action) }}
                            </span>
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                aria-label="Close"
                                @click="emit('close')"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-slate-400 dark:text-gray-500">
                                    User
                                </dt>
                                <dd class="text-slate-800 dark:text-white">
                                    {{ actor }}
                                    <span
                                        v-if="log.actor_role"
                                        class="text-slate-400 dark:text-gray-500"
                                    >
                                        · {{ humanize(log.actor_role) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400 dark:text-gray-500">
                                    Module
                                </dt>
                                <dd class="text-slate-800 dark:text-white">
                                    {{ auditModuleLabel(log.module) }}
                                </dd>
                            </div>
                            <div v-if="log.record">
                                <dt class="text-xs text-slate-400 dark:text-gray-500">
                                    Record
                                </dt>
                                <dd class="text-slate-800 dark:text-white">
                                    {{ humanize(log.record) }}
                                </dd>
                            </div>
                            <div v-if="detail?.ip_address">
                                <dt class="text-xs text-slate-400 dark:text-gray-500">
                                    IP address
                                </dt>
                                <dd class="text-slate-800 dark:text-white">
                                    {{ detail.ip_address }}
                                </dd>
                            </div>
                        </dl>

                        <div
                            v-if="loading"
                            class="flex items-center justify-center py-6 text-slate-400"
                        >
                            <LoaderCircle class="h-5 w-5 animate-spin" />
                        </div>

                        <div
                            v-else-if="changes.length"
                            class="overflow-hidden rounded-xl border border-slate-200 dark:border-white/10"
                        >
                            <table class="w-full text-sm">
                                <thead>
                                    <tr
                                        class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-white/5 dark:text-gray-400"
                                    >
                                        <th class="px-4 py-2.5 font-semibold">Field</th>
                                        <th class="px-4 py-2.5 font-semibold">Before</th>
                                        <th class="px-4 py-2.5 font-semibold">After</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 dark:divide-white/10"
                                >
                                    <tr v-for="change in changes" :key="change.field">
                                        <td
                                            class="px-4 py-2.5 font-medium text-slate-700 dark:text-gray-200"
                                        >
                                            {{ change.field }}
                                        </td>
                                        <td
                                            class="break-all px-4 py-2.5 text-slate-500 dark:text-gray-400"
                                        >
                                            {{ change.before }}
                                        </td>
                                        <td
                                            class="break-all px-4 py-2.5 text-slate-800 dark:text-white"
                                        >
                                            {{ change.after }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
