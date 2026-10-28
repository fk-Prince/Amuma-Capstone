<script setup lang="ts">
import { nextTick, ref, watch } from "vue";
import { ImagePlus, LifeBuoy, LoaderCircle, Send, X } from "lucide-vue-next";
import type { SupportReply, SupportTicket } from "~/types/support";
import { stringToDateTime } from "~/utils/time";

const props = defineProps<{
    ticket: SupportTicket | null;
    loading: boolean;
    sending: boolean;
    viewer: "branch" | "support";
}>();

const emit = defineEmits<{
    reply: [payload: { message: string; attachment: File | null }];
}>();

const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;

const message = ref("");
const attachment = ref<File | null>(null);
const replyError = ref("");
const fileInput = ref<HTMLInputElement | null>(null);
const scroller = ref<HTMLElement | null>(null);

const isMine = (reply: SupportReply) =>
    props.viewer === "support" ? reply.is_support : !reply.is_support;

const authorLabel = (reply: SupportReply) => {
    if (reply.is_support) {
        return props.viewer === "support"
            ? `${reply.author_name ?? "Support"} · AMUMA Support`
            : "AMUMA Support";
    }

    return reply.author_name ?? "Branch staff";
};

const scrollToBottom = async () => {
    await nextTick();
    scroller.value?.scrollTo({ top: scroller.value.scrollHeight });
};

watch(
    () => props.ticket?.replies?.length,
    () => scrollToBottom(),
    { immediate: true },
);

watch(
    () => props.ticket?.uuid,
    () => {
        message.value = "";
        attachment.value = null;
        replyError.value = "";
    },
);

const pickFile = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    (event.target as HTMLInputElement).value = "";

    if (!file) return;

    if (!file.type.startsWith("image/")) {
        replyError.value = "Only images can be attached.";
        return;
    }

    if (file.size > MAX_ATTACHMENT_BYTES) {
        replyError.value = "The screenshot must be 5 MB or smaller.";
        return;
    }

    replyError.value = "";
    attachment.value = file;
};

const send = () => {
    const text = message.value.trim();

    if (!text) {
        replyError.value = "Write a message before sending.";
        return;
    }

    replyError.value = "";
    emit("reply", { message: text, attachment: attachment.value });
};

const reset = () => {
    message.value = "";
    attachment.value = null;
};

defineExpose({ reset });
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <div
            ref="scroller"
            class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5"
        >
            <div v-if="loading" class="space-y-4">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="h-16 animate-pulse rounded-2xl bg-slate-100 dark:bg-white/5"
                    :class="n % 2 ? 'mr-12' : 'ml-12'"
                />
            </div>

            <template v-else-if="ticket">
                <div
                    v-for="reply in ticket.replies ?? []"
                    :key="reply.id"
                    class="flex flex-col"
                    :class="isMine(reply) ? 'items-end' : 'items-start'"
                >
                    <p
                        class="mb-1 flex items-center gap-1.5 text-xs text-slate-400 dark:text-gray-500"
                    >
                        <LifeBuoy
                            v-if="reply.is_support"
                            class="h-3.5 w-3.5 text-primary"
                        />
                        <span
                            class="font-medium text-slate-600 dark:text-gray-300"
                        >
                            {{ authorLabel(reply) }}
                        </span>
                        · {{ stringToDateTime(reply.created_at) }}
                    </p>

                    <div
                        class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-6"
                        :class="
                            isMine(reply)
                                ? 'rounded-tr-sm bg-primary text-white'
                                : 'rounded-tl-sm bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-gray-200'
                        "
                    >
                        <p class="whitespace-pre-line break-words">
                            {{ reply.message }}
                        </p>

                        <a
                            v-if="reply.attachment"
                            :href="reply.attachment"
                            target="_blank"
                            rel="noopener"
                            class="mt-2 block overflow-hidden rounded-xl border border-black/5"
                        >
                            <img
                                :src="reply.attachment"
                                alt="Attached screenshot"
                                class="max-h-48 w-full object-cover"
                            />
                        </a>
                    </div>
                </div>
            </template>
        </div>

        <div
            v-if="ticket && !loading"
            class="shrink-0 border-t border-slate-100 px-6 py-4 dark:border-white/10"
        >
            <p
                v-if="ticket.status === 'resolved'"
                class="mb-2 text-xs text-slate-400 dark:text-gray-500"
            >
                This ticket is resolved. Sending a message reopens it.
            </p>

            <textarea
                v-model="message"
                rows="3"
                maxlength="5000"
                :disabled="sending"
                :placeholder="
                    viewer === 'support'
                        ? 'Write your reply to the branch...'
                        : 'Write a message to AMUMA Support...'
                "
                class="w-full resize-none rounded-xl border bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-slate-50 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500"
                :class="
                    replyError
                        ? 'border-red-400'
                        : 'border-slate-200 dark:border-white/10'
                "
                @input="replyError = ''"
            />

            <div class="mt-2 flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="hidden"
                        @change="pickFile"
                    />

                    <button
                        v-if="!attachment"
                        type="button"
                        :disabled="sending"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-primary dark:text-gray-400 dark:hover:bg-white/5"
                        @click="fileInput?.click()"
                    >
                        <ImagePlus class="h-4 w-4" />
                        Attach screenshot
                    </button>

                    <span
                        v-else
                        class="inline-flex min-w-0 items-center gap-1.5 rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-600 dark:bg-white/10 dark:text-gray-300"
                    >
                        <span class="truncate">{{ attachment.name }}</span>
                        <button
                            type="button"
                            aria-label="Remove screenshot"
                            class="shrink-0 text-slate-400 hover:text-slate-600"
                            @click="attachment = null"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </span>

                    <p v-if="replyError" class="truncate text-xs text-red-500">
                        {{ replyError }}
                    </p>
                </div>

                <button
                    type="button"
                    :disabled="sending"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="send"
                >
                    <LoaderCircle
                        v-if="sending"
                        class="h-4 w-4 animate-spin"
                    />
                    <Send v-else class="h-4 w-4" />
                    {{ sending ? "Sending..." : "Send" }}
                </button>
            </div>
        </div>
    </div>
</template>
