<template>
    <div class="flex min-h-screen-header flex-col dark:bg-surface">
        <div
            class="mx-auto flex w-full flex-1 flex-col space-y-4 px-3 pb-4 sm:px-0 sm:pb-0 sm:pt-2"
        >
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1
                        class="text-xl font-bold text-slate-900 dark:text-white"
                    >
                        Inquiries
                    </h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-gray-400">
                        Questions sent by families from your provider page.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value ?? 'all'"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition"
                        :class="
                            activeStatus === tab.value
                                ? 'border-primary bg-primary text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-primary/40 dark:border-white/10 dark:bg-secondary dark:text-gray-300'
                        "
                        @click="selectStatus(tab.value)"
                    >
                        {{ tab.label }}
                        <span
                            v-if="tab.count"
                            class="rounded-full px-1.5 text-xs font-semibold"
                            :class="
                                activeStatus === tab.value
                                    ? 'bg-white/20 text-white'
                                    : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-400'
                            "
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </div>
            </div>

            <div
                class="flex flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <InquiryList
                    :inquiries="inquiries"
                    :loading="loading"
                    :current-page="currentPage"
                    :total-items="totalItems"
                    :page-size="PAGE_SIZE"
                    @select="openInquiry"
                    @search="onSearch"
                    @page-change="onPageChange"
                />
            </div>
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
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute } from "vue-router";
import InquiryList from "~/components/sections/app/Inquiry/InquiryList.vue";
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
const activeStatus = ref<InquiryStatus | null>(null);
const selectedInquiry = ref<Inquiry | null>(null);

const statusCounts = reactive<Record<InquiryStatus, number>>({
    new: 0,
    read: 0,
    resolved: 0,
});

const tabs = computed(() => [
    {
        label: "All",
        value: null,
        count: statusCounts.new + statusCounts.read + statusCounts.resolved,
    },
    { label: "New", value: "new" as const, count: statusCounts.new },
    { label: "Read", value: "read" as const, count: statusCounts.read },
    {
        label: "Resolved",
        value: "resolved" as const,
        count: statusCounts.resolved,
    },
]);

const fetchInquiries = async () => {
    loading.value = true;

    try {
        const res: any = await inquiryService.list({
            branch_uuid: uuid,
            page: currentPage.value,
            per_page: PAGE_SIZE,
            search: search.value || undefined,
            status: activeStatus.value || undefined,
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

const selectStatus = (status: InquiryStatus | null) => {
    activeStatus.value = status;
    currentPage.value = 1;
    fetchInquiries();
};

const onSearch = (query: string) => {
    search.value = query;
    currentPage.value = 1;
    fetchInquiries();
};

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
        success(
            status === "resolved"
                ? "Inquiry marked as resolved."
                : "Inquiry reopened.",
        );
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
