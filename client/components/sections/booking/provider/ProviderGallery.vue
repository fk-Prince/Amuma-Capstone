<template>
    <div>
        <section
            class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-secondary dark:text-white">
                    Gallery
                </h2>

                <span
                    v-if="images.length"
                    class="text-sm text-gray-500 dark:text-gray-400"
                >
                    {{ images.length }} photo{{ images.length === 1 ? "" : "s" }}
                </span>
            </div>

            <div
                v-if="loading"
                class="mt-5 grid animate-pulse grid-cols-2 gap-3 sm:grid-cols-3"
            >
                <div
                    v-for="n in 6"
                    :key="n"
                    class="aspect-[4/3] rounded-xl bg-gray-200 dark:bg-white/10"
                />
            </div>

            <div
                v-else-if="!images.length"
                class="mt-5 flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-gray-200 bg-gray-50 py-16 text-gray-400 dark:border-white/10 dark:bg-white/5 dark:text-gray-500"
            >
                <ImageOff class="h-8 w-8" />
                <p class="text-sm font-medium">No photos available</p>
            </div>

            <div v-else class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <button
                    v-for="(image, index) in images"
                    :key="image.branch_image_id"
                    type="button"
                    class="group relative aspect-[4/3] overflow-hidden rounded-xl bg-gray-100 dark:bg-white/10"
                    @click="lightboxIndex = index"
                >
                    <img
                        :src="image.image_url"
                        :alt="image.description ?? ''"
                        loading="lazy"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <span
                        class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/10"
                    />
                </button>
            </div>
        </section>

        <ImagePopup
            v-model="lightboxIndex"
            :images="images"
            @close="lightboxIndex = null"
        />
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { ImageOff } from "lucide-vue-next";
import ImagePopup from "~/components/ui/ImagePopup.vue";
import type { BranchImage } from "~/types/branch";

defineProps<{
    images: BranchImage[];
    loading?: boolean;
}>();

const lightboxIndex = ref<number | null>(null);
</script>
