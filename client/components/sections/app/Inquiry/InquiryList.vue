<script setup lang="ts">
import { computed, watch } from "vue";
import { Eye } from "lucide-vue-next";
import DataTable, { type DataTableColumn } from "~/components/ui/DataTable.vue";
import { usePagination } from "~/composables/usePagination";
import {
    INQUIRY_STATUS_CLASSES,
    INQUIRY_STATUS_LABELS,
    type Inquiry,
} from "~/types/inquiry";
import { formatPhone } from "~/utils/phone";
import { stringToDateTime } from "~/utils/time";

const props = withDefaults(
    defineProps<{
        inquiries: Inquiry[];
        loading: boolean;
        currentPage?: number;
        totalItems?: number;
        pageSize?: number;
    }>(),
    {
        currentPage: 1,
        totalItems: 0,
        pageSize: 10,
    },
);

const emit = defineEmits<{
    select: [inquiry: Inquiry];
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
    { key: "name", label: "From" },
    { key: "phone", label: "Phone Number" },
    { key: "message", label: "Message" },
    { key: "received", label: "Received" },
    { key: "status", label: "Status" },
    { key: "actions", label: "Action", align: "right" },
];

const rows = computed(() =>
    props.inquiries.map((inquiry) => ({
        id: inquiry.uuid,
        name: inquiry.full_name,
        initials: `${inquiry.first_name?.[0] ?? ""}${inquiry.last_name?.[0] ?? ""}`.toUpperCase(),
        email: inquiry.email,
        city: inquiry.city,
        phone: formatPhone(inquiry.phone_number) || "—",
        message: inquiry.message,
        received: stringToDateTime(inquiry.created_at),
        status: inquiry.status,
        raw: inquiry,
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
        :searchable="false"
        empty-title="No inquiries found"
        empty-description="Questions sent from your provider page will show up here."
        :row-key="(row) => row.id"
        :on-row-click="(row) => emit('select', row.raw)"
        @page-change="emit('page-change', $event)"
    >

        <template #cell-name="{ row }">
            <div class="flex items-center gap-3">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                    :class="
                        row.status === 'new'
                            ? 'bg-primary text-white'
                            : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-300'
                    "
                >
                    {{ row.initials }}
                </span>

                <div class="min-w-0">
                    <p
                        class="truncate text-slate-800 dark:text-white"
                        :class="row.status === 'new' ? 'font-semibold' : 'font-medium'"
                    >
                        {{ row.name }}
                    </p>
                    <p class="truncate text-xs text-slate-400 dark:text-gray-500">
                        {{ row.email }}<template v-if="row.city"> · {{ row.city }}</template>
                    </p>
                </div>
            </div>
        </template>

        <template #cell-message="{ row }">
            <p
                class="max-w-xs truncate"
                :class="
                    row.status === 'new'
                        ? 'text-slate-700 dark:text-gray-200'
                        : 'text-slate-500 dark:text-gray-400'
                "
                :title="row.message"
            >
                {{ row.message }}
            </p>
        </template>

        <template #cell-status="{ row }">
            <span
                class="rounded-full px-3 py-1 text-xs font-medium"
                :class="INQUIRY_STATUS_CLASSES[row.status as Inquiry['status']]"
            >
                {{ INQUIRY_STATUS_LABELS[row.status as Inquiry["status"]] }}
            </span>
        </template>

        <template #cell-actions="{ row }">
            <button
                type="button"
                title="View inquiry"
                aria-label="View inquiry"
                class="text-slate-400 hover:text-slate-700 dark:text-gray-500 dark:hover:text-gray-200"
                @click.stop="emit('select', row.raw)"
            >
                <Eye class="h-4 w-4" />
            </button>
        </template>
    </DataTable>
</template>
