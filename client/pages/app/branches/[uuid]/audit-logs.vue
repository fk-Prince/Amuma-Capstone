<template>
    <div
        class="min-h-screen-header bg-[#EEF3FB] dark:bg-surface flex flex-col"
    >
        <div
            class="rounded-lg bg-white shadow-sm border border-[#E4EFED] overflow-visible flex flex-col flex-1 min-h-0 dark:bg-secondary dark:border-white/10"
        >
            <div
                class="flex flex-col gap-3 px-6 py-4 border-b border-[#E4EFED] shrink-0 dark:border-white/10"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <AuditLogFilter
                        :search="search"
                        :module="moduleFilter"
                        :action="actionFilter"
                        :date-from="dateFrom"
                        :date-to="dateTo"
                        @update:search="search = $event"
                        @update:module="moduleFilter = $event"
                        @update:action="actionFilter = $event"
                        @update:dateFrom="dateFrom = $event"
                        @update:dateTo="dateTo = $event"
                    />

                    <div class="flex shrink-0 items-center gap-2 sm:ml-auto">
                        <ViewModeToggle
                            :model-value="viewMode"
                            @update:model-value="setViewMode"
                        />

                        <button
                            type="button"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[#E4EFED] text-slate-500 transition hover:bg-slate-50 hover:text-primary disabled:opacity-60 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                            aria-label="Refresh audit logs"
                            :disabled="loading"
                            @click="fetchLogs"
                        >
                            <RefreshCw
                                class="h-4 w-4"
                                :class="{ 'animate-spin': loading }"
                            />
                        </button>
                    </div>
                </div>
            </div>

            <AuditLogList
                :logs="logs"
                :loading="loading"
                :current-page="currentPage"
                :total-items="totalItems"
                :page-size="PAGE_SIZE"
                :view="viewMode"
                @select="openLog"
                @page-change="onPageChange"
            />
        </div>

        <AuditLogDetailModal
            :log="selectedLog"
            :detail="detail"
            :loading="detailLoading"
            @close="closeLog"
        />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { useDebounceFn } from "@vueuse/core";
import { RefreshCw } from "lucide-vue-next";
import AuditLogList from "~/components/sections/app/AuditLog/AuditLogList.vue";
import AuditLogFilter from "~/components/sections/app/AuditLog/AuditLogFilter.vue";
import AuditLogDetailModal from "~/components/sections/app/AuditLog/AuditLogDetailModal.vue";
import { auditLogService } from "~/api/audit-log/AuditLogService";
import type { AuditLog, AuditLogDetail } from "~/types/audit-log";
import { useToast } from "~/composables/useToast";
import ViewModeToggle from "~/components/ui/ViewModeToggle.vue";
import { useListView } from "~/composables/useListView";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Audit Logs" });

const PAGE_SIZE = 15;

const route = useRoute();
const uuid = route.params.uuid as string;

const { error } = useToast();
const { view: viewMode, setView: setViewMode } = useListView();

const logs = ref<AuditLog[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const totalItems = ref(0);
const search = ref("");
const moduleFilter = ref("");
const actionFilter = ref("");
const dateFrom = ref("");
const dateTo = ref("");

const selectedLog = ref<AuditLog | null>(null);
const detail = ref<AuditLogDetail | null>(null);
const detailLoading = ref(false);

const fetchLogs = async () => {
    loading.value = true;

    try {
        const res: any = await auditLogService.list({
            branch_uuid: uuid,
            page: currentPage.value,
            per_page: PAGE_SIZE,
            search: search.value.trim() || undefined,
            module: moduleFilter.value || undefined,
            action: actionFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        });

        logs.value = res.data ?? [];
        totalItems.value = res.meta?.total ?? res.total ?? 0;
    } catch (err: any) {
        logs.value = [];
        error(err?.data?.message || err?.message || "Unable to load audit logs.");
    } finally {
        loading.value = false;
    }
};

const refetchFromFirstPage = () => {
    currentPage.value = 1;
    fetchLogs();
};

const debouncedSearch = useDebounceFn(refetchFromFirstPage, 400);

watch(search, debouncedSearch);
watch([moduleFilter, actionFilter, dateFrom, dateTo], refetchFromFirstPage);

const onPageChange = (page: number) => {
    currentPage.value = page;
    fetchLogs();
};

const openLog = async (log: AuditLog) => {
    selectedLog.value = log;
    detail.value = null;
    detailLoading.value = true;

    try {
        const res: any = await auditLogService.show(log.uuid, uuid);

        if (selectedLog.value?.uuid === log.uuid) {
            detail.value = res.data ?? null;
        }
    } catch (err: any) {
        error(err?.data?.message || err?.message || "Unable to load the details.");
    } finally {
        detailLoading.value = false;
    }
};

const closeLog = () => {
    selectedLog.value = null;
    detail.value = null;
};

onMounted(fetchLogs);
</script>
