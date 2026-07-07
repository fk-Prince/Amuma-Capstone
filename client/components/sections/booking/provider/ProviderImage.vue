<template>
    <div class="text-white space-y-3">
        <div
            v-if="loading"
            class="relative w-full h-[320px] overflow-hidden bg-gray-800 animate-pulse"
        ></div>

        <div
            v-else-if="images.length"
            class="relative w-full h-[320px] overflow-hidden bg-gray-900"
            @click="openImage(images[0])"
        >
            <img
                :src="images[0]"
                class="w-full h-full object-cover object-left"
            />
        </div>

        <div class="flex gap-2">
            <template v-if="loading">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="w-20 h-20 rounded-lg bg-gray-800 animate-pulse"
                ></div>
            </template>

            <template v-else>
                <div
                    v-for="(img, index) in previewImages"
                    :key="index"
                    @click="openImage(img)"
                    class="relative w-20 h-20 rounded-lg overflow-hidden cursor-pointer transition-transform duration-300 hover:scale-110"
                >
                    <img :src="img" class="w-full h-full object-cover" />
                </div>

                <div
                    v-if="images.length > 5"
                    class="relative w-20 h-20 rounded-lg overflow-hidden cursor-pointer transition-transform duration-300 hover:scale-110"
                >
                    <img
                        :src="images[5]"
                        class="w-full h-full object-cover blur-sm scale-110"
                    />

                    <div
                        class="absolute inset-0 flex items-center justify-center bg-black/40"
                    >
                        <span class="text-white font-semibold text-sm">
                            +{{ images.length - 5 }}
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <ImagePopup :image="selectedImage" @close="closeImage" />
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import ImagePopup from "~/components/ui/ImagePopup.vue";

const props = defineProps<{
    primaryImage?: string;
    secondaryImage?: string[];
    loading?: boolean;
}>();

const images = computed<string[]>(() => {
    return [
        props.primaryImage,
        ...(props.secondaryImage ?? []),
        "https://picsum.photos/id/1019/800/500",
        "https://picsum.photos/id/1020/800/500",
        "https://picsum.photos/id/1021/800/500",
        "https://picsum.photos/id/1022/800/500",
        "https://picsum.photos/id/1023/800/500",
    ].filter((i): i is string => typeof i === "string");
});

const previewImages = computed(() => images.value.slice(1, 5));

const selectedImage = ref<string | null>(null);

const openImage = (img?: string) => {
    if (!img) return;
    selectedImage.value = img;
};

const closeImage = () => {
    selectedImage.value = null;
};
</script>
