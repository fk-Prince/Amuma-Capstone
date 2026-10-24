<template>
    <div
        v-if="onTrial"
        class="flex flex-col gap-2 rounded-2xl border px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
        :class="
            locked
                ? 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200'
                : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200'
        "
        role="status"
    >
        <div class="flex items-start gap-3">
            <component
                :is="locked ? Lock : FlaskConical"
                class="mt-0.5 h-4 w-4 shrink-0"
                :class="
                    locked
                        ? 'text-rose-600 dark:text-rose-300'
                        : 'text-amber-600 dark:text-amber-300'
                "
            />

            <p class="text-xs leading-5 sm:text-sm">
                <template v-if="locked">
                    <span class="font-semibold"
                        >Your subscription is locked. The free trial has
                        ended.</span
                    >
                    This branch is read-only: you can still view everything,
                    but nothing can be added or changed.
                    <template v-if="canManage">
                        To unlock it, subscribe in the Manage Subscription tab.
                    </template>
                    <template v-else>
                        Ask your agency owner to subscribe in Manage
                        Subscription.
                    </template>
                </template>

                <template
                    v-else-if="
                        awaitingPayment &&
                        branch?.subscription_status === 'pending'
                    "
                >
                    <span class="font-semibold"
                        >Your free trial request is being reviewed.</span
                    >
                    The 2-week trial starts once an admin verifies your branch.
                    Nothing has been charged.
                </template>

                <template v-else-if="awaitingPayment">
                    <span class="font-semibold"
                        >This branch is on its free trial until
                        {{ trialEndsOn }}.</span
                    >
                    Nothing has been charged. Pay for your plan before then to
                    keep your branch running.
                </template>

                <template v-else>
                    <span class="font-semibold"
                        >This branch is on its free trial.</span
                    >
                    People can't find it in the booking search yet, so take this
                    time to set up your branch.
                    <template v-if="canStart">
                        When you're ready, start your plan now.
                    </template>
                    <template v-else>
                        It will be listed once your plan starts.
                    </template>
                </template>
            </p>
        </div>

        <div
            v-if="practiceLink || canPay || canStart"
            class="flex shrink-0 flex-wrap items-center gap-2 self-start sm:self-auto"
        >
            <a
                v-if="practiceLink && !locked"
                :href="practiceLink"
                title="Open a copy of this branch filled with demo patients. Nothing you do there touches your real branch."
                class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100 dark:border-amber-500/30 dark:bg-transparent dark:text-amber-200 dark:hover:bg-amber-500/10"
            >
                <PlayCircle class="h-4 w-4" />
                Try with demo data
            </a>

            <button
                v-if="canPay"
                type="button"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.98]"
                @click="goToSubscription({})"
            >
                Manage subscription
            </button>

            <button
                v-else-if="canStart"
                type="button"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.98]"
                @click="goToSubscription({ end_trial: '1' })"
            >
                Start now
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { FlaskConical, Lock, PlayCircle } from "lucide-vue-next";
import { useBranchStore } from "~/stores/branch";
import { usePermissions } from "~/composables/usePermission";
import { useSandbox } from "~/composables/useSandbox";
import { Modules } from "~/types/module";

const branchStore = useBranchStore();
const { hasModule } = usePermissions();
const { isSandbox, switchLink } = useSandbox();

const branch = computed(() => branchStore.activeBranch);

const onTrial = computed(
    () =>
        branch.value?.subscription_mode === "test" &&
        branch.value.subscription_status !== "rejected",
);

const awaitingPayment = computed(
    () => Boolean(branch.value?.subscription_awaiting_payment),
);

const locked = computed(() => Boolean(branch.value?.subscription_locked));

const canManage = computed(() => hasModule(Modules.ManageSubscription));

const canPay = computed(
    () =>
        awaitingPayment.value &&
        canManage.value &&
        !isSandbox.value &&
        branch.value?.subscription_status === "approved",
);

const canStart = computed(
    () =>
        !awaitingPayment.value &&
        branch.value?.subscription_status === "approved" &&
        canManage.value,
);

const trialEndsOn = computed(() => {
    const end = branch.value?.subscription_end_date;

    if (!end) return "the end of the trial";

    return new Date(end).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
});

const practiceLink = computed(() =>
    isSandbox.value || branch.value?.subscription_status !== "approved"
        ? ""
        : switchLink.value,
);

function goToSubscription(query: Record<string, string>) {
    if (!branch.value?.uuid) return;

    navigateTo({
        path: `/app/branches/${branch.value.uuid}/manage-subscription`,
        query,
    });
}
</script>
