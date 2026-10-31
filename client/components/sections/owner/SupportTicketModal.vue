<script setup lang="ts">
import { ref } from "vue";
import {
    Building2,
    CheckCircle2,
    Globe,
    LoaderCircle,
    Mail,
    RotateCcw,
    X,
} from "lucide-vue-next";
import SupportThread from "~/components/sections/support/SupportThread.vue";
import {
    SUPPORT_CATEGORY_LABELS,
    SUPPORT_STATUS_CLASSES,
    SUPPORT_STATUS_LABELS,
    type SupportTicket,
} from "~/types/support";

defineProps<{
    ticket: SupportTicket | null;
    loading: boolean;
    sending: boolean;
    saving: boolean;
}>();

const emit = defineEmits<{
    close: [];
    reply: [payload: { message: string; attachment: File | null }];
    status: [status: "open" | "resolved"];
}>();

const thread = ref<InstanceType<typeof SupportThread> | null>(null);

defineExpose({ reset: () => thread.value?.reset() });
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
                v-if="ticket"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Support ticket"
                    class="flex h-[85vh] max-h-[760px] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="shrink-0 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h2
                                    class="truncate text-base font-semibold text-slate-900 dark:text-white"
                                >
                                    {{ ticket.subject }}
                                </h2>
                                <p
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    {{ ticket.ticket_code }} ·
                                    {{ SUPPORT_CATEGORY_LABELS[ticket.category] }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-medium"
                                    :class="SUPPORT_STATUS_CLASSES[ticket.status]"
                                >
                                    {{ SUPPORT_STATUS_LABELS[ticket.status] }}
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

                        <div
                            class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            <span
                                v-if="ticket.source === 'public'"
                                class="flex items-center gap-1.5"
                            >
                                <Globe class="h-3.5 w-3.5 text-primary" />
                                Public website
                            </span>
                            <span v-else class="flex items-center gap-1.5">
                                <Building2 class="h-3.5 w-3.5 text-primary" />
                                {{ ticket.agency.name }} · {{ ticket.branch.name }}
                            </span>
                            <a
                                v-if="ticket.submitted_by.email"
                                :href="`mailto:${ticket.submitted_by.email}`"
                                class="flex items-center gap-1.5 hover:text-primary"
                            >
                                <Mail class="h-3.5 w-3.5 text-primary" />
                                {{ ticket.submitted_by.name }}
                                &lt;{{ ticket.submitted_by.email }}&gt;
                            </a>
                        </div>
                    </div>

                    <SupportThread
                        ref="thread"
                        :ticket="ticket"
                        :loading="loading"
                        :sending="sending"
                        viewer="support"
                        @reply="emit('reply', $event)"
                    />

                    <div
                        v-if="!loading"
                        class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-3 dark:border-white/10 dark:bg-white/5"
                    >
                        <p class="text-xs text-slate-400 dark:text-gray-500">
                            Replies are also emailed to the person who sent the
                            ticket.
                        </p>

                        <button
                            v-if="ticket.status !== 'resolved'"
                            type="button"
                            :disabled="saving"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600 disabled:opacity-60"
                            @click="emit('status', 'resolved')"
                        >
                            <LoaderCircle
                                v-if="saving"
                                class="h-4 w-4 animate-spin"
                            />
                            <CheckCircle2 v-else class="h-4 w-4" />
                            Mark as resolved
                        </button>
                        <button
                            v-else
                            type="button"
                            :disabled="saving"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 disabled:opacity-60 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                            @click="emit('status', 'open')"
                        >
                            <LoaderCircle
                                v-if="saving"
                                class="h-4 w-4 animate-spin"
                            />
                            <RotateCcw v-else class="h-4 w-4" />
                            Reopen
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
