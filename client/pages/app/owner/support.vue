<template>
    <div class="flex flex-1 flex-col">
        <div
            class="flex flex-1 min-h-0 flex-col overflow-visible rounded-lg border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div
                class="flex shrink-0 flex-col gap-3 border-b border-slate-100 px-6 py-4 lg:flex-row lg:items-center dark:border-white/10"
            >
                <div class="w-full min-w-0 lg:max-w-sm lg:flex-1">
                    <BaseInput
                        v-model="search"
                        mode="text"
                        :is-search="true"
                        placeholder="Ticket #, subject, agency or branch"
                        input-class="px-4 py-2.5 rounded-lg w-full"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center">
                    <Combobox
                        v-model="statusFilter"
                        class="sm:w-48"
                        input-class="px-4 py-2.5 text-sm"
                        placeholder="Status"
                        :items="statusOptions"
                    />

                    <Combobox
                        v-model="sourceFilter"
                        class="sm:w-48"
                        input-class="px-4 py-2.5 text-sm"
                        placeholder="Source"
                        :items="sourceOptions"
                    />
                </div>

                <div class="flex shrink-0 items-center gap-2 lg:ml-auto">
                    <ViewModeToggle
                        :model-value="viewMode"
                        @update:model-value="setViewMode"
                    />

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-primary disabled:opacity-60 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                        aria-label="Refresh tickets"
                        :disabled="loading"
                        @click="fetchTickets"
                    >
                        <RefreshCw
                            class="h-4 w-4"
                            :class="{ 'animate-spin': loading }"
                        />
                    </button>
                </div>
            </div>

            <SupportTicketList
                :tickets="tickets"
                :loading="loading"
                :current-page="currentPage"
                :total-items="totalItems"
                :page-size="PAGE_SIZE"
                :view="viewMode"
                @select="(ticket) => openTicket(ticket.uuid)"
                @page-change="onPageChange"
            />
        </div>

        <SupportTicketModal
            ref="modal"
            :ticket="selected"
            :loading="detailLoading"
            :sending="sending"
            :saving="saving"
            @close="closeTicket"
            @reply="sendReply"
            @status="changeStatus"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useDebounceFn } from "@vueuse/core";
import { RefreshCw } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import ViewModeToggle from "~/components/ui/ViewModeToggle.vue";
import SupportTicketList from "~/components/sections/owner/SupportTicketList.vue";
import SupportTicketModal from "~/components/sections/owner/SupportTicketModal.vue";
import { supportService } from "~/api/support/SupportService";
import { useListView } from "~/composables/useListView";
import {
    applySupportUpdate,
    useSupportTicketLive,
    type SupportTicketUpdate,
} from "~/composables/useSupportTicketLive";
import { useToast } from "~/composables/useToast";
import {
    SUPPORT_STATUS_LABELS,
    type SupportReply,
    type SupportStatus,
    type SupportTicket,
} from "~/types/support";

definePageMeta({
    layout: "owner",
    middleware: ["auth-client", "owner-guard"],
});

useHead({ title: "AMUMA Customer Support" });

const PAGE_SIZE = 15;

const route = useRoute();
const router = useRouter();
const { success, error } = useToast();
const { view: viewMode, setView: setViewMode } = useListView();

const tickets = ref<SupportTicket[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const totalItems = ref(0);
const search = ref("");
const statusFilter = ref<SupportStatus | "all">("all");

const statusCounts = reactive<Record<SupportStatus, number>>({
    open: 0,
    answered: 0,
    resolved: 0,
});

const sourceFilter = ref<"all" | "branch" | "public">("all");

const withCount = (label: string, count: number) =>
    count ? `${label} (${count})` : label;

const statusOptions = computed(() => [
    {
        label: withCount(
            "All statuses",
            statusCounts.open + statusCounts.answered + statusCounts.resolved,
        ),
        value: "all",
    },
    ...(Object.keys(SUPPORT_STATUS_LABELS) as SupportStatus[]).map((value) => ({
        label: withCount(SUPPORT_STATUS_LABELS[value], statusCounts[value]),
        value,
    })),
]);

const sourceOptions = [
    { label: "All sources", value: "all" },
    { label: "Branches", value: "branch" },
    { label: "Public website", value: "public" },
];

const selected = ref<SupportTicket | null>(null);
const detailLoading = ref(false);
const sending = ref(false);
const saving = ref(false);
const modal = ref<InstanceType<typeof SupportTicketModal> | null>(null);

const fetchTickets = async () => {
    loading.value = true;

    try {
        const res: any = await supportService.list({
            page: currentPage.value,
            per_page: PAGE_SIZE,
            search: search.value.trim() || undefined,
            status: statusFilter.value === "all" ? undefined : statusFilter.value,
            source: sourceFilter.value === "all" ? undefined : sourceFilter.value,
        });

        tickets.value = res.data ?? [];
        totalItems.value = res.meta?.total ?? 0;
        Object.assign(statusCounts, res.status_counts ?? {});
    } catch (err: any) {
        tickets.value = [];
        error(err?.message || "Unable to load support tickets.");
    } finally {
        loading.value = false;
    }
};

const refetchFromFirstPage = () => {
    currentPage.value = 1;
    fetchTickets();
};

watch(search, useDebounceFn(refetchFromFirstPage, 400));
watch([statusFilter, sourceFilter], refetchFromFirstPage);

const onPageChange = (page: number) => {
    currentPage.value = page;
    fetchTickets();
};

const openTicket = async (uuid: string) => {
    selected.value =
        tickets.value.find((ticket) => ticket.uuid === uuid) ?? selected.value;
    detailLoading.value = true;

    try {
        const res: any = await supportService.show(uuid);
        selected.value = res.data ?? null;
    } catch (err: any) {
        selected.value = null;
        error(err?.message || "Unable to open this ticket.");
    } finally {
        detailLoading.value = false;
    }
};

const closeTicket = () => {
    selected.value = null;

    if (route.query.ticket) {
        router.replace({ query: { ...route.query, ticket: undefined } });
    }
};

const applyStatus = (uuid: string, status: SupportStatus, lastReplyAt?: string) => {
    const previous = tickets.value.find((ticket) => ticket.uuid === uuid)?.status;

    if (previous && previous !== status) {
        statusCounts[previous] = Math.max(0, statusCounts[previous] - 1);
        statusCounts[status] += 1;
    }

    tickets.value = tickets.value.map((ticket) =>
        ticket.uuid === uuid
            ? { ...ticket, status, last_reply_at: lastReplyAt ?? ticket.last_reply_at }
            : ticket,
    );

    if (selected.value?.uuid === uuid) {
        selected.value = { ...selected.value, status };
    }
};

const applyLiveUpdate = (update: SupportTicketUpdate) => {
    applyStatus(update.uuid, update.status, update.reply?.created_at);

    if (selected.value?.uuid === update.uuid) {
        selected.value = applySupportUpdate(selected.value, update);
    }
};

useSupportTicketLive(() => selected.value?.uuid, applyLiveUpdate);

const sendReply = async (payload: { message: string; attachment: File | null }) => {
    const ticket = selected.value;
    if (!ticket) return;

    sending.value = true;

    try {
        const res: any = await supportService.reply(ticket.uuid, payload);

        applyLiveUpdate({
            uuid: ticket.uuid,
            status: res.data.status,
            reply: res.data.reply as SupportReply,
        });
        modal.value?.reset();
    } catch (err: any) {
        error(err?.message || "Unable to send the reply.");
    } finally {
        sending.value = false;
    }
};

const changeStatus = async (status: "open" | "resolved") => {
    const ticket = selected.value;
    if (!ticket) return;

    saving.value = true;

    try {
        const res: any = await supportService.updateStatus(ticket.uuid, { status });
        applyStatus(ticket.uuid, res.data.status);
        success(res.message);
    } catch (err: any) {
        error(err?.message || "Unable to update this ticket.");
    } finally {
        saving.value = false;
    }
};

watch(
    () => route.query.ticket,
    (uuid) => {
        if (typeof uuid === "string" && uuid) openTicket(uuid);
    },
);

onMounted(() => {
    fetchTickets();

    const uuid = route.query.ticket;
    if (typeof uuid === "string" && uuid) openTicket(uuid);
});
</script>
