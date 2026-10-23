<template>
    <div v-if="isSandbox" class="fixed bottom-3 left-3 z-[9990] print:hidden">
        <button
            type="button"
            class="flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 shadow-md transition hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-200"
            :aria-expanded="open"
            @click="open = !open"
        >
            <FlaskConical class="h-3.5 w-3.5" />
            Sandbox
        </button>

        <div
            v-if="open"
            class="absolute bottom-10 left-0 w-72 rounded-xl border border-amber-200 bg-white p-4 text-xs leading-5 text-slate-600 shadow-lg dark:border-amber-500/20 dark:bg-secondary dark:text-gray-300"
        >
            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                You're in the AMUMA sandbox
            </p>
            <p class="mt-1">
                This is a copy of your branch filled with demo patients. Nothing
                here touches your real branch, and payments run in test mode.
            </p>
            <p class="mt-2">
                Demo staff and family accounts sign in with the password
                <span
                    class="font-mono font-semibold text-slate-900 dark:text-white"
                    >password</span
                >. Find their emails under Employee Management.
            </p>
            <a
                v-if="liveUrl"
                :href="liveUrl"
                class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90"
            >
                <Radio class="h-3.5 w-3.5" />
                Go back to live AMUMA
            </a>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { FlaskConical, Radio } from "lucide-vue-next";
import { useSandbox } from "~/composables/useSandbox";

const { isSandbox, liveUrl } = useSandbox();
const open = ref(false);
</script>
