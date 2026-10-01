<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-secondary/50 p-4"
                @click.self="!submitting && close()"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Review AMUMA"
                    class="w-full max-w-xl max-h-[90dvh] overflow-y-auto rounded-2xl bg-white p-8 shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-secondary dark:text-white">
                                Review AMUMA
                            </h3>
                            <p class="mt-0.5 text-xs text-muted dark:text-gray-400">
                                This review is about the AMUMA platform, not a
                                specific care provider.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-full p-1.5 text-muted hover:bg-light/60 dark:text-gray-400 dark:hover:bg-white/5"
                            :disabled="submitting"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <template v-if="!user">
                        <p class="mt-4 text-sm text-muted dark:text-gray-400">
                            Please sign in to leave a review for AMUMA.
                        </p>

                        <NuxtLink
                            to="/auth/signin"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-600"
                        >
                            Sign In
                        </NuxtLink>
                    </template>

                    <template v-else>
                        <p class="mt-5 text-sm font-medium text-secondary dark:text-white">
                            Your Rating
                        </p>

                        <div class="mt-2 flex gap-1">
                            <button
                                v-for="n in 5"
                                :key="n"
                                type="button"
                                class="p-0.5"
                                @click="form.rate = n"
                            >
                                <Star
                                    class="h-6 w-6"
                                    :class="
                                        n <= form.rate
                                            ? 'text-amber-500 fill-amber-400 dark:text-amber-300'
                                            : 'text-muted-light dark:text-white/10'
                                    "
                                />
                            </button>
                        </div>

                        <p v-if="errors.rate" class="mt-1 text-xs text-danger">
                            {{ errors.rate }}
                        </p>

                        <label class="mt-5 block text-sm font-medium text-secondary dark:text-white">
                            Your Review
                        </label>

                        <p class="mt-0.5 text-xs text-muted dark:text-gray-400">
                            Tell others what stood out — ease of use, features,
                            support, or anything that made a difference for
                            you.
                        </p>

                        <textarea
                            v-model="form.description"
                            rows="7"
                            placeholder="Share your experience using AMUMA..."
                            class="mt-2 w-full rounded-lg border border-muted-light p-3 text-sm text-secondary focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary dark:text-white dark:border-white/10 dark:bg-secondary dark:placeholder-gray-500"
                        />

                        <p v-if="errors.description" class="mt-1 text-xs text-danger">
                            {{ errors.description }}
                        </p>

                        <label class="mt-5 block text-sm font-medium text-secondary dark:text-white">
                            Add a Screenshot
                            <span class="font-normal text-muted dark:text-gray-400">
                                (optional)
                            </span>
                        </label>

                        <div
                            v-if="!imagePreviewUrl"
                            class="mt-2 flex cursor-pointer flex-col items-center gap-1.5 rounded-lg border border-dashed border-muted-light bg-light/40 p-5 text-center transition hover:border-primary/40 hover:bg-primary/5 dark:border-white/10 dark:bg-white/5"
                            @click="imageInput?.click()"
                        >
                            <ImagePlus class="h-5 w-5 text-muted dark:text-gray-400" />
                            <span class="text-xs text-muted dark:text-gray-400">
                                Click to upload an image (max 5MB)
                            </span>
                        </div>

                        <div v-else class="mt-2 flex items-center gap-3">
                            <img
                                :src="imagePreviewUrl"
                                alt="Selected review image"
                                class="h-20 w-20 rounded-lg object-cover ring-1 ring-muted-light dark:ring-white/10"
                            />

                            <button
                                type="button"
                                class="text-xs font-medium text-danger hover:underline"
                                @click="removeImage"
                            >
                                Remove image
                            </button>
                        </div>

                        <input
                            ref="imageInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onImageChange"
                        />

                        <p v-if="errors.image" class="mt-1 text-xs text-danger">
                            {{ errors.image }}
                        </p>

                        <div class="mt-6 flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="submitting"
                                @click="submit"
                            >
                                <LoaderCircle v-if="submitting" class="h-4 w-4 animate-spin" />
                                {{ submitting ? "Submitting..." : "Submit Review" }}
                            </button>

                            <button
                                type="button"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-muted hover:bg-light/60 dark:text-gray-400 dark:hover:bg-white/5"
                                :disabled="submitting"
                                @click="close"
                            >
                                Cancel
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Star, LoaderCircle, X, ImagePlus } from "lucide-vue-next";
import { reviewService } from "~/api/review/ReviewService";
import { useAuthUser, fetchAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import type { Review } from "~/types/review";

const open = defineModel<boolean>({ default: false });

const emit = defineEmits<{
    (e: "submitted", review: Review): void;
}>();

const user = useAuthUser();
const { success, error } = useToast();

onMounted(() => {
    if (!user.value) {
        fetchAuthUser().catch(() => {});
    }
});

const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

const submitting = ref(false);
const form = ref({ rate: 0, description: "" });
const errors = ref({ rate: "", description: "", image: "" });

const imageInput = ref<HTMLInputElement | null>(null);
const imageFile = ref<File | null>(null);
const imagePreviewUrl = ref<string | null>(null);

function onImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (!file) return;

    if (!file.type.startsWith("image/")) {
        errors.value.image = "Please choose an image file.";
        return;
    }

    if (file.size > MAX_IMAGE_BYTES) {
        errors.value.image = "Image must be smaller than 5MB.";
        return;
    }

    errors.value.image = "";

    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value);
    }

    imageFile.value = file;
    imagePreviewUrl.value = URL.createObjectURL(file);
}

function removeImage() {
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value);
    }

    imageFile.value = null;
    imagePreviewUrl.value = null;
    errors.value.image = "";

    if (imageInput.value) {
        imageInput.value.value = "";
    }
}

function close() {
    open.value = false;
    form.value = { rate: 0, description: "" };
    errors.value = { rate: "", description: "", image: "" };
    removeImage();
}

async function submit() {
    errors.value = { rate: "", description: "", image: errors.value.image };

    if (!form.value.rate) {
        errors.value.rate = "Please select a rating.";
    }

    if (!form.value.description.trim()) {
        errors.value.description = "Please share a few words about your experience.";
    }

    if (errors.value.rate || errors.value.description || errors.value.image) {
        return;
    }

    submitting.value = true;

    try {
        const rate = form.value.rate;
        const description = form.value.description.trim();

        const res = await reviewService.create({
            rate: rate.toFixed(2),
            description,
            ...(imageFile.value ? { image: imageFile.value } : {}),
        });

        const now = new Date().toISOString();

        emit("submitted", {
            review_id: Date.now(),
            branch_id: null,
            rate,
            description,
            image: null,
            created_at: now,
            updated_at: now,
            reviewer: null,
            ...res?.data,
            user: res?.data?.user ?? user.value,
        } as unknown as Review);

        success("Thank you! Your review has been submitted.");
        close();
    } catch (err: any) {
        error(err?.message ?? "Failed to submit review.");
    } finally {
        submitting.value = false;
    }
}
</script>
