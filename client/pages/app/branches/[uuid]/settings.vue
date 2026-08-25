<template>
    <div class="w-full mx-auto p-4 md:p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Branch Settings</h1>

            <p class="text-sm text-slate-500 mt-1">
                Manage your branch information, agency details, images, and
                operation settings.
            </p>
        </div>

        <div
            class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
        >
            <div
                class="border-b border-slate-200 bg-slate-50/50 px-6 overflow-x-auto"
            >
                <div class="flex gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        @click="activeTab = tab.value"
                        class="relative flex items-center gap-2 px-4 py-4 text-sm font-medium whitespace-nowrap transition-all"
                        :class="
                            activeTab === tab.value
                                ? 'text-primary'
                                : 'text-slate-500 hover:text-slate-800'
                        "
                    >
                        <component :is="tab.icon" class="w-4 h-4" />

                        {{ tab.label }}

                        <span
                            class="absolute bottom-0 left-3 right-3 h-[3px] rounded-full transition"
                            :class="
                                activeTab === tab.value
                                    ? 'bg-primary'
                                    : 'bg-transparent'
                            "
                        />
                    </button>
                </div>
            </div>

            <div class="p-6 min-h-[65vh]">
                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-200"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <BranchGeneralTab
                        v-if="activeTab === 'branch'"
                        v-model:branch="branchStore.activeBranch"
                    />

                    <BranchAgencyTab
                        v-else-if="
                            activeTab === 'agency' && branchStore.activeBranch
                        "
                        v-model:agency="branchStore.activeBranch.agency"
                        :uuid="branchStore.activeBranch.uuid"
                    />

                    <BranchImagesTab
                        v-else-if="
                            activeTab === 'images' && branchStore.activeBranch
                        "
                        v-model:images="branchStore.activeBranch.images"
                        :uuid="branchStore.activeBranch.uuid"
                    />

                    <BranchOperationTab
                        v-else-if="
                            activeTab === 'operation' &&
                            branchStore.activeBranch?.settings
                        "
                        v-model:setting="branchStore.activeBranch.settings"
                    />

                    <BranchRenewalTab
                        v-else-if="
                            activeTab === 'renewal' && branchStore.activeBranch
                        "
                        :uuid="branchStore.activeBranch.uuid"
                    />
                </Transition>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Building2, Landmark, Image, Settings, RefreshCw } from "lucide-vue-next";

import { useBranchStore } from "~/stores/branch";

import BranchGeneralTab from "~/components/sections/app/settings/BranchInfoTab.vue";
import BranchAgencyTab from "~/components/sections/app/settings/BranchAgencyTab.vue";
import BranchImagesTab from "~/components/sections/app/settings/BranchImagesTab.vue";
import BranchOperationTab from "~/components/sections/app/settings/BranchOperationTab.vue";
import BranchRenewalTab from "~/components/sections/app/settings/BranchRenewalTab.vue";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Settings",
});

const branchStore = useBranchStore();

const tabs = [
    {
        label: "Branch Information",
        value: "branch",
        icon: Building2,
    },
    {
        label: "Agency Information",
        value: "agency",
        icon: Landmark,
    },
    {
        label: "Images",
        value: "images",
        icon: Image,
    },
    {
        label: "Operation Settings",
        value: "operation",
        icon: Settings,
    },
    {
        label: "Branch Renewal",
        value: "renewal",
        icon: RefreshCw,
    },
];

const activeTab = ref("branch");
</script>
