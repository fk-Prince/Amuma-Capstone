<template>
    <div
        class="flex h-[100dvh] min-h-0 w-full flex-1 bg-slate-100 lg:h-full lg:p-6"
    >
        <div
            class="grid h-full min-h-0 w-full grid-cols-1 gap-0 lg:grid-cols-[340px_minmax(0,1fr)] lg:gap-5"
        >
            <aside
                class="flex h-full min-h-0 flex-col overflow-hidden bg-white lg:rounded-2xl lg:border lg:border-slate-200"
            >
                <div class="shrink-0 border-b border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p
                                class="text-sm font-bold text-slate-800 sm:text-base"
                            >
                                Messages
                            </p>

                            <p class="mt-0.5 text-xs leading-4 text-slate-400">
                                {{
                                    tab === "families"
                                        ? "Families of patients assigned to you."
                                        : "Staff at this branch."
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-600 active:scale-95"
                            @click="openComposer"
                        >
                            New Message
                        </button>
                    </div>

                    <div
                        class="mt-3 inline-flex w-full rounded-xl bg-slate-100 p-1"
                    >
                        <button
                            v-for="option in tabs"
                            :key="option.value"
                            type="button"
                            class="min-w-0 flex-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                            :class="
                                tab === option.value
                                    ? 'bg-white text-primary shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700'
                            "
                            @click="switchTab(option.value)"
                        >
                            <span class="truncate">
                                {{ option.label }}
                            </span>
                        </button>
                    </div>

                    <BaseInput
                        v-if="tab === 'families'"
                        v-model="query"
                        placeholder="Search family or resident..."
                        is-search
                        class="mt-3"
                        @keyup.enter="fetchConversations"
                    />
                </div>

                <div
                    class="min-h-0 flex-1 overflow-y-auto p-2 overscroll-contain"
                >
                    <div v-if="loadingList" class="space-y-2 p-1">
                        <div
                            v-for="n in 5"
                            :key="n"
                            class="h-16 animate-pulse rounded-xl bg-slate-100"
                        />
                    </div>

                    <p
                        v-else-if="!conversations.length"
                        class="px-3 py-10 text-center text-sm text-slate-400"
                    >
                        No conversations yet.
                    </p>

                    <button
                        v-for="item in conversations"
                        :key="item.conversation_id"
                        type="button"
                        class="mb-1 w-full rounded-xl border px-3 py-2.5 text-left transition active:scale-[0.99]"
                        :class="
                            item.conversation_id === activeId
                                ? 'border-primary-200 bg-primary-50'
                                : 'border-transparent hover:bg-slate-50'
                        "
                        @click="openThread(item.conversation_id)"
                    >
                        <div class="flex min-w-0 items-start gap-2.5">
                            <MessageAvatar
                                :src="item.avatar"
                                :name="item.client_name ?? item.staff_name"
                            />

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex min-w-0 items-center justify-between gap-2"
                                >
                                    <p
                                        class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800"
                                    >
                                        {{
                                            item.client_name ??
                                            item.staff_name ??
                                            "Conversation"
                                        }}
                                    </p>

                                    <span
                                        v-if="item.unread_count"
                                        class="shrink-0 rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white"
                                    >
                                        {{ item.unread_count }}
                                    </span>
                                </div>

                                <p
                                    v-if="item.patient_names?.length"
                                    class="mt-0.5 truncate text-[11px] text-primary-600"
                                >
                                    Patient:
                                    {{ patientLabel(item.patient_names) }}
                                </p>

                                <p class="mt-1 truncate text-xs text-slate-400">
                                    {{ item.last_message ?? "No messages yet" }}
                                </p>
                            </div>
                        </div>
                    </button>
                </div>
            </aside>

            <div
                :class="[
                    'min-h-0 flex-col lg:static lg:z-auto lg:flex',
                    mobileThreadOpen
                        ? 'fixed inset-0 z-50 flex h-[100dvh] w-full bg-white'
                        : 'hidden',
                ]"
            >
                <MessageThread
                    :messages="messages"
                    viewer="staff"
                    :avatar="activeConversation?.avatar"
                    :title="
                        activeConversation?.client_name ??
                        activeConversation?.staff_name ??
                        'Select a conversation'
                    "
                    :subtitle="threadSubtitle"
                    :patients="
                        tab === 'families'
                            ? (activeConversation?.patient_names ?? [])
                            : []
                    "
                    :channel="threadChannel"
                    :conversation-id="activeId"
                    :loading="loadingThread"
                    :sending="sending"
                    :disabled="!activeId"
                    show-close
                    empty-text="No messages in this conversation yet."
                    @send="sendMessage"
                    @incoming="onIncoming"
                    @close="mobileThreadOpen = false"
                />
            </div>
        </div>

        <button
            v-if="activeId && !mobileThreadOpen"
            type="button"
            aria-label="Open conversation"
            class="fixed bottom-4 right-4 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/30 transition hover:bg-primary-600 active:scale-95 lg:hidden"
            @click="mobileThreadOpen = true"
        >
            <MessageCircle class="h-6 w-6" />

            <span
                v-if="activeUnread"
                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
            >
                {{ activeUnread }}
            </span>
        </button>

        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="composerOpen"
                    class="fixed inset-0 z-[60] flex items-end justify-center bg-slate-900/50 p-0 backdrop-blur-sm sm:items-center sm:p-4"
                    @click.self="composerOpen = false"
                >
                    <Transition
                        appear
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-4 opacity-0 sm:scale-95"
                        enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-to-class="translate-y-4 opacity-0 sm:scale-95"
                    >
                        <div
                            v-if="composerOpen"
                            class="w-full overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:max-w-lg sm:rounded-2xl"
                        >
                            <div
                                class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-bold text-slate-800 sm:text-base"
                                    >
                                        New message
                                    </p>

                                    <p
                                        class="mt-0.5 text-xs leading-4 text-slate-400"
                                    >
                                        {{
                                            tab === "colleagues"
                                                ? "To a staff member at this branch."
                                                : "To a family of a patient assigned to you."
                                        }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    aria-label="Close"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                    @click="composerOpen = false"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        class="h-4 w-4"
                                    >
                                        <line x1="18" y1="6" x2="6" y2="18" />
                                        <line x1="6" y1="6" x2="18" y2="18" />
                                    </svg>
                                </button>
                            </div>

                            <div class="border-b border-slate-100 p-2.5">
                                <BaseInput
                                    v-model="composerSearch"
                                    placeholder="Search by name or email..."
                                    is-search
                                    @update:model-value="onComposerSearch"
                                />
                            </div>

                            <div
                                class="max-h-[70dvh] overflow-y-auto p-2.5 overscroll-contain sm:max-h-[24rem]"
                            >
                                <p
                                    v-if="
                                        !loadingRecipients &&
                                        composerSearch.trim() &&
                                        ((tab === 'colleagues' &&
                                            !colleagues.length) ||
                                            (tab !== 'colleagues' &&
                                                !recipients.length))
                                    "
                                    class="px-3 py-8 text-center text-sm text-slate-400"
                                >
                                    No one found matching "{{
                                        composerSearch
                                    }}".
                                </p>

                                <p
                                    v-if="loadingRecipients"
                                    class="px-3 py-8 text-center text-sm text-slate-400"
                                >
                                    Loading...
                                </p>

                                <template v-else-if="tab === 'colleagues'">
                                    <p
                                        v-if="
                                            !colleagues.length &&
                                            !composerSearch.trim()
                                        "
                                        class="px-3 py-8 text-center text-sm text-slate-400"
                                    >
                                        No other staff at this branch yet.
                                    </p>

                                    <button
                                        v-for="row in colleagues"
                                        :key="row.employee_id"
                                        type="button"
                                        class="mb-1 w-full rounded-xl px-3 py-3 text-left transition hover:bg-slate-50 active:scale-[0.99]"
                                        @click="startColleagueConversation(row)"
                                    >
                                        <div
                                            class="flex min-w-0 items-center gap-2.5"
                                        >
                                            <MessageAvatar
                                                :src="row.avatar"
                                                :name="row.name"
                                                size="md"
                                            />

                                            <div class="min-w-0 flex-1">
                                                <div
                                                    class="flex min-w-0 items-center justify-between gap-2"
                                                >
                                                    <p
                                                        class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800"
                                                    >
                                                        {{ row.name }}
                                                    </p>

                                                    <span
                                                        v-if="
                                                            row.conversation_id
                                                        "
                                                        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500"
                                                    >
                                                        Existing
                                                    </span>
                                                </div>

                                                <p
                                                    v-if="row.role_name"
                                                    class="mt-0.5 truncate text-xs capitalize text-slate-400"
                                                >
                                                    {{
                                                        row.role_name.replace(
                                                            "_",
                                                            " ",
                                                        )
                                                    }}
                                                </p>

                                                <p
                                                    v-if="row.email"
                                                    class="mt-0.5 truncate text-xs text-slate-400"
                                                >
                                                    {{ row.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </button>
                                </template>

                                <template v-else>
                                    <p
                                        v-if="
                                            !recipients.length &&
                                            !composerSearch.trim()
                                        "
                                        class="px-3 py-8 text-center text-sm text-slate-400"
                                    >
                                        You have no families to message yet.
                                    </p>

                                    <button
                                        v-for="row in recipients"
                                        :key="row.client_id"
                                        type="button"
                                        class="mb-1 w-full rounded-xl px-3 py-3 text-left transition hover:bg-slate-50 active:scale-[0.99]"
                                        @click="startConversation(row)"
                                    >
                                        <div
                                            class="flex min-w-0 items-center gap-2.5"
                                        >
                                            <MessageAvatar
                                                :src="row.avatar"
                                                :name="row.client_name"
                                                size="md"
                                            />

                                            <div class="min-w-0 flex-1">
                                                <div
                                                    class="flex min-w-0 items-center justify-between gap-2"
                                                >
                                                    <p
                                                        class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800"
                                                    >
                                                        {{ row.client_name }}
                                                    </p>

                                                    <span
                                                        v-if="
                                                            row.conversation_id
                                                        "
                                                        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500"
                                                    >
                                                        Existing
                                                    </span>
                                                </div>

                                                <p
                                                    class="mt-0.5 truncate text-xs text-slate-400"
                                                >
                                                    {{
                                                        patientLabel(
                                                            row.patient_names,
                                                        )
                                                    }}
                                                </p>

                                                <p
                                                    v-if="row.email"
                                                    class="mt-0.5 truncate text-xs text-slate-400"
                                                >
                                                    {{ row.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>

                            <div
                                class="flex justify-end border-t border-slate-200 px-4 py-3 pb-[env(safe-area-inset-bottom)] sm:px-5"
                            >
                                <button
                                    type="button"
                                    class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                    @click="composerOpen = false"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { MessageCircle } from "lucide-vue-next";

import BaseInput from "~/components/ui/BaseInput.vue";
import MessageThread from "~/components/messaging/MessageThread.vue";
import MessageAvatar from "~/components/messaging/MessageAvatar.vue";
import { messageService } from "~/api/message/MessageService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";

import type {
    ChatMessage,
    Colleague,
    ConversationSummary,
    MessageRecipient,
} from "~/types/message";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Messages" });

const route = useRoute();
const { error } = useToast();
const user = useAuthUser();

const { $echo } = useNuxtApp();

const uuid = computed(() => route.params.uuid as string);

const conversations = ref<ConversationSummary[]>([]);
const messages = ref<ChatMessage[]>([]);
const activeId = ref<number | null>(null);
const query = ref("");
const mobileThreadOpen = ref(false);

const loadingList = ref(true);
const loadingThread = ref(false);
const sending = ref(false);

type Tab = "families" | "colleagues";

const tabs: { label: string; value: Tab }[] = [
    { label: "Families", value: "families" },
    { label: "Staff", value: "colleagues" },
];

const tab = ref<Tab>("families");

const composerOpen = ref(false);
const composerSearch = ref("");
const loadingRecipients = ref(false);
const recipients = ref<MessageRecipient[]>([]);
const colleagues = ref<Colleague[]>([]);

async function switchTab(value: Tab) {
    if (tab.value === value) return;

    tab.value = value;
    activeId.value = null;
    messages.value = [];

    await fetchConversations();
}

async function loadComposerList(search = "") {
    loadingRecipients.value = true;

    try {
        if (tab.value === "colleagues") {
            const res = await messageService.colleagues({
                branch_uuid: uuid.value,
                search,
            });

            colleagues.value = res ?? [];
            recipients.value = [];
        } else {
            const res = await messageService.recipients({
                branch_uuid: uuid.value,
                search,
            });

            recipients.value = res ?? [];
            colleagues.value = [];
        }
    } catch (err: any) {
        error(err?.message ?? "Unable to load recipients.");
        recipients.value = [];
        colleagues.value = [];
    } finally {
        loadingRecipients.value = false;
    }
}

let composerSearchTimer: ReturnType<typeof setTimeout> | null = null;

function onComposerSearch(value: string) {
    if (composerSearchTimer) clearTimeout(composerSearchTimer);

    composerSearchTimer = setTimeout(() => {
        loadComposerList(value.trim());
    }, 300);
}

async function openComposer() {
    composerOpen.value = true;
    composerSearch.value = "";

    await loadComposerList();
}

async function startColleagueConversation(row: Colleague) {
    try {
        const res = await messageService.openStaff({
            branch_uuid: uuid.value,
            employee_id: row.employee_id,
        });

        composerOpen.value = false;

        await fetchConversations();

        const id = res?.conversation?.conversation_id;

        if (id) {
            activeId.value = id;
            messages.value = res?.messages ?? [];
        }
    } catch (err: any) {
        error(err?.message ?? "Unable to open that conversation.");
    }
}

async function startConversation(row: MessageRecipient) {
    try {
        const res = await messageService.open({
            branch_uuid: uuid.value,
            client_id: row.client_id,
        });

        composerOpen.value = false;

        await fetchConversations();

        const id = res?.conversation?.conversation_id;

        if (id) {
            activeId.value = id;
            messages.value = res?.messages ?? [];
        }
    } catch (err: any) {
        error(err?.message ?? "Unable to open that conversation.");
    }
}

const activeConversation = computed(() =>
    conversations.value.find((c) => c.conversation_id === activeId.value),
);

const activeUnread = computed(
    () => activeConversation.value?.unread_count ?? 0,
);

function patientLabel(names?: string[]) {
    if (!names?.length) return "No patients listed";

    return names.length > 2
        ? `${names.slice(0, 2).join(", ")} +${names.length - 2} more`
        : names.join(", ");
}

const threadSubtitle = computed(() => {
    const row = activeConversation.value;

    if (!row) return null;

    if (tab.value === "colleagues") return "Staff at this branch";

    if (!row.patient_names?.length) return null;

    return `Caring for ${patientLabel(row.patient_names)}`;
});

async function fetchConversations() {
    loadingList.value = true;

    try {
        const res =
            tab.value === "colleagues"
                ? await messageService.staffConversations({
                      branch_uuid: uuid.value,
                  })
                : await messageService.branchConversations({
                      branch_uuid: uuid.value,
                      search: query.value,
                  });

        conversations.value = res ?? [];
    } catch (err: any) {
        error(err?.message ?? "Unable to load conversations.");
        conversations.value = [];
    } finally {
        loadingList.value = false;
    }
}

async function openThread(conversationId: number) {
    activeId.value = conversationId;
    mobileThreadOpen.value = true;
    loadingThread.value = true;

    try {
        const res = await messageService.thread({
            conversation_id: conversationId,
            as_staff: true,
        });

        messages.value = res?.messages ?? [];

        const row = conversations.value.find(
            (c) => c.conversation_id === conversationId,
        );

        if (row) row.unread_count = 0;
    } catch (err: any) {
        error(err?.message ?? "Unable to open this conversation.");
        messages.value = [];
    } finally {
        loadingThread.value = false;
    }
}

async function sendMessage(body: string) {
    if (!activeId.value) return;

    sending.value = true;

    try {
        const res = await messageService.send({
            conversation_id: activeId.value,
            body,
            as_staff: true,
        });

        if (res?.message) {
            messages.value.push(res.message);
            patchPreview(activeId.value, body);
        }
    } catch (err: any) {
        error(err?.message ?? "Message failed to send.");
    } finally {
        sending.value = false;
    }
}

function patchPreview(conversationId: number, body: string) {
    const row = conversations.value.find(
        (c) => c.conversation_id === conversationId,
    );

    if (!row) return;

    row.last_message = body;
    row.last_message_at = new Date().toISOString();
}

const listChannel = computed(() =>
    tab.value === "colleagues"
        ? `User.Messages.${(user.value as any)?.uuid}`
        : `Branch.Messages.${uuid.value}`,
);

const threadChannel = computed(() =>
    activeId.value ? listChannel.value : null,
);

function onIncoming(message: ChatMessage) {
    messages.value.push(message);
}

let joined = "";
let listHandler: ((payload: any) => void) | null = null;

function bindList(channel: string | null) {
    if ($echo && joined && listHandler) {
        ($echo as any).private(joined).stopListening(".MessageSent", listHandler);
        joined = "";
        listHandler = null;
    }

    if (!$echo || !channel || channel.endsWith("undefined")) return;

    joined = channel;

    listHandler = (payload: any) => {
        const row = conversations.value.find(
            (c) => c.conversation_id === payload.conversation_id,
        );

        if (!row) {
            fetchConversations();
            return;
        }

        row.last_message = payload.body;
        row.last_message_at = new Date().toISOString();

        const mine =
            Number(payload.sender_user_id) ===
            Number((user.value as any)?.user_id);

        const isBeingViewed =
            payload.conversation_id === activeId.value &&
            mobileThreadOpen.value;

        if (!mine && !isBeingViewed) {
            row.unread_count += 1;
        }
    };

    ($echo as any).private(joined).listen(".MessageSent", listHandler);
}

watch(listChannel, bindList, { immediate: true });

onMounted(async () => {
    await fetchConversations();
});

onBeforeUnmount(() => {
    if ($echo && joined && listHandler) {
        ($echo as any)
            .private(joined)
            .stopListening(".MessageSent", listHandler);
    }
});
</script>
