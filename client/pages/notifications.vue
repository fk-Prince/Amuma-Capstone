<template>
    <div class="min-h-screen bg-white pt-[100px]">
        <div class="mx-auto w-full md:px-[5%] lg:px-[8%] px-5 pb-16 sm:px-8">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 py-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-bold tracking-tight text-slate-900"
                    >
                        Notifications
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        <span v-if="unreadCount">
                            You have {{ unreadCount }} unread
                            {{
                                unreadCount === 1
                                    ? "notification"
                                    : "notifications"
                            }}.
                        </span>
                        <span v-else>You're all caught up.</span>
                    </p>
                </div>

                <button
                    type="button"
                    :disabled="!unreadCount || marking"
                    class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="markAllRead"
                >
                    <LoaderCircle
                        v-if="marking"
                        class="h-3.5 w-3.5 animate-spin"
                    />
                    <CheckCheck v-else class="h-4 w-4" />
                    Mark all as read
                </button>
            </div>

            <!-- Filter -->
            <div class="flex gap-1 border-b border-slate-200">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="rounded-t-lg border px-4 py-2 text-sm font-medium transition"
                    :class="
                        filter === tab.value
                            ? 'border-slate-200 border-b-white bg-white text-slate-900'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    "
                    @click="setFilter(tab.value)"
                >
                    {{ tab.label }}

                    <span
                        v-if="tab.value === 'unread' && unreadCount"
                        class="ml-1.5 rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white"
                    >
                        {{ unreadCount }}
                    </span>
                </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="space-y-3 py-6">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="h-20 animate-pulse rounded-xl bg-slate-100"
                />
            </div>

            <!-- Empty -->
            <div
                v-else-if="!notifications.length"
                class="flex flex-col items-center justify-center py-20 text-center"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                >
                    <Bell class="h-6 w-6" />
                </div>

                <p class="mt-3 text-sm font-semibold text-slate-800">
                    {{
                        filter === "unread"
                            ? "No unread notifications"
                            : "No notifications yet"
                    }}
                </p>

                <p class="mt-1 max-w-sm text-sm text-slate-500">
                    {{
                        filter === "unread"
                            ? "Everything here has been read."
                            : "Updates about bookings, schedules and billing will show up here."
                    }}
                </p>
            </div>

            <!-- List -->
            <ul v-else class="divide-y divide-slate-100">
                <li
                    v-for="item in notifications"
                    :key="item.id"
                    class="flex cursor-pointer items-start gap-4 px-2 py-4 transition hover:bg-slate-50"
                    :class="item.unread ? 'bg-primary-50/30' : ''"
                    @click="openNotification(item)"
                >
                    <span
                        class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                        :class="toneFor(item.message_type).wrapper"
                    >
                        <component
                            :is="toneFor(item.message_type).icon"
                            class="h-4 w-4"
                        />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ item.message_type }}
                            </p>

                            <span
                                v-if="item.branch?.name"
                                class="truncate rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500"
                            >
                                {{ item.branch.name }}
                            </span>
                        </div>

                        <p class="mt-0.5 text-sm leading-6 text-slate-600">
                            {{ item.message }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            {{ notifcationFormatDate(item.created_at) }}
                        </p>
                    </div>

                    <span
                        v-if="item.unread"
                        class="mt-2 h-2 w-2 shrink-0 rounded-full bg-primary"
                        aria-label="Unread"
                    />
                </li>
            </ul>

            <div
                v-if="!loading && currentPage < lastPage"
                class="flex justify-center pt-6"
            >
                <button
                    type="button"
                    :disabled="loadingMore"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="fetchNotifications(currentPage + 1)"
                >
                    {{ loadingMore ? "Loading..." : "Load more" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import {
    Bell,
    CalendarClock,
    CheckCheck,
    ClipboardList,
    CreditCard,
    LoaderCircle,
    MessageSquare,
} from "lucide-vue-next";

import { notificationService } from "~/api/notification/NotificationService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import { notifcationFormatDate } from "~/utils/notification-time";
import type { Notification } from "~/types/notification";

// No layout: this page is reached from both the dashboard and the portal, so
// it stands on its own the same way /profile does.
definePageMeta({
    middleware: "auth-client",
    navVariant: 1,
});

useHead({ title: "Notifications" });

const { error } = useToast();
const user = useAuthUser();
const { $echo } = useNuxtApp();

const tabs = [
    { label: "All", value: "all" },
    { label: "Unread", value: "unread" },
] as const;

const filter = ref<"all" | "unread">("all");

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);

const loading = ref(true);
const loadingMore = ref(false);
const marking = ref(false);

const currentPage = ref(1);
const lastPage = ref(1);

let channel: any = null;
let handler: ((event: any) => void) | null = null;

const TONES: Record<string, { icon: any; wrapper: string }> = {
    Booking: {
        icon: ClipboardList,
        wrapper: "bg-primary-50 text-primary",
    },
    Schedule: {
        icon: CalendarClock,
        wrapper: "bg-accent-50 text-accent-700",
    },
    Billing: {
        icon: CreditCard,
        wrapper: "bg-amber-50 text-amber-600",
    },
};

const toneFor = (type: string) =>
    TONES[type] ?? {
        icon: MessageSquare,
        wrapper: "bg-slate-100 text-slate-500",
    };

// Guards against a slow earlier page landing after a filter switch.
let requestId = 0;

const fetchNotifications = async (page = 1) => {
    const thisRequest = ++requestId;

    if (page === 1) loading.value = true;
    else loadingMore.value = true;

    try {
        const res: any = await notificationService.list({
            page,
            per_page: 15,
            unread_only: filter.value === "unread" ? 1 : undefined,
        });

        if (thisRequest !== requestId) return;

        const rows: Notification[] = res?.data ?? [];

        notifications.value =
            page === 1 ? rows : [...notifications.value, ...rows];

        unreadCount.value = res?.meta?.unread_count ?? unreadCount.value;
        currentPage.value = res?.meta?.current_page ?? page;
        lastPage.value = res?.meta?.last_page ?? page;
    } catch (err: any) {
        if (thisRequest !== requestId) return;

        console.error(err);
        error(err?.message ?? "Failed to load notifications.");
    } finally {
        if (thisRequest === requestId) {
            loading.value = false;
            loadingMore.value = false;
        }
    }
};

const setFilter = (value: "all" | "unread") => {
    if (filter.value === value) return;

    filter.value = value;
    fetchNotifications(1);
};

const openNotification = async (item: Notification) => {
    if (!item.unread) return;

    // Patched locally rather than refetching the list.
    item.unread = false;
    unreadCount.value = Math.max(unreadCount.value - 1, 0);

    try {
        await notificationService.markRead(item.id);
    } catch (err: any) {
        item.unread = true;
        unreadCount.value += 1;
        error(err?.message ?? "Failed to mark as read.");
    }
};

const markAllRead = async () => {
    if (!unreadCount.value || marking.value) return;

    marking.value = true;

    try {
        await notificationService.markRead();

        notifications.value.forEach((n) => (n.unread = false));
        unreadCount.value = 0;

        // The unread tab's contents no longer match its filter.
        if (filter.value === "unread") {
            await fetchNotifications(1);
        }
    } catch (err: any) {
        error(err?.message ?? "Failed to mark all as read.");
    } finally {
        marking.value = false;
    }
};

const bindChannel = () => {
    const uuid = user.value?.uuid;

    if (channel && handler) {
        channel.stopListening(".NotificationEvent", handler);
        channel = null;
    }

    if (!uuid || !$echo) return;

    handler = (event: any) => {
        notifications.value.unshift({
            id: Date.now(),
            message: event.message,
            message_type: event.message_type,
            created_at: new Date().toISOString(),
            unread: true,
        } as Notification);

        unreadCount.value += 1;
    };

    channel = $echo
        .private(`Notification.${uuid}`)
        .listen(".NotificationEvent", handler);
};

watch(() => user.value?.uuid, bindChannel, { immediate: true });

onMounted(async () => {
    await fetchNotifications(1);
});

onBeforeUnmount(() => {
    if (!channel || !handler) return;

    channel.stopListening(".NotificationEvent", handler);
});
</script>
