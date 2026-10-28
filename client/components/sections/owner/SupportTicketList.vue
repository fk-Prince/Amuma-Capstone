<script setup lang="ts">
import { computed, watch } from "vue";
import { Eye } from "lucide-vue-next";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import { usePagination } from "~/composables/usePagination";
import {
    SUPPORT_CATEGORY_LABELS,
    SUPPORT_STATUS_CLASSES,
    SUPPORT_STATUS_LABELS,
    type SupportTicket,
} from "~/types/support";
import { stringToDateTime } from "~/utils/time";

const props = withDefaults(
    defineProps<{
        tickets: SupportTicket[];
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
    select: [ticket: SupportTicket];
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
    { key: "code", label: "Ticket" },
    { key: "subject", label: "Subject", primary: true },
    { key: "branch", label: "From" },
    { key: "category", label: "Topic" },
    { key: "status", label: "Status", badge: true },
    { key: "activity", label: "Last activity" },
    { key: "actions", label: "", align: "right" },
];

const rows = computed(() =>
    props.tickets.map((ticket) => ({
        id: ticket.uuid,
        code: ticket.ticket_code,
        subject: ticket.subject,
        submitter: ticket.submitted_by.name || ticket.submitted_by.email,
        agency: ticket.source === "public" ? "Public" : ticket.agency.name,
        branch:
            ticket.source === "public"
                ? ticket.submitted_by.email
                : ticket.branch.name,
        category: SUPPORT_CATEGORY_LABELS[ticket.category],
        status: ticket.status,
        activity: stringToDateTime(ticket.last_reply_at ?? ticket.created_at),
        raw: ticket,
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
        empty-title="No support tickets"
        empty-description="Tickets sent by branches and users will show up here."
        :row-key="(row) => row.id"
        :on-row-click="(row) => emit('select', row.raw)"
        @page-change="emit('page-change', $event)"
    >
        <template #cell-code="{ row }">
            <span
                class="whitespace-nowrap font-medium text-slate-600 dark:text-gray-300"
            >
                {{ row.code }}
            </span>
        </template>

        <template #cell-subject="{ row }">
            <div class="min-w-0">
                <p
                    class="max-w-xs truncate font-medium text-slate-800 dark:text-white"
                    :title="row.subject"
                >
                    {{ row.subject }}
                </p>
                <p class="truncate text-xs text-slate-400 dark:text-gray-500">
                    {{ row.submitter }}
                </p>
            </div>
        </template>

        <template #cell-branch="{ row }">
            <div class="min-w-0">
                <p class="truncate text-slate-700 dark:text-gray-200">
                    {{ row.agency }}
                </p>
                <p class="truncate text-xs text-slate-400 dark:text-gray-500">
                    {{ row.branch }}
                </p>
            </div>
        </template>

        <template #cell-status="{ row }">
            <span
                class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium"
                :class="SUPPORT_STATUS_CLASSES[row.status]"
            >
                {{ SUPPORT_STATUS_LABELS[row.status] }}
            </span>
        </template>

        <template #cell-activity="{ row }">
            <span class="whitespace-nowrap text-slate-600 dark:text-gray-300">
                {{ row.activity }}
            </span>
        </template>

        <template #cell-actions="{ row }">
            <button
                type="button"
                title="Open ticket"
                aria-label="Open ticket"
                class="text-slate-400 hover:text-slate-700 dark:text-gray-500 dark:hover:text-gray-200"
                @click.stop="emit('select', row.raw)"
            >
                <Eye class="h-4 w-4" />
            </button>
        </template>
    </DataTable>
</template>
