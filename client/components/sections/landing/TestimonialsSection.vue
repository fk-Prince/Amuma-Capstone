<template>
    <section class="relative overflow-hidden bg-slate-50 py-24 dark:bg-secondary">
        <!-- Blob -->

        <div
            class="absolute top-[20%] right-[-150px] h-[500px] w-[500px] rounded-full bg-blue-200/30 blur-[120px]"
        ></div>

        <div class="relative z-10 mx-auto w-[94%] max-w-[1600px] px-2 sm:px-10">
            <!-- Header -->

            <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
                <div>
                    <h2
                        class="mb-3 text-[clamp(2rem,3vw,3rem)] font-black leading-tight text-secondary dark:text-white"
                    >
                        What

                        <span
                            class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent"
                        >
                            Our Users
                        </span>

                        Say About AMUMA
                    </h2>

                    <p class="text-muted dark:text-gray-400">
                        Real reviews from care teams and families using AMUMA.
                        <NuxtLink
                            to="/company/reviews"
                            class="font-semibold text-primary hover:underline"
                        >
                            See all reviews
                        </NuxtLink>
                    </p>
                </div>

                <div
                    v-if="reviews.length > 1"
                    class="hidden shrink-0 items-center gap-2 sm:flex"
                >
                    <button
                        type="button"
                        aria-label="Previous testimonials"
                        class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-secondary shadow-sm transition-colors hover:bg-primary-50 hover:text-primary dark:border-white/10 dark:bg-secondary dark:text-white dark:hover:bg-white/10"
                        @click="go(-1)"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>

                    <button
                        type="button"
                        aria-label="Next testimonials"
                        class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-secondary shadow-sm transition-colors hover:bg-primary-50 hover:text-primary dark:border-white/10 dark:bg-secondary dark:text-white dark:hover:bg-white/10"
                        @click="go(1)"
                    >
                        <ChevronRight class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Cards -->

            <div v-if="!loaded" class="flex gap-5 overflow-hidden pb-4">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="h-48 w-[320px] shrink-0 animate-pulse rounded-2xl border border-gray-100 bg-white dark:border-white/10 dark:bg-white/5"
                />
            </div>

            <div
                v-else-if="!reviews.length"
                class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center dark:border-white/10 dark:bg-white/5"
            >
                <p class="text-base font-semibold text-secondary dark:text-white">
                    No reviews yet
                </p>

                <p class="max-w-md text-sm text-muted dark:text-gray-400">
                    Using AMUMA? Share your experience and help other care teams
                    and families decide.
                </p>

                <NuxtLink
                    to="/company/reviews"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600"
                >
                    Write a review
                </NuxtLink>
            </div>

            <div
                v-else
                ref="track"
                class="flex gap-5 overflow-x-auto pb-4 scrollbar-none"
                :class="[
                    isDragging
                        ? 'cursor-grabbing select-none'
                        : 'cursor-grab snap-x snap-mandatory',
                ]"
                @pointerdown="onPointerDown"
                @pointermove="onPointerMove"
                @pointerup="onPointerUp"
                @pointercancel="onPointerUp"
                @mouseenter="stopAutoplay"
                @mouseleave="onMouseLeave"
            >
                <ReviewCard
                    v-for="review in reviews"
                    :key="review.review_id"
                    class="w-[min(320px,85vw)] shrink-0 snap-start"
                    :name="fullName(review.user)"
                    :avatar="review.user?.avatar"
                    :rate="review.rate"
                    :text="review.description"
                    :role="roleLabel(review.reviewer?.role)"
                    :role-tone="roleTone(review.reviewer?.role)"
                    :organization="
                        review.reviewer?.role === 'amuma_team'
                            ? null
                            : review.reviewer?.organization
                    "
                />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import ReviewCard from "~/components/sections/reviews/ReviewCard.vue";
import { reviewService } from "~/api/review/ReviewService";
import { formatRole, roleMeta } from "~/utils/user";

// Real, published AMUMA app reviews (same source as the Reviews page).
const reviews = ref<any[]>([]);
const loaded = ref(false);

async function loadReviews() {
    try {
        const res = await reviewService.list({ per_page: 8, withComments: 1 });
        reviews.value = res?.paginator?.data ?? [];
    } catch {
        reviews.value = [];
    } finally {
        loaded.value = true;
    }
}

function fullName(user: any) {
    return (
        [user?.first_name, user?.last_name].filter(Boolean).join(" ") ||
        "AMUMA User"
    );
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

const AUTOPLAY_INTERVAL = 4000;

const track = ref<HTMLElement | null>(null);
const isDragging = ref(false);

let dragStartX = 0;
let dragStartScroll = 0;
let autoplayTimer: ReturnType<typeof setInterval> | null = null;

function cardStep() {
    const el = track.value;
    if (!el) return 0;

    const card = el.firstElementChild as HTMLElement | null;
    if (!card) return el.clientWidth;

    const gap = parseFloat(getComputedStyle(el).columnGap || "0");
    return card.offsetWidth + gap;
}

function go(direction: number) {
    const el = track.value;
    if (!el) return;

    const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
    const atStart = el.scrollLeft <= 4;

    if (direction > 0 && atEnd) {
        el.scrollTo({ left: 0, behavior: "smooth" });
    } else if (direction < 0 && atStart) {
        el.scrollTo({ left: el.scrollWidth, behavior: "smooth" });
    } else {
        el.scrollBy({ left: direction * cardStep(), behavior: "smooth" });
    }
}

function startAutoplay() {
    stopAutoplay();
    if (reviews.value.length < 2) return;
    autoplayTimer = setInterval(() => go(1), AUTOPLAY_INTERVAL);
}

function stopAutoplay() {
    if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
    }
}

function onMouseLeave() {
    if (!isDragging.value) startAutoplay();
}

function onPointerDown(e: PointerEvent) {
    if (e.pointerType !== "mouse") return;

    const el = track.value;
    if (!el) return;

    isDragging.value = true;
    dragStartX = e.clientX;
    dragStartScroll = el.scrollLeft;
    el.setPointerCapture(e.pointerId);
    stopAutoplay();
    e.preventDefault();
}

function onPointerMove(e: PointerEvent) {
    if (!isDragging.value) return;

    const el = track.value;
    if (!el) return;

    el.scrollLeft = dragStartScroll - (e.clientX - dragStartX);
}

function onPointerUp(e: PointerEvent) {
    const el = track.value;
    if (el?.hasPointerCapture(e.pointerId)) {
        el.releasePointerCapture(e.pointerId);
    }

    isDragging.value = false;
    startAutoplay();
}

onMounted(async () => {
    await loadReviews();
    startAutoplay();
});
onBeforeUnmount(stopAutoplay);
</script>
