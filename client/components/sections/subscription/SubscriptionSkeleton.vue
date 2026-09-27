<template>
    <div
        class="flex w-full min-h-[560px] items-start gap-3 sm:gap-8"
        role="status"
        aria-busy="true"
        aria-label="Loading subscription plans"
    >
        <aside class="shrink-0 sm:w-60">
            <ol class="flex flex-col">
                <li v-for="index in STEP_COUNT" :key="index" class="flex gap-3">
                    <div class="flex flex-col items-center self-stretch">
                        <div
                            class="sk h-7 w-7 sm:h-9 sm:w-9 rounded-full"
                            :class="
                                index === 1
                                    ? 'bg-primary-200 dark:bg-primary-500/30'
                                    : 'bg-muted-light dark:bg-white/10'
                            "
                        ></div>

                        <div
                            v-if="index < STEP_COUNT"
                            class="my-1 h-8 w-px flex-1 bg-muted-light sm:min-h-10 dark:bg-white/10"
                        ></div>
                    </div>

                    <div class="hidden space-y-2 pt-2 sm:block">
                        <div
                            class="sk h-3 rounded bg-muted-light/70 dark:bg-white/10"
                            :style="{ width: LABEL_WIDTHS[index - 1] }"
                        ></div>
                        <div
                            class="sk h-2.5 w-32 rounded bg-muted-light/50 dark:bg-white/5"
                        ></div>
                    </div>
                </li>
            </ol>
        </aside>

        <div class="min-w-0 flex-1 space-y-8">
            <div class="flex flex-col items-center gap-3">
                <div
                    class="sk h-11 w-[250px] max-w-full rounded-full border border-muted-light bg-muted-light/50 dark:border-white/10 dark:bg-white/5"
                ></div>
                <div
                    class="sk h-3 w-64 max-w-full rounded bg-muted-light/60 dark:bg-white/10"
                ></div>
            </div>

            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch"
            >
                <div
                    v-for="index in 3"
                    :key="index"
                    class="relative flex h-full flex-col gap-3 rounded-xl border p-5 sm:p-6"
                    :class="
                        index === 1
                            ? 'border-primary-200 bg-primary-50/60 dark:border-primary-500/20 dark:bg-primary-500/10'
                            : 'border-muted-light dark:border-white/10'
                    "
                >
                    <div
                        class="sk absolute top-4 right-4 h-5 w-5 rounded-full"
                        :class="
                            index === 1
                                ? 'bg-primary-200 dark:bg-primary-500/30'
                                : 'bg-muted-light dark:bg-white/10'
                        "
                    ></div>

                    <div
                        class="sk h-10 w-10 rounded-lg bg-primary-100 dark:bg-primary-500/15"
                    ></div>

                    <div class="space-y-2.5">
                        <div
                            class="sk h-4 w-32 max-w-full rounded bg-muted-light dark:bg-white/10"
                        ></div>
                        <div class="space-y-2">
                            <div
                                class="sk h-3 w-full rounded bg-muted-light/60 dark:bg-white/5"
                            ></div>
                            <div
                                class="sk h-3 w-5/6 rounded bg-muted-light/60 dark:bg-white/5"
                            ></div>
                            <div
                                class="sk h-3 w-2/3 rounded bg-muted-light/60 dark:bg-white/5"
                            ></div>
                        </div>
                    </div>

                    <div
                        class="mt-auto flex items-center justify-between gap-2 border-t border-muted-light/70 pt-4 dark:border-white/10"
                    >
                        <div
                            class="sk h-3 w-14 rounded bg-muted-light/60 dark:bg-white/5"
                        ></div>
                        <div
                            class="sk h-6 w-24 rounded bg-primary-200 dark:bg-primary-500/25"
                        ></div>
                    </div>
                </div>
            </div>

            <div
                class="flex items-center justify-between border-t border-slate-200 pt-6 dark:border-white/10"
            >
                <div class="w-[110px]"></div>
                <div
                    class="sk h-10 w-32 rounded-xl bg-primary-200 dark:bg-primary-500/25"
                ></div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const STEP_COUNT = 5;
const LABEL_WIDTHS = ["4rem", "5.5rem", "5.25rem", "4.5rem", "3.5rem"];
</script>

<style scoped>
.sk {
    position: relative;
    overflow: hidden;
}

.sk::after {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.6),
        transparent
    );
    animation: sk-shimmer 1.5s ease-in-out infinite;
}

:global(.dark) .sk::after {
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.08),
        transparent
    );
}

@keyframes sk-shimmer {
    100% {
        transform: translateX(100%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .sk::after {
        animation: none;
    }
}
</style>
