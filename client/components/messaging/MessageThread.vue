<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import {
    X,
    SendHorizontal,
    LoaderCircle,
    Paperclip,
    FileText,
    Download,
    ArrowDown,
} from "lucide-vue-next";

import MessageAvatar from "~/components/messaging/MessageAvatar.vue";
import ImagePopup from "~/components/ui/ImagePopup.vue";
import { useAuthUser } from "~/composables/useAuthUser";

import type {
    ChatMessage,
    MessageSender,
    OutgoingMessage,
} from "~/types/message";

const props = withDefaults(
    defineProps<{
        messages: ChatMessage[];
        viewer: MessageSender;
        title?: string | null;
        subtitle?: string | null;
        avatar?: string | null;
        patients?: string[];
        channel?: string | string[] | null;
        conversationId?: number | null;
        loading?: boolean;
        sending?: boolean;
        disabled?: boolean;
        emptyText?: string;
        showClose?: boolean;
    }>(),
    {
        patients: () => [],
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
const scroller = ref<HTMLElement | null>(null);
const showAllPatients = ref(false);

const PATIENT_PREVIEW = 2;

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

function submit() {
    if (!canSend.value) return;

    emit("send", { body: draft.value.trim(), attachment: attachment.value });
    draft.value = "";
    attachmentError.value = "";
    setAttachment(null);
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

let joined: string[] = [];
let handler: ((payload: any) => void) | null = null;

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
    });
}

function unsubscribe() {
    if ($echo && handler) {
        for (const channel of joined) {
            ($echo as any)
                .private(channel)
                .stopListening(".MessageSent", handler);
        }
    }

    joined = [];
    handler = null;
}

onBeforeUnmount(() => setAttachment(null));

watch(
    channels,
    (list) => {
        if (joined.join() === list.join()) return;

        unsubscribe();

        if (!$echo || !list.length) return;

        joined = [...list];
        handler = onBroadcast;

        for (const channel of joined) {
            ($echo as any).private(channel).listen(".MessageSent", handler);
        }
    },
    { immediate: true },
);

onBeforeUnmount(unsubscribe);
</script>

<template>
    <div
        class="relative flex h-full min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-white/10 dark:bg-secondary"
    >
        <div
            v-if="title"
            class="shrink-0 border-b border-slate-200 px-4 py-3.5 sm:px-5 sm:py-4 dark:border-white/10 overflow-hidden"
        >
            <div class="flex items-center gap-3">
                <MessageAvatar :src="avatar" :name="title" size="md" />

                <div class="min-w-0 flex-1">
                    <p
                        class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                    >
                        {{ title }}
                    </p>

                    <p
                        v-if="patients.length"
                        class="mt-0.5 text-xs text-slate-400 dark:text-gray-500"
                    >
                        Caring for {{ visiblePatients.join(", ") }}

                        <button
                            v-if="hiddenPatientCount && !showAllPatients"
                            type="button"
                            class="font-semibold text-primary hover:underline"
                            @click="showAllPatients = true"
                        >
                            +{{ hiddenPatientCount }} more
                        </button>

                        <button
                            v-else-if="showAllPatients && hiddenPatientCount"
                            type="button"
                            class="font-semibold text-primary hover:underline"
                            @click="showAllPatients = false"
                        >
                            Show less
                        </button>
                    </p>

                    <p
                        v-else-if="subtitle"
                        class="mt-0.5 truncate text-xs text-slate-400 dark:text-gray-500"
                    >
                        {{ subtitle }}
                    </p>
                </div>

                <button
                    v-if="showClose"
                    type="button"
                    aria-label="Close conversation"
                    class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 lg:hidden dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                    @click="$emit('close')"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div
            ref="scroller"
            class="flex-1 min-h-0 space-y-3 overflow-y-auto p-3 sm:p-5"
            @scroll.passive="onScroll"
        >
            <div v-if="loading" class="space-y-3">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="h-12 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                />
            </div>

            <p
                v-else-if="!messages.length"
                class="py-12 text-center text-sm text-slate-400 dark:text-gray-500"
            >
                {{ emptyText ?? "No messages yet. Say hello." }}
            </p>

            <div
                v-for="message in messages"
                :key="message.message_id"
                class="flex"
                :class="isMine(message) ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="flex min-w-0 max-w-[85%] flex-col gap-1.5 sm:max-w-[75%]"
                    :class="isMine(message) ? 'items-end' : 'items-start'"
                >
                    <button
                        v-if="message.attachment?.type === 'image'"
                        type="button"
                        class="block max-w-full overflow-hidden rounded-2xl border border-slate-200 transition hover:opacity-90 dark:border-white/10"
                        @click="openImage(message.attachment.url)"
                    >
                        <img
                            :src="message.attachment.url"
                            :alt="message.attachment.name ?? 'Attachment'"
                            loading="lazy"
                            class="max-h-48 w-auto max-w-full object-cover sm:max-h-64"
                        />
                    </button>

                    <a
                        v-else-if="message.attachment"
                        :href="message.attachment.url"
                        :download="message.attachment.name ?? 'attachment'"
                        class="flex max-w-full items-center gap-2.5 rounded-2xl px-3.5 py-2.5 text-sm transition"
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
                        class="min-w-0 max-w-full whitespace-pre-wrap [overflow-wrap:anywhere] rounded-2xl px-4 py-2.5 text-sm"
                        :class="
                            isMine(message)
                                ? 'bg-primary text-white'
                                : 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-gray-100'
                        "
                    >
                        {{ message.body }}
                    </div>

                    <p
                        class="mt-1 text-[10px] text-slate-400 dark:text-gray-500"
                        :class="isMine(message) ? 'text-right' : 'text-left'"
                    >
                        {{ formatTime(message.created_at) }}
                    </p>
                </div>
            </div>
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
                class="absolute bottom-[86px] left-1/2 z-10 flex -translate-x-1/2 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-600 shadow-lg transition hover:bg-slate-50 dark:border-white/10 dark:bg-secondary dark:text-gray-200 dark:hover:bg-white/10"
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
            class="shrink-0 border-t border-slate-200 p-3 dark:border-white/10"
        >
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

            <div class="flex items-end gap-2">
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
                    class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                    @click="fileInput?.click()"
                >
                    <Paperclip class="h-4 w-4" />
                </button>

                <textarea
                    v-model="draft"
                    rows="1"
                    :disabled="disabled"
                    placeholder="Write a message..."
                    class="max-h-32 min-h-[42px] flex-1 resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100 dark:placeholder:text-gray-500 dark:disabled:bg-white/5"
                    @keydown.enter.exact.prevent="submit"
                />

                <button
                    type="button"
                    :disabled="!canSend"
                    class="flex h-[42px] shrink-0 items-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-40"
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

        <Teleport to="body">
            <ImagePopup
                v-model="openImageIndex"
                :images="imageAttachments"
                @close="openImageIndex = null"
            />
        </Teleport>
    </div>
</template>
