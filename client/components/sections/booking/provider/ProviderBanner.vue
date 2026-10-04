<template>
    <div
        v-if="loading"
        class="h-[300px] w-full animate-pulse rounded-3xl bg-gray-200 sm:h-[340px] dark:bg-white/10"
    />

    <div
        v-else
        class="relative h-[300px] w-full overflow-hidden rounded-3xl bg-secondary shadow-sm sm:h-[340px]"
    >
        <img
            v-if="coverImage"
            :src="coverImage"
            alt=""
            class="absolute inset-0 h-full w-full object-cover"
        />
        <div
            v-else
            class="absolute inset-0 bg-gradient-to-br from-secondary-800 via-primary-900 to-primary-700"
        />
        <div
            class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-black/10"
        />

        <div
            class="absolute inset-x-0 top-0 flex items-start justify-between p-4 sm:p-5"
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-full bg-black/40 px-3.5 py-2 text-xs font-medium text-white ring-1 ring-white/20 backdrop-blur-md transition hover:bg-black/55"
                @click="emit('back')"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to Providers
            </button>

            <div class="flex gap-2">
                <button
                    type="button"
                    aria-label="Share"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-black/40 text-white ring-1 ring-white/20 backdrop-blur-md transition hover:bg-black/55"
                    @click="emit('share')"
                >
                    <Share2 class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    aria-label="Add to favorites"
                    class="group flex h-9 w-9 items-center justify-center rounded-full bg-black/40 text-white ring-1 ring-white/20 backdrop-blur-md transition hover:bg-black/55"
                    @click="emit('favorite')"
                >
                    <Heart
                        class="h-4 w-4 transition-colors group-hover:fill-red-500 group-hover:text-red-500"
                    />
                </button>
            </div>
        </div>

        <div
            class="absolute inset-x-0 bottom-0 flex flex-wrap items-end justify-between gap-4 p-4 sm:p-6"
        >
            <div class="flex min-w-0 items-end gap-4 sm:gap-5">
                <div
                    class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border-2 border-white bg-white shadow-lg sm:h-28 sm:w-28"
                >
                    <img
                        v-if="logoImage"
                        :src="logoImage"
                        :alt="branch?.name ?? 'Provider'"
                        class="h-full w-full object-cover"
                    />
                    <Building2 v-else class="h-10 w-10 text-primary" />
                </div>

                <div class="min-w-0 pb-1 text-white">
                    <h1
                        class="truncate text-2xl font-bold tracking-tight sm:text-4xl"
                    >
                        {{ branch?.name }}
                    </h1>

                    <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
                        <span class="inline-flex items-center gap-1.5">
                            <Star
                                class="h-4 w-4 fill-amber-400 text-amber-400"
                            />
                            <span class="text-base font-semibold">
                                {{ (branch?.averageRating ?? 0).toFixed(1) }}
                            </span>
                        </span>

                        <span
                            v-if="isTopRated"
                            class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium ring-1 ring-white/25 backdrop-blur-md"
                        >
                            Top Rated Provider
                        </span>
                        <span v-else class="text-xs text-white/75">
                            {{ branch?.reviewCount || 0 }}
                            review{{ branch?.reviewCount === 1 ? "" : "s" }}
                        </span>
                    </div>

                    <div
                        v-if="locationLabel"
                        class="mt-1.5 flex items-center gap-1.5 text-sm text-white/85"
                    >
                        <MapPin class="h-4 w-4 shrink-0" />
                        <span class="truncate">{{ locationLabel }}</span>
                    </div>
                </div>
            </div>

            <button
                v-if="images.length"
                type="button"
                class="inline-flex items-center gap-2 rounded-full bg-black/40 px-4 py-2 text-xs font-medium text-white ring-1 ring-white/25 backdrop-blur-md transition hover:bg-black/55"
                @click="openGallery"
            >
                <ImageIcon class="h-4 w-4" />
                View All Photos
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
import { computed, ref } from "vue";
import {
    ArrowLeft,
    Building2,
    Heart,
    Image as ImageIcon,
    MapPin,
    Share2,
    Star,
} from "lucide-vue-next";
import ImagePopup from "~/components/ui/ImagePopup.vue";
import type { BranchImage, BranchRetrieve } from "~/types/branch";

const props = defineProps<{
    branch: BranchRetrieve | null;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "back"): void;
    (e: "favorite"): void;
    (e: "share"): void;
}>();

// Same list the old gallery used: the branch's primary image first,
// then the rest of its photos.
const images = computed<BranchImage[]>(() => {
    const primary: BranchImage[] = props.branch?.image
        ? [
              {
                  branch_image_id: -1,
                  image_url: props.branch.image,
                  type: "branch",
                  description: null,
              },
          ]
        : [];

    const cover: BranchImage[] = props.branch?.cover_image
        ? [
              {
                  branch_image_id: -2,
                  image_url: props.branch.cover_image,
                  type: "cover",
                  description: null,
              },
          ]
        : [];

    return [...primary, ...cover, ...(props.branch?.images ?? [])];
});

// Profile photo = the branch image. Banner = the latest cover-type photo only
// (a gradient when the branch hasn't set one).
const logoImage = computed(() => props.branch?.image ?? null);
const coverImage = computed(() => props.branch?.cover_image ?? null);

const isTopRated = computed(
    () =>
        (props.branch?.reviewCount ?? 0) > 0 &&
        (props.branch?.averageRating ?? 0) >= 4.5,
);

const locationLabel = computed(() => {
    const loc = props.branch?.location;
    if (!loc) return "";
    return [loc.city, loc.country].filter(Boolean).join(", ");
});

const lightboxIndex = ref<number | null>(null);
const openGallery = () => {
    if (!images.value.length) return;
    lightboxIndex.value = 0;
};

defineExpose({ openGallery });
</script>