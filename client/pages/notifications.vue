<template>
    <div class="min-h-screen bg-slate-50/70 pt-[100px] dark:bg-surface">
        <div class="mx-auto w-full max-w-[100rem] px-6 pb-16">
            <div class="max-w-4xl">
                <!-- Header -->
                <div
                    class="flex flex-col gap-4 py-6 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5">
                            <h1
                                class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                            >
                                Notifications
                            </h1>

                            <span
                                v-if="unreadCount"
                                class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary dark:bg-primary-500/15"
                            >
                                {{ unreadCount }} new
                            </span>
                        </div>

                        <p
                            class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                        >
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
                        class="inline-flex w-fit shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-secondary dark:text-gray-300 dark:hover:bg-white/10"
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
                <div
                    class="inline-flex gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm dark:border-white/10 dark:bg-secondary"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-1.5 text-sm font-medium transition"
                        :class="
                            filter === tab.value
                                ? 'bg-primary text-white shadow-sm'
                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200'
                        "
                        @click="setFilter(tab.value)"
                    >
                        {{ tab.label }}

                        <span
                            v-if="tab.value === 'unread' && unreadCount"
                            class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                            :class="
                                filter === tab.value
                                    ? 'bg-white/20 text-white'
                                    : 'bg-rose-500 text-white'
                            "
                        >
                            {{ unreadCount }}
                        </span>
                    </button>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="mt-5 space-y-3">
                    <div
                        v-for="n in 4"
                        :key="n"
                        class="h-[86px] animate-pulse rounded-xl bg-slate-200/70 dark:bg-white/5"
                    />
                </div>

                <!-- Empty -->
                <div
                    v-else-if="!notifications.length"
                    class="mt-5 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white py-20 text-center dark:border-white/10 dark:bg-secondary"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-white/5 dark:text-gray-500"
                    >
                        <Bell class="h-6 w-6" />
                    </div>

                    <p
                        class="mt-3 text-sm font-semibold text-slate-800 dark:text-white"
                    >
                        {{
                            filter === "unread"
                                ? "No unread notifications"
                                : "No notifications yet"
                        }}
                    </p>

                    <p
                        class="mt-1 max-w-sm text-sm text-slate-500 dark:text-gray-400"
                    >
                        {{
                            filter === "unread"
                                ? "Everything here has been read."
                                : "Updates about bookings, schedules and billing will show up here."
                        }}
                    </p>
                </div>

                <!-- List -->
                <ul v-else class="mt-5 space-y-2.5">
                    <li
                        v-for="item in notifications"
                        :key="item.id"
                        class="group relative flex cursor-pointer items-start gap-4 overflow-hidden rounded-xl border bg-white p-4 shadow-sm transition hover:-translate-y-px hover:border-primary-200 hover:shadow-md dark:bg-secondary dark:hover:border-primary-500/40"
                        :class="
                            item.unread
                                ? 'border-primary-100 dark:border-primary-500/25'
                                : 'border-slate-200 dark:border-white/10'
                        "
                        @click="openNotification(item)"
                    >
                        <span
                            v-if="item.unread"
                            class="absolute inset-y-0 left-0 w-1 bg-primary"
                            aria-hidden="true"
                        />

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
                            <div class="flex flex-wrap items-center gap-2">
                                <p
                                    class="text-sm font-semibold text-slate-800 dark:text-white"
                                >
                                    {{ item.message_type }}
                                </p>

                                <span
                                    v-if="item.branch?.name"
                                    class="truncate rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-white/5 dark:text-gray-400"
                                >
                                    {{ item.branch.name }}
                                </span>
                            </div>

                            <p
                                class="mt-1 text-sm leading-6 text-slate-600 dark:text-gray-300"
                            >
                                {{ item.message }}
                            </p>

                            <p
                                class="mt-1.5 text-xs text-slate-400 dark:text-gray-500"
                            >
                                {{ notifcationFormatDate(item.created_at) }}
                            </p>
                        </div>

                        <span
                            v-if="item.unread"
                            class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary"
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
                        class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-secondary dark:text-gray-300 dark:hover:bg-white/10"
                        @click="fetchNotifications(currentPage + 1)"
                    >
                        {{ loadingMore ? "Loading..." : "Load more" }}
                    </button>
                </div>
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
        wrapper: "bg-primary-50 text-primary dark:bg-primary-500/10 dark:text-primary-300",
    },
    Schedule: {
        icon: CalendarClock,
        wrapper: "bg-accent-50 text-accent-700 dark:bg-accent-500/10 dark:text-accent-300",
    },
    Billing: {
        icon: CreditCard,
        wrapper: "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400",
    },
};

const toneFor = (type: string) =>
    TONES[type] ?? {
        icon: MessageSquare,
        wrapper: "bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-gray-400",
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
