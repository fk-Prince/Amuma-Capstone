<script setup lang="ts">
import { ref, computed } from "vue";
import Icon from "./Icon.vue";
useHead({ title: "Settings" });
definePageMeta({
    layout: "portal",
});
interface Message {
    id: number;
    sender: "you" | "caregiver";
    content: string;
    time: string;
    avatar?: string;
}

interface Conversation {
    id: number;
    name: string;
    avatar: string;
    lastMessage: string;
    time: string;
    unread: boolean;
    messages: Message[];
}

const conversations = ref<Conversation[]>([
    {
        id: 1,
        name: "Caregiver Maritess Uy",
        avatar: "https://i.pravatar.cc/64?img=32",
        lastMessage: "Your mother enjoyed her lunch today!",
        time: "10:30 AM",
        unread: true,
        messages: [
            {
                id: 1,
                sender: "caregiver",
                content: "Good morning! Your mother slept well last night.",
                time: "8:00 AM",
                avatar: "https://i.pravatar.cc/64?img=32",
            },
            {
                id: 2,
                sender: "you",
                content: "That's great to hear! How is she doing today?",
                time: "8:15 AM",
            },
            {
                id: 3,
                sender: "caregiver",
                content:
                    "She's doing well. We just finished breakfast and she's about to have her morning walk.",
                time: "8:45 AM",
                avatar: "https://i.pravatar.cc/64?img=32",
            },
            {
                id: 4,
                sender: "you",
                content: "Wonderful! Please send me a photo if you can.",
                time: "9:00 AM",
            },
            {
                id: 5,
                sender: "caregiver",
                content: "Your mother enjoyed her lunch today!",
                time: "10:30 AM",
                avatar: "https://i.pravatar.cc/64?img=32",
            },
        ],
    },
    {
        id: 2,
        name: "Front Desk - AMUMA",
        avatar: "https://i.pravatar.cc/64?img=12",
        lastMessage: "Your visit request for Sunday has been approved.",
        time: "Yesterday",
        unread: false,
        messages: [
            {
                id: 1,
                sender: "caregiver",
                content: "Your visit request for Sunday has been approved.",
                time: "Yesterday · 2:30 PM",
                avatar: "https://i.pravatar.cc/64?img=12",
            },
            {
                id: 2,
                sender: "caregiver",
                content: "Visiting hours are from 10:00 AM to 6:00 PM.",
                time: "Yesterday · 2:31 PM",
                avatar: "https://i.pravatar.cc/64?img=12",
            },
        ],
    },
]);

const selectedConversationId = ref<number | null>(
    conversations.value[0]?.id ?? null,
);
const selectedConversation = computed(() =>
    conversations.value.find((c) => c.id === selectedConversationId.value),
);

const messageInput = ref("");

// Below lg there isn't room for list + chat side by side, so the two
// panes take turns: picking a conversation swaps to the chat, and the
// chat's back button returns to the list. On lg+ both are always shown
// and this flag is ignored.
const showChatOnMobile = ref(false);

function selectConversation(id: number) {
    selectedConversationId.value = id;
    showChatOnMobile.value = true;
    const conv = conversations.value.find((c) => c.id === id);
    if (conv) {
        conv.unread = false;
    }
}

function sendMessage() {
    if (!messageInput.value.trim() || !selectedConversation.value) return;

    selectedConversation.value.messages.push({
        id: Date.now(),
        sender: "you",
        content: messageInput.value,
        time: new Date().toLocaleTimeString("en-US", {
            hour: "2-digit",
            minute: "2-digit",
        }),
    });

    messageInput.value = "";
}

const messagesContainerRef = ref<HTMLElement | null>(null);

function scrollToBottom() {
    if (messagesContainerRef.value) {
        messagesContainerRef.value.scrollTop =
            messagesContainerRef.value.scrollHeight;
    }
}
</script>

<template>
    <div
        class="grid h-[calc(100vh-10rem)] grid-cols-1 gap-5 p-4 sm:p-6 lg:h-[600px] lg:grid-cols-3 lg:p-8"
    >
        <!-- Conversation list -->
        <div
            class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col"
            :class="showChatOnMobile ? 'hidden lg:flex' : 'flex'"
        >
            <div class="p-4 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-800">Messages</p>
            </div>
            <ul class="flex-1 overflow-y-auto divide-y divide-gray-50">
                <li
                    v-for="conv in conversations"
                    :key="conv.id"
                    @click="selectConversation(conv.id)"
                    class="p-3 hover:bg-gray-50 cursor-pointer transition-colors"
                    :class="
                        selectedConversationId === conv.id
                            ? 'bg-brand-50 border-l-2 border-brand-500'
                            : ''
                    "
                >
                    <div class="flex items-start gap-3">
                        <img
                            :src="conv.avatar"
                            class="w-9 h-9 rounded-full object-cover shrink-0"
                            alt=""
                        />
                        <div class="flex-1 min-w-0">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <p
                                    class="text-sm font-medium text-gray-800 truncate"
                                >
                                    {{ conv.name }}
                                </p>
                                <span
                                    class="text-[11px] text-gray-400 shrink-0"
                                    >{{ conv.time }}</span
                                >
                            </div>
                            <p
                                class="text-xs truncate mt-0.5"
                                :class="
                                    conv.unread
                                        ? 'text-gray-600 font-medium'
                                        : 'text-gray-400'
                                "
                            >
                                {{ conv.lastMessage }}
                            </p>
                        </div>
                        <span
                            v-if="conv.unread"
                            class="w-2 h-2 rounded-full bg-brand-500 shrink-0 mt-1.5"
                        />
                    </div>
                </li>
            </ul>
        </div>

        <!-- Chat view -->
        <div
            v-if="selectedConversation"
            class="lg:col-span-2 min-h-0 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex-col"
            :class="showChatOnMobile ? 'flex' : 'hidden lg:flex'"
        >
            <!-- Header -->
            <div
                class="px-4 sm:px-5 py-4 border-b border-gray-100 flex items-center gap-3"
            >
                <button
                    type="button"
                    class="-ml-1 shrink-0 rounded-full p-1.5 text-gray-400 hover:bg-gray-50 hover:text-gray-600 lg:hidden"
                    aria-label="Back to conversations"
                    @click="showChatOnMobile = false"
                >
                    <Icon name="chevron-left" class="w-4 h-4" />
                </button>

                <img
                    :src="selectedConversation.avatar"
                    class="w-10 h-10 rounded-full object-cover shrink-0"
                    alt=""
                />
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">
                        {{ selectedConversation.name }}
                    </p>
                    <p class="text-xs text-gray-400">Active now</p>
                </div>
                <button
                    class="w-9 h-9 rounded-full hover:bg-gray-50 flex items-center justify-center text-gray-400"
                >
                    <Icon name="phone" class="w-4 h-4" />
                </button>
            </div>

            <!-- Messages -->
            <div
                ref="messagesContainerRef"
                class="flex-1 min-h-0 overflow-y-auto p-4 sm:p-5 space-y-4"
            >
                <div
                    v-for="msg in selectedConversation.messages"
                    :key="msg.id"
                    class="flex gap-3"
                    :class="msg.sender === 'you' ? 'justify-end' : ''"
                >
                    <img
                        v-if="msg.sender === 'caregiver'"
                        :src="msg.avatar"
                        class="w-8 h-8 rounded-full object-cover shrink-0"
                        alt=""
                    />
                    <div :class="msg.sender === 'you' ? 'order-last' : ''">
                        <div
                            class="rounded-2xl px-4 py-2.5 max-w-[75vw] sm:max-w-xs"
                            :class="
                                msg.sender === 'you'
                                    ? 'bg-brand-500 text-white rounded-br-none'
                                    : 'bg-gray-100 text-gray-800 rounded-bl-none'
                            "
                        >
                            <p class="text-sm">{{ msg.content }}</p>
                        </div>
                        <p
                            class="text-xs text-gray-400 mt-1"
                            :class="msg.sender === 'you' ? 'text-right' : ''"
                        >
                            {{ msg.time }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="border-t border-gray-100 p-4">
                <div class="flex items-center gap-2">
                    <input
                        v-model="messageInput"
                        type="text"
                        placeholder="Type your message..."
                        @keyup.enter="sendMessage"
                        class="flex-1 border border-gray-200 rounded-full px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200"
                    />
                    <button
                        @click="sendMessage"
                        class="w-9 h-9 rounded-full bg-brand-500 text-white flex items-center justify-center hover:bg-brand-600 disabled:opacity-50"
                        :disabled="!messageInput.trim()"
                    >
                        <Icon name="send" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else
            class="hidden lg:col-span-2 lg:flex bg-white rounded-2xl border border-gray-100 shadow-sm items-center justify-center"
        >
            <div class="text-center">
                <Icon
                    name="message"
                    class="w-12 h-12 text-gray-300 mx-auto mb-3"
                />
                <p class="text-gray-400">
                    Select a conversation to start messaging
                </p>
            </div>
        </div>
    </div>
</template>
