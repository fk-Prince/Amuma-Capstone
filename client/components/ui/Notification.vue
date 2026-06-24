<template>
    <div v-if="isMounted" class="relative" ref="dropdownRef">
        <button
            @click="toggle"
            class="relative w-[38px] h-[38px] flex items-center justify-center rounded-lg hover:bg-gray-50 text-gray-500"
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
                    d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"
                />
                <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
            </svg>

            <span
                v-if="unreadCount > 0"
                class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 border-2 border-white"
            />
        </button>

        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="open"
                class="absolute right-0 mt-2 w-[360px] bg-white border border-gray-200 rounded-xl shadow-lg z-50 overflow-hidden"
            >
                <div
                    class="flex items-center justify-between px-4 py-3 border-b border-gray-100"
                >
                    <span class="text-sm font-medium">Notifications</span>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-xs text-blue-500 hover:underline"
                    >
                        Mark all as read
                    </button>
                </div>

                <div
                    class="max-h-[400px] overflow-y-auto divide-y divide-gray-100"
                >
                    <div
                        v-for="notif in notifications"
                        :key="notif.id"
                        @click="markRead(notif.id)"
                        class="flex gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="{
                            'bg-blue-50 hover:bg-blue-50/80': notif.unread,
                        }"
                    >
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-base"
                            :style="{
                                background: notif.bg,
                                color: notif.color,
                            }"
                        >
                            <i :class="`ti ${notif.icon}`" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-medium truncate">
                                {{ notif.title }}
                            </p>
                            <p class="text-xs text-gray-500 truncate">
                                {{ notif.message }}
                            </p>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ notif.date }}
                            </p>
                        </div>

                        <div
                            v-if="notif.unread"
                            class="w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-1.5"
                        />
                    </div>
                </div>

                <div class="px-4 py-2.5 border-t border-gray-100 text-center">
                    <NuxtLink
                        to="/app/notifications"
                        class="text-sm text-blue-500 hover:underline"
                    >
                        View all notifications
                    </NuxtLink>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { onClickOutside } from "@vueuse/core";

const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

interface Notification {
    id: number;
    title: string;
    message: string;
    date: string;
    unread: boolean;
    icon: string;
    color: string;
    bg: string;
}

const open = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const notifications = ref<Notification[]>([
    {
        id: 1,
        title: "New booking request",
        message: "YAWAYAYAWYAYWAYAWYAWYAYAWYA.",
        date: "Just now",
        unread: true,
        icon: "",
        color: "#185FA5",
        bg: "#E6F1FB",
    },
    {
        id: 2,
        title: "YAWA",
        message: "YAWA",
        date: "5 min ago",
        unread: true,
        icon: "",
        color: "#0F6E56",
        bg: "#E1F5EE",
    },
    {
        id: 3,
        title: "New booking request",
        message: "YAWAYAYAWYAYWAYAWYAWYAYAWYA.",
        date: "Just now",
        unread: true,
        icon: "",
        color: "#185FA5",
        bg: "#E6F1FB",
    },
    {
        id: 4,
        title: "YAWA",
        message: "YAWA",
        date: "5 min ago",
        unread: true,
        icon: "",
        color: "#0F6E56",
        bg: "#E1F5EE",
    },
]);

const unreadCount = computed(
    () => notifications.value.filter((n) => n.unread).length,
);

const toggle = () => (open.value = !open.value);

const markRead = (id: number) => {
    const n = notifications.value.find((n) => n.id === id);
    if (n) n.unread = false;
};

const markAllRead = () => {
    notifications.value.forEach((n) => (n.unread = false));
};

onClickOutside(dropdownRef, () => (open.value = false));
</script>
