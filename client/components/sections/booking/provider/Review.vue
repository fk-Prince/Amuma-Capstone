<template>
    <section class="rounded-2xl border-slate-200 bg-white p-6 md:p-8">
        <div>
            <h2 class="text-3xl font-bold text-slate-900 my-2">
                Ratings & Reviews
            </h2>
        </div>

        <template v-if="loading">
            <div class="animate-pulse">
                <div
                    class="rounded-xl border border-orange-100 bg-orange-50/60 p-5 mb-6"
                >
                    <div class="h-10 w-20 bg-gray-200 rounded"></div>

                    <div class="flex gap-1 mt-3 mb-5">
                        <div
                            v-for="i in 5"
                            :key="i"
                            class="h-5 w-5 bg-gray-200 rounded"
                        ></div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="i in 7"
                            :key="i"
                            class="h-8 w-20 bg-gray-200 rounded-lg"
                        ></div>
                    </div>
                </div>

                <div
                    v-for="i in 3"
                    :key="i"
                    class="py-5 border-b border-slate-100"
                >
                    <div class="h-4 w-32 bg-gray-200 rounded mb-2"></div>

                    <div class="h-4 w-24 bg-gray-200 rounded mb-2"></div>

                    <div class="h-3 w-28 bg-gray-200 rounded mb-3"></div>

                    <div class="space-y-2">
                        <div class="h-3 bg-gray-200 rounded w-full"></div>
                        <div class="h-3 bg-gray-200 rounded w-5/6"></div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div
                class="rounded-xl border border-orange-100 bg-orange-50/60 p-5 mb-6"
            >
                <div class="flex items-baseline gap-3">
                    <span class="text-3xl font-bold text-orange-500">
                        {{ (averageRating ?? 0).toFixed(1) }}
                    </span>

                    <span class="text-sm text-slate-500"> out of 5 </span>
                </div>

                <div class="flex gap-0.5 text-lg mt-1 mb-4">
                    <span v-for="n in 5" :key="n">
                        <Star
                            v-if="n <= Math.round(averageRating ?? 0)"
                            class="h-4 w-4 text-orange-400 fill-orange-400"
                        />

                        <Star v-else class="h-4 w-4 text-slate-300" />
                    </span>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="setFilter('all')"
                        :class="btnClass(activeFilter === 'all')"
                    >
                        All
                    </button>

                    <button
                        v-for="star in [5, 4, 3, 2, 1]"
                        :key="star"
                        @click="setFilter(star)"
                        :class="btnClass(activeFilter === star)"
                    >
                        {{ star }} Star ({{ ratingBreakdown[star] ?? 0 }})
                    </button>

                    <button
                        @click="setFilter('comments')"
                        :class="btnClass(activeFilter === 'comments')"
                    >
                        With Comments ({{ withCommentCounts }})
                    </button>
                </div>
            </div>

            <div v-if="reviews.length">
                <div
                    v-for="review in reviews"
                    :key="review.review_id"
                    class="py-5 border-b border-slate-100"
                >
                    <p class="text-sm font-medium">
                        {{ fullName(review.user) }}
                    </p>

                    <div class="text-orange-500 text-sm">
                        {{ "★".repeat(review.rate)
                        }}{{ "☆".repeat(5 - review.rate) }}
                    </div>

                    <p class="text-xs text-slate-400">
                        {{ formatDate(review.created_at) }}
                    </p>

                    <p class="mt-2 text-sm text-slate-700">
                        {{ review.description }}
                    </p>
                </div>

                <div v-if="hasMore" class="pt-6 flex justify-center">
                    <button
                        @click="loadMore"
                        :disabled="loadingMore"
                        class="px-5 py-2 text-sm border rounded-lg"
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
                    class="flex h-16 w-16 items-center justify-center rounded-full bg-orange-50 pl-1"
                >
                    <Star class="h-8 w-8 text-orange-400 fill-orange-400" />
                </div>

                <h3 class="mt-4 text-lg font-semibold text-slate-900">
                    No reviews yet
                </h3>

                <p class="mt-2 max-w-sm text-sm text-slate-500">
                    This provider does not have any patient reviews yet. Be the
                    first one to share your experience.
                </p>
            </div>
        </template>
    </section>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { reviewService } from "~/api/review/ReviewService";
import type { Review } from "~/types/review";
import { Star } from "lucide-vue-next";
const props = defineProps<{
    branchUuid: string;
}>();

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

function fullName(user: any) {
    return `${user.first_name} ${user.last_name}`;
}

function formatDate(dateStr: string) {
    const date = new Date(dateStr);

    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

const btnClass = (active: boolean) =>
    `px-3 py-1.5 text-sm rounded-lg border transition ${
        active
            ? "border-orange-400 text-orange-500 bg-white"
            : "border-slate-200 text-slate-600 hover:border-slate-300"
    }`;
</script>
