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
                        <p
                            class="text-xs font-semibold text-gray-400 dark:text-gray-500"
                        >
                            Admission Timeline
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
                    <div v-if="loading" class="space-y-4 animate-pulse">
                        <div
                            v-for="row in 3"
                            :key="row"
                            class="h-16 rounded-xl bg-gray-100 dark:bg-white/5"
                        />
                    </div>

                    <AdmissionTimeline v-else :admissions="admissions" flat />
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { X } from "lucide-vue-next";
import AdmissionTimeline from "~/components/sections/app/Admission/AdmissionTimeline.vue";

withDefaults(
    defineProps<{
        open?: boolean;
        loading?: boolean;
        patientName?: string;
        admissions?: any[];
    }>(),
    {
        open: false,
        loading: false,
        patientName: "",
        admissions: () => [],
    },
);

const emit = defineEmits<{
    (e: "close"): void;
}>();
</script>
