<template>
    <div
        v-if="isSandbox"
        class="flex flex-col gap-3 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-violet-900 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:border-violet-500/25 dark:bg-violet-500/10 dark:text-violet-200"
        role="status"
    >
        <div class="flex items-start gap-3">
            <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300"
            >
                <FlaskConical class="h-4 w-4" />
            </span>

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
            v-if="exitLink"
            :href="exitLink"
            title="Back to your live branch"
            class="inline-flex shrink-0 items-center justify-center gap-1.5 self-start whitespace-nowrap rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 sm:self-auto"
        >
            <Radio class="h-4 w-4" />
            Go live
        </a>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { FlaskConical, Radio } from "lucide-vue-next";
import { useSandbox } from "~/composables/useSandbox";

const { isSandbox, liveUrl, switchLink } = useSandbox();

const exitLink = computed(() => switchLink.value || liveUrl.value);
</script>
