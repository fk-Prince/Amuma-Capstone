<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { MessageCircle, PenSquare } from "lucide-vue-next";

import MessageThread from "~/components/messaging/MessageThread.vue";
import MessageAvatar from "~/components/messaging/MessageAvatar.vue";
import NewMessageModal from "~/components/sections/portal/NewMessageModal.vue";
import EmptyState from "~/components/ui/EmptyState.vue";
import { messageService } from "~/api/message/MessageService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";

import type {
    CareTeamMember,
    ChatMessage,
    ConversationSummary,
    OutgoingMessage,
    PortalContact,
} from "~/types/message";

useHead({ title: "Messages" });

definePageMeta({
    layout: "portal",
    middleware: "portal",
});

const { error } = useToast();
const user = useAuthUser();

const { $echo } = useNuxtApp();

const rows = ref<ConversationSummary[]>([]);
const messages = ref<ChatMessage[]>([]);
const activeId = ref<number | null>(null);
const mobileThreadOpen = ref(false);

const loadingList = ref(true);
const loadingThread = ref(false);
const sending = ref(false);

const composerOpen = ref(false);
const contacts = ref<PortalContact[]>([]);
const loadingContacts = ref(true);

const activeRow = computed(() =>
    rows.value.find((r) => r.conversation_id === activeId.value),
);

const hasLovedOnes = computed(() => contacts.value.length > 0);

const threadSubtitle = computed(() => {
    const row = activeRow.value;

    if (!row) return null;

    const names = row.patient_names ?? [];

    const caring = names.length
        ? "Caring for " +
          (names.length > 2
              ? `${names.slice(0, 2).join(", ")} +${names.length - 2} more`
              : names.join(", "))
        : null;

    return [row.staff_role, row.branch?.name, caring]
        .filter(Boolean)
        .join(" · ");
});

async function load() {
    loadingList.value = true;
    loadingContacts.value = true;

    try {
        const [conversationRes, contactRes] = await Promise.all([
            messageService.conversations(),
            messageService.contacts(),
        ]);

        rows.value = conversationRes ?? [];
        contacts.value = Array.isArray(contactRes)
            ? contactRes
            : (contactRes?.data ?? []);

        if (rows.value.length) {
            await openThread(rows.value[0]!.conversation_id);
        }
    } catch (err: any) {
        error(err?.message ?? "Unable to load your messages.");
        rows.value = [];
    } finally {
        loadingList.value = false;
        loadingContacts.value = false;
    }
}

async function openThread(conversationId: number) {
    activeId.value = conversationId;
    mobileThreadOpen.value = true;
    loadingThread.value = true;

    try {
        const res = await messageService.thread({
            conversation_id: conversationId,
        });

        messages.value = res?.messages ?? [];

        const row = rows.value.find(
            (r) => r.conversation_id === conversationId,
        );

        if (row) row.unread_count = 0;
    } catch (err: any) {
        error(err?.message ?? "Unable to open this conversation.");
        messages.value = [];
    } finally {
        loadingThread.value = false;
    }
}

async function sendMessage({ body, attachment }: OutgoingMessage) {
    const row = activeRow.value;

    if (!row) return;

    sending.value = true;

    try {
        const res = await messageService.send({
            conversation_id: row.conversation_id,
            body,
            attachment,
        });

        if (res?.message) {
            messages.value.push(res.message);
            row.last_message = res.message.preview ?? body;
            row.last_message_at = new Date().toISOString();
        }
    } catch (err: any) {
        error(err?.message ?? "Message failed to send.");
    } finally {
        sending.value = false;
    }
}

function openComposer() {
    composerOpen.value = true;
}

async function startConversation(
    contact: PortalContact,
    member: CareTeamMember,
) {
    composerOpen.value = false;

    try {
        const res = await messageService.openContact({
            patient_id: contact.patient_id,
            employee_id: member.employee_id,
        });

        const summary: ConversationSummary | undefined = res?.conversation;

        if (!summary) return;

        const existing = rows.value.find(
            (r) => r.conversation_id === summary.conversation_id,
        );

        if (existing) {
            Object.assign(existing, summary);
        } else {
            rows.value.unshift(summary);
        }

        member.conversation_id = summary.conversation_id;
        activeId.value = summary.conversation_id;
        messages.value = res?.messages ?? [];
        mobileThreadOpen.value = true;
    } catch (err: any) {
        error(err?.message ?? "Unable to open this conversation.");
    }
}

const clientChannel = computed(() => {
    const uuid = (user.value as any)?.uuid;

    return uuid ? `Client.Messages.${uuid}` : null;
});

const threadChannel = computed(() =>
    activeId.value ? clientChannel.value : null,
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

        if (!row) {
            load();

            return;
        }

        row.last_message = payload.preview ?? payload.body;
        row.last_message_at = new Date().toISOString();

        const isBeingViewed =
            row.conversation_id === activeId.value && mobileThreadOpen.value;

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
            v-else-if="!hasLovedOnes"
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
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p
                                class="text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Messages
                            </p>

                            <p
                                class="mt-1 text-xs text-gray-400 dark:text-gray-500"
                            >
                                Talk with the staff looking after your loved
                                one.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex shrink-0 items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-600 active:scale-95"
                            @click="openComposer"
                        >
                            <PenSquare class="h-3.5 w-3.5" />
                            New Message
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-2.5">
                    <p
                        v-if="!rows.length"
                        class="px-3 py-10 text-center text-sm text-gray-400 dark:text-gray-500"
                    >
                        No conversations yet. Tap New Message to reach your
                        loved one's care team.
                    </p>

                    <button
                        v-for="row in rows"
                        :key="row.conversation_id"
                        type="button"
                        class="mb-1.5 w-full rounded-2xl border px-3.5 py-3 text-left transition"
                        :class="
                            row.conversation_id === activeId
                                ? 'border-primary-200 bg-primary-50 dark:border-primary-500/20 dark:bg-primary-500/10'
                                : 'border-transparent hover:bg-gray-50 dark:hover:bg-white/5'
                        "
                        @click="openThread(row.conversation_id)"
                    >
                        <div class="flex items-start gap-2.5">
                            <MessageAvatar
                                :src="row.staff_avatar"
                                :name="row.staff_name ?? row.branch?.name"
                            />

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ row.staff_name ?? row.branch?.name }}
                                    </p>

                                    <span
                                        v-if="row.unread_count"
                                        class="shrink-0 rounded-full bg-primary-600 px-2 py-0.5 text-[10px] font-bold text-white"
                                    >
                                        {{ row.unread_count }}
                                    </span>
                                </div>

                                <p
                                    class="mt-0.5 truncate text-[11px] text-gray-500 dark:text-gray-400"
                                >
                                    <template v-if="row.staff_role">
                                        {{ row.staff_role }} ·
                                    </template>
                                    {{ row.branch?.name }}
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
                    :title="
                        activeRow?.staff_name ??
                        activeRow?.branch?.name ??
                        'Select a conversation'
                    "
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

        <NewMessageModal
            :open="composerOpen"
            :contacts="contacts"
            :loading="loadingContacts"
            @close="composerOpen = false"
            @select="startConversation"
        />
    </div>
</template>
