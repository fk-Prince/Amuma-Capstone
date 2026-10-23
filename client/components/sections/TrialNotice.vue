<template>
    <div
        v-if="onTrial"
        class="flex flex-col gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200"
        role="status"
    >
        <div class="flex items-start gap-3">
            <FlaskConical
                class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-300"
            />

            <p class="text-xs leading-5 sm:text-sm">
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
            </p>
        </div>

        <div
            v-if="practiceLink || canStart"
            class="flex shrink-0 flex-wrap items-center gap-2 self-start sm:self-auto"
        >
            <a
                v-if="practiceLink"
                :href="practiceLink"
                title="Open a copy of this branch filled with demo patients. Nothing you do there touches your real branch."
                class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100 dark:border-amber-500/30 dark:bg-transparent dark:text-amber-200 dark:hover:bg-amber-500/10"
            >
                <PlayCircle class="h-4 w-4" />
                Try with demo data
            </a>

            <button
                v-if="canStart"
                type="button"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.98]"
                @click="startNow"
            >
                Start now
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { FlaskConical, PlayCircle } from "lucide-vue-next";
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

const canStart = computed(
    () =>
        branch.value?.subscription_status === "approved" &&
        hasModule(Modules.ManageSubscription),
);

const practiceLink = computed(() => (isSandbox.value ? "" : switchLink.value));

function startNow() {
    if (!branch.value?.uuid) return;

    navigateTo({
        path: `/app/branches/${branch.value.uuid}/manage-subscription`,
        query: { end_trial: "1" },
    });
}
</script>
