<template>
    <div
        v-if="isSandbox"
        class="flex flex-col gap-2 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-violet-900 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:border-violet-500/25 dark:bg-violet-500/10 dark:text-violet-200"
        role="status"
    >
        <div class="flex items-start gap-3">
            <FlaskConical
                class="mt-0.5 h-4 w-4 shrink-0 text-violet-600 dark:text-violet-300"
            />

            <div class="text-xs leading-5 sm:text-sm">
                <p>
                    <span class="font-semibold"
                        >Sandbox mode: everything here is a simulation.</span
                    >
                    Anything you add, book or pay for stays in this sandbox and
                    never reaches your live branch.
                </p>
                <p class="mt-0.5 text-xs text-violet-700/80 dark:text-violet-300/70">
                    Demo staff and family accounts sign in with the password
                    <span class="font-mono font-semibold">password</span>.
                </p>
            </div>
        </div>

        <a
            v-if="showExit && liveUrl"
            :href="liveUrl"
            class="inline-flex shrink-0 items-center justify-center gap-1.5 self-start whitespace-nowrap rounded-lg border border-violet-300 bg-white px-4 py-2 text-sm font-semibold text-violet-800 transition hover:bg-violet-100 sm:self-auto dark:border-violet-500/30 dark:bg-transparent dark:text-violet-200 dark:hover:bg-violet-500/10"
        >
            <Radio class="h-4 w-4" />
            Back to live
        </a>
    </div>
</template>

<script setup lang="ts">
import { FlaskConical, Radio } from "lucide-vue-next";
import { useSandbox } from "~/composables/useSandbox";

defineProps<{
    showExit?: boolean;
}>();

const { isSandbox, liveUrl } = useSandbox();
</script>
