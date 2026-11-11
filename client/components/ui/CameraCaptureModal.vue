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
                class="fixed inset-0 z-[1000] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4"
                @click.self="close"
            >
                <div class="my-auto w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary">
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                {{ title }}
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                {{ description }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                            aria-label="Close"
                            @click="close"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="space-y-4 p-5">
                        <div
                            class="relative mx-auto max-h-[60vh] w-full overflow-hidden rounded-xl bg-slate-900"
                            :style="{ aspectRatio: ratio, maxWidth: `calc(60vh * ${ratio})` }"
                        >
                            <video
                                v-show="!shot"
                                ref="video"
                                autoplay
                                playsinline
                                muted
                                class="h-full w-full -scale-x-100 object-cover"
                            />

                            <img
                                v-if="shot"
                                :src="shot.url"
                                class="h-full w-full object-cover"
                            />

                            <template v-if="!shot && liveScan">
                                <div
                                    v-for="(box, i) in liveBoxes"
                                    :key="i"
                                    class="pointer-events-none absolute rounded border-2 transition-all duration-200"
                                    :class="liveScan.status === 'face' ? 'border-emerald-400' : 'border-amber-400'"
                                    :style="{
                                        left: `${100 - box.x - box.width}%`,
                                        top: `${box.y}%`,
                                        width: `${box.width}%`,
                                        height: `${box.height}%`,
                                    }"
                                />
                            </template>

                            <span
                                v-if="!shot && streaming"
                                class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-semibold shadow-sm"
                                :class="liveChip.class"
                            >
                                <Check v-if="liveScan?.status === 'face'" class="h-3.5 w-3.5" />
                                {{ liveChip.label }}
                            </span>

                            <div
                                v-if="!streaming && !shot"
                                class="absolute inset-0 flex flex-col items-center justify-center gap-2 px-6 text-center text-sm text-slate-300"
                            >
                                <LoaderCircle v-if="!cameraError" class="h-6 w-6 animate-spin" />
                                <CameraOff v-else class="h-6 w-6" />
                                {{ cameraError || "Starting the camera…" }}
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Hold the ID next to your face with its photo facing the camera.
                        </p>

                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <template v-if="shot">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                                    @click="retake"
                                >
                                    <RotateCcw class="h-4 w-4" />
                                    Retake
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600"
                                    @click="usePhoto"
                                >
                                    Use photo
                                </button>
                            </template>

                            <button
                                v-else
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:opacity-50"
                                :disabled="!streaming"
                                @click="capture"
                            >
                                <Camera class="h-4 w-4" />
                                Capture
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { Camera, CameraOff, Check, LoaderCircle, RotateCcw, X } from "lucide-vue-next";
import { scanFace, type FaceScanResult } from "~/utils/faceMatch";

const props = withDefaults(
    defineProps<{
        open: boolean;
        title?: string;
        description?: string;
    }>(),
    {
        title: "Take a photo",
        description: "Use your camera to take the photo.",
    },
);

const emit = defineEmits<{
    (event: "close"): void;
    (event: "capture", file: File): void;
}>();

const video = ref<HTMLVideoElement | null>(null);
const stream = ref<MediaStream | null>(null);
const streaming = ref(false);
const cameraError = ref("");
const shot = ref<{ url: string; file: File } | null>(null);
const ratio = ref(4 / 3);
const liveScan = ref<FaceScanResult | null>(null);
let scanTimer: ReturnType<typeof setTimeout> | null = null;

const liveBoxes = computed(() => {
    const scan = liveScan.value;
    if (scan?.status === "face") return [scan.box, scan.idBox];
    if (scan?.status === "no_id") return [scan.box];
    return [];
});

const liveChip = computed(() => {
    const status = liveScan.value?.status;
    if (status === "face") return { label: "Face and ID found", class: "bg-emerald-500 text-white" };
    if (status === "no_id") return { label: "Show your ID", class: "bg-amber-500 text-white" };
    if (status === "no_face") return { label: "No face found", class: "bg-rose-500 text-white" };
    return { label: "Looking for your face…", class: "bg-white/90 text-slate-600" };
});

async function start() {
    cameraError.value = "";
    shot.value = null;
    liveScan.value = null;

    if (!navigator.mediaDevices?.getUserMedia) {
        cameraError.value = "This browser can't use the camera. Upload a photo instead.";
        return;
    }

    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: "user", width: { ideal: 1280 }, height: { ideal: 960 } },
            audio: false,
        });
        await nextTick();
        if (!video.value) return;
        video.value.srcObject = stream.value;
        await video.value.play();
        if (video.value.videoWidth && video.value.videoHeight) {
            ratio.value = video.value.videoWidth / video.value.videoHeight;
        }
        streaming.value = true;
        scanLoop();
    } catch {
        cameraError.value = "We couldn't open the camera. Allow camera access or upload a photo instead.";
    }
}

function stop() {
    if (scanTimer) clearTimeout(scanTimer);
    scanTimer = null;
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
    streaming.value = false;
}

async function scanLoop() {
    if (!streaming.value || shot.value || !video.value) return;
    const result = await scanFace(video.value);
    if (!streaming.value || shot.value) return;
    if (result.status !== "error") liveScan.value = result;
    scanTimer = setTimeout(scanLoop, 400);
}

function capture() {
    const el = video.value;
    if (!el || !el.videoWidth) return;

    const canvas = document.createElement("canvas");
    canvas.width = el.videoWidth;
    canvas.height = el.videoHeight;
    canvas.getContext("2d")?.drawImage(el, 0, 0);

    canvas.toBlob(
        (blob) => {
            if (!blob) return;
            const file = new File([blob], `id-selfie-${Date.now()}.jpg`, { type: "image/jpeg" });
            shot.value = { url: URL.createObjectURL(file), file };
        },
        "image/jpeg",
        0.92,
    );
}

function retake() {
    if (shot.value) URL.revokeObjectURL(shot.value.url);
    shot.value = null;
    liveScan.value = null;
    scanLoop();
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
