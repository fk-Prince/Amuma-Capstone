<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

import { messageService } from "~/api/message/MessageService";
import { useBranchStore } from "~/stores/branch";

import type { ConversationSummary } from "~/types/message";

const route = useRoute();
const router = useRouter();
const branchStore = useBranchStore();

const { $echo } = useNuxtApp();

const conversations = ref<ConversationSummary[]>([]);
const isMounted = ref(false);

const branchUuid = computed(
    () =>
        (route.params.uuid as string) ??
        (branchStore.activeBranch?.uuid as string | undefined),
);

const unreadCount = computed(() =>
    conversations.value.reduce((sum, c) => sum + (c.unread_count ?? 0), 0),
);

async function load() {
    if (!branchUuid.value) return;

    try {
        const res = await messageService.branchConversations({
            branch_uuid: branchUuid.value,
        });

        conversations.value = res ?? [];
    } catch {
        conversations.value = [];
    }
}

function goToMessages() {
    if (!branchUuid.value) return;

    router.push(`/app/branches/${branchUuid.value}/messages`);
}

let channelName = "";
let handler: ((payload: any) => void) | null = null;

function bindChannel() {
    if ($echo && channelName && handler) {
        ($echo as any)
            .private(channelName)
            .stopListening(".MessageSent", handler);
        channelName = "";
        handler = null;
    }

    if (!$echo || !branchUuid.value) return;

    channelName = `Branch.Messages.${branchUuid.value}`;

    handler = (payload: any) => {
        if (payload.sender_type === "staff") return;

        const row = conversations.value.find(
            (c) => c.conversation_id === payload.conversation_id,
        );

        if (row) {
            row.unread_count += 1;
            row.last_message = payload.body;
            return;
        }

        load();
    };

    ($echo as any).private(channelName).listen(".MessageSent", handler);
}

watch(branchUuid, () => {
    load();
    bindChannel();
}, { immediate: true });

onMounted(() => {
    isMounted.value = true;
});

// stopListening, not leave: the messages page shares this channel and leave()
// would tear it down for both.
onBeforeUnmount(() => {
    if ($echo && channelName && handler) {
        ($echo as any)
            .private(channelName)
            .stopListening(".MessageSent", handler);
    }
});
</script>

<template>
    <button
        v-if="isMounted"
        type="button"
        aria-label="Messages"
        class="relative w-[38px] h-[38px] flex items-center justify-center rounded-lg hover:bg-gray-50 dark:hover:bg-white/10 text-gray-500 dark:text-white/70"
        @click="goToMessages"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path
                d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"
            />
        </svg>

        <span
            v-if="unreadCount > 0"
            class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 border-2 border-white dark:border-secondary"
        />
    </button>
</template>
