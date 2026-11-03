<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import {
    Copy,
    Forward,
    Pin,
    PinOff,
    Plus,
    Reply,
} from "lucide-vue-next";

import { MORE_REACTIONS, QUICK_REACTIONS } from "~/utils/chatEmoji";

// The menu that opens on long-press / right-click: reactions on top,
// actions underneath, both floating next to the message that was pressed.
const props = defineProps<{
    open: boolean;
    /** Bounding box of the pressed message. */
    anchor: { top: number; bottom: number; left: number; right: number } | null;
    mine: boolean;
    pinned: boolean;
    canCopy: boolean;
    /** The emoji this person already reacted with, if any. */
    myReaction?: string | null;
}>();

const emit = defineEmits<{
    close: [];
    react: [emoji: string];
    reply: [];
    copy: [];
    forward: [];
    pin: [];
}>();

const MARGIN = 12;

const panel = ref<HTMLElement | null>(null);
const style = ref<Record<string, string>>({ visibility: "hidden" });
const showMore = ref(false);

const strip = (value: string) => value.replace(/\uFE0F/g, "");

const isMine = (emoji: string) =>
    !!props.myReaction && strip(props.myReaction) === strip(emoji);

const actions = computed(() => [
    { key: "reply", label: "Reply", icon: Reply, show: true },
    { key: "copy", label: "Copy", icon: Copy, show: props.canCopy },
    { key: "forward", label: "Forward", icon: Forward, show: true },
    {
        key: "pin",
        label: props.pinned ? "Unpin" : "Pin",
        icon: props.pinned ? PinOff : Pin,
        show: true,
    },
]);

// The action goes out first: the parent still needs to know which message
// the menu was opened for when it handles it, and "close" clears that.
function run(key: string) {
    if (key === "reply") emit("reply");
    else if (key === "copy") emit("copy");
    else if (key === "forward") emit("forward");
    else if (key === "pin") emit("pin");

    emit("close");
}

function pick(emoji: string) {
    emit("react", emoji);
    emit("close");
}

async function place() {
    await nextTick();

    const element = panel.value;
    const anchor = props.anchor;

    if (!element || !anchor) return;

    const { offsetHeight: height, offsetWidth: width } = element;

    const below = anchor.bottom + 8;
    const above = anchor.top - height - 8;

    let top = below + height <= window.innerHeight - MARGIN ? below : above;

    top = Math.min(Math.max(top, MARGIN), window.innerHeight - height - MARGIN);

    // Line the panel up with the side the bubble sits on.
    let left = props.mine ? anchor.right - width : anchor.left;

    left = Math.min(Math.max(left, MARGIN), window.innerWidth - width - MARGIN);

    style.value = {
        top: `${top}px`,
        left: `${left}px`,
        visibility: "visible",
    };
}

function onKey(event: KeyboardEvent) {
    if (event.key === "Escape") emit("close");
}

watch(
    () => props.open,
    (isOpen) => {
        showMore.value = false;

        if (isOpen) {
            style.value = { visibility: "hidden" };
            place();
            window.addEventListener("keydown", onKey);
            window.addEventListener("resize", place);
        } else {
            window.removeEventListener("keydown", onKey);
            window.removeEventListener("resize", place);
        }
    },
);

watch(showMore, place);

onBeforeUnmount(() => {
    window.removeEventListener("keydown", onKey);
    window.removeEventListener("resize", place);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[70] bg-slate-900/30 backdrop-blur-[2px]"
                @click.self="emit('close')"
                @contextmenu.prevent="emit('close')"
            >
                <div
                    ref="panel"
                    class="fixed flex w-max max-w-[calc(100vw-24px)] flex-col gap-2"
                    :class="mine ? 'items-end' : 'items-start'"
                    :style="style"
                >
                    <div
                        class="rounded-3xl border border-slate-200 bg-white p-1.5 shadow-xl dark:border-white/10 dark:bg-secondary"
                    >
                        <div class="flex items-center gap-0.5">
                            <button
                                v-for="emoji in QUICK_REACTIONS"
                                :key="emoji"
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-full text-[22px] transition hover:scale-125 active:scale-95"
                                :class="
                                    isMine(emoji)
                                        ? 'bg-primary/15 ring-2 ring-primary/40'
                                        : 'hover:bg-slate-100 dark:hover:bg-white/10'
                                "
                                @click="pick(emoji)"
                            >
                                {{ emoji }}
                            </button>

                            <button
                                type="button"
                                aria-label="More reactions"
                                class="flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-white/10"
                                :class="showMore ? 'bg-slate-100 dark:bg-white/10' : ''"
                                @click="showMore = !showMore"
                            >
                                <Plus class="h-5 w-5" />
                            </button>
                        </div>

                        <div
                            v-if="showMore"
                            class="mt-1 grid grid-cols-6 gap-0.5 border-t border-slate-100 pt-1.5 dark:border-white/10"
                        >
                            <button
                                v-for="emoji in MORE_REACTIONS"
                                :key="emoji"
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-full text-[22px] transition hover:scale-125 active:scale-95"
                                :class="
                                    isMine(emoji)
                                        ? 'bg-primary/15 ring-2 ring-primary/40'
                                        : 'hover:bg-slate-100 dark:hover:bg-white/10'
                                "
                                @click="pick(emoji)"
                            >
                                {{ emoji }}
                            </button>
                        </div>
                    </div>

                    <div
                        class="w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-xl dark:border-white/10 dark:bg-secondary"
                    >
                        <template v-for="action in actions" :key="action.key">
                            <button
                                v-if="action.show"
                                type="button"
                                class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:text-gray-100 dark:hover:bg-white/5"
                                @click="run(action.key)"
                            >
                                {{ action.label }}

                                <component
                                    :is="action.icon"
                                    class="h-4 w-4 text-slate-400 dark:text-gray-400"
                                />
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>