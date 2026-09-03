<template>
    <Transition name="fade">
        <div
            v-if="images.length && currentIndex !== null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
            @click="close"
            @keydown.esc="close"
            @keydown.left="prev"
            @keydown.right="next"
            tabindex="0"
            ref="overlayRef"
        >
            <button
                type="button"
                class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors dark:hover:bg-white/10"
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
                class="absolute left-4 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors dark:hover:bg-white/10"
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
                class="absolute right-4 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors dark:hover:bg-white/10"
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

            <figure class="flex flex-col items-center gap-3" @click.stop>
                <img
                    :src="currentImage?.image_url"
                    :alt="currentImage?.description ?? ''"
                    class="max-w-5xl max-h-[80vh] rounded-lg shadow-lg object-contain"
                />
                <figcaption
                    v-if="currentImage?.description"
                    class="text-sm text-white/80 text-center max-w-2xl"
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
