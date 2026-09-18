<template>
    <Transition name="fade">
        <div
            v-if="images.length && currentIndex !== null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-3 sm:p-6"
            @click="close"
            @keydown.esc="close"
            @keydown.left="prev"
            @keydown.right="next"
            tabindex="0"
            ref="overlayRef"
        >
            <button
                type="button"
                class="absolute top-3 right-3 sm:top-4 sm:right-4 w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors dark:hover:bg-white/10"
                @click.stop="close"
                aria-label="Close"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path
                        d="M15 5L5 15M5 5l10 10"
                        stroke="currentColor"
                        stroke-width="1.5"
                        fill="none"
                        stroke-linecap="round"
                    />
                </svg>
            </button>

            <button
                v-if="images.length > 1"
                type="button"
                class="absolute left-2 sm:left-4 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors dark:hover:bg-white/10"
                @click.stop="prev"
                aria-label="Previous image"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path
                        d="M12 15l-5-5 5-5"
                        stroke="currentColor"
                        stroke-width="1.5"
                        fill="none"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>

            <button
                v-if="images.length > 1"
                type="button"
                class="absolute right-2 sm:right-4 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors dark:hover:bg-white/10"
                @click.stop="next"
                aria-label="Next image"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path
                        d="M8 5l5 5-5 5"
                        stroke="currentColor"
                        stroke-width="1.5"
                        fill="none"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>

            <figure
                class="flex w-full max-w-5xl flex-col items-center gap-2 px-10 sm:gap-3 sm:px-14"
                @click.stop
            >
                <img
                    :src="currentImage?.image_url"
                    :alt="currentImage?.description ?? ''"
                    class="max-h-[70vh] max-w-full rounded-lg shadow-lg object-contain sm:max-h-[80vh]"
                />
                <figcaption
                    v-if="currentImage?.description"
                    class="max-w-full break-words text-center text-xs text-white/80 sm:max-w-2xl sm:text-sm"
                >
                    {{ currentImage.description }}
                </figcaption>
                <span v-if="images.length > 1" class="text-xs text-white/60">
                    {{ currentIndex! + 1 }} / {{ images.length }}
                </span>
            </figure>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from "vue";
import type { BranchImage } from "~/types/branch";

const props = defineProps<{
    images: BranchImage[];
    modelValue: number | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "update:modelValue", value: number | null): void;
}>();

const overlayRef = ref<HTMLElement | null>(null);
const currentIndex = ref<number | null>(props.modelValue);

const currentImage = computed(() =>
    currentIndex.value !== null ? props.images[currentIndex.value] : null,
);

watch(
    () => props.modelValue,
    async (value) => {
        currentIndex.value = value;
        if (value !== null) {
            await nextTick();
            overlayRef.value?.focus();
        }
    },
);

const close = () => {
    currentIndex.value = null;
    emit("update:modelValue", null);
    emit("close");
};

const prev = () => {
    if (currentIndex.value === null || !props.images.length) return;
    currentIndex.value =
        (currentIndex.value - 1 + props.images.length) % props.images.length;
    emit("update:modelValue", currentIndex.value);
};

const next = () => {
    if (currentIndex.value === null || !props.images.length) return;
    currentIndex.value = (currentIndex.value + 1) % props.images.length;
    emit("update:modelValue", currentIndex.value);
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
