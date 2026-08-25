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

        <div
            ref="dropdownAreaRef"
            class="flex items-center gap-1 sm:gap-6 lg:gap-8 shrink-0"
        >
            <div class="relative">
                <button
                    type="button"
                    @click.stop="toggleDropdown('messages')"
                    class="w-9 h-9 sm:w-auto sm:h-auto flex items-center justify-center text-gray-700 hover:text-brand-500 transition-colors"
                    aria-label="Messages"
                >
                    <MessagesSquare class="w-5 h-5" />

                    <span
                        v-if="unreadMessageCount"
                        class="absolute top-1 right-1 sm:-top-1 sm:-right-1 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-white"
                    />
                </button>

                <div
                    v-if="openDropdown === 'messages'"
                    class="absolute right-0 top-full mt-3 w-[calc(100vw-2rem)] max-w-80 bg-white rounded-2xl border border-gray-100 shadow-lg z-50 overflow-hidden"
                >
                    <div
                        class="px-4 py-3 border-b border-gray-50 flex items-center justify-between"
                    >
                        <p class="text-sm font-semibold text-gray-800">
                            Messages
                        </p>

                        <button
                            type="button"
                            @click="closeDropdowns"
                            class="text-gray-300 hover:text-gray-500"
                            aria-label="Close messages"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <ul class="max-h-[60vh] overflow-y-auto">
                        <li
                            v-for="c in conversationPreviews"
                            :key="c.id"
                            @click="goToMessages"
                            class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer"
                        >
                            <img
                                :src="c.avatar"
                                class="w-9 h-9 rounded-full object-cover shrink-0"
                                alt=""
                            />

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="text-sm font-medium text-gray-800 truncate"
                                    >
                                        {{ c.name }}
                                    </p>

                                    <span
                                        class="text-[11px] text-gray-400 shrink-0"
                                    >
                                        {{ c.time }}
                                    </span>
                                </div>

                                <p
                                    class="text-xs text-gray-400 truncate mt-0.5"
                                    :class="
                                        c.unread
                                            ? 'text-gray-600 font-medium'
                                            : ''
                                    "
                                >
                                    {{ c.lastMessage }}
                                </p>
                            </div>

                            <span
                                v-if="c.unread"
                                class="w-2 h-2 rounded-full bg-brand-500 mt-1.5 shrink-0"
                            />
                        </li>
                    </ul>

                    <button
                        type="button"
                        @click="goToMessages"
                        class="w-full text-center text-xs font-medium text-brand-600 py-3 border-t border-gray-50 hover:bg-gray-50"
                    >
                        View all messages
                    </button>
                </div>
            </div>

            <div class="relative">
                <button
                    type="button"
                    @click.stop="toggleDropdown('notifications')"
                    class="w-9 h-9 sm:w-auto sm:h-auto flex items-center justify-center text-gray-700 hover:text-brand-500 transition-colors"
                    aria-label="Notifications"
                >
                    <Bell class="w-5 h-5" />

                    <span
                        v-if="unreadCount"
                        class="absolute top-1 right-1 sm:-top-1 sm:-right-1 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-white"
                    />
                </button>

                <div
                    v-if="openDropdown === 'notifications'"
                    class="absolute right-0 top-full mt-3 w-[calc(100vw-2rem)] max-w-80 bg-white rounded-2xl border border-gray-100 shadow-lg z-50 overflow-hidden"
                >
                    <div
                        class="px-4 py-3 border-b border-gray-50 flex items-center justify-between"
                    >
                        <p class="text-sm font-semibold text-gray-800">
                            Notifications
                        </p>

                        <button
                            type="button"
                            @click="closeDropdowns"
                            class="text-gray-300 hover:text-gray-500"
                            aria-label="Close notifications"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <ul class="max-h-[60vh] overflow-y-auto">
                        <li
                            v-for="n in notifications"
                            :key="n.id"
                            class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50"
                            :class="n.unread ? 'bg-brand-50/40' : ''"
                        >
                            <span
                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-brand-50 text-brand-600"
                            >
                                <Pill v-if="n.icon === 'med'" class="w-4 h-4" />
                                <CalendarClock
                                    v-else-if="n.icon === 'appointment'"
                                    class="w-4 h-4"
                                />
                                <CreditCard
                                    v-else-if="n.icon === 'billing'"
                                    class="w-4 h-4"
                                />
                                <MessageSquare v-else class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-gray-700 leading-snug">
                                    {{ n.text }}
                                </p>

                                <p class="text-[11px] text-gray-400 mt-1">
                                    {{ n.time }}
                                </p>
                            </div>
                        </li>
                    </ul>

                    <button
                        type="button"
                        @click="goToUpdates"
                        class="w-full text-center text-xs font-medium text-brand-600 py-3 border-t border-gray-50 hover:bg-gray-50"
                    >
                        View all updates
                    </button>
                </div>
            </div>

            <NavbarProfileDropdown v-if="user" :user="user" />
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    Calendar,
    Clock,
    MessagesSquare,
    Bell,
    X,
    Pill,
    CalendarClock,
    CreditCard,
    MessageSquare,
    Menu,
} from "lucide-vue-next";
import NavbarProfileDropdown from "~/components/ui/NavbarProfileDropdown.vue";
import { useAuthUser } from "~/composables/useAuthUser";

const user = useAuthUser();

const emit = defineEmits<{
    open: [];
}>();

const route = useRoute();
const router = useRouter();

const pageTitles: Record<string, { title: string; subtitle: string }> = {
    "/": {
        title: "Overview",
        subtitle: "Stay connected with your loved ones.",
    },
    "/loved-ones": {
        title: "My Loved Ones",
        subtitle: "View and manage your loved one's profile and records.",
    },
    "/monitoring": {
        title: "Monitoring",
        subtitle: "Check in on the room, live and in real time.",
    },
    "/schedule": {
        title: "Schedule",
        subtitle: "Upcoming appointments and daily care schedule.",
    },
    "/medications": {
        title: "Medications",
        subtitle: "Track medications, dosage, and schedule.",
    },
    "/updates": {
        title: "Updates",
        subtitle: "Daily updates and highlights from the care team.",
    },
    "/messages": {
        title: "Messages",
        subtitle: "Chat directly with your loved one's caregiver.",
    },
    "/balance": {
        title: "Balance",
        subtitle: "Review invoices, payments, and outstanding balance.",
    },
    "/settings": {
        title: "Settings",
        subtitle: "Manage your profile, notifications, and preferences.",
    },
};

const pageTitle = computed(() => pageTitles[route.path]?.title ?? "Overview");

const pageSubtitle = computed(
    () =>
        pageTitles[route.path]?.subtitle ??
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

type DropdownKey = "messages" | "notifications" | null;

const openDropdown = ref<DropdownKey>(null);

function toggleDropdown(key: Exclude<DropdownKey, null>) {
    openDropdown.value = openDropdown.value === key ? null : key;
}

function closeDropdowns() {
    openDropdown.value = null;
}

const dropdownAreaRef = ref<HTMLElement | null>(null);

function handleOutsideClick(e: MouseEvent) {
    const target = e.target as Node;

    if (dropdownAreaRef.value && !dropdownAreaRef.value.contains(target)) {
        closeDropdowns();
    }
}

function goToMessages() {
    closeDropdowns();
    router.push("/messages");
}

function goToUpdates() {
    closeDropdowns();
    router.push("/updates");
}

interface Notification {
    id: number;
    icon: "med" | "appointment" | "billing" | "message";
    text: string;
    time: string;
    unread: boolean;
}

const notifications = ref<Notification[]>([
    {
        id: 1,
        icon: "appointment",
        text: "Doctor consultation with Dr. Gem Manolo at 2:00 PM today",
        time: "10m ago",
        unread: true,
    },
    {
        id: 2,
        icon: "med",
        text: "Amlodipine 5mg was given as scheduled",
        time: "2h ago",
        unread: true,
    },
    {
        id: 3,
        icon: "billing",
        text: "Your August billing statement is ready",
        time: "1d ago",
        unread: false,
    },
]);

const unreadCount = computed(
    () => notifications.value.filter((n) => n.unread).length,
);

interface ConversationPreview {
    id: number;
    name: string;
    avatar: string;
    lastMessage: string;
    time: string;
    unread: boolean;
}

const conversationPreviews = ref<ConversationPreview[]>([
    {
        id: 1,
        name: "Caregiver Maritess Uy",
        avatar: "https://i.pravatar.cc/64?img=32",
        lastMessage: "She enjoyed her physical therapy session today!",
        time: "9:45 AM",
        unread: true,
    },
    {
        id: 2,
        name: "Front Desk - AMUMA",
        avatar: "https://i.pravatar.cc/64?img=12",
        lastMessage: "Your visit request for Sunday has been approved.",
        time: "Yesterday",
        unread: false,
    },
]);

const unreadMessageCount = computed(
    () => conversationPreviews.value.filter((c) => c.unread).length,
);

onMounted(() => {
    clockTimer = setInterval(() => {
        now.value = new Date();
    }, 1000 * 30);

    window.addEventListener("click", handleOutsideClick);
});

onUnmounted(() => {
    if (clockTimer) {
        clearInterval(clockTimer);
    }

    window.removeEventListener("click", handleOutsideClick);
});
</script>
