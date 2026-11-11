<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Move, X, ZoomIn, ZoomOut } from "lucide-vue-next";
import ActionButton from "./ActionButton.vue";

const VIEWPORT = 256;
const WIDE_VIEWPORT = 400;
const OUTPUT = 512;
const WIDE_OUTPUT = 1280;
const MAX_ZOOM = 3;

const props = withDefaults(
    defineProps<{
        show: boolean;
        file: File | null;
        shape?: "circle" | "square";
        aspect?: number;
        title?: string;
        description?: string;
    }>(),
    {
        shape: "circle",
        aspect: 1,
        title: "Adjust photo",
        description: "Drag to reposition. Zoom to fit your face in the circle.",
    },
);

const emit = defineEmits<{
    close: [];
    apply: [file: File];
}>();

const src = ref<string | null>(null);
const natural = ref({ width: 0, height: 0 });
const zoom = ref(1);
const offset = ref({ x: 0, y: 0 });
const dragging = ref(false);
const saving = ref(false);

let dragStart = { x: 0, y: 0, ox: 0, oy: 0 };

function revoke() {
    if (src.value) URL.revokeObjectURL(src.value);
    src.value = null;
}

watch(
    () => [props.show, props.file] as const,
    ([show, file]) => {
        revoke();
        natural.value = { width: 0, height: 0 };
        zoom.value = 1;
        offset.value = { x: 0, y: 0 };

        if (show && file) src.value = URL.createObjectURL(file);
    },
    { immediate: true },
);

onBeforeUnmount(revoke);

const viewW = computed(() => (props.aspect > 1 ? WIDE_VIEWPORT : VIEWPORT));
const viewH = computed(() => Math.round(viewW.value / props.aspect));
const outW = computed(() => (props.aspect > 1 ? WIDE_OUTPUT : OUTPUT));
const outH = computed(() => Math.round(outW.value / props.aspect));

const baseScale = computed(() => {
    const { width, height } = natural.value;
    if (!width || !height) return 1;
    return Math.max(viewW.value / width, viewH.value / height);
});

const displayed = computed(() => ({
    width: natural.value.width * baseScale.value * zoom.value,
    height: natural.value.height * baseScale.value * zoom.value,
}));

function clamp(x: number, y: number) {
    const maxX = Math.max(0, (displayed.value.width - viewW.value) / 2);
    const maxY = Math.max(0, (displayed.value.height - viewH.value) / 2);

    return {
        x: Math.min(maxX, Math.max(-maxX, x)),
        y: Math.min(maxY, Math.max(-maxY, y)),
    };
}

watch(zoom, () => {
    offset.value = clamp(offset.value.x, offset.value.y);
});

const imageStyle = computed(() => ({
    width: `${displayed.value.width}px`,
    height: `${displayed.value.height}px`,
    transform: `translate(calc(-50% + ${offset.value.x}px), calc(-50% + ${offset.value.y}px))`,
}));

function onLoad(event: Event) {
    const img = event.target as HTMLImageElement;
    natural.value = { width: img.naturalWidth, height: img.naturalHeight };
}

function onPointerDown(event: PointerEvent) {
    dragging.value = true;
    dragStart = {
        x: event.clientX,
        y: event.clientY,
        ox: offset.value.x,
        oy: offset.value.y,
    };
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function onPointerMove(event: PointerEvent) {
    if (!dragging.value) return;

    offset.value = clamp(
        dragStart.ox + event.clientX - dragStart.x,
        dragStart.oy + event.clientY - dragStart.y,
    );
}

function onPointerUp() {
    dragging.value = false;
}

function onWheel(event: WheelEvent) {
    zoom.value = Math.min(
        MAX_ZOOM,
        Math.max(1, zoom.value - event.deltaY * 0.0015),
    );
}

function nudgeZoom(step: number) {
    zoom.value = Math.min(MAX_ZOOM, Math.max(1, zoom.value + step));
}

function reset() {
    zoom.value = 1;
    offset.value = { x: 0, y: 0 };
}

async function apply() {
    if (!props.file || !src.value || !natural.value.width) return;

    saving.value = true;

    try {
        const img = new Image();
        img.src = src.value;
        await img.decode();

        const canvas = document.createElement("canvas");
        canvas.width = outW.value;
        canvas.height = outH.value;

        const context = canvas.getContext("2d");
        if (!context) return;

        const ratio = outW.value / viewW.value;
        const width = displayed.value.width * ratio;
        const height = displayed.value.height * ratio;
        const x = outW.value / 2 - width / 2 + offset.value.x * ratio;
        const y = outH.value / 2 - height / 2 + offset.value.y * ratio;

        const type = props.file.type === "image/png" ? "image/png" : "image/jpeg";

        if (type === "image/jpeg") {
            context.fillStyle = "#ffffff";
            context.fillRect(0, 0, outW.value, outH.value);
        }

        context.drawImage(img, x, y, width, height);

        const blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, type, 0.9),
        );
        if (!blob) return;

        const extension = type === "image/png" ? "png" : "jpg";
        const name = props.file.name.replace(/\.[^.]+$/, "") || "avatar";

        emit("apply", new File([blob], `${name}.${extension}`, { type }));
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    class="w-full rounded-2xl border border-slate-100 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-secondary"
                    :class="aspect > 1 ? 'max-w-lg' : 'max-w-sm'"
                >
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div>
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                {{ title }}
                            </h2>
                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ description }}
                            </p>
                        </div>

                        <button
                            type="button"
                            aria-label="Close"
                            class="shrink-0 rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200"
                            @click="emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        class="relative mx-auto touch-none select-none overflow-hidden bg-slate-100 ring-4 ring-primary/20 dark:bg-white/5"
                        :class="[
                            shape === 'square' ? 'rounded-2xl' : 'rounded-full',
                            dragging ? 'cursor-grabbing' : 'cursor-grab',
                        ]"
                        :style="{ width: `${viewW}px`, height: `${viewH}px` }"
                        @pointerdown="onPointerDown"
                        @pointermove="onPointerMove"
                        @pointerup="onPointerUp"
                        @pointercancel="onPointerUp"
                        @wheel.prevent="onWheel"
                    >
                        <img
                            v-if="src"
                            :src="src"
                            alt="Photo to adjust"
                            draggable="false"
                            class="pointer-events-none absolute left-1/2 top-1/2 max-w-none"
                            :style="imageStyle"
                            @load="onLoad"
                        />

                        <div
                            v-if="!dragging"
                            class="pointer-events-none absolute bottom-3 left-1/2 flex -translate-x-1/2 items-center gap-1 rounded-full bg-black/45 px-2.5 py-1 text-[11px] font-medium text-white"
                        >
                            <Move class="h-3 w-3" />
                            Drag
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-3">
                        <button
                            type="button"
                            aria-label="Zoom out"
                            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-primary dark:text-gray-400 dark:hover:bg-white/10"
                            @click="nudgeZoom(-0.2)"
                        >
                            <ZoomOut class="h-4 w-4" />
                        </button>

                        <input
                            id="avatar-crop-zoom"
                            v-model.number="zoom"
                            type="range"
                            min="1"
                            :max="MAX_ZOOM"
                            step="0.01"
                            aria-label="Zoom"
                            class="h-1.5 flex-1 cursor-pointer accent-primary"
                        />

                        <button
                            type="button"
                            aria-label="Zoom in"
                            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-primary dark:text-gray-400 dark:hover:bg-white/10"
                            @click="nudgeZoom(0.2)"
                        >
                            <ZoomIn class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-2">
                        <button
                            type="button"
                            class="text-sm font-medium text-slate-500 transition hover:text-primary dark:text-gray-400"
                            @click="reset"
                        >
                            Reset
                        </button>

                        <div class="flex gap-2">
                            <ActionButton
                                variant="outline"
                                @click="emit('close')"
                            >
                                Cancel
                            </ActionButton>

                            <ActionButton
                                variant="primary"
                                :loading="saving"
                                @click="apply"
                            >
                                Apply
                            </ActionButton>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
