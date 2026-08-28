<template>
    <header
        class="min-h-[88px] sm:min-h-[104px] lg:h-[120px] px-3 sm:px-6 lg:px-8 py-4 sm:py-5 flex items-center justify-between gap-2 sm:gap-4 shrink-0 border-b border-gray-100 bg-white"
    >
        <button
            type="button"
            class="-ml-1 shrink-0 rounded-lg p-2 text-gray-600 hover:bg-gray-50 hover:text-brand-500 lg:hidden"
            aria-label="Open navigation"
            @click="emit('open')"
        >
            <Menu class="h-5 w-5" />
        </button>

        <div class="min-w-0 flex-1">
            <h1
                class="text-lg sm:text-2xl lg:text-[26px] font-bold text-gray-900 leading-tight truncate"
            >
                {{ pageTitle }}
            </h1>

            <p class="text-xs sm:text-sm text-gray-400 mt-0.5 truncate">
                {{ pageSubtitle }}
            </p>

            <div
                class="flex items-center gap-2 sm:gap-3 mt-2 text-[11px] sm:text-xs text-gray-500"
            >
                <span class="flex items-center gap-1.5 whitespace-nowrap">
                    <Calendar class="w-3.5 h-3.5 text-brand-500 shrink-0" />
                    <span class="hidden sm:inline">{{ formattedDate }}</span>
                    <span class="sm:hidden">{{ formattedShortDate }}</span>
                </span>

                <span class="w-px h-3 bg-gray-200 shrink-0" />

                <span class="flex items-center gap-1.5 whitespace-nowrap">
                    <Clock class="w-3.5 h-3.5 text-brand-500 shrink-0" />
                    {{ formattedTime }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-6 lg:gap-8 shrink-0">
            <button
                type="button"
                aria-label="Messages"
                class="relative w-9 h-9 sm:w-auto sm:h-auto flex items-center justify-center text-gray-700 hover:text-brand-500 transition-colors"
                @click="goToMessages"
            >
                <MessagesSquare class="w-5 h-5" />

                <span
                    v-if="unreadMessageCount"
                    class="absolute top-1 right-1 sm:-top-1 sm:-right-1 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-white"
                />
            </button>

            <Notification />
            <NavbarProfileDropdown v-if="user" :user="user" />
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Calendar, Clock, MessagesSquare, Menu } from "lucide-vue-next";
import NavbarProfileDropdown from "~/components/ui/NavbarProfileDropdown.vue";
import Notification from "~/components/ui/Notification.vue";
import { useAuthUser } from "~/composables/useAuthUser";
import { messageService } from "~/api/message/MessageService";
import type { ConversationSummary } from "~/types/message";

const user = useAuthUser();
const { $echo } = useNuxtApp();

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

const now = ref(new Date());

let clockTimer: ReturnType<typeof setInterval> | undefined;

const formattedDate = computed(() =>
    now.value.toLocaleDateString("en-US", {
        weekday: "long",
        month: "long",
        day: "2-digit",
        year: "numeric",
    }),
);

const formattedShortDate = computed(() =>
    now.value.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    }),
);

const formattedTime = computed(() =>
    now.value.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
    }),
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

onMounted(async () => {
    clockTimer = setInterval(() => {
        now.value = new Date();
    }, 1000 * 30);

    await loadConversations();

    const uuid = (user.value as any)?.uuid;

    if (!$echo || !uuid) return;

    messageChannel = `Client.Messages.${uuid}`;

    messageHandler = (payload: any) => {
        const row = conversations.value.find(
            (c) => c.conversation_id === payload.conversation_id,
        );

        if (!row) {
            loadConversations();
            return;
        }

        if (payload.sender_type !== "client") {
            row.unread_count += 1;
        }
    };

    ($echo as any).private(messageChannel).listen(".MessageSent", messageHandler);
});

// stopListening, not leave: the messages page shares this channel and leave()
// would tear it down for both.
onUnmounted(() => {
    if (clockTimer) {
        clearInterval(clockTimer);
    }

    if ($echo && messageChannel && messageHandler) {
        ($echo as any)
            .private(messageChannel)
            .stopListening(".MessageSent", messageHandler);
    }
});
</script>
