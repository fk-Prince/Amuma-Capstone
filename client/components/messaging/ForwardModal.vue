<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Check, LoaderCircle, Search, X } from "lucide-vue-next";

import MessageAvatar from "~/components/messaging/MessageAvatar.vue";
import { messageService } from "~/api/message/MessageService";
import { useToast } from "~/composables/useToast";

import type {
    ChatMessage,
    ConversationSummary,
    MessageSender,
} from "~/types/message";

const MAX_TARGETS = 5;

const props = defineProps<{
    open: boolean;
    message: ChatMessage | null;
    viewer: MessageSender;
    /** Staff only: the branch whose conversations can be forwarded to. */
    branchUuid?: string | null;
    /** The conversation the message is in (hidden from the list). */
    currentId?: number | null;
    /** Family conversations can't be forwarded into a different family. */
    sourceType?: "family" | "staff" | null;
    sourceClientUuid?: string | null;
}>();

const emit = defineEmits<{
    close: [];
    done: [];
}>();

const { success, error } = useToast();

const loading = ref(false);
const sending = ref(false);
const search = ref("");
const conversations = ref<ConversationSummary[]>([]);
const selected = ref<number[]>([]);

const name = (item: ConversationSummary) =>
    props.viewer === "client"
        ? (item.staff_name ?? "Branch team")
        : (item.client_name ?? item.staff_name ?? "Conversation");

const caption = (item: ConversationSummary) => {
    if (props.viewer === "client") {
        return [item.staff_role, item.branch?.name].filter(Boolean).join(" · ");
    }

    if (item.type === "staff") return "Staff";

    return item.patient_names?.length
        ? `Family of ${item.patient_names.slice(0, 2).join(", ")}`
        : "Family";
};

const avatar = (item: ConversationSummary) =>
    props.viewer === "client"
        ? (item.staff_avatar ?? item.avatar)
        : item.avatar;

const options = computed(() => {
    const term = search.value.trim().toLowerCase();

    return conversations.value.filter((item) => {
        if (item.conversation_id === props.currentId) return false;

        if (
            props.viewer === "staff" &&
            props.sourceType === "family" &&
            item.type !== "staff" &&
            item.client_uuid !== props.sourceClientUuid
        ) {
            return false;
        }

        return !term || name(item).toLowerCase().includes(term);
    });
});

async function load() {
    loading.value = true;

    try {
        if (props.viewer === "client") {
            conversations.value = (await messageService.conversations()) ?? [];
        } else {
            const payload = { branch_uuid: props.branchUuid };

            const [families, staff] = await Promise.all([
                messageService.branchConversations(payload),
                messageService.staffConversations(payload),
            ]);

            conversations.value = [...(families ?? []), ...(staff ?? [])];
        }
    } catch (err: any) {
        error(err?.message ?? "Unable to load your conversations.");
        conversations.value = [];
    } finally {
        loading.value = false;
    }
}

function toggle(id: number) {
    if (selected.value.includes(id)) {
        selected.value = selected.value.filter((value) => value !== id);
    } else if (selected.value.length < MAX_TARGETS) {
        selected.value = [...selected.value, id];
    }
}

async function submit() {
    if (!props.message || !selected.value.length || sending.value) return;

    sending.value = true;

    try {
        await messageService.forward({
            message_id: props.message.message_id,
            conversation_ids: selected.value,
            as_staff: props.viewer === "staff",
        });

        success(
            selected.value.length === 1
                ? "Message forwarded"
                : `Message forwarded to ${selected.value.length} chats`,
        );
        emit("done");
        emit("close");
    } catch (err: any) {
        error(err?.message ?? "Unable to forward this message.");
    } finally {
        sending.value = false;
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        selected.value = [];
        search.value = "";
        load();
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    class="flex max-h-[85dvh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 dark:text-white">
                                Forward message
                            </p>

                            <p
                                v-if="message"
                                class="mt-0.5 truncate text-xs text-slate-400 dark:text-gray-500"
                            >
                                {{
                                    message.body ??
                                    message.preview ??
                                    "Attachment"
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            aria-label="Close"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10"
                            @click="emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="border-b border-slate-100 p-2.5 dark:border-white/10">
                        <div
                            class="flex items-center gap-2 rounded-xl bg-slate-100 px-3 dark:bg-white/10"
                        >
                            <Search class="h-4 w-4 shrink-0 text-slate-400" />

                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search..."
                                class="h-10 w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400 dark:text-gray-100"
                            />
                        </div>
                    </div>

                    <div class="min-h-[8rem] flex-1 overflow-y-auto p-2 overscroll-contain">
                        <p
                            v-if="loading"
                            class="px-3 py-8 text-center text-sm text-slate-400 dark:text-gray-500"
                        >
                            Loading...
                        </p>

                        <p
                            v-else-if="!options.length"
                            class="px-3 py-8 text-center text-sm text-slate-400 dark:text-gray-500"
                        >
                            No conversations to forward to.
                        </p>

                        <template v-else>
                        <button
                            v-for="item in options"
                            :key="item.conversation_id"
                            type="button"
                            class="mb-1 flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-50 dark:hover:bg-white/5"
                            @click="toggle(item.conversation_id)"
                        >
                            <MessageAvatar :src="avatar(item)" :name="name(item)" />

                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                >
                                    {{ name(item) }}
                                </p>

                                <p
                                    class="truncate text-xs text-slate-400 dark:text-gray-500"
                                >
                                    {{ caption(item) }}
                                </p>
                            </div>

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition"
                                :class="
                                    selected.includes(item.conversation_id)
                                        ? 'border-primary bg-primary text-white'
                                        : 'border-slate-300 dark:border-white/20'
                                "
                            >
                                <Check
                                    v-if="selected.includes(item.conversation_id)"
                                    class="h-3 w-3"
                                />
                            </span>
                        </button>
                        </template>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 dark:border-white/10"
                    >
                        <p class="text-xs text-slate-400 dark:text-gray-500">
                            {{ selected.length }} / {{ MAX_TARGETS }} selected
                        </p>

                        <button
                            type="button"
                            :disabled="!selected.length || sending"
                            class="flex h-9 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-white transition hover:bg-primary-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="submit"
                        >
                            <LoaderCircle v-if="sending" class="h-4 w-4 animate-spin" />

                            {{ sending ? "Forwarding..." : "Forward" }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>