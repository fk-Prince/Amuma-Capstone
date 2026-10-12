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
                    <InquiryFilter
                        :search="search"
                        :status="statusFilter"
                        :date-from="dateFrom"
                        :date-to="dateTo"
                        :counts="statusCounts"
                        @update:search="search = $event"
                        @update:status="statusFilter = $event"
                        @update:dateFrom="dateFrom = $event"
                        @update:dateTo="dateTo = $event"
                    />

                    <div class="flex shrink-0 items-center gap-2 sm:ml-auto">
                        <button
                            type="button"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[#E4EFED] text-slate-500 transition hover:bg-slate-50 hover:text-primary disabled:opacity-60 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                            aria-label="Refresh inquiries"
                            :disabled="loading"
                            @click="fetchInquiries"
                        >
                            <RefreshCw
                                class="h-4 w-4"
                                :class="{ 'animate-spin': loading }"
                            />
                        </button>
                    </div>
                </div>
            </div>

            <InquiryList
                :inquiries="inquiries"
                :loading="loading"
                :current-page="currentPage"
                :total-items="totalItems"
                :page-size="PAGE_SIZE"
                @select="openInquiry"
                @page-change="onPageChange"
            />
        </div>

        <InquiryDetailModal
            :inquiry="selectedInquiry"
            :can-update="canUpdateInquiries"
            :saving="saving"
            :sending="sendingReply"
            @close="selectedInquiry = null"
            @status="changeStatus"
            @reply="sendReply"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { useDebounceFn } from "@vueuse/core";
import { RefreshCw } from "lucide-vue-next";
import InquiryList from "~/components/sections/app/Inquiry/InquiryList.vue";
import InquiryFilter from "~/components/sections/app/Inquiry/InquiryFilter.vue";
import InquiryDetailModal from "~/components/sections/app/Inquiry/InquiryDetailModal.vue";
import { inquiryService } from "~/api/inquiry/InquiryService";
import type { Inquiry, InquiryStatus } from "~/types/inquiry";
import { Modules } from "~/types/module";
import { usePermissions } from "~/composables/usePermission";
import { useToast } from "~/composables/useToast";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Inquiries" });

const PAGE_SIZE = 15;

const route = useRoute();
const uuid = route.params.uuid as string;

const { canUpdate } = usePermissions();
const { success, error } = useToast();

const canUpdateInquiries = computed(() => canUpdate(Modules.Inquiries));

const inquiries = ref<Inquiry[]>([]);
const loading = ref(false);
const saving = ref(false);
const currentPage = ref(1);
const totalItems = ref(0);
const search = ref("");
const statusFilter = ref<InquiryStatus | "all">("all");
const dateFrom = ref("");
const dateTo = ref("");
const selectedInquiry = ref<Inquiry | null>(null);

const statusCounts = reactive<Record<InquiryStatus, number>>({
    new: 0,
    read: 0,
    resolved: 0,
});

const fetchInquiries = async () => {
    loading.value = true;

    try {
        const res: any = await inquiryService.list({
            branch_uuid: uuid,
            page: currentPage.value,
            per_page: PAGE_SIZE,
            search: search.value.trim() || undefined,
            status: statusFilter.value === "all" ? undefined : statusFilter.value,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        });

        inquiries.value = res.data ?? [];
        totalItems.value = res.meta?.total ?? res.total ?? 0;
        Object.assign(statusCounts, res.status_counts ?? {});
    } catch (err: any) {
        inquiries.value = [];
        error(err?.data?.message || err?.message || "Unable to load inquiries.");
    } finally {
        loading.value = false;
    }
};

const refetchFromFirstPage = () => {
    currentPage.value = 1;
    fetchInquiries();
};

const debouncedSearch = useDebounceFn(refetchFromFirstPage, 400);

watch(search, debouncedSearch);
watch([statusFilter, dateFrom, dateTo], refetchFromFirstPage);

const onPageChange = (page: number) => {
    currentPage.value = page;
    fetchInquiries();
};

const applyStatus = (inquiry: Inquiry, status: InquiryStatus) => {
    if (inquiry.status === status) return;

    statusCounts[inquiry.status] = Math.max(0, statusCounts[inquiry.status] - 1);
    statusCounts[status] += 1;

    const row = inquiries.value.find((item) => item.uuid === inquiry.uuid);
    if (row) row.status = status;
    if (selectedInquiry.value?.uuid === inquiry.uuid) {
        selectedInquiry.value = { ...selectedInquiry.value, status };
    }
};

const updateStatus = async (inquiry: Inquiry, status: InquiryStatus) => {
    await inquiryService.updateStatus(inquiry.uuid, {
        branch_uuid: uuid,
        status,
    });
    applyStatus(inquiry, status);
};

const openInquiry = (inquiry: Inquiry) => {
    selectedInquiry.value = inquiry;

    if (inquiry.status === "new" && canUpdateInquiries.value) {
        updateStatus(inquiry, "read").catch((err) => console.error(err));
    }
};

const changeStatus = async (status: InquiryStatus) => {
    if (!selectedInquiry.value) return;

    saving.value = true;

    try {
        await updateStatus(selectedInquiry.value, status);
        success("Inquiry marked as resolved.");
    } catch (err: any) {
        error(err?.data?.message || err?.message || "Unable to update inquiry.");
    } finally {
        saving.value = false;
    }
};

const sendingReply = ref(false);

const sendReply = async (reply: string) => {
    const inquiry = selectedInquiry.value;
    if (!inquiry) return;

    sendingReply.value = true;

    try {
        const res: any = await inquiryService.reply(inquiry.uuid, {
            branch_uuid: uuid,
            reply,
        });

        const saved = (res?.data ?? null) as Inquiry | null;

        applyStatus(inquiry, "resolved");

        const patch = {
            reply: saved?.reply ?? reply,
            replied_at: saved?.replied_at ?? new Date().toISOString(),
        };

        const row = inquiries.value.find((item) => item.uuid === inquiry.uuid);
        if (row) Object.assign(row, patch);
        if (selectedInquiry.value?.uuid === inquiry.uuid) {
            selectedInquiry.value = { ...selectedInquiry.value, ...patch };
        }

        success(res?.message ?? `Reply sent to ${inquiry.email}.`);
    } catch (err: any) {
        error(err?.data?.message || err?.message || "Unable to send the reply.");
    } finally {
        sendingReply.value = false;
    }
};

onMounted(fetchInquiries);
</script>
