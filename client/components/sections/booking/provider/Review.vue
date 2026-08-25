<template>
    <section class="rounded-2xl border-muted-light bg-white p-6 md:p-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-3xl font-bold text-secondary">
                    Ratings & Reviews
                </h2>
                <p class="mt-1 text-sm text-muted">
                    See what families are saying about their experience with
                    this provider.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-medium text-primary hover:bg-primary/10"
                @click="openForm"
            >
                <Pencil class="h-4 w-4" />
                Write a Review
            </button>
        </div>

        <div
            class="mt-6 mb-6 grid grid-cols-1 gap-6 rounded-2xl border border-primary/10 bg-gradient-to-br from-light to-white p-6 md:grid-cols-[auto_1fr] md:gap-10"
        >
            <div
                class="flex flex-col items-center border-primary/10 pb-6 text-center md:items-start md:border-r md:pb-0 md:pr-10 md:text-left"
            >
                <span class="text-5xl font-extrabold leading-none text-secondary">
                    {{ (averageRating ?? 0).toFixed(1) }}
                </span>

                <div class="mt-3 flex gap-0.5">
                    <Star
                        v-for="n in 5"
                        :key="n"
                        class="h-5 w-5"
                        :class="
                            n <= Math.round(averageRating ?? 0)
                                ? 'text-amber-400 fill-amber-400'
                                : 'text-amber-100 fill-amber-100'
                        "
                    />
                </div>

                <p class="mt-2 text-xs text-muted">
                    Based on {{ total }}
                    {{ total === 1 ? "review" : "reviews" }}
                </p>
            </div>

            <div class="flex flex-col justify-center gap-2">
                <button
                    v-for="star in [5, 4, 3, 2, 1]"
                    :key="star"
                    type="button"
                    class="group flex w-full items-center gap-3 rounded-lg px-1.5 py-1 transition"
                    :class="
                        activeFilter === star ? 'bg-primary/5' : 'hover:bg-primary/5'
                    "
                    @click="setFilter(star)"
                >
                    <span
                        class="w-11 shrink-0 text-xs font-medium"
                        :class="
                            activeFilter === star ? 'text-primary' : 'text-muted'
                        "
                    >
                        {{ star }} star
                    </span>

                    <span
                        class="h-2 flex-1 overflow-hidden rounded-full bg-muted-light"
                    >
                        <span
                            class="block h-full rounded-full bg-amber-400 transition-all"
                            :style="{ width: ratingPercent(star) + '%' }"
                        />
                    </span>

                    <span class="w-6 shrink-0 text-right text-xs text-muted">
                        {{ ratingBreakdown[star] ?? 0 }}
                    </span>
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-6">
            <button
                @click="setFilter('all')"
                :class="btnClass(activeFilter === 'all')"
            >
                All Reviews
            </button>

            <button
                @click="setFilter('comments')"
                :class="btnClass(activeFilter === 'comments')"
            >
                With Comments ({{ withCommentCounts }})
            </button>
        </div>

        <template v-if="loading">
            <div class="animate-pulse">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="py-5 border-b border-muted-light"
                >
                    <div class="h-4 w-32 bg-muted-light rounded mb-2"></div>

                    <div class="h-4 w-24 bg-muted-light rounded mb-2"></div>

                    <div class="h-3 w-28 bg-muted-light rounded mb-3"></div>

                    <div class="space-y-2">
                        <div class="h-3 bg-muted-light rounded w-full"></div>
                        <div class="h-3 bg-muted-light rounded w-5/6"></div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div v-if="reviews.length">
                <div
                    v-for="review in reviews"
                    :key="review.review_id"
                    class="py-5 border-b border-muted-light"
                >
                    <p class="text-sm font-medium text-secondary">
                        {{ fullName(review.user) }}
                    </p>

                    <div class="text-amber-500 text-sm">
                        {{ "★".repeat(review.rate)
                        }}{{ "☆".repeat(5 - review.rate) }}
                    </div>

                    <p class="text-xs text-muted">
                        {{ formatDate(review.created_at) }}
                    </p>

                    <p class="mt-2 text-sm text-secondary/80">
                        {{ review.description }}
                    </p>

                    <img
                        v-if="review.image"
                        :src="review.image"
                        alt="Review photo"
                        class="mt-3 h-28 w-28 rounded-lg object-cover ring-1 ring-muted-light"
                    />
                </div>

                <div v-if="hasMore" class="pt-6 flex justify-center">
                    <button
                        @click="loadMore"
                        :disabled="loadingMore"
                        class="px-5 py-2 text-sm border border-muted-light rounded-lg text-secondary hover:bg-light/60"
                    >
                        {{ loadingMore ? "Loading..." : "See More Reviews" }}
                    </button>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center py-12 text-center"
            >
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-full bg-light"
                >
                    <Star class="h-8 w-8 text-amber-500 fill-amber-400" />
                </div>

                <h3 class="mt-4 text-lg font-semibold text-secondary">
                    No reviews yet
                </h3>

                <p class="mt-2 max-w-sm text-sm text-muted">
                    This provider does not have any patient reviews yet. Be the
                    first one to share your experience.
                </p>

                <button
                    type="button"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-medium text-primary hover:bg-primary/10"
                    @click="openForm"
                >
                    <Pencil class="h-4 w-4" />
                    Write a Review
                </button>
            </div>
        </template>
    </section>

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
                v-if="showForm"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-secondary/50 p-4"
                @click.self="!submitting && closeForm()"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Write a Review"
                    class="w-full max-w-xl max-h-[90dvh] overflow-y-auto rounded-2xl bg-white p-8 shadow-2xl ring-1 ring-black/5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-lg font-semibold text-secondary">
                            Write a Review
                        </h3>

                        <button
                            type="button"
                            class="rounded-full p-1.5 text-muted hover:bg-light/60"
                            :disabled="submitting"
                            @click="closeForm"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <template v-if="!user">
                        <p class="mt-4 text-sm text-muted">
                            Please sign in to leave a review for this
                            provider.
                        </p>

                        <NuxtLink
                            to="/auth/signin"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-600"
                        >
                            Sign In
                        </NuxtLink>
                    </template>

                    <template v-else>
                        <p class="mt-4 text-sm font-medium text-secondary">
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
                                            ? 'text-amber-500 fill-amber-400'
                                            : 'text-muted-light'
                                    "
                                />
                            </button>
                        </div>

                        <p v-if="errors.rate" class="mt-1 text-xs text-danger">
                            {{ errors.rate }}
                        </p>

                        <label
                            class="mt-5 block text-sm font-medium text-secondary"
                        >
                            Your Review
                        </label>

                        <p class="mt-0.5 text-xs text-muted">
                            Tell other families what stood out — the care
                            team, communication, cleanliness, or anything
                            that helped you decide.
                        </p>

                        <textarea
                            v-model="form.description"
                            rows="8"
                            placeholder="Share your experience with this provider..."
                            class="mt-2 w-full rounded-lg border border-muted-light p-3 text-sm text-secondary focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary"
                        />

                        <p
                            v-if="errors.description"
                            class="mt-1 text-xs text-danger"
                        >
                            {{ errors.description }}
                        </p>

                        <label
                            class="mt-5 block text-sm font-medium text-secondary"
                        >
                            Add a Photo
                            <span class="font-normal text-muted">
                                (optional)
                            </span>
                        </label>

                        <div
                            v-if="!imagePreviewUrl"
                            class="mt-2 flex cursor-pointer flex-col items-center gap-1.5 rounded-lg border border-dashed border-muted-light bg-light/40 p-5 text-center transition hover:border-primary/40 hover:bg-primary/5"
                            @click="imageInput?.click()"
                        >
                            <ImagePlus class="h-5 w-5 text-muted" />
                            <span class="text-xs text-muted">
                                Click to upload a photo (max 5MB)
                            </span>
                        </div>

                        <div v-else class="mt-2 flex items-center gap-3">
                            <img
                                :src="imagePreviewUrl"
                                alt="Selected review photo"
                                class="h-20 w-20 rounded-lg object-cover ring-1 ring-muted-light"
                            />

                            <button
                                type="button"
                                class="text-xs font-medium text-danger hover:underline"
                                @click="removeImage"
                            >
                                Remove photo
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

                        <div class="mt-5 flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="submitting"
                                @click="submitReview"
                            >
                                <LoaderCircle
                                    v-if="submitting"
                                    class="h-4 w-4 animate-spin"
                                />
                                {{
                                    submitting
                                        ? "Submitting..."
                                        : "Submit Review"
                                }}
                            </button>

                            <button
                                type="button"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-muted hover:bg-light/60"
                                :disabled="submitting"
                                @click="closeForm"
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
import { ref, watch, computed, onMounted } from "vue";
import { reviewService } from "~/api/review/ReviewService";
import type { Review } from "~/types/review";
import { Star, Pencil, LoaderCircle, X, ImagePlus } from "lucide-vue-next";
import { useAuthUser, fetchAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";

const props = defineProps<{
    branchUuid: string;
}>();

const user = useAuthUser();
const { success, error } = useToast();

onMounted(() => {
    if (!user.value) {
        fetchAuthUser().catch(() => {});
    }
});

const showForm = ref(false);
const submitting = ref(false);

const form = ref({
    rate: 0,
    description: "",
});

const errors = ref({
    rate: "",
    description: "",
    image: "",
});

const imageInput = ref<HTMLInputElement | null>(null);
const imageFile = ref<File | null>(null);
const imagePreviewUrl = ref<string | null>(null);

const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

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

function openForm() {
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    form.value = { rate: 0, description: "" };
    errors.value = { rate: "", description: "", image: "" };
    removeImage();
}

async function submitReview() {
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
            branch_uuid: props.branchUuid,
            // decimal:2 on the backend rejects a bare integer/1-decimal
            // value (e.g. 5 or 5.0) — it must be a string with exactly
            // two decimal places.
            rate: rate.toFixed(2),
            description,
            ...(imageFile.value ? { image: imageFile.value } : {}),
        });

        // The backend has already finished uploading by the time it
        // responds, so its returned URL (not the local blob preview,
        // which gets revoked once the form closes) is what gets shown.
        syncNewReview(res?.data, rate, description, res?.data?.image ?? null);

        success("Thank you! Your review has been submitted.");
        closeForm();
    } catch (err: any) {
        error(err?.message ?? "Failed to submit review.");
    } finally {
        submitting.value = false;
    }
}

const reviews = ref<Review[]>([]);
const averageRating = ref<number | null>(null);
const withCommentCounts = ref<number | null>(null);

const ratingBreakdown = ref<Record<number, number>>({
    1: 0,
    2: 0,
    3: 0,
    4: 0,
    5: 0,
});

const loading = ref(false);
const loadingMore = ref(false);

const page = ref(1);
const total = ref(0);

const activeFilter = ref<"all" | "comments" | number>("all");

const fetchReviews = async (reset = true) => {
    if (!props.branchUuid) return;

    if (reset) {
        loading.value = true;
        page.value = 1;
        reviews.value = [];
    } else {
        loadingMore.value = true;
    }

    try {
        const res = await reviewService.list({
            branch_uuid: props.branchUuid,
            per_page: 4,
            page: page.value,
            rate:
                typeof activeFilter.value === "number"
                    ? activeFilter.value
                    : undefined,
            withComments: activeFilter.value === "comments" || undefined,
        });

        reviews.value = reset
            ? res.paginator.data
            : [...reviews.value, ...res.paginator.data];

        total.value = res.paginator.total;
        averageRating.value = res.average_rating;
        ratingBreakdown.value = res.rating_breakdown;
        withCommentCounts.value = res.with_comments_count;
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

// Update local state from the just-created review instead of round-tripping
// through fetchReviews — the create response doesn't carry the `user`
// relation, so the display fields are filled in from the signed-in user
// already held in state.
function syncNewReview(
    created: any,
    rate: number,
    description: string,
    image: string | null,
) {
    const roundedRate = Math.round(rate) as 1 | 2 | 3 | 4 | 5;

    const previousSum =
        (averageRating.value ?? 0) * total.value;

    total.value += 1;
    averageRating.value = (previousSum + rate) / total.value;

    ratingBreakdown.value = {
        ...ratingBreakdown.value,
        [roundedRate]: (ratingBreakdown.value[roundedRate] ?? 0) + 1,
    };

    if (description) {
        withCommentCounts.value = (withCommentCounts.value ?? 0) + 1;
    }

    const matchesFilter =
        activeFilter.value === "all" ||
        (activeFilter.value === "comments" && !!description) ||
        activeFilter.value === roundedRate;

    if (matchesFilter && user.value) {
        reviews.value = [
            {
                review_id: created?.review_id ?? Date.now(),
                branch_id: created?.branch_id,
                rate,
                description,
                image,
                created_at: created?.created_at ?? new Date().toISOString(),
                updated_at: created?.updated_at ?? new Date().toISOString(),
                user: user.value,
            } as Review,
            ...reviews.value,
        ];
    }
}

watch(
    () => props.branchUuid,
    () => fetchReviews(true),
    { immediate: true },
);

const setFilter = (filter: "all" | "comments" | number) => {
    activeFilter.value = filter;
    fetchReviews(true);
};

const loadMore = async () => {
    page.value += 1;
    await fetchReviews(false);
};

const hasMore = computed(() => reviews.value.length < total.value);

function ratingPercent(star: number): number {
    const count = ratingBreakdown.value[star] ?? 0;
    const sum = Object.values(ratingBreakdown.value).reduce(
        (a, b) => a + b,
        0,
    );

    return sum ? Math.round((count / sum) * 100) : 0;
}

function fullName(user: any) {
    return `${user.first_name} ${user.last_name}`;
}

function formatDate(dateStr: string) {
    const date = new Date(dateStr);

    return date.toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

const btnClass = (active: boolean) =>
    `px-3 py-1.5 text-sm rounded-lg border transition ${
        active
            ? "border-amber-400 text-amber-500 bg-white"
            : "border-muted-light text-muted hover:border-primary/30"
    }`;
</script>
