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
                    <p
                        v-if="!notifications.length"
                        class="px-4 py-8 text-center text-xs text-gray-400"
                    >
                        No notifications yet.
                    </p>

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
                                {{ notif.message_type }}
                            </p>
                            <p class="text-xs text-gray-500 truncate">
                                {{ notif.message }}
                            </p>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ notifcationFormatDate(notif.created_at) }}
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
                        to="/notifications"
                        class="text-sm text-blue-500 hover:underline"
                        @click="open = false"
                    >
                        View all notifications
                    </NuxtLink>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
import { onClickOutside } from "@vueuse/core";
import { notificationService } from "~/api/notification/NotificationService";
import type { Notification } from "~/types/notification";
import { useAuthUser } from "~/composables/useAuthUser";
import { useRoute } from "vue-router";
import { notifcationFormatDate } from "~/utils/notification-time";
import { useToast } from "~/composables/useToast";
const { info } = useToast();
const route = useRoute();
const user = useAuthUser();
const { $echo } = useNuxtApp();

const isMounted = ref(false);
const open = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const notifications = ref<Notification[]>([]);

let channel: any = null;

const unreadCount = computed(
    () => notifications.value.filter((n) => n.unread).length,
);

const toggle = () => (open.value = !open.value);

const markRead = async (id: number) => {
    const n = notifications.value.find((n) => n.id === id);

    if (!n || !n.unread) return;

    n.unread = false;

    try {
        await notificationService.markRead(id);
    } catch (err) {
        console.error(err);
        n.unread = true;
    }
};

const markAllRead = async () => {
    const previous = notifications.value.map((n) => n.unread);

    notifications.value.forEach((n) => (n.unread = false));

    try {
        await notificationService.markRead();
    } catch (err) {
        console.error(err);
        notifications.value.forEach(
            (n, i) => (n.unread = previous[i] ?? false),
        );
    }
};

onClickOutside(dropdownRef, () => (open.value = false));

const loadNotifications = async (branchUuid?: string) => {
    try {
        const res = await notificationService.list({
            per_page: 4,
            branch_uuid: branchUuid || undefined,
        });
        notifications.value = Array.isArray(res?.data) ? res.data : [];
    } catch (err) {
        console.error(err);
        notifications.value = [];
    }
};

const announce = (notification: Notification) => {
    notifications.value = [notification, ...notifications.value].slice(0, 4);

    info("New Notification", notification.message);
};

const bindChannel = (branchUuid?: string) => {
    const uuid = user.value?.uuid;

    if (!uuid || !$echo) return;

    if (channel) {
        channel.stopListening(".NotificationEvent");
        $echo.leave(`private-Notification.${uuid}`);
        channel = null;
    }

    channel = $echo
        .private(`Notification.${uuid}`)
        .listen(".NotificationEvent", (e: any) => {
            if (branchUuid && e.branch_uuid !== branchUuid) return;

            announce({
                id: Date.now(),
                uuid: generateId(),
                message: e.message,
                message_type: e.message_type,
                created_at: new Date().toISOString(),
                unread: true,
                icon: "ti-bell",
                bg: "#EAF4F2",
                color: "#0E7C7B",
            });
        });
};

const currentBranchUuid = () => {
    const value = route.params.uuid;
    const uuid = Array.isArray(value) ? value[0] : value;

    return (uuid as string | undefined) || undefined;
};

const POLL_MS = 15000;
let poll: ReturnType<typeof setInterval> | null = null;

const refresh = async () => {
    const known = new Set(notifications.value.map((n) => n.id));

    try {
        const res = await notificationService.list({
            per_page: 4,
            branch_uuid: currentBranchUuid(),
        });

        const rows: Notification[] = Array.isArray(res?.data) ? res.data : [];

        const fresh = rows.filter((n) => n.unread && !known.has(n.id));

        notifications.value = rows;

        if (known.size) {
            fresh.forEach((n) => info("New Notification", n.message));
        }
    } catch (err) {
        console.error(err);
    }
};

watch(
    [() => route.params.uuid, () => user.value?.uuid],
    async () => {
        await loadNotifications(currentBranchUuid());
        bindChannel(currentBranchUuid());
    },
    { immediate: true },
);
function generateId(): string {
    return `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}
onMounted(() => {
    isMounted.value = true;

    poll = setInterval(refresh, POLL_MS);

    // Catch up immediately on returning to the tab rather than waiting out
    // the interval.
    document.addEventListener("visibilitychange", onVisible);
});

const onVisible = () => {
    if (document.visibilityState === "visible") refresh();
};

onBeforeUnmount(() => {
    if (poll) clearInterval(poll);

    document.removeEventListener("visibilitychange", onVisible);

    if (channel && $echo) {
        channel.stopListening(".NotificationEvent");
        $echo.leave(`Notification.${user.value?.uuid}`);
    }
});
</script>
