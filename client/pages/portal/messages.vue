<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { MessageCircle } from "lucide-vue-next";

import MessageThread from "~/components/messaging/MessageThread.vue";
import MessageAvatar from "~/components/messaging/MessageAvatar.vue";
import EmptyState from "~/components/ui/EmptyState.vue";
import { messageService } from "~/api/message/MessageService";
import { patientAccessService } from "~/api/patient-access/PatientAccessService.js";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";

import type { ChatMessage, ConversationSummary } from "~/types/message";

useHead({ title: "Messages" });

definePageMeta({
    layout: "portal",
});

interface BranchRow {
    branch_id: number;
    branch_name: string;
    seed_patient_id: number | null;
    patient_names: string[];
    staff_name: string | null;
    staff_role: string | null;
    staff_avatar: string | null;
    conversation_id: number | null;
    last_message: string | null;
    unread_count: number;
}

const { error } = useToast();
const user = useAuthUser();

const { $echo } = useNuxtApp();

const rows = ref<BranchRow[]>([]);
const messages = ref<ChatMessage[]>([]);
const activeBranchId = ref<number | null>(null);
const mobileThreadOpen = ref(false);

const loadingList = ref(true);
const loadingThread = ref(false);
const sending = ref(false);

const activeRow = computed(() =>
    rows.value.find((r) => r.branch_id === activeBranchId.value),
);

const threadSubtitle = computed(() => {
    const row = activeRow.value;

    if (!row) return null;

    const staff = [row.staff_name, row.staff_role].filter(Boolean).join(" · ");

    const names = row.patient_names;

    const caring = names.length
        ? "Caring for " +
          (names.length > 2
              ? `${names.slice(0, 2).join(", ")} +${names.length - 2} more`
              : names.join(", "))
        : null;

    return [staff, caring].filter(Boolean).join(" — ") || null;
});

async function load() {
    loadingList.value = true;

    try {
        const [accessRes, conversationRes] = await Promise.all([
            patientAccessService.retrieveAction({
                action: "overview",
                section: "profile",
            }),
            messageService.conversations(),
        ]);

        const conversations: ConversationSummary[] = conversationRes ?? [];

        const records: any[] = Array.isArray(accessRes?.data)
            ? accessRes.data
            : [];

        const byBranch = new Map<number, BranchRow>();

        for (const record of records) {
            const branchId = record.organization?.branch_id;

            if (!branchId) continue;

            if (!byBranch.has(branchId)) {
                byBranch.set(branchId, {
                    branch_id: branchId,
                    branch_name: record.organization?.name ?? "Branch",
                    seed_patient_id: record.patient?.patient_id ?? null,
                    patient_names: [],
                    staff_name: null,
                    staff_role: null,
                    staff_avatar: null,
                    conversation_id: null,
                    last_message: null,
                    unread_count: 0,
                });
            }

            const row = byBranch.get(branchId)!;

            if (record.patient?.full_name) {
                row.patient_names.push(record.patient.full_name);
            }
        }

        for (const conversation of conversations) {
            const row = [...byBranch.values()].find(
                (r) => r.branch_id === conversation.branch?.branch_id,
            );

            if (!row) continue;

            row.conversation_id = conversation.conversation_id;
            row.staff_name = conversation.staff_name;
            row.staff_role = conversation.staff_role;
            row.staff_avatar = conversation.staff_avatar ?? null;
            row.last_message = conversation.last_message;
            row.unread_count = conversation.unread_count;

            if (conversation.patient_names?.length) {
                row.patient_names = conversation.patient_names;
            }
        }

        rows.value = [...byBranch.values()];

        if (rows.value.length) {
            await openThread(rows.value[0]!.branch_id);
        }
    } catch (err: any) {
        error(err?.message ?? "Unable to load your messages.");
        rows.value = [];
    } finally {
        loadingList.value = false;
    }
}

async function openThread(branchId: number) {
    activeBranchId.value = branchId;
    mobileThreadOpen.value = true;

    const row = rows.value.find((r) => r.branch_id === branchId);

    if (!row?.conversation_id) {
        messages.value = [];
        return;
    }

    loadingThread.value = true;

    try {
        const res = await messageService.thread({
            conversation_id: row.conversation_id,
        });

        messages.value = res?.messages ?? [];
        row.unread_count = 0;
    } catch (err: any) {
        error(err?.message ?? "Unable to open this conversation.");
        messages.value = [];
    } finally {
        loadingThread.value = false;
    }
}

async function sendMessage(body: string) {
    const row = activeRow.value;

    if (!row) return;

    sending.value = true;

    try {
        const res = await messageService.send({
            conversation_id: row.conversation_id,
            patient_id: row.seed_patient_id,
            body,
        });

        if (res?.message) {
            messages.value.push(res.message);
            row.conversation_id = res.conversation_id;
            row.last_message = body;
        }
    } catch (err: any) {
        error(err?.message ?? "Message failed to send.");
    } finally {
        sending.value = false;
    }
}

const clientChannel = computed(() => {
    const uuid = (user.value as any)?.uuid;

    return uuid ? `Client.Messages.${uuid}` : null;
});

const threadChannel = computed(() =>
    activeRow.value?.conversation_id ? clientChannel.value : null,
);

function onIncoming(message: ChatMessage) {
    messages.value.push(message);
}

let channelName = "";
let listHandler: ((payload: any) => void) | null = null;

function bindChannel(channel: string | null) {
    if ($echo && channelName && listHandler) {
        ($echo as any)
            .private(channelName)
            .stopListening(".MessageSent", listHandler);
        channelName = "";
        listHandler = null;
    }

    if (!$echo || !channel) return;

    channelName = channel;

    listHandler = (payload: any) => {
        const row = rows.value.find(
            (r) => r.conversation_id === payload.conversation_id,
        );

        if (!row) return;

        row.last_message = payload.body;

        const isBeingViewed =
            row.branch_id === activeBranchId.value && mobileThreadOpen.value;

        if (payload.sender_type !== "client" && !isBeingViewed) {
            row.unread_count += 1;
        }
    };

    ($echo as any).private(channelName).listen(".MessageSent", listHandler);
}

watch(clientChannel, bindChannel, { immediate: true });

onMounted(async () => {
    await load();
});

onBeforeUnmount(() => {
    if ($echo && channelName && listHandler) {
        ($echo as any)
            .private(channelName)
            .stopListening(".MessageSent", listHandler);
    }
});
</script>

<template>
    <div
        class="flex h-screen-header min-h-0 w-full flex-col bg-slate-50/60 p-0 dark:bg-surface lg:p-5"
    >
        <div
            v-if="loadingList"
            class="grid h-full min-h-0 w-full flex-1 grid-cols-1 gap-0 lg:grid-cols-[320px_minmax(0,1fr)] lg:gap-5"
        >
            <aside
                class="flex h-full min-h-0 flex-col overflow-hidden bg-white lg:rounded-3xl lg:border lg:border-gray-100 lg:shadow-sm dark:bg-secondary dark:lg:border-white/10"
            >
                <div
                    class="shrink-0 border-b border-gray-100 px-5 py-5 dark:border-white/10"
                >
                    <div
                        class="h-4 w-24 animate-pulse rounded bg-gray-200 dark:bg-white/15"
                    />
                    <div
                        class="mt-2 h-3 w-40 animate-pulse rounded bg-gray-100 dark:bg-white/10"
                    />
                </div>

                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto p-2.5">
                    <div
                        v-for="n in 4"
                        :key="n"
                        class="h-16 animate-pulse rounded-2xl bg-gray-100 dark:bg-white/10"
                    />
                </div>
            </aside>

            <div
                class="hidden min-h-0 flex-col overflow-hidden rounded-3xl border border-gray-100 bg-white lg:flex dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-center gap-3 border-b border-gray-100 px-5 py-4 dark:border-white/10"
                >
                    <div
                        class="h-10 w-10 animate-pulse rounded-full bg-gray-200 dark:bg-white/15"
                    />
                    <div class="space-y-1.5">
                        <div
                            class="h-3.5 w-32 animate-pulse rounded bg-gray-200 dark:bg-white/15"
                        />
                        <div
                            class="h-3 w-24 animate-pulse rounded bg-gray-100 dark:bg-white/10"
                        />
                    </div>
                </div>

                <div class="min-h-0 flex-1 space-y-3 p-5">
                    <div
                        class="h-12 w-2/3 animate-pulse rounded-2xl bg-gray-100 dark:bg-white/10"
                    />
                    <div
                        class="ml-auto h-12 w-1/2 animate-pulse rounded-2xl bg-gray-100 dark:bg-white/10"
                    />
                    <div
                        class="h-12 w-3/5 animate-pulse rounded-2xl bg-gray-100 dark:bg-white/10"
                    />
                </div>

                <div
                    class="shrink-0 border-t border-gray-100 p-4 dark:border-white/10"
                >
                    <div
                        class="h-11 w-full animate-pulse rounded-2xl bg-gray-100 dark:bg-white/10"
                    />
                </div>
            </div>
        </div>

        <EmptyState
            v-else-if="!rows.length"
            class="p-4 sm:p-6"
            title="You currently have no patients"
            cta-label="Book a Service"
            cta-to="/booking/search"
        />

        <div
            v-else
            class="grid h-full min-h-0 w-full flex-1 grid-cols-1 gap-0 lg:grid-cols-[320px_minmax(0,1fr)] lg:gap-5"
        >
            <aside
                class="flex h-full min-h-0 flex-col overflow-hidden bg-white lg:rounded-3xl lg:border lg:border-gray-100 lg:shadow-sm dark:bg-secondary dark:lg:border-white/10"
            >
                <div
                    class="shrink-0 border-b border-gray-100 px-5 py-5 dark:border-white/10"
                >
                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                        Messages
                    </p>

                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        Talk with the staff looking after your loved one.
                    </p>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-2.5">
                    <button
                        v-for="row in rows"
                        :key="row.branch_id"
                        type="button"
                        class="mb-1.5 w-full rounded-2xl border px-3.5 py-3 text-left transition"
                        :class="
                            row.branch_id === activeBranchId
                                ? 'border-primary-200 bg-primary-50 dark:border-primary-500/20 dark:bg-primary-500/10'
                                : 'border-transparent hover:bg-gray-50 dark:hover:bg-white/5'
                        "
                        @click="openThread(row.branch_id)"
                    >
                        <div class="flex items-start gap-2.5">
                            <MessageAvatar
                                :src="row.staff_avatar"
                                :name="row.staff_name ?? row.branch_name"
                            />

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ row.branch_name }}
                                    </p>

                                    <span
                                        v-if="row.unread_count"
                                        class="shrink-0 rounded-full bg-primary-600 px-2 py-0.5 text-[10px] font-bold text-white"
                                    >
                                        {{ row.unread_count }}
                                    </span>
                                </div>

                                <p
                                    v-if="row.staff_name"
                                    class="mt-0.5 truncate text-[11px] text-gray-500 dark:text-gray-400"
                                >
                                    {{ row.staff_name
                                    }}<template v-if="row.staff_role">
                                        · {{ row.staff_role }}</template
                                    >
                                </p>

                                <p
                                    class="mt-1 truncate text-xs text-gray-400 dark:text-gray-500"
                                >
                                    {{
                                        row.last_message ??
                                        "Start a conversation"
                                    }}
                                </p>
                            </div>
                        </div>
                    </button>
                </div>
            </aside>

            <div
                :class="[
                    'h-full min-h-0 flex-col',
                    'lg:static lg:z-auto lg:flex',
                    mobileThreadOpen
                        ? 'fixed inset-0 z-50 flex h-[100dvh] w-full '
                        : 'hidden',
                ]"
            >
                <MessageThread
                    :messages="messages"
                    viewer="client"
                    :avatar="activeRow?.staff_avatar"
                    :title="activeRow?.branch_name ?? 'Select a branch'"
                    :subtitle="threadSubtitle"
                    :patients="activeRow?.patient_names ?? []"
                    :channel="threadChannel"
                    :conversation-id="activeRow?.conversation_id ?? null"
                    show-close
                    @incoming="onIncoming"
                    :loading="loadingThread"
                    :sending="sending"
                    :disabled="!activeRow"
                    empty-text="No messages yet. Send the first one."
                    @send="sendMessage"
                    @close="mobileThreadOpen = false"
                />
            </div>
        </div>

        <button
            v-if="activeRow?.conversation_id && !mobileThreadOpen"
            type="button"
            aria-label="Open conversation"
            class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/30 transition hover:bg-primary-600 lg:hidden"
            @click="mobileThreadOpen = true"
        >
            <MessageCircle class="h-6 w-6" />

            <span
                v-if="activeRow?.unread_count"
                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
            >
                {{ activeRow.unread_count }}
            </span>
        </button>
    </div>
</template>
