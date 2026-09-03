<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="emit('close')"
            />

            <div
                class="relative z-10 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500">
                            Diagnosis
                        </p>

                        <h2
                            class="mt-0.5 truncate text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            {{ patientName }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                        @click="emit('close')"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <ul v-if="diagnoses.length" class="space-y-3">
                        <li
                            v-for="(item, index) in diagnoses"
                            :key="index"
                            class="rounded-xl border border-gray-100 p-4 dark:border-white/10"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    {{ item.diagnosis || "Diagnosis" }}
                                </p>

                                <span
                                    class="rounded-full bg-gray-50 px-2.5 py-1 text-[11px] font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400"
                                >
                                    {{
                                        item.diagnosis_date
                                            ? formatDate(item.diagnosis_date)
                                            : "No date recorded"
                                    }}
                                </span>
                            </div>

                            <p
                                v-if="item.diagnosis_notes"
                                class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
                            >
                                {{ item.diagnosis_notes }}
                            </p>

                            <a
                                v-if="item.diagnosis_file"
                                :href="item.diagnosis_file"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-3 inline-flex max-w-full items-center gap-1.5 rounded-lg bg-primary-50 px-2.5 py-1.5 text-[11px] font-semibold text-primary-600 transition hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/15"
                            >
                                <component
                                    :is="fileIcon(item.diagnosis_file)"
                                    class="h-3.5 w-3.5 shrink-0"
                                />

                                <span class="truncate">
                                    {{ fileLabel(item.diagnosis_file) }}
                                </span>

                                <ExternalLink
                                    class="h-3 w-3 shrink-0 opacity-60"
                                />
                            </a>
                        </li>
                    </ul>

                    <p v-else class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                        No diagnosis has been recorded yet.
                    </p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { X, ExternalLink, FileText, FileImage } from "lucide-vue-next";
import { formatDate } from "~/utils/time";
import type { PortalDiagnosis } from "~/types/patient";

withDefaults(
    defineProps<{
        open?: boolean;
        patientName?: string;
        diagnoses?: PortalDiagnosis[];
    }>(),
    {
        open: false,
        patientName: "",
        diagnoses: () => [],
    },
);

const emit = defineEmits<{ (e: "close"): void }>();

const IMAGE_EXTENSIONS = ["jpg", "jpeg", "png", "webp", "gif", "heic"];

function fileExtension(url: string) {
    return (
        url.split("?")[0]?.split("#")[0]?.split(".").pop()?.toLowerCase() ?? ""
    );
}

function fileIcon(url: string) {
    return IMAGE_EXTENSIONS.includes(fileExtension(url)) ? FileImage : FileText;
}

// Uploads are stored under a generated UUID name, so the filename itself says
// nothing useful to a family member — label the link by what it opens instead.
function fileLabel(url: string) {
    const extension = fileExtension(url);

    if (IMAGE_EXTENSIONS.includes(extension)) return "View attached image";
    if (extension === "pdf") return "View attached PDF";

    return "View attached file";
}
</script>
