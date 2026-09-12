<template>
    <header
        class="h-14 sm:h-[60px] lg:h-16 mx-3 mt-3 sm:mx-4 sm:mt-4 lg:mx-0 lg:mt-4 lg:mr-4 px-3 sm:px-4 lg:px-5 py-2 flex items-center justify-between gap-2 sm:gap-4 shrink-0 rounded-2xl bg-white dark:bg-secondary-900 shadow-[0_10px_28px_-16px_rgba(15,23,42,0.15)] ring-1 ring-black/[0.04] dark:ring-white/[0.06]"
    >
        <button
            type="button"
            class="-ml-1 shrink-0 rounded-lg p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-primary-500 lg:hidden"
            aria-label="Open navigation"
            @click="emit('open')"
        >
            <Menu class="h-5 w-5" />
        </button>

        <div class="min-w-0 flex-1">
            <h1
                class="text-sm sm:text-base lg:text-lg font-bold text-gray-900 dark:text-white leading-tight truncate"
            >
                {{ pageTitle }}
            </h1>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                {{ pageSubtitle }}
            </p>
        </div>

        <div class="flex items-center gap-1 sm:gap-6 lg:gap-8 shrink-0">
            <button
                type="button"
                aria-label="Messages"
                class="relative w-9 h-9 sm:w-auto sm:h-auto flex items-center justify-center text-gray-700 dark:text-gray-300 hover:text-primary-500 transition-colors"
                @click="goToMessages"
            >
                <MessagesSquare class="w-5 h-5" />

                <span
                    v-if="unreadMessageCount"
                    class="absolute top-1 right-1 sm:-top-1 sm:-right-1 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-white dark:ring-secondary-900"
                />
            </button>

            <ThemeToggle class="text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5" />
            <Notification />
            <NavbarProfileDropdown v-if="user" :user="user" :theme-aware="true" />
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Menu, MessagesSquare } from "lucide-vue-next";
import NavbarProfileDropdown from "~/components/ui/NavbarProfileDropdown.vue";
import Notification from "~/components/ui/Notification.vue";
import ThemeToggle from "~/components/ui/ThemeToggle.vue";
import { useAuthUser } from "~/composables/useAuthUser";
import { messageService } from "~/api/message/MessageService";
import { useSound } from "~/composables/useSound";
import type { ConversationSummary } from "~/types/message";

const user = useAuthUser();
const { $echo } = useNuxtApp();
const { playNotification } = useSound();

const emit = defineEmits<{
    open: [];
}>();

const route = useRoute();
const router = useRouter();

const pageTitles: Record<string, { title: string; subtitle: string }> = {
    "/portal": {
        title: "Overview",
        subtitle: "Stay connected with your loved ones.",
    },
    "/portal/overview": {
        title: "Overview",
        subtitle: "Stay connected with your loved ones.",
    },
    "/portal/bookings": {
        title: "My Bookings",
        subtitle:
            "View your booking requests, service details, payment information, and current status.",
    },
    "/portal/loved-ones": {
        title: "My Loved Ones",
        subtitle: "View and manage your loved one's profile and records.",
    },
    "/portal/monitoring": {
        title: "Monitoring",
        subtitle: "Check in on the room, live and in real time.",
    },
    "/portal/schedule": {
        title: "Schedule",
        subtitle: "Upcoming appointments and daily care schedule.",
    },
    "/portal/medications": {
        title: "Medications & Care",
        subtitle:
            "View medication schedules, vital signs, and important care instructions for your loved one.",
    },
    "/portal/updates": {
        title: "Updates",
        subtitle:
            "Stay informed about your loved one's latest activities, care, appointments, and updates.",
    },
    "/portal/messages": {
        title: "Messages",
        subtitle: "Chat directly with your loved one's provider.",
    },
    "/portal/balance": {
        title: "Financial Overview",
        subtitle:
            "View invoices, payments, balances, and available refunds for your loved ones.",
    },
};

const currentPath = computed(() =>
    route.path.length > 1 ? route.path.replace(/\/+$/, "") : route.path,
);

const pageTitle = computed(
    () => pageTitles[currentPath.value]?.title ?? "Overview",
);

const pageSubtitle = computed(
    () =>
        pageTitles[currentPath.value]?.subtitle ??
        "Stay connected with your loved ones.",
);

function goToMessages() {
    router.push("/portal/messages");
}

const conversations = ref<ConversationSummary[]>([]);

const unreadMessageCount = computed(() =>
    conversations.value.reduce((sum, c) => sum + (c.unread_count ?? 0), 0),
);

async function loadConversations() {
    try {
        const res = await messageService.conversations();
        conversations.value = res ?? [];
    } catch {
        conversations.value = [];
    }
}

let messageChannel = "";
let messageHandler: ((payload: any) => void) | null = null;

function bindMessages() {
    const uuid = (user.value as any)?.uuid;

    if ($echo && messageChannel && messageHandler) {
        ($echo as any)
            .private(messageChannel)
            .stopListening(".MessageSent", messageHandler);
        messageChannel = "";
        messageHandler = null;
    }

    if (!$echo || !uuid) return;

    messageChannel = `Client.Messages.${uuid}`;

    messageHandler = (payload: any) => {
        const row = conversations.value.find(
            (c) => c.conversation_id === payload.conversation_id,
        );

        if (!row) {
            loadConversations();
            if (payload.sender_type !== "client") playNotification();
            return;
        }

        if (payload.sender_type !== "client") {
            row.unread_count += 1;
            playNotification();
        }
    };

    ($echo as any).private(messageChannel).listen(".MessageSent", messageHandler);
}

watch(() => (user.value as any)?.uuid, bindMessages, { immediate: true });

onMounted(async () => {
    await loadConversations();
});

// stopListening, not leave: the messages page shares this channel and leave()
// would tear it down for both.
onUnmounted(() => {
    if ($echo && messageChannel && messageHandler) {
        ($echo as any)
            .private(messageChannel)
            .stopListening(".MessageSent", messageHandler);
    }
});
</script>