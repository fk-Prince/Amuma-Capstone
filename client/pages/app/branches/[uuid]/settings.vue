<template>
    <div class="w-full mx-auto space-y-4 pb-10 font-sans">
        <!-- Header: title and the sections -->
        <header
            class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="flex min-w-0 items-center gap-3">
                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary dark:bg-primary-500/15 dark:text-primary-300"
                >
                    <Settings class="h-5 w-5" />
                </span>

                <div class="min-w-0">
                    <h1
                        class="text-lg font-bold tracking-tight text-secondary sm:text-xl dark:text-white"
                    >
                        Branch Settings
                    </h1>

                    <p class="text-sm text-muted dark:text-gray-400">
                        Manage your branch information, agency details, images, and
                        operation settings.
                    </p>
                </div>
            </div>

            <!-- Save for the open section (images save one by one, so none there).
                 Both stay off until something in the form changes. -->
            <div
                v-if="activeTab !== 'images'"
                class="flex flex-wrap items-center gap-2 self-start sm:self-auto"
            >
                <span
                    v-if="tabRef?.dirty && !tabRef?.saving"
                    class="mr-1 inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 dark:text-amber-300"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                    Unsaved changes
                </span>

                <button
                    type="button"
                    :disabled="!tabRef?.dirty || Boolean(tabRef?.saving)"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                    @click="tabRef?.reset()"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    :disabled="!tabRef?.dirty || Boolean(tabRef?.saving)"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/20 transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                    @click="tabRef?.save()"
                >
                    <LoaderCircle v-if="tabRef?.saving" class="h-3.5 w-3.5 animate-spin" />
                    <Check v-else class="h-4 w-4" />
                    {{ tabRef?.saving ? "Saving..." : "Save changes" }}
                </button>
            </div>
            </div>

            <nav
                class="settings-tabs flex gap-1 overflow-x-auto border-t border-slate-100 px-2 sm:px-4 dark:border-white/10"
                aria-label="Settings sections"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    @click="activeTab = tab.value"
                    class="relative inline-flex shrink-0 items-center gap-2 whitespace-nowrap px-3 py-3 text-sm font-semibold transition sm:px-4"
                    :class="
                        activeTab === tab.value
                            ? 'text-primary dark:text-primary-300'
                            : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'
                    "
                    :aria-current="activeTab === tab.value ? 'page' : undefined"
                >
                    <component :is="tab.icon" class="w-4 h-4" />

                    {{ tab.label }}

                    <span
                        v-if="activeTab === tab.value"
                        class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-primary"
                    />
                </button>
            </nav>
        </header>

        <div
            class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div class="min-h-[65vh] p-4 sm:p-6">
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
                        ref="tabRef"
                        v-model:branch="branchStore.activeBranch"
                    />

                    <BranchAgencyTab
                        ref="tabRef"
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
                        ref="tabRef"
                        v-else-if="
                            activeTab === 'operation' &&
                            branchStore.activeBranch?.settings
                        "
                        v-model:setting="branchStore.activeBranch.settings"
                    />
                </Transition>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from "vue";
import {
    Building2,
    Landmark,
    Image,
    Settings,
    Check,
    LoaderCircle,
} from "lucide-vue-next";

import { useBranchStore } from "~/stores/branch";

import BranchGeneralTab from "~/components/sections/app/settings/BranchInfoTab.vue";
import BranchAgencyTab from "~/components/sections/app/settings/BranchAgencyTab.vue";
import BranchImagesTab from "~/components/sections/app/settings/BranchImagesTab.vue";
import BranchOperationTab from "~/components/sections/app/settings/BranchOperationTab.vue";

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
        label: "Agency Information",
        value: "agency",
        icon: Landmark,
    },
    {
        label: "Branch Information",
        value: "branch",
        icon: Building2,
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
];

const route = useRoute();

const tabFromQuery = () =>
    tabs.some((tab) => tab.value === route.query.tab)
        ? String(route.query.tab)
        : "agency";

const activeTab = ref(tabFromQuery());

// The open section's form (Agency, Branch or Operation), for the header Save.
const tabRef = ref<{
    save: () => unknown;
    reset: () => void;
    saving: boolean;
    dirty: boolean;
} | null>(null);

watch(
    () => route.query.tab,
    () => (activeTab.value = tabFromQuery()),
);

// On phones the tab row scrolls; keep the open tab in view.
watch(
    activeTab,
    async () => {
        if (import.meta.server) return;
        await nextTick();
        document
            .querySelector('.settings-tabs [aria-current="page"]')
            ?.scrollIntoView({ block: "nearest", inline: "nearest", behavior: "smooth" });
    },
    { immediate: true },
);
</script>

<style scoped>
/* The tab row scrolls sideways on phones without showing a bar. */
.settings-tabs {
    scrollbar-width: none;
}
.settings-tabs::-webkit-scrollbar {
    display: none;
}
</style>
