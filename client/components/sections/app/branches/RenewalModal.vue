<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    class="relative flex max-h-[94vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Subscription detail"
                >
                    <button
                        type="button"
                        aria-label="Close dialog"
                        class="absolute right-3 top-3 z-10 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>

                    <div class="min-h-0 flex-1 overflow-y-auto p-6 pr-12">
                        <BranchRenewalTab :uuid="uuid" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { X } from "lucide-vue-next";
import BranchRenewalTab from "~/components/sections/app/settings/BranchRenewalTab.vue";

defineProps<{
    open: boolean;
    uuid: string;
}>();

const emit = defineEmits<{
    close: [];
}>();
</script>
