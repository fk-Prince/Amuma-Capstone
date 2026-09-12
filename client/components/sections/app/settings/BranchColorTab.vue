<template>
    <div class="w-full space-y-8">
        <div>
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">
                Color Preferences
            </h2>

            <p class="text-sm text-slate-500 mt-1 dark:text-gray-400">
                Pick a color combo for this branch's dashboard. Staff and
                owners viewing this branch will see it applied.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <button
                v-for="combo in BRANCH_THEME_COMBOS"
                :key="combo.value"
                type="button"
                class="flex flex-col items-center gap-2.5 rounded-2xl border p-4 text-center transition"
                :class="
                    selected === combo.value
                        ? 'border-primary-500 ring-2 ring-primary-100 dark:ring-primary-500/20'
                        : 'border-slate-200 hover:border-slate-300 dark:border-white/10 dark:hover:border-white/20'
                "
                @click="selected = combo.value"
            >
                <span
                    class="h-10 w-10 rounded-full shadow-inner"
                    :style="{ backgroundColor: combo.swatch }"
                />
                <span class="text-xs font-medium text-slate-700 dark:text-gray-300">
                    {{ combo.label }}
                </span>
            </button>
        </div>

        <div class="flex justify-end pt-2">
            <button
                type="button"
                :disabled="saving || selected === activeBranch?.settings?.theme_color"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                @click="save"
            >
                <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />
                {{ saving ? "Saving..." : "Save changes" }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import { LoaderCircle } from "lucide-vue-next";

import { useBranchStore } from "~/stores/branch";
import { branchSettingService } from "~/api/branch-setting/BranchSettingService";
import { useToast } from "~/composables/useToast";
import { BRANCH_THEME_COMBOS, applyBranchTheme } from "~/composables/useBranchTheme";

const { success, error } = useToast();
const route = useRoute();
const branchStore = useBranchStore();
const activeBranch = computed(() => branchStore.activeBranch);

const selected = ref(activeBranch.value?.settings?.theme_color ?? "ocean");
const saving = ref(false);

async function save() {
    saving.value = true;
    try {
        const res = await branchSettingService.update(
            route.params.uuid as string,
            {
                branch_uuid: route.params.uuid,
                theme_color: selected.value,
            },
        );

        await branchStore.refreshBranch();
        applyBranchTheme(selected.value);

        success(res.message ?? res);
    } catch (err: any) {
        error(err.message);
        console.log(err);
    } finally {
        saving.value = false;
    }
}
</script>