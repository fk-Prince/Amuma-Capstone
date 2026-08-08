<template>
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    Branch Images
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Upload and manage branch images by category.
                </p>
            </div>

            <button
                type="button"
                @click="openModal = true"
                class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-600 transition"
            >
                <Plus class="w-4 h-4" />
                Add Image
            </button>
        </div>

        <div
            v-if="images.length"
            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-5"
        >
            <div
                v-for="image in images"
                :key="image.branch_image_id"
                class="relative group rounded-2xl overflow-hidden border border-[#E4EFED] bg-white shadow-sm hover:shadow-xl transition"
            >
                <img
                    :src="image.image_url"
                    class="w-full aspect-square object-cover"
                />

                <div
                    class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent px-3 pt-10 pb-3"
                >
                    <p
                        class="text-xs font-semibold text-white uppercase tracking-wide"
                    >
                        {{ image.type }}
                    </p>

                    <p
                        v-if="image.description"
                        class="mt-1 text-[11px] leading-4 text-white/90 line-clamp-2"
                    >
                        {{ image.description }}
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="openModal = true"
                class="aspect-square rounded-2xl border-2 border-dashed border-primary-200 bg-primary-50 flex flex-col items-center justify-center text-primary hover:bg-primary-100 transition"
            >
                <Plus class="w-9 h-9" />

                <span class="mt-2 text-sm font-medium"> Add Image </span>
            </button>
        </div>

        <div
            v-else-if="!loading"
            class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 py-16 flex flex-col items-center justify-center"
        >
            <div
                class="w-14 h-14 rounded-full bg-primary-50 flex items-center justify-center text-primary"
            >
                <ImageIcon class="w-7 h-7" />
            </div>

            <p class="mt-4 text-sm text-gray-500">No images uploaded yet.</p>

            <button
                type="button"
                @click="openModal = true"
                class="mt-4 flex items-center gap-2 text-sm font-medium text-primary"
            >
                <Plus class="w-4 h-4" />
                Add your first image
            </button>
        </div>

        <div
            v-if="images.length && currentPage < lastPage"
            class="flex justify-center pt-2"
        >
            <button
                type="button"
                :disabled="loading"
                @click="loadMore"
                class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium hover:bg-gray-50 transition disabled:opacity-50"
            >
                {{ loading ? "Loading..." : "Load More" }}
            </button>
        </div>

        <Teleport to="body">
            <div
                v-if="openModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
            >
                <div
                    class="w-full max-w-lg rounded-2xl bg-white shadow-xl border border-[#E4EFED]"
                >
                    <div class="flex items-center justify-between p-5 border-b">
                        <div>
                            <h3 class="font-semibold text-gray-900">
                                Add Image
                            </h3>

                            <p class="text-sm text-gray-500">
                                Upload a branch image and provide a description.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closeModal"
                            class="text-gray-400 hover:text-gray-700 text-lg"
                        >
                            ✕
                        </button>
                    </div>

                    <form class="p-5 space-y-4" @submit.prevent="submit">
                        <Combobox
                            v-model="form.type"
                            @update:modelValue="clearError('type')"
                            label="Image Type"
                            placeholder="Select image type"
                            :items="imageTypes"
                            :error="errors?.type"
                            required
                        />

                        <BaseInput
                            v-model="form.description"
                            label="Description"
                            mode="textarea"
                            :allowResize="true"
                            :textMax="1000"
                            placeholder="Enter a short description"
                            @update:modelValue="clearError('description')"
                            :error="errors?.description"
                        />

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Image
                            </label>

                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/*"
                                @change="handleFile"
                                class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-white hover:file:opacity-90"
                            />

                            <p
                                v-if="errors?.image"
                                class="text-xs text-red-500 mt-1"
                            >
                                {{ errors.image }}
                            </p>
                        </div>

                        <div
                            v-if="preview"
                            class="rounded-xl overflow-hidden border"
                        >
                            <img
                                :src="preview"
                                class="w-full h-56 object-cover"
                            />
                        </div>

                        <p v-if="submitError" class="text-xs text-red-500">
                            {{ submitError }}
                        </p>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="rounded-xl border border-gray-200 px-4 py-3 text-sm hover:bg-gray-50 transition"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="rounded-xl bg-primary text-white px-4 py-3 text-sm font-medium hover:opacity-90 transition disabled:opacity-50"
                            >
                                {{ submitting ? "Uploading..." : "Add Image" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import { branchSettingService } from "~/api/branch-setting/BranchSettingService";
import type { BranchImageRetrieve } from "~/types/branch-utils";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { Plus, ImageIcon } from "lucide-vue-next";
interface ImageForm {
    type: string;
    description: string;
    image: File | null;
}

interface FormErrors {
    type?: string;
    description?: string;
    image?: string;
    [key: string]: string | undefined;
}

const props = defineProps<{
    uuid?: string;
}>();

const images = ref<BranchImageRetrieve[]>([]);

const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(20);
const loading = ref(false);

const openModal = ref(false);
const submitting = ref(false);

const preview = ref<string | null>(null);
const submitError = ref("");
const errors = ref<FormErrors>({});
const fileInputRef = ref<HTMLInputElement | null>(null);

const imageTypes = ref([
    { label: "Branch", value: "branch" },
    { label: "Room", value: "room" },
    { label: "Other", value: "other" },
]);

const form = ref<ImageForm>({
    type: "",
    description: "",
    image: null,
});

const fetchImages = async (page = 1) => {
    if (loading.value) return;

    loading.value = true;

    try {
        const res = await branchSettingService.list({
            action: "image",
            branch_uuid: props.uuid,
            per_page: perPage.value,
            page,
        });
        const pagination = res;

        images.value =
            page === 1
                ? pagination.data
                : [...images.value, ...pagination.data];

        currentPage.value = pagination.current_page;
        lastPage.value = pagination.last_page;
    } catch (err: any) {
        console.error("fetchImages failed:", err.response?.data ?? err);
    } finally {
        loading.value = false;
    }
};

const loadMore = () => {
    if (loading.value || currentPage.value >= lastPage.value) return;

    fetchImages(currentPage.value + 1);
};

const handleFile = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    form.value.image = file;
    preview.value = URL.createObjectURL(file);

    clearError("image");
};

const clearError = (field: keyof FormErrors) => {
    delete errors.value[field];
};

const resetForm = () => {
    form.value = {
        type: "",
        description: "",
        image: null,
    };

    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = null;
    errors.value = {};
    submitError.value = "";

    if (fileInputRef.value) {
        fileInputRef.value.value = "";
    }
};

const closeModal = () => {
    openModal.value = false;
    resetForm();
};

const validate = (): boolean => {
    const nextErrors: FormErrors = {};

    if (!form.value.type) {
        nextErrors.type = "Please select an image type.";
    }

    if (!form.value.image) {
        nextErrors.image = "Please choose an image to upload.";
    }

    errors.value = nextErrors;

    return Object.keys(nextErrors).length === 0;
};

const submit = async () => {
    submitError.value = "";

    if (!validate()) return;

    submitting.value = true;

    try {
        const payload = {
            action: "image",
            branch_uuid: props.uuid,
            type: form.value.type,
            description: form.value.description ?? "",
            image: form.value.image,
        };

        const res = await branchSettingService.create(payload);
        images.value.unshift(res.data ?? res.data.data);

        closeModal();
    } catch (err: any) {
        errors.value = err.response?.data?.errors ?? {};
        submitError.value = err.response?.data?.message ?? "Upload failed.";
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    fetchImages(1);
});

onBeforeUnmount(() => {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }
});
</script>
