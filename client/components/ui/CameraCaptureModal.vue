<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-[1000] overflow-hidden bg-black">
                <video
                    v-show="!shot"
                    ref="video"
                    autoplay
                    playsinline
                    muted
                    class="absolute inset-0 h-full w-full object-cover"
                    :class="{ '-scale-x-100': isSelfie }"
                />

                <img
                    v-if="shot"
                    :src="shot.url"
                    class="absolute inset-0 h-full w-full object-contain"
                />

                <div
                    v-if="!isSelfie && !shot"
                    ref="guide"
                    class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-2xl shadow-[0_0_0_9999px_rgba(0,0,0,0.5)]"
                    :style="{ width: `min(88vw, calc(62vh * ${ID_CARD_RATIO}))`, aspectRatio: ID_CARD_RATIO }"
                >
                    <span
                        v-for="corner in corners"
                        :key="corner"
                        class="absolute h-8 w-8 border-white"
                        :class="corner"
                    />
                </div>

                <div
                    v-if="!streaming && !shot"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-2 px-6 text-center text-sm text-slate-300"
                >
                    <LoaderCircle v-if="!cameraError" class="h-7 w-7 animate-spin" />
                    <CameraOff v-else class="h-7 w-7" />
                    {{ cameraError }}
                </div>

                <button
                    type="button"
                    class="absolute right-[max(1rem,env(safe-area-inset-right))] top-[max(1rem,env(safe-area-inset-top))] flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white transition hover:bg-black/70"
                    aria-label="Close"
                    @click="close"
                >
                    <X class="h-5 w-5" />
                </button>

                <div
                    class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-3 px-4 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-6"
                >
                    <template v-if="shot">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/25"
                            @click="retake"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Retake
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-600"
                            @click="usePhoto"
                        >
                            <Check class="h-4 w-4" />
                            Use photo
                        </button>
                    </template>

                    <button
                        v-else
                        type="button"
                        aria-label="Capture"
                        class="flex h-[72px] w-[72px] items-center justify-center rounded-full border-4 border-white/90 transition disabled:opacity-40"
                        :disabled="!streaming"
                        @click="capture"
                    >
                        <span class="h-14 w-14 rounded-full bg-white transition active:scale-90" />
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { CameraOff, Check, LoaderCircle, RotateCcw, X } from "lucide-vue-next";

const ID_CARD_RATIO = 85.6 / 53.98;

const props = withDefaults(
    defineProps<{
        open: boolean;
        mode?: "selfie" | "id";
        fileName?: string;
    }>(),
    {
        mode: "selfie",
        fileName: "photo",
    },
);

const emit = defineEmits<{
    (event: "close"): void;
    (event: "capture", file: File): void;
}>();

const isSelfie = computed(() => props.mode === "selfie");

const video = ref<HTMLVideoElement | null>(null);
const guide = ref<HTMLElement | null>(null);
const stream = ref<MediaStream | null>(null);
const streaming = ref(false);
const cameraError = ref("");
const shot = ref<{ url: string; file: File } | null>(null);

const corners = [
    "left-0 top-0 rounded-tl-2xl border-l-4 border-t-4",
    "right-0 top-0 rounded-tr-2xl border-r-4 border-t-4",
    "bottom-0 left-0 rounded-bl-2xl border-b-4 border-l-4",
    "bottom-0 right-0 rounded-br-2xl border-b-4 border-r-4",
];

async function start() {
    cameraError.value = "";
    shot.value = null;

    if (!navigator.mediaDevices?.getUserMedia) {
        cameraError.value = "This browser can't use the camera. Upload a photo instead.";
        return;
    }

    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: isSelfie.value ? "user" : "environment",
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
            audio: false,
        });
        await nextTick();
        if (!video.value) return;
        video.value.srcObject = stream.value;
        await video.value.play();
        streaming.value = true;
    } catch {
        cameraError.value = "We couldn't open the camera. Allow camera access or upload a photo instead.";
    }
}

function stop() {
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
    streaming.value = false;
}

function cropArea(el: HTMLVideoElement) {
    const vw = el.videoWidth;
    const vh = el.videoHeight;
    const view = el.getBoundingClientRect();
    const scale = Math.max(view.width / vw, view.height / vh);
    const offsetX = (view.width - vw * scale) / 2;
    const offsetY = (view.height - vh * scale) / 2;
    const area = !isSelfie.value && guide.value ? guide.value.getBoundingClientRect() : view;

    const sx = Math.max(0, (area.left - view.left - offsetX) / scale);
    const sy = Math.max(0, (area.top - view.top - offsetY) / scale);

    return {
        sx,
        sy,
        sw: Math.min(vw - sx, area.width / scale),
        sh: Math.min(vh - sy, area.height / scale),
    };
}

function capture() {
    const el = video.value;
    if (!el || !el.videoWidth) return;

    const { sx, sy, sw, sh } = cropArea(el);
    const canvas = document.createElement("canvas");
    canvas.width = Math.round(sw);
    canvas.height = Math.round(sh);
    canvas.getContext("2d")?.drawImage(el, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(
        (blob) => {
            if (!blob) return;
            const file = new File([blob], `${props.fileName}-${Date.now()}.jpg`, { type: "image/jpeg" });
            shot.value = { url: URL.createObjectURL(file), file };
        },
        "image/jpeg",
        0.92,
    );
}

function retake() {
    if (shot.value) URL.revokeObjectURL(shot.value.url);
    shot.value = null;
}

function usePhoto() {
    if (!shot.value) return;
    emit("capture", shot.value.file);
    close();
}

function close() {
    stop();
    emit("close");
}

watch(
    () => props.open,
    (open) => (open ? start() : stop()),
    { immediate: true },
);

onBeforeUnmount(stop);
</script>
