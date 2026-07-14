<template>
    <div class="w-full mx-auto p-4 md:p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Branch Settings</h1>
            <p class="text-sm text-gray-500 mt-1">
                Manage your branch information and preferences.
            </p>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm min-h-[70vh]">
            <div class="border-b px-6 overflow-x-auto">
                <div class="flex gap-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        @click="activeTab = tab.value"
                        class="relative flex items-center gap-2 py-4 text-sm font-medium whitespace-nowrap transition"
                        :class="
                            activeTab === tab.value
                                ? 'text-primary'
                                : 'text-gray-500 hover:text-gray-700'
                        "
                    >
                        {{ tab.label }}

                        <span
                            v-if="activeTab === tab.value"
                            class="absolute bottom-0 left-0 h-[3px] w-full rounded-full bg-primary"
                        />
                    </button>
                </div>
            </div>

            <div
                class="p-6 w-full min-h-[60vh] flex items-center justify-center"
            >
                <BranchGeneralTab
                    v-if="activeTab === 'branch'"
                    v-model:branch="branchStore.activeBranch"
                />

                <BranchAgencyTab
                    v-if="activeTab === 'agency' && branchStore.activeBranch"
                    v-model:agency="branchStore.activeBranch.agency"
                    :uuid="branchStore.activeBranch.uuid"
                />

                <BranchImagesTab
                    v-if="activeTab === 'images' && branchStore.activeBranch"
                    v-model:images="branchStore.activeBranch.images"
                    :uuid="branchStore.activeBranch.uuid"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useBranchStore } from "~/stores/branch";
import BranchGeneralTab from "~/components/sections/app/settings/BranchInfoTab.vue";
import BranchAgencyTab from "~/components/sections/app/settings/BranchAgencyTab.vue";
import BranchImagesTab from "~/components/sections/app/settings/BranchImagesTab.vue";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Settings" });

const branchStore = useBranchStore();
const tabs = [
    { label: "Branch Information", value: "branch" },
    { label: "Agency Information", value: "agency" },
    { label: "Images", value: "images" },
];

const activeTab = ref("branch");
</script>
