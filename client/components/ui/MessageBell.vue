<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";
import { useRoute, useRouter } from "vue-router";
import { onClickOutside } from "@vueuse/core";

import { Volume2, VolumeX } from "lucide-vue-next";

import MessageAvatar from "~/components/messaging/MessageAvatar.vue";
import { messageService } from "~/api/message/MessageService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useMessageAlert } from "~/composables/useMessageAlert";
import { playMessageSound, useSoundPrefs } from "~/composables/useSounds";
import { useBranchStore } from "~/stores/branch";
import { remScale } from "~/utils/rem";

import type { ConversationSummary } from "~/types/message";

type Tab = "family" | "staff";

type Row = ConversationSummary & { kind: "family" | "staff" };

// portal: the family portal's bell lists the family member's own conversations
// with branch staff instead of the Family / Staff tabs.
const props = defineProps<{ portal?: boolean }>();

const MAX_ROWS = 5;
const DROPDOWN_MARGIN = 16;
const DROPDOWN_MAX_WIDTH = 380;

const TABS: { label: string; value: Tab }[] = [
    { label: "Family", value: "family" },
    { label: "Staff", value: "staff" },
];

const route = useRoute();
const router = useRouter();
const branchStore = useBranchStore();
const user = useAuthUser();
const { notifyNewMessage } = useMessageAlert();

// Quick mute for the message sound. The volume is set on the profile page.
const { message: soundVolume } = useSoundPrefs();
const soundOn = computed(() => soundVolume.value > 0);
let lastVolume = 100;

function toggleSound() {
    if (soundOn.value) {
        lastVolume = soundVolume.value;
        soundVolume.value = 0;
        return;
    }

    soundVolume.value = lastVolume || 100;

    // let the person hear what they just turned on
    playMessageSound({ force: true });
}

const { $echo } = useNuxtApp();

const family = ref<ConversationSummary[]>([]);
const staff = ref<ConversationSummary[]>([]);
const isMounted = ref(false);
const open = ref(false);
const loading = ref(false);
const tab = ref<Tab>("family");

const dropdownRef = ref<HTMLElement | null>(null);
const buttonRef = ref<HTMLElement | null>(null);
const dropdownStyle = ref<Record<string, string>>({});

const branchUuid = computed(
    () =>
        (route.params.uuid as string) ??
        (branchStore.activeBranch?.uuid as string | undefined),
);

const sumUnread = (list: ConversationSummary[]) =>
    list.reduce((sum, c) => sum + (c.unread_count ?? 0), 0);

const familyUnread = computed(() => sumUnread(family.value));
const staffUnread = computed(() => sumUnread(staff.value));
const unreadCount = computed(() => familyUnread.value + staffUnread.value);

const tabUnread = (value: Tab) =>
    value === "family" ? familyUnread.value : staffUnread.value;

const byRecent = (a: Row, b: Row) =>
    new Date(b.last_message_at ?? 0).getTime() -
    new Date(a.last_message_at ?? 0).getTime();

const rows = computed<Row[]>(() => {
    const familyRows = family.value.map((c) => ({
        ...c,
        kind: "family" as const,
    }));
    const staffRows = staff.value.map((c) => ({
        ...c,
        kind: "staff" as const,
    }));

    const source = tab.value === "family" ? familyRows : staffRows;

    return source.sort(byRecent).slice(0, MAX_ROWS);
});

const displayName = (row: Row) =>
    (row.kind === "staff" || props.portal
        ? row.staff_name
        : row.client_name) ?? (props.portal ? "Branch team" : "Unknown");

const displayAvatar = (row: Row) =>
    row.kind === "staff" || props.portal
        ? (row.staff_avatar ?? row.avatar)
        : row.avatar;

const subtitle = (row: Row) => {
    if (props.portal) {
        return [row.staff_role, row.branch?.name].filter(Boolean).join(" · ");
    }

    if (row.kind === "staff") return row.staff_role ?? "Staff";

    const names = row.patient_names?.length
        ? row.patient_names
        : row.patient_name
          ? [row.patient_name]
          : [];

    return names.length ? `Family of ${names.join(", ")}` : "Family";
};

function timeAgo(value: string | null) {
    if (!value) return "";

    const minutes = Math.floor(
        (Date.now() - new Date(value).getTime()) / 60000,
    );

    if (minutes < 1) return "now";
    if (minutes < 60) return `${minutes}m`;
    if (minutes < 60 * 24) return `${Math.floor(minutes / 60)}h`;
    if (minutes < 60 * 24 * 7) return `${Math.floor(minutes / (60 * 24))}d`;

    return new Date(value).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
    });
}

async function load() {
    if (props.portal) {
        loading.value = true;

        try {
            family.value = (await messageService.conversations()) ?? [];
        } catch {
            family.value = [];
        }

        loading.value = false;
        return;
    }

    if (!branchUuid.value) return;

    loading.value = true;

    const payload = { branch_uuid: branchUuid.value };

    const [familyRes, staffRes] = await Promise.allSettled([
        messageService.branchConversations(payload),
        messageService.staffConversations(payload),
    ]);

    family.value =
        familyRes.status === "fulfilled" ? (familyRes.value ?? []) : [];
    staff.value = staffRes.status === "fulfilled" ? (staffRes.value ?? []) : [];

    loading.value = false;
}

// Opens the page on that conversation. People are identified by uuid in the URL.
function goToMessages(row?: Row) {
    open.value = false;

    const query: Record<string, string> = {};

    if (row) {
        if (props.portal || row.kind === "staff") {
            if (row.staff_uuid) query.staff = row.staff_uuid;
        } else if (row.client_uuid) {
            query.client = row.client_uuid;
        }
    }

    if (props.portal) {
        router.push({ path: "/portal/messages", query });
        return;
    }

    if (!branchUuid.value) return;

    router.push({
        path: `/app/branches/${branchUuid.value}/messages`,
        query,
    });
}

function updatePosition() {
    const btn = buttonRef.value;
    if (!btn) return;

    const rect = btn.getBoundingClientRect();
    const width = Math.min(
        DROPDOWN_MAX_WIDTH * remScale(),
        window.innerWidth - DROPDOWN_MARGIN * 2,
    );

    const maxLeft = window.innerWidth - DROPDOWN_MARGIN - width;
    const left = Math.min(
        Math.max(rect.right - width, DROPDOWN_MARGIN),
        maxLeft,
    );

    dropdownStyle.value = {
        top: `${rect.bottom + 8}px`,
        left: `${left}px`,
        width: `${width}px`,
    };
}

async function toggle() {
    open.value = !open.value;

    if (open.value) {
        await nextTick();
        updatePosition();
        load();
    }
}

watch(open, (isOpen) => {
    if (isOpen) window.addEventListener("resize", updatePosition);
    else window.removeEventListener("resize", updatePosition);
});

onClickOutside(dropdownRef, () => (open.value = false));

let joined: string[] = [];
let handler: ((payload: any) => void) | null = null;

function bindChannel() {
    if ($echo && handler) {
        for (const channel of joined) {
            ($echo as any)
                .private(channel)
                .stopListening(".MessageSent", handler);
        }

        joined = [];
        handler = null;
    }

    if (!$echo || (!props.portal && !branchUuid.value)) return;

    const userUuid = (user.value as any)?.uuid;

    joined = userUuid
        ? [
              props.portal
                  ? `Client.Messages.${userUuid}`
                  : `User.Messages.${userUuid}`,
          ]
        : [];

    handler = (payload: any) => {
        if (
            !props.portal &&
            payload.branch_uuid &&
            payload.branch_uuid !== branchUuid.value
        ) {
            return;
        }

        const mine = props.portal
            ? payload.sender_type === "client"
            : Number(payload.sender_user_id) ===
              Number((user.value as any)?.user_id);

        const row =
            family.value.find(
                (c) => c.conversation_id === payload.conversation_id,
            ) ??
            staff.value.find(
                (c) => c.conversation_id === payload.conversation_id,
            );

        if (!row) {
            load();
            return;
        }

        row.last_message = payload.preview ?? payload.body;
        row.last_message_at = new Date().toISOString();

        if (mine) return;

        row.unread_count += 1;

        notifyNewMessage(
            payload,
            props.portal ? row.staff_name : (row.client_name ?? row.staff_name),
        );
    };

    for (const channel of joined) {
        ($echo as any).private(channel).listen(".MessageSent", handler);
    }
}

watch(branchUuid, load, { immediate: true });

watch([branchUuid, () => (user.value as any)?.uuid], bindChannel, {
    immediate: true,
});

watch(
    () => route.path,
    () => {
        open.value = false;
    },
);

onMounted(() => {
    isMounted.value = true;
});

// stopListening, not leave: the messages page shares this channel and leave()
// would tear it down for both.
onBeforeUnmount(() => {
    window.removeEventListener("resize", updatePosition);

    if ($echo && handler) {
        for (const channel of joined) {
            ($echo as any)
                .private(channel)
                .stopListening(".MessageSent", handler);
        }
    }
});
</script>

<template>
    <div v-if="isMounted" ref="dropdownRef" class="relative">
        <button
            ref="buttonRef"
            type="button"
            aria-label="Messages"
            :aria-expanded="open"
            class="relative w-[38px] h-[38px] flex items-center justify-center rounded-lg hover:bg-gray-50 dark:hover:bg-white/10 text-gray-500 dark:text-white/70"
            @click="toggle"
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
                class="fixed z-50 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-white/10 dark:bg-secondary dark:shadow-black/30"
                :style="dropdownStyle"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-white/10"
                >
                    <span class="text-sm font-medium dark:text-white">
                        Messages
                    </span>

                    <div class="flex items-center gap-2">
                        <span
                            v-if="unreadCount > 0"
                            class="rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-600 dark:bg-primary-500/15 dark:text-primary-200"
                        >
                            {{ unreadCount }} unread
                        </span>

                        <button
                            type="button"
                            :title="
                                soundOn
                                    ? 'Message sound is on. Click to mute.'
                                    : 'Message sound is off. Click to turn on.'
                            "
                            :aria-label="
                                soundOn
                                    ? 'Mute message sound'
                                    : 'Turn on message sound'
                            "
                            :aria-pressed="soundOn"
                            class="rounded-md p-1 transition hover:bg-gray-100 dark:hover:bg-white/10"
                            :class="
                                soundOn
                                    ? 'text-primary'
                                    : 'text-gray-400 dark:text-gray-500'
                            "
                            @click="toggleSound"
                        >
                            <Volume2 v-if="soundOn" class="h-4 w-4" />
                            <VolumeX v-else class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-if="!portal" class="px-3 pt-3">
                    <div
                        class="flex rounded-lg bg-slate-100 p-1 dark:bg-white/5"
                        role="tablist"
                    >
                        <button
                            v-for="item in TABS"
                            :key="item.value"
                            type="button"
                            role="tab"
                            :aria-selected="tab === item.value"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-md py-1.5 text-xs font-semibold transition"
                            :class="
                                tab === item.value
                                    ? 'bg-white text-primary shadow-sm dark:bg-white/10 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                            "
                            @click="tab = item.value"
                        >
                            {{ item.label }}

                            <span
                                v-if="tabUnread(item.value) > 0"
                                class="min-w-[16px] rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white"
                            >
                                {{ tabUnread(item.value) }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="max-h-[360px] overflow-y-auto p-2">
                    <div v-if="loading && !rows.length" class="space-y-2 p-1">
                        <div
                            v-for="n in 3"
                            :key="n"
                            class="h-12 animate-pulse rounded-lg bg-slate-100 dark:bg-white/5"
                        />
                    </div>

                    <p
                        v-else-if="!rows.length"
                        class="px-4 py-8 text-center text-sm text-slate-400 dark:text-gray-500"
                    >
                        No messages yet.
                    </p>

                    <template v-else>
                        <button
                            v-for="row in rows"
                            :key="`${row.kind}-${row.conversation_id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition hover:bg-slate-50 dark:hover:bg-white/5"
                            @click="goToMessages(row)"
                        >
                            <MessageAvatar
                                :src="displayAvatar(row)"
                                :name="displayName(row)"
                                size="md"
                            />

                            <span class="min-w-0 flex-1">
                                <span
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span
                                        class="truncate text-sm dark:text-white"
                                        :class="
                                            row.unread_count > 0
                                                ? 'font-bold text-slate-900'
                                                : 'font-medium text-slate-700'
                                        "
                                    >
                                        {{ displayName(row) }}
                                    </span>

                                    <span
                                        class="shrink-0 text-[11px] text-slate-400 dark:text-gray-500"
                                    >
                                        {{ timeAgo(row.last_message_at) }}
                                    </span>
                                </span>

                                <span
                                    class="block truncate text-[11px] text-primary-600 dark:text-primary-300"
                                >
                                    {{ subtitle(row) }}
                                </span>

                                <span
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span
                                        class="truncate text-xs"
                                        :class="
                                            row.unread_count > 0
                                                ? 'font-medium text-slate-700 dark:text-gray-200'
                                                : 'text-slate-500 dark:text-gray-400'
                                        "
                                    >
                                        {{ row.last_message ?? "No messages yet" }}
                                    </span>

                                    <span
                                        v-if="row.unread_count > 0"
                                        class="min-w-[18px] shrink-0 rounded-full bg-primary px-1.5 text-center text-[10px] font-bold leading-[18px] text-white"
                                    >
                                        {{ row.unread_count }}
                                    </span>
                                </span>
                            </span>
                        </button>
                    </template>
                </div>

                <button
                    type="button"
                    class="w-full border-t border-gray-100 py-3 text-center text-xs font-semibold text-primary transition hover:bg-slate-50 dark:border-white/10 dark:text-primary-300 dark:hover:bg-white/5"
                    @click="goToMessages()"
                >
                    View all messages
                </button>
            </div>
        </Transition>
    </div>
</template>
