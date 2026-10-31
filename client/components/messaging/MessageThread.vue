<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import {
    X,
    SendHorizontal,
    LoaderCircle,
    Paperclip,
    FileText,
    Download,
    ArrowDown,
    Info,
    Pin,
    PinOff,
    Check,
    CheckCheck,
    Mic,
    MoreHorizontal,
    Reply,
    Forward as ForwardIcon,
    Search,
    SmilePlus,
    ChevronUp,
    ChevronDown,
} from "lucide-vue-next";

import MessageAvatar from "~/components/messaging/MessageAvatar.vue";
import ImagePopup from "~/components/ui/ImagePopup.vue";
import ConversationDetailsPanel from "~/components/messaging/ConversationDetailsPanel.vue";
import MessageActionMenu from "~/components/messaging/MessageActionMenu.vue";
import ForwardModal from "~/components/messaging/ForwardModal.vue";
import VoiceMessagePlayer from "~/components/messaging/VoiceMessagePlayer.vue";
import VoiceRecorder from "~/components/messaging/VoiceRecorder.vue";
import { HEART } from "~/utils/chatEmoji";
import { useToast } from "~/composables/useToast";
import { messageService } from "~/api/message/MessageService";
import { playSendSound } from "~/composables/useSounds";
import { useAuthUser } from "~/composables/useAuthUser";

import type {
    ChatMessage,
    ConversationDetails,
    MessageReaction,
    MessageSender,
    MessageStatus,
    OutgoingMessage,
    ReplyPreview,
} from "~/types/message";

const props = withDefaults(
    defineProps<{
        messages: ChatMessage[];
        viewer: MessageSender;
        title?: string | null;
        subtitle?: string | null;
        avatar?: string | null;
        patients?: string[];
        patientsLabel?: string;
        channel?: string | string[] | null;
        conversationId?: number | null;
        loading?: boolean;
        sending?: boolean;
        disabled?: boolean;
        emptyText?: string;
        showClose?: boolean;
        /** Staff only: lets "Forward" list this branch's conversations. */
        branchUuid?: string | null;
        conversationType?: "family" | "staff" | null;
        conversationClientUuid?: string | null;
    }>(),
    {
        patients: () => [],
        patientsLabel: "Caring for",
    },
);

const emit = defineEmits<{
    send: [message: OutgoingMessage];
    incoming: [message: ChatMessage];
    close: [];
}>();

const user = useAuthUser();

const { $echo } = useNuxtApp();

const ATTACHMENT_TYPES = [
    "image/jpeg",
    "image/png",
    "image/gif",
    "image/webp",
    "application/pdf",
];

const ATTACHMENT_MAX_BYTES = 10 * 1024 * 1024;

const draft = ref("");
const attachment = ref<File | null>(null);
const attachmentPreview = ref<string | null>(null);
const attachmentError = ref("");
const fileInput = ref<HTMLInputElement | null>(null);
const textarea = ref<HTMLTextAreaElement | null>(null);

function autosize() {
    const el = textarea.value;

    if (!el) return;

    el.style.height = "auto";
    el.style.height = `${Math.min(el.scrollHeight, 128)}px`;
}
const scroller = ref<HTMLElement | null>(null);
const showAllPatients = ref(false);

const detailsOpen = ref(false);
const detailsLoading = ref(false);
const detailsError = ref("");
const details = ref<ConversationDetails | null>(null);

async function loadDetails(silent = false) {
    if (!props.conversationId) return;

    if (!silent) {
        detailsLoading.value = true;
        detailsError.value = "";
    }

    const requestedFor = props.conversationId;

    try {
        const res = await messageService.details({
            conversation_id: requestedFor,
            as_staff: props.viewer === "staff",
        });

        if (requestedFor === props.conversationId) details.value = res;
    } catch (err: any) {
        if (!silent) {
            details.value = null;
            detailsError.value = err?.message ?? "Unable to load the details.";
        }
    } finally {
        if (!silent) detailsLoading.value = false;
    }
}

async function openDetails() {
    if (!props.conversationId) return;

    detailsOpen.value = true;
    await loadDetails();
}

function closeDetails() {
    detailsOpen.value = false;
}

// ---- Pinned messages -------------------------------------------------------
const pinOverrides = ref<Record<number, boolean>>({});
const pinningId = ref<number | null>(null);
const pinError = ref("");
const pinnedCursor = ref(0);
const highlightId = ref<number | null>(null);

let pinErrorTimer: ReturnType<typeof setTimeout> | null = null;
let highlightTimer: ReturnType<typeof setTimeout> | null = null;

const isPinned = (message: ChatMessage) =>
    pinOverrides.value[message.message_id] ?? !!message.is_pinned;

const pinnedMessages = computed(() => props.messages.filter(isPinned));

// The bar shows the newest pin first; clicking it jumps there, then cycles
// to the next older pin.
const pinnedIndex = computed(() => {
    const count = pinnedMessages.value.length;

    return count ? count - 1 - (pinnedCursor.value % count) : -1;
});

const pinnedShown = computed(
    () => pinnedMessages.value[pinnedIndex.value] ?? null,
);

async function scrollToMessage(id: number) {
    await nextTick();

    const element = scroller.value?.querySelector<HTMLElement>(
        `[data-message-id="${id}"]`,
    );

    if (!element) return;

    element.scrollIntoView({ behavior: "smooth", block: "center" });

    highlightId.value = id;

    if (highlightTimer) clearTimeout(highlightTimer);
    highlightTimer = setTimeout(() => (highlightId.value = null), 1600);
}

function jumpToPinned() {
    const message = pinnedShown.value;

    if (!message) return;

    scrollToMessage(message.message_id);
    pinnedCursor.value += 1;
}

async function togglePin(message: ChatMessage) {
    if (pinningId.value !== null) return;

    pinningId.value = message.message_id;
    pinError.value = "";

    try {
        const res = await messageService.pin({
            message_id: message.message_id,
            as_staff: props.viewer === "staff",
        });

        pinOverrides.value = {
            ...pinOverrides.value,
            [message.message_id]: !!res?.is_pinned,
        };
        pinnedCursor.value = 0;

        if (detailsOpen.value) loadDetails(true);
    } catch (err: any) {
        pinError.value = err?.message ?? "Unable to update the pin.";

        if (pinErrorTimer) clearTimeout(pinErrorTimer);
        pinErrorTimer = setTimeout(() => (pinError.value = ""), 3500);
    } finally {
        pinningId.value = null;
    }
}

function onPanelDownload(messageId: number) {
    const message = props.messages.find((item) => item.message_id === messageId);

    if (message) downloadAttachment(message);
}

// Keep the panel's media list current while it is open.
watch(
    () => props.messages.length,
    () => {
        if (detailsOpen.value) loadDetails(true);
    },
);

// Fresh thread data already carries the saved pins.
watch(
    () => props.loading,
    (isLoading, wasLoading) => {
        if (wasLoading && !isLoading) {
            pinOverrides.value = {};
            reactionOverrides.value = {};
            deliveredUpTo.value = 0;
            readUpTo.value = 0;
        }
    },
);

const PATIENT_PREVIEW = 2;

const SKELETON_BUBBLES = ["w-40", "w-56", "w-32", "w-64", "w-44"];

const hiddenPatientCount = computed(() =>
    Math.max(0, props.patients.length - PATIENT_PREVIEW),
);

const visiblePatients = computed(() =>
    showAllPatients.value
        ? props.patients
        : props.patients.slice(0, PATIENT_PREVIEW),
);

const imageAttachments = computed(() =>
    props.messages
        .filter((message) => message.attachment?.type === "image")
        .map((message) => ({
            branch_image_id: message.message_id,
            image_url: message.attachment!.url,
            type: "other" as const,
            description: message.attachment!.name,
        })),
);

const openImageIndex = ref<number | null>(null);

function openImage(url: string) {
    const index = imageAttachments.value.findIndex(
        (image) => image.image_url === url,
    );

    if (index >= 0) openImageIndex.value = index;
}

const downloadingId = ref<number | null>(null);

async function downloadAttachment(message: ChatMessage) {
    const attachment = message.attachment;

    if (!attachment || downloadingId.value !== null) return;

    downloadingId.value = message.message_id;

    try {
        const response = await fetch(attachment.url);

        if (!response.ok) throw new Error("Download failed");

        const objectUrl = URL.createObjectURL(await response.blob());
        const link = document.createElement("a");

        link.href = objectUrl;
        link.download = attachment.name ?? "attachment";
        document.body.appendChild(link);
        link.click();
        link.remove();

        URL.revokeObjectURL(objectUrl);
    } catch {
        window.open(attachment.url, "_blank", "noopener");
    } finally {
        downloadingId.value = null;
    }
}

const canSend = computed(
    () =>
        (draft.value.trim().length > 0 || !!attachment.value) &&
        !props.sending &&
        !props.disabled,
);

function setAttachment(file: File | null) {
    if (attachmentPreview.value) {
        URL.revokeObjectURL(attachmentPreview.value);
    }

    attachment.value = file;
    attachmentPreview.value =
        file && file.type.startsWith("image/")
            ? URL.createObjectURL(file)
            : null;
}

function pickAttachment(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    input.value = "";
    attachmentError.value = "";

    if (!file) return;

    if (!ATTACHMENT_TYPES.includes(file.type)) {
        attachmentError.value = "Only images and PDF files can be attached.";
        return;
    }

    if (file.size > ATTACHMENT_MAX_BYTES) {
        attachmentError.value = "Attachments must be 10 MB or smaller.";
        return;
    }

    setAttachment(file);
}

const BOTTOM_THRESHOLD = 80;

const atBottom = ref(true);
const missedCount = ref(0);

function onScroll() {
    const element = scroller.value;

    if (!element) return;

    atBottom.value =
        element.scrollHeight - element.scrollTop - element.clientHeight <
        BOTTOM_THRESHOLD;

    if (atBottom.value) missedCount.value = 0;
}

async function scrollToBottom(smooth = false) {
    await nextTick();

    const element = scroller.value;

    if (!element) return;

    element.scrollTo({
        top: element.scrollHeight,
        behavior: smooth ? "smooth" : "auto",
    });

    atBottom.value = true;
    missedCount.value = 0;
}

watch(
    () => props.messages.length,
    (length, previous) => {
        const arrived = length - (previous ?? 0);

        if (arrived <= 0) return;

        const latest = props.messages[length - 1];

        if (atBottom.value || (latest && isMine(latest))) {
            scrollToBottom();

            return;
        }

        missedCount.value += arrived;
    },
);

watch(
    () => props.conversationId,
    () => {
        detailsOpen.value = false;
        details.value = null;
        pinOverrides.value = {};
        reactionOverrides.value = {};
        deliveredUpTo.value = 0;
        readUpTo.value = 0;
        replyingTo.value = null;
        recording.value = false;
        forwardTarget.value = null;
        expandedId.value = null;
        closeSearch();
        closeMenu();
        pinnedCursor.value = 0;
        highlightId.value = null;
        showAllPatients.value = false;
        attachmentError.value = "";
        setAttachment(null);
        scrollToBottom();
    },
);

watch(
    () => props.loading,
    (loading) => {
        if (!loading) scrollToBottom();
    },
);

// "Sent" sound: when the parent finishes sending and our own message has been
// added to the thread. A failed send adds nothing, so it stays silent.
let awaitingSent = false;
let countAtSend = 0;

watch(
    () => props.sending,
    (isSending, wasSending) => {
        if (!wasSending || isSending || !awaitingSent) return;

        awaitingSent = false;

        const latest = props.messages[props.messages.length - 1];

        if (props.messages.length > countAtSend && latest && isMine(latest)) {
            playSendSound();
        }
    },
);

function submit() {
    if (!canSend.value) return;

    awaitingSent = true;
    countAtSend = props.messages.length;

    emit("send", {
        body: draft.value.trim(),
        attachment: attachment.value,
        reply_to_message_id: replyingTo.value?.message_id ?? null,
    });
    playSendSound();
    replyingTo.value = null;
    draft.value = "";
    attachmentError.value = "";
    setAttachment(null);
    nextTick(autosize);
}

function submitVoice(payload: { file: File; duration: number }) {
    recording.value = false;

    if (props.sending || props.disabled) return;

    awaitingSent = true;
    countAtSend = props.messages.length;

    emit("send", {
        body: "",
        attachment: payload.file,
        duration: payload.duration,
        reply_to_message_id: replyingTo.value?.message_id ?? null,
    });
    playSendSound();
    replyingTo.value = null;
}

function isMine(message: ChatMessage) {
    if (typeof message.is_mine === "boolean") {
        return message.is_mine;
    }

    return message.sender_type === props.viewer;
}

function formatTime(value: string | null) {
    if (!value) return "";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "";

    return parsed.toLocaleString("en-PH", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

// ---- Reactions, replies, receipts, long-press menu, voice -------------------
const { success } = useToast();

const myUserId = computed(() => Number((user.value as any)?.user_id) || 0);

const isFamilyThread = computed(
    () => props.conversationType === "family" || props.viewer === "client",
);

function flashError(message: string) {
    pinError.value = message;

    if (pinErrorTimer) clearTimeout(pinErrorTimer);
    pinErrorTimer = setTimeout(() => (pinError.value = ""), 3500);
}

const stripVariation = (value: string) => value.replace(/\uFE0F/g, "");

// Day separators ("Today", "Yesterday", "Sun, Oct 18") - exact times only
// show when the thread is swiped / hovered.
function dayKey(value: string | null) {
    const parsed = value ? new Date(value) : null;

    if (!parsed || Number.isNaN(parsed.getTime())) return "";

    return `${parsed.getFullYear()}-${parsed.getMonth()}-${parsed.getDate()}`;
}

function dayLabel(value: string) {
    const parsed = new Date(value);
    const now = new Date();
    const yesterday = new Date();

    yesterday.setDate(now.getDate() - 1);

    if (dayKey(value) === dayKey(now.toISOString())) return "Today";
    if (dayKey(value) === dayKey(yesterday.toISOString())) return "Yesterday";

    return parsed.toLocaleDateString("en-PH", {
        weekday: "short",
        month: "short",
        day: "numeric",
        year: parsed.getFullYear() === now.getFullYear() ? undefined : "numeric",
    });
}

const dayBreaks = computed(() => {
    const breaks: Record<number, string> = {};
    let last = "";

    for (const message of props.messages) {
        const key = dayKey(message.created_at);

        if (key && key !== last) {
            breaks[message.message_id] = dayLabel(message.created_at);
            last = key;
        }
    }

    return breaks;
});

function formatClock(value: string | null) {
    if (!value) return "";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "";

    return parsed.toLocaleTimeString("en-PH", {
        hour: "2-digit",
        minute: "2-digit",
    });
}

// ---- Reactions ----
const reactionOverrides = ref<Record<number, MessageReaction[]>>({});

const reactionsOf = (message: ChatMessage) =>
    reactionOverrides.value[message.message_id] ?? message.reactions ?? [];

const myReaction = (message: ChatMessage) =>
    reactionsOf(message).find((item) => item.user_id === myUserId.value)
        ?.emoji ?? null;

function reactionGroups(message: ChatMessage) {
    const groups = new Map<
        string,
        { emoji: string; count: number; mine: boolean }
    >();

    for (const item of reactionsOf(message)) {
        const key = stripVariation(item.emoji);
        const group = groups.get(key) ?? {
            emoji: item.emoji,
            count: 0,
            mine: false,
        };

        group.count += 1;
        group.mine = group.mine || item.user_id === myUserId.value;
        groups.set(key, group);
    }

    return [...groups.values()];
}

function setReactions(messageId: number, reactions: MessageReaction[]) {
    reactionOverrides.value = {
        ...reactionOverrides.value,
        [messageId]: reactions,
    };
}

async function react(message: ChatMessage, emoji: string) {
    const before = reactionsOf(message);
    const mine = before.find((item) => item.user_id === myUserId.value);
    const others = before.filter((item) => item.user_id !== myUserId.value);

    // Same emoji again removes it; a different one replaces it.
    const removing = !!mine && stripVariation(mine.emoji) === stripVariation(emoji);

    setReactions(
        message.message_id,
        removing ? others : [...others, { user_id: myUserId.value, emoji }],
    );

    try {
        const res = await messageService.react({
            message_id: message.message_id,
            emoji,
            as_staff: props.viewer === "staff",
        });

        setReactions(message.message_id, res?.reactions ?? []);
    } catch (err: any) {
        setReactions(message.message_id, before);
        flashError(err?.message ?? "Unable to add your reaction.");
    }
}

// ---- Sent / Delivered / Seen ----
const deliveredUpTo = ref(0);
const readUpTo = ref(0);

const STATUS_LABEL: Record<MessageStatus, string> = {
    sent: "Sent",
    delivered: "Delivered",
    seen: "Seen",
};

function statusOf(message: ChatMessage): MessageStatus | null {
    if (!isMine(message)) return null;

    if (message.read_at || message.message_id <= readUpTo.value) return "seen";

    if (message.delivered_at || message.message_id <= deliveredUpTo.value) {
        return "delivered";
    }

    return "sent";
}

const lastMineId = computed(() => {
    for (let index = props.messages.length - 1; index >= 0; index--) {
        const message = props.messages[index]!;

        if (isMine(message)) return message.message_id;
    }

    return null;
});

function onStatus(payload: any) {
    if (payload.conversation_id !== props.conversationId) return;
    if (Number(payload.by_user_id) === myUserId.value) return;

    // A colleague on my own team reading a family thread says nothing about
    // whether the *family* has seen my message.
    if (isFamilyThread.value && payload.reader_side === props.viewer) return;

    const upTo = Number(payload.up_to_message_id) || 0;

    deliveredUpTo.value = Math.max(deliveredUpTo.value, upTo);

    if (payload.status === "read") {
        readUpTo.value = Math.max(readUpTo.value, upTo);
    }
}

function onReaction(payload: any) {
    if (payload.conversation_id !== props.conversationId) return;

    setReactions(Number(payload.message_id), payload.reactions ?? []);
}

// Tell the sender we've seen their messages (only while the thread is on screen).
let readTimer: ReturnType<typeof setTimeout> | null = null;

const threadVisible = () =>
    document.visibilityState === "visible" &&
    !!scroller.value &&
    scroller.value.getClientRects().length > 0;

function markReadSoon() {
    if (!props.conversationId || !threadVisible()) return;

    if (readTimer) clearTimeout(readTimer);

    readTimer = setTimeout(() => {
        if (!props.conversationId) return;

        messageService
            .markRead({
                conversation_id: props.conversationId,
                as_staff: props.viewer === "staff",
            })
            .catch(() => {});
    }, 300);
}

function onVisibilityChange() {
    const last = props.messages[props.messages.length - 1];

    if (last && !isMine(last) && !last.read_at) markReadSoon();
}

// ---- Replies ----
const replyingTo = ref<ChatMessage | null>(null);

function startReply(message: ChatMessage) {
    replyingTo.value = message;
    nextTick(() => textarea.value?.focus());
}

const attachmentLabel = (type: string | null | undefined) =>
    type === "audio"
        ? "Voice message"
        : type === "image"
          ? "Photo"
          : type === "pdf"
            ? "File"
            : null;

const summaryOf = (message: ChatMessage) =>
    message.body ?? attachmentLabel(message.attachment?.type) ?? "Message";

const quoteText = (reply: ReplyPreview) =>
    reply.body ??
    attachmentLabel(reply.attachment_type) ??
    reply.preview ??
    "Message";

function authorName(mine: boolean, senderType?: string | null) {
    if (mine) return "You";

    if (senderType === props.viewer) {
        return props.viewer === "staff" ? "Team member" : "Family member";
    }

    return props.title ?? "Them";
}

function replyAuthor(reply: ReplyPreview) {
    const mine =
        reply.sender_user_id && myUserId.value
            ? Number(reply.sender_user_id) === myUserId.value
            : !!reply.is_mine;

    return authorName(mine, reply.sender_type);
}

// ---- Long-press / right-click menu ----
type Anchor = { top: number; bottom: number; left: number; right: number };

const menu = ref<{
    message: ChatMessage;
    anchor: Anchor;
    mine: boolean;
    reactionsOnly: boolean;
} | null>(null);

function openMenu(
    message: ChatMessage,
    element: HTMLElement,
    reactionsOnly = false,
) {
    if (menu.value?.message.message_id === message.message_id) return;

    const rect = element.getBoundingClientRect();

    menu.value = {
        message,
        anchor: {
            top: rect.top,
            bottom: rect.bottom,
            left: rect.left,
            right: rect.right,
        },
        mine: isMine(message),
        reactionsOnly,
    };
}

function closeMenu() {
    menu.value = null;
}

function openMenuFromButton(
    event: Event,
    message: ChatMessage,
    reactionsOnly = false,
) {
    const bubble = (event.currentTarget as HTMLElement).closest<HTMLElement>(
        ".bubble-wrap",
    );

    if (bubble) openMenu(message, bubble, reactionsOnly);
}

const LONG_PRESS_MS = 450;

let pressTimer: ReturnType<typeof setTimeout> | null = null;
let pressOrigin = { x: 0, y: 0 };
let longPressed = false;

function cancelPress() {
    if (pressTimer) clearTimeout(pressTimer);

    pressTimer = null;
}

function pressStart(event: PointerEvent, message: ChatMessage) {
    longPressed = false;

    if (event.pointerType === "mouse" && event.button !== 0) return;

    if ((event.target as HTMLElement).closest("[data-no-press]")) return;

    const element = event.currentTarget as HTMLElement;

    pressOrigin = { x: event.clientX, y: event.clientY };
    cancelPress();

    pressTimer = setTimeout(() => {
        pressTimer = null;
        longPressed = true;
        openMenu(message, element);

        if (typeof navigator.vibrate === "function") navigator.vibrate(10);
    }, LONG_PRESS_MS);
}

function pressMove(event: PointerEvent) {
    if (!pressTimer) return;

    if (
        Math.hypot(event.clientX - pressOrigin.x, event.clientY - pressOrigin.y) > 8
    ) {
        cancelPress();
    }
}

function onContextMenu(event: MouseEvent, message: ChatMessage) {
    cancelPress();
    longPressed = false;
    openMenu(message, event.currentTarget as HTMLElement);
}

// Clicking / tapping a bubble expands its date & time on the right
// (click it again to hide). Nothing shows just from hovering.
const expandedId = ref<number | null>(null);

function onBubbleClick(event: MouseEvent, message: ChatMessage) {
    // The click that follows a long-press must not open the image / play audio.
    if (longPressed) {
        longPressed = false;
        event.stopPropagation();
        event.preventDefault();
        return;
    }

    if (
        (event.target as HTMLElement).closest(
            "button, a, [role='slider'], [data-no-press]",
        )
    ) {
        return;
    }

    expandedId.value =
        expandedId.value === message.message_id ? null : message.message_id;
}

function formatShortDate(value: string | null) {
    if (!value) return "";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "";

    return parsed.toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year:
            parsed.getFullYear() === new Date().getFullYear()
                ? undefined
                : "numeric",
    });
}

// ---- Search inside the conversation (header magnifier) ----
const searchOpen = ref(false);
const searchTerm = ref("");
const searchIndex = ref(0);
const searchInput = ref<HTMLInputElement | null>(null);

const searchMatches = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();

    if (!term) return [] as number[];

    return props.messages
        .filter((message) => message.body?.toLowerCase().includes(term))
        .map((message) => message.message_id);
});

function openSearch() {
    searchOpen.value = true;
    nextTick(() => searchInput.value?.focus());
}

function closeSearch() {
    searchOpen.value = false;
    searchTerm.value = "";
    searchIndex.value = 0;
}

function stepSearch(direction: number) {
    const total = searchMatches.value.length;

    if (!total) return;

    searchIndex.value = (searchIndex.value + direction + total) % total;
    scrollToMessage(searchMatches.value[searchIndex.value]!);
}

watch(searchTerm, () => {
    const total = searchMatches.value.length;

    // Start at the newest match, like Telegram.
    searchIndex.value = Math.max(0, total - 1);

    if (total) scrollToMessage(searchMatches.value[searchIndex.value]!);
});

async function copyMessage(message: ChatMessage) {
    const text = message.body ?? "";

    if (!text) return;

    try {
        await navigator.clipboard.writeText(text);
    } catch {
        // Fallback for insecure origins / older browsers.
        const area = document.createElement("textarea");

        area.value = text;
        area.style.position = "fixed";
        area.style.opacity = "0";
        document.body.appendChild(area);
        area.select();

        try {
            document.execCommand("copy");
        } catch {
            flashError("Unable to copy this message.");
            area.remove();
            return;
        }

        area.remove();
    }

    success("Copied to clipboard");
}

// ---- Forward ----
const forwardTarget = ref<ChatMessage | null>(null);

// ---- Voice messages ----
const recording = ref(false);

const showMic = computed(
    () => !draft.value.trim() && !attachment.value && !props.sending,
);

// ---- Swipe left = reveal times, swipe right on a message = reply ----
const REVEAL_MAX = 72;
const REPLY_TRIGGER = 56;

const revealPx = ref(0);
const dragging = ref(false);
const swipe = ref<{ id: number; dx: number } | null>(null);

let gesture = { x: 0, y: 0, mode: "" as "" | "time" | "reply" | "scroll", id: 0 };

function onTouchStart(event: TouchEvent) {
    if (event.touches.length !== 1) return;

    const touch = event.touches[0]!;
    const row = (event.target as HTMLElement).closest("[data-message-id]");

    gesture = {
        x: touch.clientX,
        y: touch.clientY,
        mode: "",
        id: Number(row?.getAttribute("data-message-id")) || 0,
    };
}

function onTouchMove(event: TouchEvent) {
    const touch = event.touches[0];

    if (!touch || gesture.mode === "scroll") return;

    const dx = touch.clientX - gesture.x;
    const dy = touch.clientY - gesture.y;

    if (!gesture.mode) {
        if (Math.abs(dx) > 10 && Math.abs(dx) > Math.abs(dy) * 1.3) {
            gesture.mode = dx < 0 ? "time" : gesture.id ? "reply" : "scroll";
            dragging.value = true;
            cancelPress();
        } else if (Math.abs(dy) > 10) {
            gesture.mode = "scroll";
            return;
        }
    }

    if (gesture.mode === "time") {
        revealPx.value = Math.min(REVEAL_MAX, Math.max(0, -dx));
    } else if (gesture.mode === "reply") {
        swipe.value = { id: gesture.id, dx: Math.min(72, Math.max(0, dx)) };
    }
}

function onTouchEnd() {
    if (gesture.mode === "reply" && swipe.value?.dx! >= REPLY_TRIGGER) {
        const target = props.messages.find(
            (message) => message.message_id === swipe.value!.id,
        );

        if (target) startReply(target);
    }

    gesture.mode = "";
    dragging.value = false;
    revealPx.value = 0;
    swipe.value = null;
}

const LISTENERS: Record<string, (payload: any) => void> = {
    ".MessageSent": onBroadcast,
    ".MessageStatus": onStatus,
    ".MessageReaction": onReaction,
};

let joined: string[] = [];

const channels = computed(() => {
    const value = props.channel;

    if (!value) return [];

    return (Array.isArray(value) ? value : [value]).filter(Boolean);
});

function onBroadcast(payload: any) {
    if (payload.conversation_id !== props.conversationId) return;

    const currentUserId = Number((user.value as any)?.user_id);
    if (currentUserId && Number(payload.sender_user_id) === currentUserId) {
        return;
    }

    emit("incoming", {
        message_id: payload.message_id,
        sender_type: payload.sender_type,
        sender_user_id: payload.sender_user_id,
        is_mine: false,
        body: payload.body,
        attachment: payload.attachment ?? null,
        preview: payload.preview ?? null,
        created_at: payload.created_at,
        read_at: null,
        delivered_at: payload.delivered_at ?? null,
        is_forwarded: !!payload.is_forwarded,
        reply_to: payload.reply_to ?? null,
        reactions: payload.reactions ?? [],
    });

    markReadSoon();
}

function unsubscribe() {
    if ($echo) {
        for (const channel of joined) {
            for (const [event, listener] of Object.entries(LISTENERS)) {
                ($echo as any).private(channel).stopListening(event, listener);
            }
        }
    }

    joined = [];
}

onMounted(() => {
    document.addEventListener("visibilitychange", onVisibilityChange);
});

onBeforeUnmount(() => {
    setAttachment(null);
    cancelPress();

    if (readTimer) clearTimeout(readTimer);

    document.removeEventListener("visibilitychange", onVisibilityChange);
});

watch(
    channels,
    (list) => {
        if (joined.join() === list.join()) return;

        unsubscribe();

        if (!$echo || !list.length) return;

        joined = [...list];

        for (const channel of joined) {
            for (const [event, listener] of Object.entries(LISTENERS)) {
                ($echo as any).private(channel).listen(event, listener);
            }
        }
    },
    { immediate: true },
);

onBeforeUnmount(unsubscribe);
</script>

<template>
    <div
        class="relative flex h-full min-h-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm dark:border-white/10 dark:bg-[#0f1a2b]"
    >
        <div class="relative flex h-full min-h-0 min-w-0 flex-1 flex-col">
        <div
            v-if="loading && !conversationId"
            class="shrink-0 border-b border-slate-200 px-4 py-3.5 sm:px-5 sm:py-4 dark:border-white/10"
        >
            <div class="flex animate-pulse items-center gap-3">
                <div
                    class="h-10 w-10 shrink-0 rounded-full bg-slate-100 dark:bg-white/10"
                />

                <div class="min-w-0 flex-1 space-y-2">
                    <div
                        class="h-3.5 w-36 max-w-full rounded bg-slate-200 dark:bg-white/15"
                    />
                    <div
                        class="h-3 w-56 max-w-full rounded bg-slate-100 dark:bg-white/10"
                    />
                </div>
            </div>
        </div>

        <div v-else-if="title" class="shrink-0 px-3 pt-3 sm:px-4">
            <div
                class="flex items-center gap-3 rounded-2xl border border-slate-200/70 bg-white px-3 py-2.5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <template v-if="!searchOpen">
                    <button
                        v-if="showClose"
                        type="button"
                        aria-label="Back"
                        class="-mr-1 shrink-0 rounded-full p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 lg:hidden dark:text-gray-500 dark:hover:bg-white/10"
                        @click="$emit('close')"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <MessageAvatar :src="avatar" :name="title" size="md" />

                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                        >
                            {{ title }}
                        </p>

                        <p
                            v-if="patients.length"
                            class="mt-0.5 text-xs text-primary"
                        >
                            {{ patientsLabel }} {{ visiblePatients.join(", ") }}

                            <button
                                v-if="hiddenPatientCount && !showAllPatients"
                                type="button"
                                class="font-semibold hover:underline"
                                @click="showAllPatients = true"
                            >
                                +{{ hiddenPatientCount }} more
                            </button>

                            <button
                                v-else-if="showAllPatients && hiddenPatientCount"
                                type="button"
                                class="font-semibold hover:underline"
                                @click="showAllPatients = false"
                            >
                                Show less
                            </button>
                        </p>

                        <p
                            v-else-if="subtitle"
                            class="mt-0.5 truncate text-xs text-primary"
                        >
                            {{ subtitle }}
                        </p>
                    </div>

                    <button
                        v-if="conversationId"
                        type="button"
                        aria-label="Search in conversation"
                        title="Search"
                        class="shrink-0 rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-primary dark:text-gray-400 dark:hover:bg-white/10"
                        @click="openSearch"
                    >
                        <Search class="h-5 w-5" />
                    </button>

                    <button
                        v-if="conversationId"
                        type="button"
                        aria-label="Conversation details"
                        title="Details"
                        class="shrink-0 rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-primary dark:text-gray-400 dark:hover:bg-white/10"
                        :class="detailsOpen ? 'bg-slate-100 text-primary dark:bg-white/10' : ''"
                        @click="detailsOpen ? closeDetails() : openDetails()"
                    >
                        <Info class="h-5 w-5" />
                    </button>
                </template>

                <template v-else>
                    <Search class="h-5 w-5 shrink-0 text-slate-400" />

                    <input
                        ref="searchInput"
                        v-model="searchTerm"
                        type="text"
                        placeholder="Search in this conversation..."
                        class="h-9 min-w-0 flex-1 bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400 dark:text-gray-100"
                        @keydown.enter.exact.prevent="stepSearch(-1)"
                        @keydown.enter.shift.prevent="stepSearch(1)"
                        @keydown.esc="closeSearch"
                    />

                    <span
                        v-if="searchTerm.trim()"
                        class="shrink-0 text-xs tabular-nums text-slate-400 dark:text-gray-500"
                    >
                        {{
                            searchMatches.length
                                ? `${searchIndex + 1} of ${searchMatches.length}`
                                : "No results"
                        }}
                    </span>

                    <button
                        type="button"
                        aria-label="Older match"
                        :disabled="!searchMatches.length"
                        class="shrink-0 rounded-full p-1.5 text-slate-500 transition hover:bg-slate-100 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-white/10"
                        @click="stepSearch(-1)"
                    >
                        <ChevronUp class="h-5 w-5" />
                    </button>

                    <button
                        type="button"
                        aria-label="Newer match"
                        :disabled="!searchMatches.length"
                        class="shrink-0 rounded-full p-1.5 text-slate-500 transition hover:bg-slate-100 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-white/10"
                        @click="stepSearch(1)"
                    >
                        <ChevronDown class="h-5 w-5" />
                    </button>

                    <button
                        type="button"
                        aria-label="Close search"
                        class="shrink-0 rounded-full p-1.5 text-slate-500 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10"
                        @click="closeSearch"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </template>
            </div>
        </div>

        <div v-if="pinnedShown" class="shrink-0 px-3 pt-2 sm:px-4">
            <div
                class="flex items-center gap-1 rounded-2xl border border-slate-200/70 bg-white px-2 py-1.5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <button
                    type="button"
                    class="flex min-w-0 flex-1 items-center gap-3 rounded-xl px-2 py-1 text-left transition hover:bg-slate-50 dark:hover:bg-white/5"
                    @click="jumpToPinned"
                >
                    <Pin class="h-5 w-5 shrink-0 text-slate-500 dark:text-gray-400" />

                    <span class="h-8 w-[3px] shrink-0 rounded-full bg-primary" />

                    <span class="min-w-0">
                        <span class="block text-xs font-semibold text-primary">
                            Pinned Message
                            <template v-if="pinnedMessages.length > 1">
                                #{{ pinnedIndex + 1 }}
                            </template>
                        </span>

                        <span
                            class="block truncate text-sm text-slate-700 dark:text-gray-200"
                        >
                            {{
                                pinnedShown.body ??
                                pinnedShown.preview ??
                                "Attachment"
                            }}
                        </span>
                    </span>
                </button>

                <button
                    type="button"
                    aria-label="Unpin message"
                    title="Unpin"
                    class="shrink-0 rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-gray-400 dark:hover:bg-white/10"
                    @click="togglePin(pinnedShown)"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>
        </div>

        <p
            v-if="pinError"
            class="shrink-0 bg-red-50 px-4 py-1.5 text-xs font-medium text-red-600 dark:bg-red-500/10 dark:text-red-300"
        >
            {{ pinError }}
        </p>

        <div
            ref="scroller"
            class="flex-1 min-h-0 touch-pan-y space-y-3 overflow-y-auto p-3 sm:p-5"
            :class="dragging ? 'is-dragging' : ''"
            :style="{ '--reveal': `${revealPx}px` }"
            @scroll.passive="onScroll"
            @touchstart.passive="onTouchStart"
            @touchmove.passive="onTouchMove"
            @touchend="onTouchEnd"
            @touchcancel="onTouchEnd"
        >
            <div v-if="loading" class="animate-pulse space-y-4">
                <div
                    v-for="(width, index) in SKELETON_BUBBLES"
                    :key="index"
                    class="flex flex-col gap-1.5"
                    :class="index % 2 ? 'items-end' : 'items-start'"
                >
                    <div
                        class="h-10 max-w-[75%] rounded-3xl"
                        :class="[
                            width,
                            index % 2
                                ? 'bg-primary-100 dark:bg-primary-500/20'
                                : 'bg-slate-100 dark:bg-white/10',
                        ]"
                    />
                    <div
                        class="h-2.5 w-20 rounded bg-slate-100 dark:bg-white/5"
                    />
                </div>
            </div>

            <p
                v-else-if="!messages.length"
                class="py-12 text-center text-sm text-slate-400 dark:text-gray-500"
            >
                {{ emptyText ?? "No messages yet. Say hello." }}
            </p>

            <template v-if="!loading">
                <template v-for="message in messages" :key="message.message_id">
                    <div
                        v-if="dayBreaks[message.message_id]"
                        class="flex justify-center py-1"
                    >
                        <span
                            class="rounded-full bg-slate-200/70 px-3 py-1 text-[11px] font-semibold text-slate-500 dark:bg-white/10 dark:text-gray-400"
                        >
                            {{ dayBreaks[message.message_id] }}
                        </span>
                    </div>

                    <div
                        :data-message-id="message.message_id"
                        class="msg-row group/msg relative flex items-start rounded-xl py-0.5 transition-colors duration-500"
                        :class="[
                            highlightId === message.message_id
                                ? 'bg-primary/10'
                                : '',
                            expandedId === message.message_id ? 'is-expanded' : '',
                        ]"
                    >
                        <div
                            class="msg-shift relative flex min-w-0 flex-1 items-center gap-1.5"
                            :class="isMine(message) ? 'justify-end' : 'justify-start'"
                            :style="
                                swipe?.id === message.message_id
                                    ? { transform: `translateX(${swipe.dx}px)` }
                                    : undefined
                            "
                        >
                            <span
                                v-if="swipe?.id === message.message_id"
                                class="absolute -left-9 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-slate-200 text-slate-500 dark:bg-white/10 dark:text-gray-300"
                                :class="swipe.dx >= REPLY_TRIGGER ? 'bg-primary text-white dark:bg-primary' : ''"
                            >
                                <Reply class="h-4 w-4" />
                            </span>

                            <div
                                class="flex min-w-0 max-w-[85%] flex-col sm:max-w-[75%]"
                                :class="isMine(message) ? 'items-end' : 'items-start'"
                            >
                                <p
                                    v-if="message.is_forwarded"
                                    class="mb-1 flex items-center gap-1 text-[11px] italic text-slate-400 dark:text-gray-500"
                                >
                                    <ForwardIcon class="h-3 w-3" />
                                    Forwarded
                                </p>

                                <div
                                    class="bubble-wrap group/bubble relative flex min-w-0 max-w-full flex-col gap-1.5 [-webkit-touch-callout:none] [@media(hover:none)]:select-none"
                                    :class="[
                                        isMine(message) ? 'items-end' : 'items-start',
                                        reactionsOf(message).length ? 'mb-3' : '',
                                    ]"
                                    @pointerdown="pressStart($event, message)"
                                    @pointermove="pressMove"
                                    @pointerup="cancelPress"
                                    @pointercancel="cancelPress"
                                    @pointerleave="cancelPress"
                                    @contextmenu.prevent="onContextMenu($event, message)"
                                    @click.capture="onBubbleClick($event, message)"
                                    @dblclick.prevent="react(message, HEART)"
                                >
                                    <div
                                        data-no-press
                                        class="pointer-events-none absolute top-1/2 z-10 flex -translate-y-1/2 items-center gap-1 opacity-0 transition focus-within:pointer-events-auto focus-within:opacity-100 group-hover/bubble:pointer-events-auto group-hover/bubble:opacity-100 [@media(hover:none)]:hidden"
                                        :class="
                                            isMine(message)
                                                ? 'right-full flex-row-reverse pr-2'
                                                : 'left-full pl-2'
                                        "
                                    >
                                        <button
                                            type="button"
                                            aria-label="React"
                                            title="React"
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200/80 text-slate-600 transition hover:bg-slate-300 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20"
                                            @click="openMenuFromButton($event, message, true)"
                                        >
                                            <SmilePlus class="h-[18px] w-[18px]" />
                                        </button>

                                        <button
                                            type="button"
                                            aria-label="Reply"
                                            title="Reply"
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200/80 text-slate-600 transition hover:bg-slate-300 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20"
                                            @click="startReply(message)"
                                        >
                                            <Reply class="h-[18px] w-[18px]" />
                                        </button>

                                        <button
                                            type="button"
                                            aria-label="More"
                                            title="More"
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200/80 text-slate-600 transition hover:bg-slate-300 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20"
                                            @click="openMenuFromButton($event, message)"
                                        >
                                            <MoreHorizontal class="h-[18px] w-[18px]" />
                                        </button>
                                    </div>

                                    <button
                                        v-if="message.reply_to"
                                        type="button"
                                        class="max-w-full rounded-2xl border-l-4 px-3.5 py-2 text-left text-xs transition hover:opacity-80"
                                        :class="
                                            isMine(message)
                                                ? 'border-primary bg-primary/10 text-slate-600 dark:text-gray-300'
                                                : 'border-slate-400 bg-slate-200/70 text-slate-600 dark:border-gray-500 dark:bg-white/10 dark:text-gray-300'
                                        "
                                        @click="scrollToMessage(message.reply_to.message_id)"
                                    >
                                        <span
                                            class="block text-[11px] font-bold text-primary"
                                        >
                                            {{ replyAuthor(message.reply_to) }}
                                        </span>

                                        <span class="line-clamp-2 [overflow-wrap:anywhere]">
                                            {{ quoteText(message.reply_to) }}
                                        </span>
                                    </button>

                                    <button
                                        v-if="message.attachment?.type === 'image'"
                                        type="button"
                                        class="block max-w-full overflow-hidden rounded-3xl border border-slate-200 transition hover:opacity-90 dark:border-white/10"
                                        @click="openImage(message.attachment.url)"
                                    >
                                        <img
                                            :src="message.attachment.url"
                                            :alt="message.attachment.name ?? 'Attachment'"
                                            loading="lazy"
                                            draggable="false"
                                            class="max-h-48 w-auto max-w-full object-cover sm:max-h-64"
                                        />
                                    </button>

                                    <VoiceMessagePlayer
                                        v-else-if="message.attachment?.type === 'audio'"
                                        :src="message.attachment.url"
                                        :duration="message.attachment.duration"
                                        :mine="isMine(message)"
                                    />

                                    <a
                                        v-else-if="message.attachment"
                                        :href="message.attachment.url"
                                        :download="message.attachment.name ?? 'attachment'"
                                        class="flex max-w-full items-center gap-2.5 rounded-3xl px-4 py-2.5 text-sm transition"
                                        :class="
                                            isMine(message)
                                                ? 'bg-primary text-white hover:bg-primary-600'
                                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-white/10 dark:text-gray-100 dark:hover:bg-white/15'
                                        "
                                        @click.prevent="downloadAttachment(message)"
                                    >
                                        <FileText class="h-5 w-5 shrink-0" />

                                        <span class="min-w-0 truncate font-medium">
                                            {{ message.attachment.name ?? "Document.pdf" }}
                                        </span>

                                        <LoaderCircle
                                            v-if="downloadingId === message.message_id"
                                            class="h-4 w-4 shrink-0 animate-spin opacity-70"
                                        />

                                        <Download v-else class="h-4 w-4 shrink-0 opacity-70" />
                                    </a>

                                    <div
                                        v-if="message.body"
                                        class="min-w-0 max-w-full whitespace-pre-wrap [overflow-wrap:anywhere] rounded-[22px] px-4 py-2.5 text-sm"
                                        :class="
                                            isMine(message)
                                                ? 'bg-primary text-white'
                                                : 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-gray-100'
                                        "
                                    >
                                        {{ message.body }}
                                    </div>

                                    <div
                                        v-if="reactionGroups(message).length"
                                        class="absolute -bottom-3.5 flex gap-1"
                                        :class="isMine(message) ? 'right-2' : 'left-2'"
                                    >
                                        <button
                                            v-for="group in reactionGroups(message)"
                                            :key="group.emoji"
                                            type="button"
                                            class="flex items-center gap-0.5 rounded-full border bg-white px-1.5 py-0.5 text-xs leading-none shadow-sm transition hover:scale-110 dark:bg-secondary"
                                            :class="
                                                group.mine
                                                    ? 'border-primary/50'
                                                    : 'border-slate-200 dark:border-white/10'
                                            "
                                            @click.stop="react(message, group.emoji)"
                                        >
                                            {{ group.emoji }}

                                            <span
                                                v-if="group.count > 1"
                                                class="text-[10px] font-semibold text-slate-500 dark:text-gray-300"
                                            >
                                                {{ group.count }}
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <p
                                    v-if="isPinned(message) || message.message_id === lastMineId"
                                    class="flex items-center gap-1.5 text-[10px] text-slate-400 dark:text-gray-500"
                                    :class="[
                                        isMine(message) ? 'justify-end' : 'justify-start',
                                        reactionsOf(message).length ? 'mt-4' : 'mt-1',
                                    ]"
                                >
                                    <template v-if="isPinned(message)">
                                        <Pin class="h-3 w-3 text-primary" />
                                        Pinned
                                    </template>

                                    <span
                                        v-if="message.message_id === lastMineId"
                                        class="flex items-center gap-1"
                                        :class="
                                            statusOf(message) === 'seen'
                                                ? 'font-semibold text-primary'
                                                : ''
                                        "
                                    >
                                        <CheckCheck
                                            v-if="statusOf(message) !== 'sent'"
                                            class="h-3 w-3"
                                        />
                                        <Check v-else class="h-3 w-3" />

                                        {{ STATUS_LABEL[statusOf(message) ?? "sent"] }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="time-gutter shrink-0 self-center overflow-hidden">
                            <div
                                class="flex w-[72px] flex-col items-start gap-0.5 whitespace-nowrap pl-2 text-[10px] leading-tight text-slate-400 dark:text-gray-500"
                                :title="formatTime(message.created_at)"
                            >
                                <span>{{ formatShortDate(message.created_at) }}</span>

                                <span class="font-semibold text-slate-500 dark:text-gray-400">
                                    {{ formatClock(message.created_at) }}
                                </span>

                                <component
                                    :is="statusOf(message) === 'sent' ? Check : CheckCheck"
                                    v-if="statusOf(message)"
                                    class="h-3 w-3"
                                    :class="statusOf(message) === 'seen' ? 'text-primary' : ''"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </div>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="translate-y-2 opacity-0"
        >
            <button
                v-if="!atBottom && messages.length"
                type="button"
                class="absolute bottom-[96px] left-1/2 z-10 flex -translate-x-1/2 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-600 shadow-lg transition hover:bg-slate-50 dark:border-white/10 dark:bg-secondary dark:text-gray-200 dark:hover:bg-white/10"
                :class="
                    missedCount
                        ? 'border-primary-200 text-primary-600 dark:border-primary-500/20 dark:text-primary-300'
                        : ''
                "
                @click="scrollToBottom(true)"
            >
                <ArrowDown class="h-3.5 w-3.5" />

                {{
                    missedCount
                        ? `${missedCount} new message${missedCount === 1 ? "" : "s"}`
                        : "Latest"
                }}
            </button>
        </Transition>

        <div
            class="shrink-0 bg-slate-50/70 px-3 pb-3 pt-1 sm:px-4 sm:pb-4 dark:bg-white/[0.02]"
        >
            <div
                class="rounded-2xl border border-slate-200 bg-white p-2 shadow-lg shadow-slate-900/5 transition focus-within:border-primary/40 focus-within:ring-2 focus-within:ring-primary/15 dark:border-white/10 dark:bg-secondary"
            >
            <div
                v-if="replyingTo"
                class="mb-2 flex items-center gap-3 rounded-xl border-l-4 border-primary bg-primary/5 px-3 py-2 dark:bg-primary/10"
            >
                <Reply class="h-4 w-4 shrink-0 text-primary" />

                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-primary">
                        Replying to
                        {{ authorName(isMine(replyingTo), replyingTo.sender_type) }}
                    </p>

                    <p class="truncate text-xs text-slate-500 dark:text-gray-400">
                        {{ summaryOf(replyingTo) }}
                    </p>
                </div>

                <button
                    type="button"
                    aria-label="Cancel reply"
                    class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                    @click="replyingTo = null"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div
                v-if="attachment"
                class="mb-2 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2 dark:border-white/10 dark:bg-white/5"
            >
                <img
                    v-if="attachmentPreview"
                    :src="attachmentPreview"
                    :alt="attachment.name"
                    class="h-12 w-12 shrink-0 rounded-lg object-cover"
                />

                <div
                    v-else
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <FileText class="h-5 w-5" />
                </div>

                <p
                    class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700 dark:text-gray-200"
                >
                    {{ attachment.name }}
                </p>

                <button
                    type="button"
                    aria-label="Remove attachment"
                    :disabled="sending"
                    class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                    @click="setAttachment(null)"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <p
                v-if="attachmentError"
                class="mb-2 text-xs text-red-500 dark:text-red-400"
            >
                {{ attachmentError }}
            </p>

            <VoiceRecorder
                v-if="recording"
                @send="submitVoice"
                @cancel="recording = false"
                @error="flashError"
            />

            <div v-else class="flex items-end gap-2">
                <input
                    ref="fileInput"
                    type="file"
                    :accept="ATTACHMENT_TYPES.join(',')"
                    class="hidden"
                    @change="pickAttachment"
                />

                <button
                    type="button"
                    aria-label="Attach an image or PDF"
                    :disabled="disabled || sending"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:bg-white/10"
                    @click="fileInput?.click()"
                >
                    <Paperclip class="h-[18px] w-[18px]" />
                </button>

                <textarea
                    ref="textarea"
                    v-model="draft"
                    rows="1"
                    :disabled="disabled"
                    placeholder="Write a message..."
                    class="max-h-32 min-h-[40px] flex-1 resize-none bg-transparent px-1.5 py-2.5 text-sm text-slate-700 outline-none placeholder:text-slate-400 disabled:cursor-not-allowed dark:text-gray-100 dark:placeholder:text-gray-500"
                    @input="autosize"
                    @keydown.enter.exact.prevent="submit"
                />

                <button
                    v-if="showMic"
                    type="button"
                    aria-label="Record a voice message"
                    title="Record a voice message"
                    :disabled="disabled"
                    class="flex h-10 shrink-0 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:bg-primary-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                    @click="recording = true"
                >
                    <Mic class="h-4 w-4" />

                    <span class="hidden sm:inline">Voice</span>
                </button>

                <button
                    v-else
                    type="button"
                    :disabled="!canSend"
                    class="flex h-10 shrink-0 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:bg-primary-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                    @click="submit"
                >
                    <LoaderCircle v-if="sending" class="h-4 w-4 animate-spin" />
                    <SendHorizontal v-else class="h-4 w-4" />

                    <span class="hidden sm:inline">
                        {{ sending ? "Sending..." : "Send" }}
                    </span>
                </button>
            </div>
            </div>
        </div>
        </div>

        <MessageActionMenu
            :open="!!menu"
            :reactions-only="menu?.reactionsOnly ?? false"
            :anchor="menu?.anchor ?? null"
            :mine="menu?.mine ?? false"
            :pinned="menu ? isPinned(menu.message) : false"
            :can-copy="!!menu?.message.body"
            :my-reaction="menu ? myReaction(menu.message) : null"
            @close="closeMenu"
            @react="(emoji) => menu && react(menu.message, emoji)"
            @reply="menu && startReply(menu.message)"
            @copy="menu && copyMessage(menu.message)"
            @forward="forwardTarget = menu?.message ?? null"
            @pin="menu && togglePin(menu.message)"
        />

        <ForwardModal
            :open="!!forwardTarget"
            :message="forwardTarget"
            :viewer="viewer"
            :branch-uuid="branchUuid"
            :current-id="conversationId"
            :source-type="viewer === 'client' ? 'family' : conversationType"
            :source-client-uuid="conversationClientUuid"
            @close="forwardTarget = null"
        />

        <ConversationDetailsPanel
            :open="detailsOpen"
            :loading="detailsLoading"
            :error="detailsError"
            :details="details"
            :viewer="viewer"
            @close="closeDetails"
            @retry="openDetails"
            @jump="scrollToMessage"
            @image="openImage"
            @download="onPanelDownload"
        />

        <Teleport to="body">
            <ImagePopup
                v-model="openImageIndex"
                :images="imageAttachments"
                @close="openImageIndex = null"
            />
        </Teleport>
    </div>
</template>

<style scoped>
/* Instagram-style time reveal: swipe the thread left, or click / tap a
   message, and its date & time slides out on the right. Hovering does nothing. */
.time-gutter {
    width: var(--reveal, 0px);
    transition: width 0.18s ease;
}

.msg-shift {
    transition: transform 0.18s ease;
}

.is-dragging .time-gutter,
.is-dragging .msg-shift {
    transition: none;
}

.msg-row.is-expanded .time-gutter {
    width: max(var(--reveal, 0px), 72px);
}
</style>