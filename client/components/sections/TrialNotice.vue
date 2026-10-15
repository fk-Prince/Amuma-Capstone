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
                People can't find it in the booking search yet.
                <template v-if="canStart">
                    When you're ready, start your plan now.
                </template>
                <template v-else>
                    It will be listed once your plan starts.
                </template>
            </p>
        </div>

        <button
            v-if="canStart"
            type="button"
            class="inline-flex shrink-0 items-center justify-center self-start whitespace-nowrap rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.98] sm:self-auto"
            @click="startNow"
        >
            Start now
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { FlaskConical } from "lucide-vue-next";
import { useBranchStore } from "~/stores/branch";
import { usePermissions } from "~/composables/usePermission";
import { Modules } from "~/types/module";

const branchStore = useBranchStore();
const { hasModule } = usePermissions();

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

function startNow() {
    if (!branch.value?.uuid) return;

    navigateTo({
        path: `/app/branches/${branch.value.uuid}/manage-subscription`,
        query: { end_trial: "1" },
    });
}
</script>
