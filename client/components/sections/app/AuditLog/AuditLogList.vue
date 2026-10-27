<script setup lang="ts">
import { computed, watch } from "vue";
import { Eye } from "lucide-vue-next";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import { usePagination } from "~/composables/usePagination";
import {
    AUDIT_ACTION_CLASSES,
    auditActionLabel,
    auditModuleLabel,
    auditSummary,
    humanize,
    type AuditLog,
} from "~/types/audit-log";
import { stringToDateTime } from "~/utils/time";

const props = withDefaults(
    defineProps<{
        logs: AuditLog[];
        loading: boolean;
        currentPage?: number;
        totalItems?: number;
        pageSize?: number;
        view?: "table" | "card";
    }>(),
    {
        currentPage: 1,
        totalItems: 0,
        pageSize: 15,
    },
);

const emit = defineEmits<{
    select: [log: AuditLog];
    "page-change": [page: number];
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
    { key: "when", label: "When" },
    { key: "actor", label: "User", primary: true },
    { key: "action", label: "Action", badge: true },
    { key: "module", label: "Module" },
    { key: "summary", label: "Details" },
    { key: "actions", label: "", align: "right" },
];

const rows = computed(() =>
    props.logs.map((log) => ({
        id: log.uuid,
        when: stringToDateTime(log.created_at),
        actor: log.actor_name || log.actor_email || "System",
        email: log.actor_name ? log.actor_email : null,
        role: log.actor_role ? humanize(log.actor_role) : null,
        action: log.action,
        module: auditModuleLabel(log.module),
        summary: auditSummary(log),
        raw: log,
    })),
);
</script>

<template>
    <DataTable
        class="flex-1 h-full rounded-t-none rounded-b-xl border-none"
        :columns="columns"
        :rows="rows"
        :pagination="pagination"
        :loading="loading"
        :view="view"
        :searchable="false"
        empty-title="No activity found"
        empty-description="Actions taken in this branch will be recorded here."
        :row-key="(row) => row.id"
        :on-row-click="(row) => emit('select', row.raw)"
        @page-change="emit('page-change', $event)"
    >
        <template #cell-when="{ row }">
            <span class="whitespace-nowrap text-slate-600 dark:text-gray-300">
                {{ row.when }}
            </span>
        </template>

        <template #cell-actor="{ row }">
            <div class="min-w-0">
                <p
                    class="truncate font-medium text-slate-800 dark:text-white"
                >
                    {{ row.actor }}
                </p>
                <p class="truncate text-xs text-slate-400 dark:text-gray-500">
                    {{ [row.role, row.email].filter(Boolean).join(" · ") }}
                </p>
            </div>
        </template>

        <template #cell-action="{ row }">
            <span
                class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium"
                :class="
                    AUDIT_ACTION_CLASSES[row.action] ??
                    'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300'
                "
            >
                {{ auditActionLabel(row.action) }}
            </span>
        </template>

        <template #cell-summary="{ row }">
            <p
                class="line-clamp-2 max-w-sm text-slate-600 md:line-clamp-none md:truncate dark:text-gray-300"
                :title="row.summary"
            >
                {{ row.summary }}
            </p>
        </template>

        <template #cell-actions="{ row }">
            <button
                type="button"
                title="View details"
                aria-label="View details"
                class="text-slate-400 hover:text-slate-700 dark:text-gray-500 dark:hover:text-gray-200"
                @click.stop="emit('select', row.raw)"
            >
                <Eye class="h-4 w-4" />
            </button>
        </template>
    </DataTable>
</template>
