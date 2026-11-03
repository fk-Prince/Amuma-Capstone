<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { SendHorizontal, Trash2 } from "lucide-vue-next";

const MAX_SECONDS = 180;

const emit = defineEmits<{
    send: [payload: { file: File; duration: number }];
    cancel: [];
    error: [message: string];
}>();

const seconds = ref(0);
const ready = ref(false);

let stream: MediaStream | null = null;
let recorder: MediaRecorder | null = null;
let chunks: Blob[] = [];
let timer: ReturnType<typeof setInterval> | null = null;
let startedAt = 0;
let discard = false;

// First format this browser can record: Chrome/Firefox/Edge -> webm/ogg, Safari -> mp4.
const FORMATS: { mime: string; ext: string }[] = [
    { mime: "audio/webm;codecs=opus", ext: "webm" },
    { mime: "audio/webm", ext: "webm" },
    { mime: "audio/ogg;codecs=opus", ext: "ogg" },
    { mime: "audio/mp4", ext: "m4a" },
];

function pickFormat() {
    return FORMATS.find(
        (format) =>
            typeof MediaRecorder !== "undefined" &&
            MediaRecorder.isTypeSupported(format.mime),
    );
}

function clock(value: number) {
    return `${Math.floor(value / 60)}:${String(value % 60).padStart(2, "0")}`;
}

function release() {
    if (timer) clearInterval(timer);

    timer = null;
    stream?.getTracks().forEach((track) => track.stop());
    stream = null;
}

async function start() {
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
        emit(
            "error",
            "Voice messages need a secure (https) connection and a supported browser.",
        );
        emit("cancel");
        return;
    }

    const format = pickFormat();

    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch {
        emit("error", "Allow microphone access to record a voice message.");
        emit("cancel");
        return;
    }

    // Unmounted while the permission prompt was open.
    if (discard) {
        release();
        return;
    }

    recorder = format
        ? new MediaRecorder(stream, { mimeType: format.mime })
        : new MediaRecorder(stream);

    recorder.ondataavailable = (event) => {
        if (event.data.size) chunks.push(event.data);
    };

    recorder.onstop = () => {
        const elapsed = Math.max(1, Math.round((Date.now() - startedAt) / 1000));

        release();

        if (discard || !chunks.length) return;

        const type = recorder?.mimeType || format?.mime || "audio/webm";
        const ext = type.includes("mp4")
            ? "m4a"
            : type.includes("ogg")
              ? "ogg"
              : "webm";

        emit("send", {
            file: new File([new Blob(chunks, { type })], `voice-message.${ext}`, {
                type,
            }),
            duration: Math.min(elapsed, MAX_SECONDS),
        });
    };

    startedAt = Date.now();
    recorder.start();
    ready.value = true;

    timer = setInterval(() => {
        seconds.value = Math.round((Date.now() - startedAt) / 1000);

        if (seconds.value >= MAX_SECONDS) finish();
    }, 250);
}

function finish() {
    if (recorder && recorder.state !== "inactive") recorder.stop();
}

function cancel() {
    discard = true;

    if (recorder && recorder.state !== "inactive") recorder.stop();

    release();
    emit("cancel");
}

onMounted(start);

onBeforeUnmount(() => {
    discard = true;

    if (recorder && recorder.state !== "inactive") recorder.stop();

    release();
});
</script>

<template>
    <div class="flex items-center gap-2">
        <button
            type="button"
            aria-label="Cancel recording"
            title="Discard"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-red-50 hover:text-red-500 dark:text-gray-400 dark:hover:bg-red-500/10"
            @click="cancel"
        >
            <Trash2 class="h-[18px] w-[18px]" />
        </button>

        <div
            class="flex min-h-[40px] flex-1 items-center gap-3 rounded-xl bg-red-50 px-3 dark:bg-red-500/10"
        >
            <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"
                />
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500" />
            </span>

            <span
                class="text-sm font-semibold tabular-nums text-red-600 dark:text-red-300"
            >
                {{ clock(seconds) }}
            </span>

            <span class="truncate text-xs text-red-500/80 dark:text-red-300/70">
                {{ ready ? "Recording…" : "Waiting for microphone…" }}
            </span>
        </div>

        <button
            type="button"
            :disabled="!ready"
            class="flex h-10 shrink-0 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:bg-primary-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
            @click="finish"
        >
            <SendHorizontal class="h-4 w-4" />

            <span class="hidden sm:inline">Send</span>
        </button>
    </div>
</template>