<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useRoute } from "vue-router";
import {
    ArrowLeft,
    CheckCircle2,
    ChevronRight,
    LifeBuoy,
    LoaderCircle,
    Plus,
    RotateCcw,
    X,
} from "lucide-vue-next";
import SupportThread from "~/components/sections/support/SupportThread.vue";
import SupportTicketForm from "~/components/sections/app/Support/SupportTicketForm.vue";
import { supportService } from "~/api/support/SupportService";
import { useSupportCenter } from "~/composables/useSupportCenter";
import { useToast } from "~/composables/useToast";
import {
    SUPPORT_CATEGORY_LABELS,
    SUPPORT_STATUS_CLASSES,
    SUPPORT_STATUS_LABELS,
    type SupportCategory,
    type SupportReply,
    type SupportStatus,
    type SupportTicket,
} from "~/types/support";
import { stringToDateTime } from "~/utils/time";

const PAGE_SIZE = 20;

const route = useRoute();
const { state, closeSupport } = useSupportCenter();
const { success, error } = useToast();

const branchUuid = computed(() => route.params.uuid as string | undefined);

const view = ref<"list" | "new" | "thread">("list");
const tickets = ref<SupportTicket[]>([]);
const loading = ref(false);
const page = ref(1);
const lastPage = ref(1);

const active = ref<SupportTicket | null>(null);
const threadLoading = ref(false);
const sending = ref(false);
const submitting = ref(false);
const savingStatus = ref(false);
const formErrors = ref<Record<string, string[]>>({});
const thread = ref<InstanceType<typeof SupportThread> | null>(null);

const fetchTickets = async (nextPage = 1) => {
    if (!branchUuid.value) return;

    loading.value = true;

    try {
        const res: any = await supportService.list({
            branch_uuid: branchUuid.value,
            page: nextPage,
            per_page: PAGE_SIZE,
        });

        const rows = (res.data ?? []) as SupportTicket[];
        tickets.value = nextPage === 1 ? rows : [...tickets.value, ...rows];
        page.value = res.meta?.current_page ?? nextPage;
        lastPage.value = res.meta?.last_page ?? 1;
    } catch (err: any) {
        error(err?.message || "Unable to load your support tickets.");
    } finally {
        loading.value = false;
    }
};

const openTicket = async (uuid: string) => {
    if (!branchUuid.value) return;

    view.value = "thread";
    active.value = tickets.value.find((ticket) => ticket.uuid === uuid) ?? null;
    threadLoading.value = true;

    try {
        const res: any = await supportService.show(uuid, branchUuid.value);
        active.value = res.data ?? null;
    } catch (err: any) {
        error(err?.message || "Unable to open this ticket.");
        view.value = "list";
    } finally {
        threadLoading.value = false;
    }
};

watch(
    () => state.value.open,
    (open) => {
        if (!open) return;

        view.value = "list";
        active.value = null;
        fetchTickets();

        if (state.value.ticketUuid) openTicket(state.value.ticketUuid);
    },
    { immediate: true },
);

const patchTicket = (uuid: string, patch: Partial<SupportTicket>) => {
    tickets.value = tickets.value.map((ticket) =>
        ticket.uuid === uuid ? { ...ticket, ...patch } : ticket,
    );

    if (active.value?.uuid === uuid) {
        active.value = { ...active.value, ...patch };
    }
};

const createTicket = async (payload: {
    category: SupportCategory;
    subject: string;
    message: string;
    attachment: File | null;
}) => {
    if (!branchUuid.value) return;

    submitting.value = true;
    formErrors.value = {};

    try {
        const res: any = await supportService.create({
            branch_uuid: branchUuid.value,
            ...payload,
        });

        const ticket = res.data as SupportTicket;
        tickets.value = [ticket, ...tickets.value];
        active.value = ticket;
        view.value = "thread";
        success(res.message ?? "Your ticket has been sent.");
    } catch (err: any) {
        formErrors.value = err?.errors ?? {};
        error(err?.message || "Unable to send your ticket.");
    } finally {
        submitting.value = false;
    }
};

const sendReply = async (payload: { message: string; attachment: File | null }) => {
    const ticket = active.value;
    if (!ticket || !branchUuid.value) return;

    sending.value = true;

    try {
        const res: any = await supportService.reply(ticket.uuid, {
            branch_uuid: branchUuid.value,
            ...payload,
        });

        const reply = res.data.reply as SupportReply;

        patchTicket(ticket.uuid, {
            status: res.data.status as SupportStatus,
            last_reply_at: reply.created_at,
            replies: [...(active.value?.replies ?? []), reply],
        });

        thread.value?.reset();
    } catch (err: any) {
        error(err?.message || "Unable to send your message.");
    } finally {
        sending.value = false;
    }
};

const changeStatus = async (status: "open" | "resolved") => {
    const ticket = active.value;
    if (!ticket || !branchUuid.value) return;

    savingStatus.value = true;

    try {
        const res: any = await supportService.updateStatus(ticket.uuid, {
            branch_uuid: branchUuid.value,
            status,
        });

        patchTicket(ticket.uuid, { status: res.data.status });
        success(res.message);
    } catch (err: any) {
        error(err?.message || "Unable to update this ticket.");
    } finally {
        savingStatus.value = false;
    }
};

const backToList = () => {
    view.value = "list";
    active.value = null;
    formErrors.value = {};
};

const title = computed(() => {
    if (view.value === "new") return "New support ticket";
    if (view.value === "thread") return active.value?.subject ?? "Support ticket";
    return "Customer Support";
});
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
                v-if="state.open"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
                @click.self="closeSupport"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Customer support"
                    class="flex h-[85vh] max-h-[720px] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <button
                                v-if="view !== 'list'"
                                type="button"
                                aria-label="Back to tickets"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                @click="backToList"
                            >
                                <ArrowLeft class="h-4 w-4" />
                            </button>
                            <span
                                v-else
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                            >
                                <LifeBuoy class="h-5 w-5" />
                            </span>

                            <div class="min-w-0">
                                <h2
                                    class="truncate text-base font-semibold text-slate-900 dark:text-white"
                                >
                                    {{ title }}
                                </h2>
                                <p
                                    v-if="view === 'thread' && active"
                                    class="truncate text-xs text-slate-400 dark:text-gray-500"
                                >
                                    {{ active.ticket_code }} ·
                                    {{ SUPPORT_CATEGORY_LABELS[active.category] }}
                                </p>
                                <p
                                    v-else
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    Questions about billing, verification or
                                    the system? Message the AMUMA team.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                v-if="view === 'thread' && active"
                                class="rounded-full px-3 py-1 text-xs font-medium"
                                :class="SUPPORT_STATUS_CLASSES[active.status]"
                            >
                                {{ SUPPORT_STATUS_LABELS[active.status] }}
                            </span>
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                aria-label="Close"
                                @click="closeSupport"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <template v-if="view === 'list'">
                        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                            <button
                                type="button"
                                class="mb-4 flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:opacity-90"
                                @click="view = 'new'"
                            >
                                <Plus class="h-4 w-4" />
                                New ticket
                            </button>

                            <div
                                v-if="loading && !tickets.length"
                                class="space-y-3"
                            >
                                <div
                                    v-for="n in 3"
                                    :key="n"
                                    class="h-16 animate-pulse rounded-xl bg-slate-100 dark:bg-white/5"
                                />
                            </div>

                            <div
                                v-else-if="!tickets.length"
                                class="flex flex-col items-center justify-center py-12 text-center"
                            >
                                <p
                                    class="text-sm font-medium text-slate-600 dark:text-gray-300"
                                >
                                    No tickets yet
                                </p>
                                <p
                                    class="mt-1 max-w-xs text-xs text-slate-400 dark:text-gray-500"
                                >
                                    Send a ticket and the AMUMA team will reply
                                    here and by email.
                                </p>
                            </div>

                            <div v-else class="space-y-2">
                                <button
                                    v-for="ticket in tickets"
                                    :key="ticket.uuid"
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-primary/40 hover:bg-primary/5 dark:border-white/10 dark:hover:bg-white/5"
                                    @click="openTicket(ticket.uuid)"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                        >
                                            {{ ticket.subject }}
                                        </p>
                                        <p
                                            class="mt-0.5 truncate text-xs text-slate-400 dark:text-gray-500"
                                        >
                                            {{ ticket.ticket_code }} ·
                                            {{
                                                stringToDateTime(
                                                    ticket.last_reply_at ??
                                                        ticket.created_at,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="
                                            SUPPORT_STATUS_CLASSES[ticket.status]
                                        "
                                    >
                                        {{ SUPPORT_STATUS_LABELS[ticket.status] }}
                                    </span>
                                    <ChevronRight
                                        class="h-4 w-4 shrink-0 text-slate-300 dark:text-gray-600"
                                    />
                                </button>

                                <button
                                    v-if="page < lastPage"
                                    type="button"
                                    :disabled="loading"
                                    class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:opacity-60 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                                    @click="fetchTickets(page + 1)"
                                >
                                    {{ loading ? "Loading..." : "Load more" }}
                                </button>
                            </div>
                        </div>
                    </template>

                    <SupportTicketForm
                        v-else-if="view === 'new'"
                        :submitting="submitting"
                        :server-errors="formErrors"
                        @submit="createTicket"
                        @cancel="backToList"
                    />

                    <template v-else>
                        <SupportThread
                            ref="thread"
                            :ticket="active"
                            :loading="threadLoading"
                            :sending="sending"
                            viewer="branch"
                            @reply="sendReply"
                        />

                        <div
                            v-if="active && !threadLoading"
                            class="flex shrink-0 justify-end border-t border-slate-100 bg-slate-50/60 px-6 py-3 dark:border-white/10 dark:bg-white/5"
                        >
                            <button
                                v-if="active.status !== 'resolved'"
                                type="button"
                                :disabled="savingStatus"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-60 dark:text-emerald-400 dark:hover:bg-emerald-500/10"
                                @click="changeStatus('resolved')"
                            >
                                <LoaderCircle
                                    v-if="savingStatus"
                                    class="h-4 w-4 animate-spin"
                                />
                                <CheckCircle2 v-else class="h-4 w-4" />
                                My issue is resolved
                            </button>
                            <button
                                v-else
                                type="button"
                                :disabled="savingStatus"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 disabled:opacity-60 dark:text-gray-300 dark:hover:bg-white/10"
                                @click="changeStatus('open')"
                            >
                                <LoaderCircle
                                    v-if="savingStatus"
                                    class="h-4 w-4 animate-spin"
                                />
                                <RotateCcw v-else class="h-4 w-4" />
                                Reopen ticket
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
