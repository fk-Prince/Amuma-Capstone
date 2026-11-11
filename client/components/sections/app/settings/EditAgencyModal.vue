<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="flex w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="edit-agency-title"
                >
                    <div
                        class="flex items-center justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <Landmark class="h-5 w-5" />
                            </div>
                            <div>
                                <h2
                                    id="edit-agency-title"
                                    class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                                >
                                    Edit Agency
                                </h2>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    These details apply to every branch of your agency.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            aria-label="Close dialog"
                            class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                            :disabled="formRef?.saving"
                            @click="close"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="p-4 sm:p-6">
                        <BranchAgencyTab
                            ref="formRef"
                            v-model:agency="agency"
                            :uuid="uuid"
                        />
                    </div>

                    <div
                        class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <span
                            v-if="formRef?.dirty && !formRef?.saving"
                            class="mr-auto inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 dark:text-amber-300"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                            Unsaved changes
                        </span>

                        <button
                            type="button"
                            :disabled="Boolean(formRef?.saving)"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-primary hover:text-primary disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                            @click="close"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            :disabled="!formRef?.dirty || Boolean(formRef?.saving)"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/20 transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                            @click="save"
                        >
                            <LoaderCircle v-if="formRef?.saving" class="h-3.5 w-3.5 animate-spin" />
                            <Check v-else class="h-4 w-4" />
                            {{ formRef?.saving ? "Saving..." : "Save changes" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Check, Landmark, LoaderCircle, X } from "lucide-vue-next";
import BranchAgencyTab from "~/components/sections/app/settings/BranchAgencyTab.vue";
import type { Agency } from "~/types/agency";

defineProps<{
    uuid: string;
}>();

const agency = defineModel<Agency | null | undefined>("agency", { required: true });

const emit = defineEmits<{
    close: [];
    saved: [];
}>();

const formRef = ref<{
    save: () => Promise<boolean>;
    reset: () => void;
    saving: boolean;
    dirty: boolean;
} | null>(null);

async function save() {
    if (await formRef.value?.save()) emit("saved");
}

function close() {
    if (formRef.value?.saving) return;
    formRef.value?.reset();
    emit("close");
}
</script>
