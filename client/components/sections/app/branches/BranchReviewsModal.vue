<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-secondary/50 p-4"
                @click.self="$emit('close')"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Branch reviews"
                    class="w-full max-w-lg max-h-[85dvh] overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10 flex flex-col"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3
                                class="text-sm font-semibold text-secondary dark:text-white"
                            >
                                Reviews
                            </h3>
                            <p
                                v-if="branchName"
                                class="mt-0.5 truncate text-xs text-muted dark:text-gray-400"
                            >
                                {{ branchName }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-full p-1.5 text-muted hover:bg-light/60 dark:text-gray-400 dark:hover:bg-white/5"
                            @click="$emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-2">
                        <template v-if="loading">
                            <div class="animate-pulse">
                                <div
                                    v-for="i in 3"
                                    :key="i"
                                    class="py-4 border-b border-slate-100 last:border-b-0 dark:border-white/10"
                                >
                                    <div
                                        class="h-3.5 w-32 rounded bg-slate-100 dark:bg-white/10"
                                    />
                                    <div
                                        class="mt-2 h-3 w-24 rounded bg-slate-100 dark:bg-white/10"
                                    />
                                    <div
                                        class="mt-3 h-3 w-full rounded bg-slate-100 dark:bg-white/10"
                                    />
                                </div>
                            </div>
                        </template>

                        <template v-else-if="reviews.length">
                            <div
                                v-for="review in reviews"
                                :key="review.review_id"
                                class="py-4 border-b border-slate-100 last:border-b-0 dark:border-white/10"
                            >
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="flex min-w-0 items-center gap-3">
                                        <PatientAvatar
                                            :src="review.user?.avatar"
                                            :name="fullName(review.user)"
                                            size-class="h-10 w-10 text-sm"
                                        />

                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-medium text-secondary dark:text-white"
                                            >
                                                {{ fullName(review.user) }}
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs text-muted dark:text-gray-400"
                                            >
                                                {{ formatDate(review.created_at) }}
                                            </p>
                                        </div>
                                    </div>

                                    <span
                                        class="flex shrink-0 items-center gap-0.5"
                                        :aria-label="`${review.rate} out of 5`"
                                    >
                                        <Star
                                            v-for="n in 5"
                                            :key="n"
                                            class="h-4 w-4"
                                            :class="
                                                n <= Math.round(review.rate)
                                                    ? 'fill-amber-400 text-amber-400'
                                                    : 'text-slate-300 dark:text-gray-600'
                                            "
                                        />
                                    </span>
                                </div>

                                <p
                                    v-if="review.description"
                                    class="mt-2 text-sm text-secondary/80 dark:text-gray-300"
                                >
                                    {{ review.description }}
                                </p>

                                <img
                                    v-if="review.image"
                                    :src="review.image"
                                    alt="Review photo"
                                    class="mt-3 h-20 w-20 rounded-lg object-cover ring-1 ring-slate-100 dark:ring-white/10"
                                />
                            </div>
                        </template>

                        <div
                            v-else
                            class="flex flex-col items-center justify-center py-14 text-center"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-light dark:bg-white/5"
                            >
                                <Star
                                    class="h-6 w-6 text-amber-500 fill-amber-400 dark:text-amber-300"
                                />
                            </div>

                            <p
                                class="mt-3 text-sm font-medium text-secondary dark:text-white"
                            >
                                No reviews yet
                            </p>

                            <p
                                class="mt-1 max-w-xs text-xs text-muted dark:text-gray-400"
                            >
                                This branch hasn't received any reviews yet.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="!loading && total > perPage"
                        class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 dark:border-white/10"
                    >
                        <p class="text-xs text-muted dark:text-gray-400">
                            Page {{ page }} of {{ lastPage }}
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                :disabled="page <= 1 || loadingPage"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-secondary transition hover:bg-light/60 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:text-white dark:hover:bg-white/5"
                                @click="goToPage(page - 1)"
                            >
                                Previous
                            </button>

                            <button
                                type="button"
                                :disabled="page >= lastPage || loadingPage"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-secondary transition hover:bg-light/60 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:text-white dark:hover:bg-white/5"
                                @click="goToPage(page + 1)"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { Star, X } from "lucide-vue-next";
import PatientAvatar from "~/components/ui/PatientAvatar.vue";
import { reviewService } from "~/api/review/ReviewService";
import { useToast } from "~/composables/useToast";
import type { Review } from "~/types/review";

const props = defineProps<{
    open: boolean;
    branchUuid: string | null;
    branchName?: string | null;
}>();

const emit = defineEmits<{ close: [] }>();

const { error } = useToast();

const reviews = ref<Review[]>([]);
const loading = ref(false);
const loadingPage = ref(false);

const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = 5;

const fetchReviews = async () => {
    if (!props.branchUuid) return;

    loadingPage.value = true;

    try {
        const res: any = await reviewService.list({
            branch_uuid: props.branchUuid,
            per_page: perPage,
            page: page.value,
        });

        reviews.value = res.paginator?.data ?? [];
        total.value = res.paginator?.total ?? 0;
        lastPage.value = res.paginator?.last_page ?? 1;
    } catch (err: any) {
        error(err?.message ?? "Failed to load reviews.");
    } finally {
        loading.value = false;
        loadingPage.value = false;
    }
};

const goToPage = (target: number) => {
    if (target < 1 || target > lastPage.value || loadingPage.value) return;

    page.value = target;
    fetchReviews();
};

watch(
    () => [props.open, props.branchUuid],
    ([isOpen]) => {
        if (!isOpen) return;

        page.value = 1;
        loading.value = true;
        fetchReviews();
    },
);

function fullName(user: any) {
    return `${user?.first_name ?? ""} ${user?.last_name ?? ""}`.trim() || "Anonymous";
}

function formatDate(dateStr: string) {
    const date = new Date(dateStr);

    return date.toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
</script>
