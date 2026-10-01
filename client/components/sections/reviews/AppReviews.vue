<template>
    <section class="mx-auto w-full max-w-6xl px-4 pb-24 sm:px-6">
        <div
            v-if="loading && !summaryLoaded"
            class="h-[220px] animate-pulse rounded-3xl border border-gray-200 bg-white/70 dark:border-white/10 dark:bg-white/[0.03]"
        />

        <div
            v-else-if="!totalReviews"
            class="rounded-3xl border border-gray-200 bg-white/80 px-6 py-14 text-center shadow-[0_2px_24px_rgba(15,23,42,0.05)] backdrop-blur-sm dark:border-white/10 dark:bg-white/[0.03]"
        >
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary dark:bg-primary/15 dark:text-primary-300"
            >
                <MessageSquareQuote class="h-8 w-8" />
            </div>

            <h2 class="mt-5 text-xl font-bold text-secondary dark:text-white">
                No reviews yet
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted dark:text-gray-400">
                Using AMUMA at your agency or as a family member? Be the first
                to tell others what it's like.
            </p>

            <button
                type="button"
                :class="ctaClass"
                class="mt-6"
                @click="showForm = true"
            >
                <Pencil class="h-4 w-4" />
                Write the first review
            </button>
        </div>

        <template v-else>
            <div
                class="grid gap-8 rounded-3xl border border-gray-200 bg-white/80 p-6 shadow-[0_2px_24px_rgba(15,23,42,0.05)] backdrop-blur-sm sm:p-8 md:grid-cols-[auto_1fr_auto] md:items-center md:gap-10 dark:border-white/10 dark:bg-white/[0.03]"
            >
                <div class="text-center md:text-left">
                    <p class="text-5xl font-black leading-none text-secondary dark:text-white">
                        {{ averageRating.toFixed(1) }}
                    </p>

                    <div class="mt-3 flex justify-center gap-0.5 md:justify-start">
                        <Star
                            v-for="n in 5"
                            :key="n"
                            class="h-5 w-5"
                            :class="starClass(n <= Math.round(averageRating))"
                        />
                    </div>

                    <p class="mt-2 text-xs text-muted dark:text-gray-400">
                        {{ totalReviews }}
                        {{ totalReviews === 1 ? "review" : "reviews" }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5 md:border-l md:border-gray-200 md:pl-10 dark:md:border-white/10">
                    <button
                        v-for="star in [5, 4, 3, 2, 1]"
                        :key="star"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-lg px-2 py-1 transition"
                        :class="activeRate === star ? 'bg-primary/5 dark:bg-white/5' : 'hover:bg-primary/5 dark:hover:bg-white/5'"
                        @click="toggleRate(star)"
                    >
                        <span
                            class="flex w-8 shrink-0 items-center gap-1 text-xs font-medium"
                            :class="activeRate === star ? 'text-primary dark:text-primary-300' : 'text-muted dark:text-gray-400'"
                        >
                            {{ star }}
                            <Star class="h-3 w-3 fill-current" />
                        </span>

                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <span
                                class="block h-full rounded-full bg-amber-400 transition-all duration-500"
                                :style="{ width: ratingPercent(star) + '%' }"
                            />
                        </span>

                        <span class="w-8 shrink-0 text-right text-xs tabular-nums text-muted dark:text-gray-400">
                            {{ ratingBreakdown[star] ?? 0 }}
                        </span>
                    </button>
                </div>

                <div class="flex flex-col items-center gap-2 md:items-end">
                    <button type="button" :class="ctaClass" @click="showForm = true">
                        <Pencil class="h-4 w-4" />
                        Write a Review
                    </button>
                    <p class="text-xs text-muted dark:text-gray-400">
                        Share your experience with AMUMA
                    </p>
                </div>
            </div>

            <div class="mt-10 flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-lg font-bold text-secondary dark:text-white">
                    {{ listHeading }}
                </h2>

                <div class="flex flex-wrap items-center gap-2">
                    <div
                        class="inline-flex rounded-xl border border-gray-200 bg-white p-1 dark:border-white/10 dark:bg-white/[0.03]"
                    >
                        <button
                            v-for="opt in rateOptions"
                            :key="opt.label"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                            :class="
                                activeRate === opt.value
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                            "
                            @click="setRate(opt.value)"
                        >
                            {{ opt.label }}
                            <Star v-if="opt.value" class="h-3 w-3 fill-current" />
                        </button>
                    </div>

                    <button
                        v-if="withMediaCount"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition"
                        :class="
                            withMedia
                                ? 'border-primary bg-primary/10 text-primary dark:text-primary-300'
                                : 'border-gray-200 text-muted hover:text-secondary dark:border-white/10 dark:text-gray-400 dark:hover:text-white'
                        "
                        @click="toggleMedia"
                    >
                        <ImageIcon class="h-3.5 w-3.5" />
                        With photos
                        <span class="opacity-70">{{ withMediaCount }}</span>
                    </button>
                </div>
            </div>

            <div v-if="loading" class="mt-6 columns-1 gap-5 md:columns-2 lg:columns-3">
                <div
                    v-for="i in 6"
                    :key="i"
                    class="mb-5 animate-pulse break-inside-avoid rounded-2xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]"
                >
                    <div class="h-3 w-24 rounded bg-gray-100 dark:bg-white/10" />
                    <div class="mt-5 space-y-2">
                        <div class="h-3 w-full rounded bg-gray-100 dark:bg-white/10" />
                        <div class="h-3 w-5/6 rounded bg-gray-100 dark:bg-white/10" />
                        <div v-if="i % 2" class="h-3 w-2/3 rounded bg-gray-100 dark:bg-white/10" />
                    </div>
                    <div class="mt-6 flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-gray-100 dark:bg-white/10" />
                        <div class="h-3 w-28 rounded bg-gray-100 dark:bg-white/10" />
                    </div>
                </div>
            </div>

            <template v-else-if="reviews.length">
                <div class="mt-6 columns-1 gap-5 md:columns-2 lg:columns-3">
                    <ReviewCard
                        v-for="review in reviews"
                        :key="review.review_id"
                        class="mb-5 break-inside-avoid"
                        :name="fullName(review.user)"
                        :avatar="review.user?.avatar"
                        :rate="review.rate"
                        :text="review.description"
                        :image="review.image"
                        :role="roleLabel(review.reviewer?.role)"
                        :role-tone="roleTone(review.reviewer?.role)"
                        :organization="review.reviewer?.role === 'amuma_team' ? null : review.reviewer?.organization"
                        :date="formatDate(review.created_at)"
                    />
                </div>

                <div v-if="hasMore" class="mt-6 flex justify-center">
                    <button
                        type="button"
                        :disabled="loadingMore"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-secondary transition hover:border-primary/30 hover:text-primary disabled:opacity-60 dark:border-white/10 dark:bg-white/[0.03] dark:text-white"
                        @click="loadMore"
                    >
                        <LoaderCircle v-if="loadingMore" class="h-4 w-4 animate-spin" />
                        {{ loadingMore ? "Loading..." : "Show more reviews" }}
                    </button>
                </div>
            </template>

            <div v-else class="mt-6 rounded-2xl bg-gray-50 px-6 py-10 text-center dark:bg-white/[0.03]">
                <p class="text-sm font-medium text-secondary dark:text-white">
                    No reviews match this filter
                </p>
                <button
                    type="button"
                    class="mt-2 text-sm font-medium text-primary hover:underline"
                    @click="clearFilters"
                >
                    Show all reviews
                </button>
            </div>
        </template>

        <WriteAppReviewModal v-model="showForm" @submitted="syncNewReview" />
    </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
    Star,
    Pencil,
    LoaderCircle,
    MessageSquareQuote,
    Image as ImageIcon,
} from "lucide-vue-next";
import { reviewService } from "~/api/review/ReviewService";
import { formatDate } from "~/utils/time";
import { formatRole, roleMeta } from "~/utils/user";
import type { Review } from "~/types/review";
import WriteAppReviewModal from "./WriteAppReviewModal.vue";
import ReviewCard from "./ReviewCard.vue";

const PER_PAGE = 9;

const ctaClass =
    "inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary-500/25 transition hover:bg-primary-600 active:scale-[0.98]";

const rateOptions: { label: string; value: number | null }[] = [
    { label: "All", value: null },
    { label: "5", value: 5 },
    { label: "4", value: 4 },
    { label: "3", value: 3 },
    { label: "2", value: 2 },
    { label: "1", value: 1 },
];

const reviews = ref<Review[]>([]);
const averageRating = ref(0);
const ratingBreakdown = ref<Record<number, number>>({ 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 });
const withMediaCount = ref(0);
const filteredTotal = ref(0);

const loading = ref(true);
const loadingMore = ref(false);
const summaryLoaded = ref(false);
const page = ref(1);
const activeRate = ref<number | null>(null);
const withMedia = ref(false);
const showForm = ref(false);

const totalReviews = computed(() =>
    Object.values(ratingBreakdown.value).reduce((a, b) => a + b, 0),
);

const hasMore = computed(() => reviews.value.length < filteredTotal.value);

const listHeading = computed(() => {
    if (activeRate.value) return `${activeRate.value}-star reviews`;
    if (withMedia.value) return "Reviews with photos";
    return "All reviews";
});

async function fetchReviews(reset = true) {
    if (reset) {
        loading.value = true;
        page.value = 1;
    } else {
        loadingMore.value = true;
    }

    try {
        const res = await reviewService.list({
            per_page: PER_PAGE,
            page: page.value,
            rate: activeRate.value ?? undefined,
            withMedia: withMedia.value || undefined,
        });

        reviews.value = reset ? res.paginator.data : [...reviews.value, ...res.paginator.data];
        filteredTotal.value = res.paginator.total;
        averageRating.value = Number(res.average_rating ?? 0);
        ratingBreakdown.value = res.rating_breakdown;
        withMediaCount.value = res.with_media_count;
    } catch {
        if (reset) reviews.value = [];
    } finally {
        summaryLoaded.value = true;
        loading.value = false;
        loadingMore.value = false;
    }
}

function setRate(rate: number | null) {
    activeRate.value = rate;
    fetchReviews(true);
}

function toggleRate(rate: number) {
    setRate(activeRate.value === rate ? null : rate);
}

function toggleMedia() {
    withMedia.value = !withMedia.value;
    fetchReviews(true);
}

function clearFilters() {
    activeRate.value = null;
    withMedia.value = false;
    fetchReviews(true);
}

function syncNewReview(review: Review) {
    const rate = Number(review.rate);
    const star = Math.round(rate);
    const previousTotal = totalReviews.value;

    averageRating.value = (averageRating.value * previousTotal + rate) / (previousTotal + 1);
    ratingBreakdown.value = {
        ...ratingBreakdown.value,
        [star]: (ratingBreakdown.value[star] ?? 0) + 1,
    };

    if (review.image) withMediaCount.value += 1;

    const matchesFilter =
        (activeRate.value === null || activeRate.value === star) &&
        (!withMedia.value || !!review.image);

    if (matchesFilter) {
        reviews.value = [review, ...reviews.value];
        filteredTotal.value += 1;
    }
}

function ratingPercent(star: number) {
    const count = ratingBreakdown.value[star] ?? 0;
    return totalReviews.value ? Math.round((count / totalReviews.value) * 100) : 0;
}

function starClass(filled: boolean) {
    return filled
        ? "fill-amber-400 text-amber-400"
        : "fill-gray-200 text-gray-200 dark:fill-white/10 dark:text-white/10";
}

function fullName(user: any) {
    return [user?.first_name, user?.last_name].filter(Boolean).join(" ") || "AMUMA User";
}

const REVIEWER_LABELS: Record<string, string> = {
    client: "Client",
    amuma_team: "AMUMA Team",
};

function roleLabel(role?: string | null) {
    if (!role) return null;
    return REVIEWER_LABELS[role] ?? roleMeta[role]?.label ?? formatRole(role);
}

function roleTone(role?: string | null) {
    if (role === "agency_owner") return "owner";
    if (role === "client") return "client";
    if (role === "amuma_team") return "team";
    return "staff";
}

onMounted(() => fetchReviews(true));
</script>
