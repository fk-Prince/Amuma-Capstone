<script lang="ts">
let activeAudio: HTMLAudioElement | null = null;
</script>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from "vue";
import { Pause, Play } from "lucide-vue-next";

const props = defineProps<{
    src: string;
    duration?: number | null;
    mine?: boolean;
}>();

const SPEEDS = [1, 1.5, 2];

const audio = ref<HTMLAudioElement | null>(null);
const playing = ref(false);
const current = ref(0);
const speedIndex = ref(0);
const loadedDuration = ref(0);

const total = computed(() => {
    if (props.duration && props.duration > 0) return props.duration;

    return Number.isFinite(loadedDuration.value) ? loadedDuration.value : 0;
});

const progress = computed(() =>
    total.value ? Math.min(100, (current.value / total.value) * 100) : 0,
);

function clock(seconds: number) {
    const safe = Math.max(0, Math.floor(seconds || 0));

    return `${Math.floor(safe / 60)}:${String(safe % 60).padStart(2, "0")}`;
}

function ensureAudio() {
    if (audio.value) return audio.value;

    const element = new Audio(props.src);

    element.preload = "metadata";
    element.addEventListener("timeupdate", () => {
        current.value = element.currentTime;
    });
    element.addEventListener("loadedmetadata", () => {
        loadedDuration.value = element.duration;
    });
    element.addEventListener("ended", () => {
        playing.value = false;
        current.value = 0;
        element.currentTime = 0;
    });
    element.addEventListener("pause", () => {
        playing.value = false;
    });
    element.addEventListener("play", () => {
        playing.value = true;
    });

    audio.value = element;

    return element;
}

async function toggle() {
    const element = ensureAudio();

    if (playing.value) {
        element.pause();
        return;
    }

    if (activeAudio && activeAudio !== element) activeAudio.pause();

    activeAudio = element;
    element.playbackRate = SPEEDS[speedIndex.value]!;

    try {
        await element.play();
    } catch {
        playing.value = false;
    }
}

function seek(event: MouseEvent) {
    const track = event.currentTarget as HTMLElement;
    const rect = track.getBoundingClientRect();
    const ratio = Math.min(
        1,
        Math.max(0, (event.clientX - rect.left) / rect.width),
    );

    const element = ensureAudio();

    if (!total.value) return;

    element.currentTime = ratio * total.value;
    current.value = element.currentTime;
}

function cycleSpeed() {
    speedIndex.value = (speedIndex.value + 1) % SPEEDS.length;

    if (audio.value) audio.value.playbackRate = SPEEDS[speedIndex.value]!;
}

onBeforeUnmount(() => {
    audio.value?.pause();

    if (activeAudio === audio.value) activeAudio = null;

    audio.value = null;
});
</script>

<template>
    <div
        class="flex w-56 max-w-full items-center gap-2.5 rounded-lg px-3 py-2.5 sm:w-64"
        :class="
            mine
                ? 'bg-primary text-white'
                : 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-gray-100'
        "
    >
        <button
            type="button"
            :aria-label="playing ? 'Pause voice message' : 'Play voice message'"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition active:scale-95"
            :class="
                mine
                    ? 'bg-white/20 hover:bg-white/30'
                    : 'bg-primary/10 text-primary hover:bg-primary/20'
            "
            @click.stop="toggle"
        >
            <Pause v-if="playing" class="h-4 w-4" />
            <Play v-else class="h-4 w-4 translate-x-px" />
        </button>

        <div class="min-w-0 flex-1">
            <div
                class="group/track relative h-5 cursor-pointer"
                role="slider"
                aria-label="Seek voice message"
                :aria-valuenow="Math.round(progress)"
                aria-valuemin="0"
                aria-valuemax="100"
                @click.stop="seek"
            >
                <div
                    class="absolute left-0 right-0 top-1/2 h-1 -translate-y-1/2 rounded-full"
                    :class="mine ? 'bg-white/30' : 'bg-slate-300 dark:bg-white/20'"
                />

                <div
                    class="absolute left-0 top-1/2 h-1 -translate-y-1/2 rounded-full"
                    :class="mine ? 'bg-white' : 'bg-primary'"
                    :style="{ width: `${progress}%` }"
                />

                <div
                    class="absolute top-1/2 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full shadow"
                    :class="mine ? 'bg-white' : 'bg-primary'"
                    :style="{ left: `${progress}%` }"
                />
            </div>

            <div
                class="mt-0.5 flex items-center justify-between text-[11px] tabular-nums"
                :class="mine ? 'text-white/80' : 'text-slate-400 dark:text-gray-400'"
            >
                <span>{{ playing || current ? clock(current) : clock(total) }}</span>

                <button
                    type="button"
                    class="rounded-full px-1.5 font-semibold transition"
                    :class="
                        mine
                            ? 'bg-white/20 hover:bg-white/30'
                            : 'bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/20'
                    "
                    title="Playback speed"
                    @click.stop="cycleSpeed"
                >
                    {{ SPEEDS[speedIndex] }}x
                </button>
            </div>
        </div>
    </div>
</template>