<template>
    <div
        class="flex h-full flex-col rounded-2xl border border-slate-100 bg-slate-50/60 p-4 transition-all duration-200 ease-out transform-gpu dark:border-white/10 dark:bg-white/5"
        :class="
            isRejected
                ? 'opacity-80'
                : active
                  ? 'border-primary-200 bg-white ring-1 ring-primary-200 dark:border-primary-500/40 dark:bg-white/10 dark:ring-primary-500/30'
                  : ''
        "
    >
        <div class="flex items-start gap-3">
            <img
                :src="branch.image"
                :alt="branch.name"
                class="h-14 w-14 rounded-xl object-cover shrink-0"
            />

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p
                            class="text-sm font-semibold text-slate-900 truncate dark:text-white"
                        >
                            {{ branch.name }}
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <span
                            class="inline-flex items-center gap-1 text-[11px] font-medium rounded-full px-2.5 py-1"
                            :class="
                                isRejected
                                    ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'
                                    : branch.status === 'verified'
                                      ? 'bg-primary-50 text-primary dark:bg-primary-500/10'
                                      : 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400'
                            "
                        >
                            <svg
                                v-if="isRejected"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                class="w-3 h-3 shrink-0"
                            >
                                <circle cx="12" cy="12" r="9" />
                                <path
                                    d="m15 9-6 6m0-6 6 6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            <svg
                                v-else-if="branch.status === 'verified'"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                class="w-3 h-3 shrink-0"
                            >
                                <path
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                class="w-3 h-3 shrink-0"
                            >
                                <circle cx="12" cy="12" r="9" />
                                <path
                                    d="M12 7v5l3 2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            {{
                                isRejected
                                    ? "Rejected"
                                    : branch.status === 'verified'
                                      ? "Verified"
                                      : "Pending review"
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 space-y-1.5 text-xs text-slate-500 dark:text-gray-400">
            <p class="flex items-center gap-1.5">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-3.5 h-3.5 shrink-0"
                >
                    <path
                        d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"
                    />
                    <circle cx="12" cy="10" r="2.5" />
                </svg>
                {{ branch.address }}
            </p>
            <p class="flex items-center gap-1.5">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-3.5 h-3.5 shrink-0"
                >
                    <path
                        d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.9.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2.1Z"
                    />
                </svg>
                {{ branch.phone }}
            </p>
            <p class="flex items-center gap-1.5">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-3.5 h-3.5 shrink-0"
                >
                    <rect x="2" y="4" width="20" height="16" rx="2" />
                    <path d="m22 6-10 7L2 6" />
                </svg>
                {{ branch.email }}
            </p>
            <p v-if="branch.tin" class="flex items-center gap-1.5">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-3.5 h-3.5 shrink-0"
                >
                    <path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18" />
                </svg>
                TIN {{ branch.tin }}
            </p>
        </div>

        <div
            class="mt-4 grid grid-cols-2 divide-x divide-slate-200 border-t border-slate-200 pt-3 dark:divide-white/10 dark:border-white/10"
        >
            <div class="flex flex-col justify-center pr-3">
                <p class="text-[11px] text-slate-400 dark:text-gray-500">
                    Reviews
                </p>
                <button
                    type="button"
                    class="mt-1 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-primary hover:underline"
                    @click.stop="reviewsOpen = true"
                >
                    <Star class="h-3.5 w-3.5" />
                    View reviews
                </button>
            </div>

            <div
                v-if="isRejected"
                class="min-w-0 pl-3 text-left"
            >
                <p
                    class="text-[10px] font-semibold uppercase tracking-wide text-rose-500 dark:text-rose-300"
                >
                    Reason
                </p>

                <p
                    class="mt-0.5 text-[11px] leading-4 text-rose-700 dark:text-rose-300"
                >
                    {{ branch.rejection_reason || "No reason was given." }}
                </p>
            </div>

            <div
                v-else
                class="flex flex-col divide-y divide-slate-200 pl-3 dark:divide-white/10"
            >
                <div class="flex items-center justify-between pb-1.5 text-xs">
                    <span class="text-slate-500 dark:text-gray-400">
                        Patients
                    </span>
                    <span class="font-semibold text-slate-900 dark:text-white">
                        {{ branch.patients }}
                    </span>
                </div>

                <div class="flex items-center justify-between pt-1.5 text-xs">
                    <span class="text-slate-500 dark:text-gray-400">
                        Staff
                    </span>
                    <span class="font-semibold text-slate-900 dark:text-white">
                        {{ branch.staffs }}
                    </span>
                </div>
            </div>
        </div>

        <div v-if="isRejected" class="mt-auto flex items-center gap-2 pt-4">
            <ActionButton
                variant="primary"
                class="flex-1"
                extra-class="w-full !px-3 !text-xs"
                :disabled="!canResubmit"
                tooltip="You need permission to update Manage Branches to resubmit a branch."
                @click="emit('resubmit', branch)"
            >
                <RotateCw class="h-3.5 w-3.5" />
                Resubmit
            </ActionButton>
        </div>
    </div>

    <BranchReviewsModal
        :open="reviewsOpen"
        :branch-uuid="branch.uuid"
        :branch-name="branch.name"
        @close="reviewsOpen = false"
    />
</template>

<script setup lang="ts">
import { ref } from "vue";
import { RotateCw, Star } from "lucide-vue-next";
import BranchReviewsModal from "./BranchReviewsModal.vue";
import ActionButton from "~/components/ui/ActionButton.vue";

interface BranchCardData {
    branch_id: number;
    uuid: string;
    name: string;
    address: string;
    phone: string;
    email: string;
    tin?: string | null;
    status: "pending" | "verified" | "rejected";
    review_status?: "pending" | "verified" | "rejected";
    rejection_reason?: string | null;
    staffs: number;
    patients: number;
    plan: { plan_code: string; name: string } | null;
    image: string;
}

const props = withDefaults(
    defineProps<{
        branch: BranchCardData;
        active?: boolean;
        canResubmit?: boolean;
    }>(),
    { canResubmit: true },
);

const isRejected = computed(() => props.branch.review_status === "rejected");
const reviewsOpen = ref(false);

const emit = defineEmits<{
    resubmit: [branch: BranchCardData];
}>();
</script>
