<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { X } from "lucide-vue-next";

import MessageAvatar from "~/components/messaging/MessageAvatar.vue";
import { useAuthUser } from "~/composables/useAuthUser";

import type { ChatMessage, MessageSender } from "~/types/message";

const props = withDefaults(
    defineProps<{
        messages: ChatMessage[];
        viewer: MessageSender;
        title?: string | null;
        subtitle?: string | null;
        avatar?: string | null;
        patients?: string[];
        channel?: string | null;
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
    send: [body: string];
    incoming: [message: ChatMessage];
    close: [];
}>();

const user = useAuthUser();

const { $echo } = useNuxtApp();

const draft = ref("");
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

const canSend = computed(
    () => draft.value.trim().length > 0 && !props.sending && !props.disabled,
);

async function scrollToBottom() {
    await nextTick();

    if (scroller.value) {
        scroller.value.scrollTop = scroller.value.scrollHeight;
    }
}

watch(() => props.messages.length, scrollToBottom);

watch(
    () => props.conversationId,
    () => {
        showAllPatients.value = false;
    },
);

function submit() {
    if (!canSend.value) return;

    emit("send", draft.value.trim());
    draft.value = "";
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

let joined = "";
let handler: ((payload: any) => void) | null = null;

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
        created_at: payload.created_at,
        read_at: null,
    });
}

function unsubscribe() {
    if ($echo && joined && handler) {
        ($echo as any).private(joined).stopListening(".MessageSent", handler);
    }

    joined = "";
    handler = null;
}

watch(
    () => props.channel,
    (channel) => {
        if (joined === channel) return;

        unsubscribe();

        if (!$echo || !channel) return;

        joined = channel;
        handler = onBroadcast;

        ($echo as any).private(joined).listen(".MessageSent", handler);
    },
    { immediate: true },
);

onBeforeUnmount(unsubscribe);
</script>

<template>
    <div
        class="flex h-full min-h-0 flex-col rounded-2xl border border-slate-200 bg-white"
    >
        <div v-if="title" class="shrink-0 border-b border-slate-200 px-5 py-4">
            <div class="flex items-center gap-3">
                <MessageAvatar :src="avatar" :name="title" size="md" />

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">
                        {{ title }}
                    </p>

                    <p
                        v-if="patients.length"
                        class="mt-0.5 text-xs text-slate-400"
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
                        class="mt-0.5 truncate text-xs text-slate-400"
                    >
                        {{ subtitle }}
                    </p>
                </div>

                <button
                    v-if="showClose"
                    type="button"
                    aria-label="Close conversation"
                    class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 lg:hidden"
                    @click="$emit('close')"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div
            ref="scroller"
            class="flex-1 min-h-0 space-y-3 overflow-y-auto p-5"
        >
            <div v-if="loading" class="space-y-3">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="h-12 animate-pulse rounded-xl bg-slate-100"
                />
            </div>

            <p
                v-else-if="!messages.length"
                class="py-12 text-center text-sm text-slate-400"
            >
                {{ emptyText ?? "No messages yet. Say hello." }}
            </p>

            <div
                v-for="message in messages"
                :key="message.message_id"
                class="flex"
                :class="isMine(message) ? 'justify-end' : 'justify-start'"
            >
                <div class="max-w-[75%]">
                    <div
                        class="whitespace-pre-wrap break-words rounded-2xl px-4 py-2.5 text-sm"
                        :class="
                            isMine(message)
                                ? 'bg-primary text-white'
                                : 'bg-slate-100 text-slate-700'
                        "
                    >
                        {{ message.body }}
                    </div>

                    <p
                        class="mt-1 text-[10px] text-slate-400"
                        :class="isMine(message) ? 'text-right' : 'text-left'"
                    >
                        {{ formatTime(message.created_at) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="shrink-0 border-t border-slate-200 p-3">
            <div class="flex items-end gap-2">
                <textarea
                    v-model="draft"
                    rows="1"
                    :disabled="disabled"
                    placeholder="Write a message..."
                    class="max-h-32 min-h-[42px] flex-1 resize-y rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:bg-slate-50"
                    @keydown.enter.exact.prevent="submit"
                />

                <button
                    type="button"
                    :disabled="!canSend"
                    class="h-[42px] shrink-0 rounded-xl bg-primary px-5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="submit"
                >
                    {{ sending ? "Sending..." : "Send" }}
                </button>
            </div>
        </div>
    </div>
</template>
