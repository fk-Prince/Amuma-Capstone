<template>
    <div
        v-if="activeBranch && isSubscriptionPending"
        class="min-h-screen-header bg-light flex items-center justify-center p-4 sm:p-6 lg:p-8 dark:bg-surface"
    >
        <SubscriptionReview />
    </div>

    <div v-else-if="!activeBranch" class="min-h-screen-header p-4">
        <div class="space-y-3">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="h-24 animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
                />
            </div>
            <div
                class="h-[300px] animate-pulse rounded-xl bg-white/50 dark:bg-white/5"
            />
        </div>
    </div>

    <div v-else class="min-h-screen-header">
        <GeneralOverview
            v-if="view === 'general'"
            :key="`general-${uuid}`"
        />

        <div v-else class="p-4">
            <AdmissionOverview
                v-if="view === 'admission'"
                :key="`admission-${uuid}`"
                :branch-uuid="uuid"
            />

            <CashierOverview
                v-else-if="view === 'cashier'"
                :key="`cashier-${uuid}`"
                :branch-uuid="uuid"
            />

            <CaregiverOverview
                v-else
                :key="`care-${uuid}`"
                :branch-uuid="uuid"
                :role-label="role === 'nurse' ? 'Nurse' : 'Caregiver'"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";

import SubscriptionReview from "~/components/sections/app/Dashboard/SubscriptionReview.vue";
import AdmissionOverview from "~/components/sections/app/Dashboard/overview/AdmissionOverview.vue";
import CashierOverview from "~/components/sections/app/Dashboard/overview/CashierOverview.vue";
import CaregiverOverview from "~/components/sections/app/Dashboard/overview/CaregiverOverview.vue";
import GeneralOverview from "~/components/sections/app/Dashboard/overview/GeneralOverview.vue";
import { useBranchStore } from "~/stores/branch";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Dashboard",
});

const route = useRoute();
const branchStore = useBranchStore();

const uuid = computed(() => route.params.uuid as string);
const activeBranch = computed(() => branchStore.activeBranch);

const isSubscriptionPending = computed(() => {
    const branch = activeBranch.value;
    return branch?.agency?.status !== "verified" || branch?.status !== "verified";
});

// role_name comes from the API as agency_owner, branch_manager, admission,
// cashier, nurse or caregiver. Normalise so "Agency Owner" also matches.
const role = computed(() =>
    (activeBranch.value?.role_name ?? "")
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, "_"),
);

type View = "general" | "admission" | "cashier" | "care";

const view = computed<View>(() => {
    switch (role.value) {
        case "admission":
            return "admission";
        case "cashier":
            return "cashier";
        case "caregiver":
        case "nurse":
            return "care";
        default:
            // agency_owner and branch_manager use the original dashboard.
            // The agency owner picks the branch from the header dropdown.
            return "general";
    }
});
</script>