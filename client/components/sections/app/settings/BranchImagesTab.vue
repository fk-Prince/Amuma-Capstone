<template>
    <div v-if="localImages && hasImages" class="space-y-8">
        <section v-if="localImages.branch.length">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                Branch Images
            </h3>

            <ImageGrid
                :images="localImages.branch"
                @remove="removeImage('branch', $event)"
            />
        </section>

        <section
            v-for="room in localImages.rooms.filter(
                (room: any) => room.images.length,
            )"
            :key="room.id"
        >
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                {{ room.name }}
            </h3>

            <ImageGrid
                :images="room.images"
                @remove="removeRoomImage(room.id, $event)"
            />
        </section>

        <section v-if="localImages.other.length">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                Other Images
            </h3>

            <ImageGrid
                :images="localImages.other"
                @remove="removeImage('other', $event)"
            />
        </section>

        <div class="flex justify-end items-center gap-3">
            <p v-if="error" class="text-sm text-red-600">
                {{ error }}
            </p>

            <p v-if="saved" class="text-sm text-green-600">
                Saved successfully.
            </p>

            <button
                type="button"
                :disabled="saving"
                @click="handleSave"
                class="px-5 py-2 rounded-lg bg-primary text-white text-sm font-medium disabled:opacity-50"
            >
                {{ saving ? "Saving..." : "Update Images" }}
            </button>
        </div>
    </div>

    <div v-else class="py-12 text-center text-gray-400">
        No images available.
    </div>
</template>

<script setup lang="ts">
import { ref, watch, toRaw, computed, defineComponent, h } from "vue";
import { useToast } from "~/composables/useToast";
import { useBranchStore } from "~/stores/branch";

type RoomImage = {
    id: string;
    name: string;
    images: string[];
};

type ImageData = {
    branch: string[];
    rooms: RoomImage[];
    other: string[];
};

const props = defineProps<{
    uuid?: string;
}>();

const images = defineModel<any>("images", {
    required: true,
});

const branchStore = useBranchStore();

const { success: toastSuccess, error: toastError } = useToast();

const localImages = ref<ImageData | null | any>(null);

const saving = ref(false);
const error = ref("");
const saved = ref(false);

const clone = <T,>(data: T): T => {
    return JSON.parse(JSON.stringify(toRaw(data)));
};

watch(
    images,
    (value) => {
        if (value) {
            localImages.value = clone(value);
        }
    },
    {
        immediate: true,
        deep: true,
    },
);

const hasImages = computed(() => {
    if (!localImages.value) return false;

    return (
        localImages.value.branch.length > 0 ||
        localImages.value.other.length > 0 ||
        localImages.value.rooms.some((room: any) => room.images.length > 0)
    );
});

function removeImage(type: "branch" | "other", index: number) {
    localImages.value?.[type].splice(index, 1);
}

function removeRoomImage(roomId: string, index: number) {
    const room = localImages.value?.rooms.find(
        (item: any) => item.id === roomId,
    );

    room?.images.splice(index, 1);
}

async function handleSave() {
    if (!localImages.value) return;

    saving.value = true;
    error.value = "";
    saved.value = false;

    try {
        images.value = clone(localImages.value);

        await branchStore.refreshBranch();

        saved.value = true;

        toastSuccess("Images updated successfully");
    } catch (err: any) {
        error.value = err?.message || "Failed to update images";

        toastError(error.value);
    } finally {
        saving.value = false;
    }
}

const ImageGrid = defineComponent({
    props: {
        images: {
            type: Array,
            required: true,
        },
    },

    emits: ["remove"],

    setup(props, { emit }) {
        return () =>
            h(
                "div",
                {
                    class: "grid grid-cols-2 md:grid-cols-4 gap-4",
                },
                props.images.map((image, index) =>
                    h(
                        "div",
                        {
                            class: "relative group",
                        },
                        [
                            h("img", {
                                src: image,
                                class: "h-32 w-full object-cover rounded-xl border",
                            }),

                            h(
                                "button",
                                {
                                    type: "button",
                                    class: "absolute top-2 right-2 bg-black/60 text-white w-7 h-7 rounded-full opacity-0 group-hover:opacity-100 transition",
                                    onClick: () => emit("remove", index),
                                },
                                "×",
                            ),
                        ],
                    ),
                ),
            );
    },
});
</script>
