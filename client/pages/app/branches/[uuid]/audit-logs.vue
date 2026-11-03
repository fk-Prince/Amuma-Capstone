<template>
    <div class="dashboard-fit min-h-screen-header flex flex-col gap-4 font-sans">
        <!-- Header: title, totals, filters -->
        <header
            class="shrink-0 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div class="flex flex-wrap items-center gap-3 p-4 sm:p-5">
                <div class="flex min-w-0 flex-1 basis-64 items-center gap-3">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary dark:bg-primary-500/15 dark:text-primary-300"
                    >
                        <History class="h-5 w-5" />
                    </span>

                    <div class="min-w-0">
                        <h1
                            class="text-lg font-bold tracking-tight text-secondary sm:text-xl dark:text-white"
                        >
                            Audit Logs
                        </h1>
                        <p class="text-sm text-muted dark:text-gray-400">
                            Who did what in this branch, and when.
                        </p>
                    </div>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-white/10 dark:text-gray-300"
                    >
                        {{ totalItems }} {{ totalItems === 1 ? "entry" : "entries" }}
                    </span>

                    <ViewModeToggle
                        :model-value="viewMode"
                        @update:model-value="setViewMode"
                    />

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-primary hover:text-primary disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
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

            <div
                class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:flex-wrap sm:items-center sm:px-5 dark:border-white/10"
            >
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

                <button
                    v-if="hasFilters"
                    type="button"
                    class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-xl px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 sm:self-auto dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                    @click="clearFilters"
                >
                    <X class="h-3.5 w-3.5" />
                    Clear filters
                </button>
            </div>
        </header>

        <!-- Activity -->
        <section
            class="dashboard-fit-fill flex min-h-[24rem] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
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
        </section>

        <AuditLogDetailModal
            :log="selectedLog"
            :detail="detail"
            :loading="detailLoading"
            @close="closeLog"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { useDebounceFn } from "@vueuse/core";
import { History, RefreshCw, X } from "lucide-vue-next";
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

const hasFilters = computed(
    () =>
        Boolean(search.value || moduleFilter.value || actionFilter.value || dateFrom.value || dateTo.value),
);

// Resets every filter; the watchers above reload the list.
function clearFilters() {
    search.value = "";
    moduleFilter.value = "";
    actionFilter.value = "";
    dateFrom.value = "";
    dateTo.value = "";
}

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
