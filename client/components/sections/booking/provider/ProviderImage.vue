<template>
    <div class="relative py-4">
        <!-- Mirrors the real gallery: one large panel with a stacked column of
             thumbnails, so the layout doesn't jump when the images arrive. -->
        <div v-if="loading" class="animate-pulse">
            <div
                class="hidden sm:flex gap-[3px] h-[420px] overflow-hidden rounded-2xl"
            >
                <div class="flex-1 bg-gray-200" />

                <div class="flex w-40 flex-col gap-[3px]">
                    <div class="flex-1 bg-gray-200 rounded-tr-2xl" />
                    <div class="flex-1 bg-gray-200" />
                    <div class="flex-1 bg-gray-200 rounded-br-2xl" />
                </div>
            </div>

            <div class="sm:hidden h-[240px] w-full rounded-2xl bg-gray-200" />
        </div>

        <div
            v-else-if="!hasImages"
            class="flex h-[240px] sm:h-[420px] w-full flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-gray-200 bg-gray-50 text-gray-400"
        >
            <ImageOff class="h-8 w-8" />
            <p class="text-sm font-medium">No photos available</p>
        </div>

        <template v-else>
            <div
                class="hidden sm:flex gap-[3px] h-[420px] overflow-hidden rounded-2xl"
            >
                <button
                    type="button"
                    class="group relative flex-1 overflow-hidden"
                    @click="openImage(0)"
                >
                    <img
                        :src="images[0]?.image_url"
                        :alt="images[0]?.description ?? ''"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <div
                        class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/10"
                    ></div>
                </button>

                <div class="flex w-40 flex-col gap-[3px]">
                    <button
                        v-for="(img, i) in sideImages"
                        :key="img.branch_image_id"
                        type="button"
                        class="group relative flex-1 overflow-hidden"
                        :class="[
                            i === 0 ? 'rounded-tr-2xl' : '',
                            i === sideImages.length - 1 ? 'rounded-br-2xl' : '',
                        ]"
                        @click="openImage(i + 1)"
                    >
                        <img
                            :src="img.image_url"
                            :alt="img.description ?? ''"
                            class="h-full w-full object-cover transition-transform duration-500"
                            :class="
                                i === sideImages.length - 1 && overflowCount > 0
                                    ? 'blur-sm scale-110'
                                    : 'group-hover:scale-105'
                            "
                        />
                        <div
                            v-if="
                                i === sideImages.length - 1 && overflowCount > 0
                            "
                            class="absolute inset-0 flex items-center justify-center bg-black/40 text-sm font-semibold text-white"
                        >
                            +{{ overflowCount }}
                        </div>
                        <div
                            v-else
                            class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/10"
                        ></div>
                    </button>
                </div>
            </div>

            <button
                type="button"
                @click="openImage(0)"
                class="hidden sm:inline-flex absolute bottom-8 right-3 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50"
            >
                <LayoutGrid class="h-4 w-4" />
                Show all photos
            </button>

            <div class="sm:hidden">
                <button
                    type="button"
                    @click="openImage(selectedIndex)"
                    class="relative block h-[240px] w-full overflow-hidden rounded-2xl bg-gray-100"
                >
                    <img
                        :src="heroImage?.image_url"
                        :alt="heroImage?.description ?? ''"
                        class="h-full w-full object-cover"
                    />
                    <span
                        class="absolute bottom-3 left-3 rounded-full bg-black/50 px-2.5 py-1 text-xs font-medium text-white"
                    >
                        {{ selectedIndex + 1 }} / {{ images.length }}
                    </span>
                </button>
                <div class="mt-2 flex gap-2 overflow-x-auto">
                    <button
                        v-for="(img, i) in images"
                        :key="img.branch_image_id"
                        type="button"
                        @click="selectedIndex = i"
                        class="h-16 w-16 shrink-0 overflow-hidden rounded-lg"
                        :class="
                            i === selectedIndex
                                ? 'ring-2 ring-primary'
                                : 'ring-1 ring-black/5'
                        "
                    >
                        <img
                            :src="img.image_url"
                            :alt="img.description ?? ''"
                            class="h-full w-full object-cover"
                        />
                    </button>
                </div>
            </div>
        </template>

        <!-- Hidden while loading: these were rendering on top of the skeleton,
             offering actions for a provider that isn't there yet. -->
        <div v-if="!loading" class="absolute top-8 left-3 flex gap-2">
            <button
                @click="emit('share')"
                aria-label="Share"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-md hover:bg-white"
            >
                <Share2 class="h-4 w-4" />
            </button>
            <button
                @click="emit('favorite')"
                aria-label="Add to favorites"
                class="group flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-md hover:bg-white"
            >
                <Heart
                    class="h-4 w-4 transition-colors group-hover:fill-red-500 group-hover:text-red-500"
                />
            </button>
        </div>
    </div>

    <ImagePopup
        v-model="lightboxIndex"
        :images="images"
        @close="lightboxIndex = null"
    />
</template>
<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Heart, Share2, LayoutGrid, ImageOff } from "lucide-vue-next";
import ImagePopup from "~/components/ui/ImagePopup.vue";
import type { BranchImage } from "~/types/branch";

const props = defineProps<{
    primaryImage?: string | null;
    secondaryImage?: BranchImage[];
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "favorite"): void;
    (e: "share"): void;
}>();

const images = computed<BranchImage[]>(() => {
    const primary: BranchImage[] = props.primaryImage
        ? [
              {
                  branch_image_id: -1,
                  image_url: props.primaryImage,
                  type: "branch",
                  description: null,
              },
          ]
        : [];

    return [...primary, ...(props.secondaryImage ?? [])];
});

const hasImages = computed(() => images.value.length > 0);

const sideImages = computed(() => images.value.slice(1, 5));
const overflowCount = computed(() => Math.max(images.value.length - 5, 0));

const selectedIndex = ref(0);
watch(images, () => (selectedIndex.value = 0));
const heroImage = computed(() => images.value[selectedIndex.value]);

const lightboxIndex = ref<number | null>(null);
const openImage = (index: number) => {
    if (!images.value[index]) return;
    lightboxIndex.value = index;
};
</script>
