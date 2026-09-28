<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
                @click.self="close"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-danger"
                            >
                                Reject request
                            </p>

                            <h3
                                class="mt-1 truncate text-base font-semibold text-secondary dark:text-white"
                            >
                                {{ branchName || "This branch" }}
                            </h3>
                        </div>

                        <button
                            type="button"
                            :disabled="submitting"
                            class="rounded-lg p-1.5 text-muted transition hover:bg-slate-100 hover:text-secondary disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/10"
                            @click="close"
                        >
                            <AppIcon name="x" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        <p
                            class="text-xs leading-5 text-muted dark:text-gray-400"
                        >
                            The reason is emailed to the agency and shown on the
                            branch, so write something they can act on.
                        </p>

                        <div v-if="canRejectAgency">
                            <p
                                class="text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                What needs fixing?
                            </p>

                            <div class="mt-2 grid grid-cols-2 gap-1.5">
                                <button
                                    v-for="option in SCOPES"
                                    :key="option.value"
                                    type="button"
                                    :disabled="submitting"
                                    class="rounded-lg border px-2.5 py-2 text-xs font-medium transition disabled:opacity-50"
                                    :class="
                                        scope === option.value
                                            ? 'border-red-300 bg-red-50 text-red-600 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-300'
                                            : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5'
                                    "
                                    @click="scope = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label
                                class="text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                Reason for rejection
                                <span class="text-danger">*</span>
                            </label>

                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <button
                                    v-for="fill in QUICK_FILLS"
                                    :key="fill.label"
                                    type="button"
                                    :disabled="submitting"
                                    class="rounded-full border px-2.5 py-1 text-[11px] font-medium transition disabled:opacity-50"
                                    :class="
                                        reason === fill.text
                                            ? 'border-red-300 bg-red-50 text-red-600 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-300'
                                            : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5'
                                    "
                                    @click="applyQuickFill(fill.text)"
                                >
                                    {{ fill.label }}
                                </button>
                            </div>

                            <textarea
                                v-model="reason"
                                rows="4"
                                :maxlength="MAX_LENGTH"
                                placeholder="Pick a quick fill above or describe what the agency needs to fix before re-submitting."
                                class="mt-2 w-full resize-none rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 dark:border-white/10 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500"
                            />

                            <div
                                class="mt-1 flex items-center justify-between gap-3"
                            >
                                <p v-if="error" class="text-xs text-danger">
                                    {{ error }}
                                </p>

                                <span
                                    class="ml-auto text-[11px] text-muted dark:text-gray-500"
                                >
                                    {{ reason.length }}/{{ MAX_LENGTH }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="serverError"
                            role="alert"
                            class="flex items-start gap-2 rounded-xl bg-red-50 p-3 text-xs leading-5 text-red-700 dark:bg-red-500/10 dark:text-red-300"
                        >
                            <AppIcon
                                name="alert-circle"
                                class="mt-0.5 h-4 w-4 shrink-0"
                            />

                            <span>{{ serverError }}</span>
                        </div>

                        <div
                            v-if="refunds"
                            class="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-xs leading-5 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                        >
                            <AppIcon
                                name="alert-circle"
                                class="mt-0.5 h-4 w-4 shrink-0"
                            />

                            <span>
                                This is the first branch on the subscription,
                                so rejecting it also refunds the payment<template
                                    v-if="rejectsAgencyWithRefund"
                                >
                                    and rejects the agency, which hasn't been
                                    verified yet</template
                                >.
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            type="button"
                            :disabled="submitting"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium text-muted transition hover:bg-slate-100 hover:text-secondary disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/10"
                            @click="close"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            :disabled="submitting"
                            class="inline-flex items-center gap-2 rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                            @click="submit"
                        >
                            <span
                                v-if="submitting"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
                            />
                            {{ submitting ? "Rejecting…" : confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import AppIcon from "~/components/ui/AppIcon.vue";

const MAX_LENGTH = 500;

const QUICK_FILLS = [
    {
        label: "Unclear ID",
        text: "The uploaded ID is blurry or cropped. Please re-submit a clear photo of the front and back.",
    },
    {
        label: "Expired ID",
        text: "The uploaded ID is already expired. Please re-submit a valid, unexpired government ID.",
    },
    {
        label: "Invalid business permit",
        text: "The business document is expired or not a valid registration. Please re-submit a current DTI, SEC or BIR certificate.",
    },
    {
        label: "Name mismatch",
        text: "The name on the documents does not match the agency or branch name. Please re-submit documents under the registered name.",
    },
    {
        label: "Wrong address",
        text: "The branch address does not match the address on the submitted documents. Please update the address or re-submit matching documents.",
    },
    {
        label: "Invalid TIN",
        text: "The TIN provided is incomplete or does not match the BIR certificate. Please correct it and re-submit.",
    },
];

function applyQuickFill(text: string) {
    reason.value = text;
    error.value = "";
}

type RejectionScope = "branch" | "both";

const SCOPES: { value: RejectionScope; label: string }[] = [
    { value: "branch", label: "Branch only" },
    { value: "both", label: "Agency & branch" },
];

const props = defineProps<{
    open: boolean;
    branchName?: string | null;
    refunds?: boolean;
    submitting?: boolean;
    serverError?: string;
    agencyUnverified?: boolean;
}>();

const canRejectAgency = computed(() => props.agencyUnverified && !props.refunds);

const rejectsAgencyWithRefund = computed(
    () => props.agencyUnverified && props.refunds,
);

const emit = defineEmits<{
    (event: "close"): void;
    (event: "confirm", reason: string, scope: RejectionScope): void;
}>();

const reason = ref("");
const error = ref("");
const scope = ref<RejectionScope>("branch");

const confirmLabel = computed(() =>
    props.refunds ? "Reject & Refund" : "Reject",
);

watch(
    () => props.open,
    (open) => {
        if (!open) return;

        reason.value = "";
        error.value = "";
        scope.value = "branch";
    },
);

function close() {
    if (props.submitting) return;

    emit("close");
}

function submit() {
    const value = reason.value.trim();

    if (value.length < 10) {
        error.value =
            "Give at least 10 characters so the agency can act on it.";
        return;
    }

    error.value = "";
    emit(
        "confirm",
        value,
        rejectsAgencyWithRefund.value
            ? "both"
            : canRejectAgency.value
              ? scope.value
              : "branch",
    );
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: translateY(12px) scale(0.98);
}
</style>
