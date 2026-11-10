<template>
    <div
        v-if="deactivated || expired"
        class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
        role="status"
    >
        <p class="flex items-start gap-3 text-xs leading-5 sm:text-sm">
            <Lock class="mt-0.5 h-4 w-4 shrink-0" />
            {{
                deactivated
                    ? "This branch is deactivated. It's view-only."
                    : "This branch's subscription has expired. It's view-only."
            }}
        </p>

        <button
            v-if="canManage"
            type="button"
            class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-[0.98]"
            @click="goToSubscription"
        >
            {{ deactivated ? "Manage subscription" : "Renew" }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Lock } from "lucide-vue-next";
import { useBranchStore } from "~/stores/branch";
import { usePermissions } from "~/composables/usePermission";
import { Modules } from "~/types/module";

const branchStore = useBranchStore();
const { canUpdate } = usePermissions();

const branch = computed(() => branchStore.activeBranch);
const deactivated = computed(() => Boolean(branch.value?.branch_deactivated));
const expired = computed(() => Boolean(branch.value?.subscription_expired));
const canManage = computed(() => canUpdate(Modules.ManageSubscription));

function goToSubscription() {
    if (!branch.value?.uuid) return;
    navigateTo(`/app/branches/${branch.value.uuid}/manage-subscription`);
}
</script>
