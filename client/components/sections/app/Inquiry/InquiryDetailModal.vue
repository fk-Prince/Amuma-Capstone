<script setup lang="ts">
import { ref, watch } from "vue";
import {
    CheckCircle2,
    LoaderCircle,
    Mail,
    MapPin,
    Phone,
    Reply,
    Send,
    X,
} from "lucide-vue-next";
import {
    INQUIRY_STATUS_CLASSES,
    INQUIRY_STATUS_LABELS,
    type Inquiry,
    type InquiryStatus,
} from "~/types/inquiry";
import { formatPhone } from "~/utils/phone";
import { stringToDateTime } from "~/utils/time";

const props = defineProps<{
    inquiry: Inquiry | null;
    canUpdate: boolean;
    saving: boolean;
    sending: boolean;
}>();

const emit = defineEmits<{
    close: [];
    status: [status: InquiryStatus];
    reply: [reply: string];
}>();

const replyText = ref("");
const replyError = ref("");

watch(
    () => props.inquiry?.uuid,
    () => {
        replyText.value = "";
        replyError.value = "";
    },
);

const sendReply = () => {
    const reply = replyText.value.trim();

    if (!reply) {
        replyError.value = "Write a reply before sending.";
        return;
    }

    replyError.value = "";
    emit("reply", reply);
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="inquiry"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Inquiry details"
                    class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                            >
                                {{
                                    `${inquiry.first_name?.[0] ?? ""}${inquiry.last_name?.[0] ?? ""}`.toUpperCase()
                                }}
                            </span>
                            <div class="min-w-0">
                                <h2
                                    class="truncate text-base font-semibold text-slate-900 dark:text-white"
                                >
                                    {{ inquiry.full_name }}
                                </h2>
                                <p
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    Received
                                    {{ stringToDateTime(inquiry.created_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="rounded-full px-3 py-1 text-xs font-medium"
                                :class="INQUIRY_STATUS_CLASSES[inquiry.status]"
                            >
                                {{ INQUIRY_STATUS_LABELS[inquiry.status] }}
                            </span>
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                aria-label="Close"
                                @click="emit('close')"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <a
                                :href="`mailto:${inquiry.email}`"
                                class="flex min-w-0 items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-200"
                            >
                                <Mail class="h-4 w-4 shrink-0 text-primary" />
                                <span class="truncate">{{ inquiry.email }}</span>
                            </a>
                            <a
                                :href="`tel:${inquiry.phone_number}`"
                                class="flex min-w-0 items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-200"
                            >
                                <Phone class="h-4 w-4 shrink-0 text-primary" />
                                <span class="truncate">{{
                                    formatPhone(inquiry.phone_number)
                                }}</span>
                            </a>
                            <div
                                v-if="inquiry.city"
                                class="flex min-w-0 items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 sm:col-span-2 dark:border-white/10 dark:text-gray-200"
                            >
                                <MapPin
                                    class="h-4 w-4 shrink-0 text-primary"
                                />
                                <span class="truncate">{{ inquiry.city }}</span>
                            </div>
                        </div>

                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Message
                            </p>
                            <p
                                class="mt-2 whitespace-pre-line rounded-xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700 dark:bg-white/5 dark:text-gray-200"
                            >
                                {{ inquiry.message }}
                            </p>
                        </div>

                        <div v-if="inquiry.reply">
                            <p
                                class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400"
                            >
                                <Reply class="h-3.5 w-3.5" />
                                Reply sent
                                <span
                                    v-if="inquiry.replied_at"
                                    class="font-normal normal-case tracking-normal text-slate-400 dark:text-gray-500"
                                >
                                    · {{ stringToDateTime(inquiry.replied_at) }}
                                </span>
                            </p>
                            <p
                                class="mt-2 whitespace-pre-line rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 text-sm leading-6 text-slate-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-gray-200"
                            >
                                {{ inquiry.reply }}
                            </p>
                        </div>

                        <div v-else-if="canUpdate">
                            <label
                                for="inquiry-reply"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Reply by email
                            </label>
                            <textarea
                                id="inquiry-reply"
                                v-model="replyText"
                                rows="5"
                                maxlength="5000"
                                :disabled="sending"
                                :placeholder="`Write your reply to ${inquiry.first_name}. It will be emailed to ${inquiry.email}.`"
                                class="mt-2 w-full resize-none rounded-xl border bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-slate-50 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500"
                                :class="
                                    replyError
                                        ? 'border-red-400'
                                        : 'border-slate-200 dark:border-white/10'
                                "
                                @input="replyError = ''"
                            />
                            <div
                                class="mt-2 flex items-center justify-between gap-3"
                            >
                                <p
                                    v-if="replyError"
                                    class="text-xs text-red-500"
                                >
                                    {{ replyError }}
                                </p>
                                <p
                                    v-else
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    Sending marks this inquiry as resolved.
                                </p>
                                <button
                                    type="button"
                                    :disabled="sending"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="sendReply"
                                >
                                    <LoaderCircle
                                        v-if="sending"
                                        class="h-4 w-4 animate-spin"
                                    />
                                    <Send v-else class="h-4 w-4" />
                                    {{ sending ? "Sending..." : "Send reply" }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-6 py-4 dark:border-white/10 dark:bg-white/5"
                    >
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                            @click="emit('close')"
                        >
                            Close
                        </button>

                        <button
                            v-if="canUpdate && inquiry.status !== 'resolved'"
                            type="button"
                            :disabled="saving"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600 disabled:opacity-60"
                            @click="emit('status', 'resolved')"
                        >
                            <LoaderCircle
                                v-if="saving"
                                class="h-4 w-4 animate-spin"
                            />
                            <CheckCircle2 v-else class="h-4 w-4" />
                            Mark as resolved
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
