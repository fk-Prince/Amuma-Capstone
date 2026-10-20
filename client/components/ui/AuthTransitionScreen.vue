<script setup lang="ts">
import ThemedLogo from "~/components/ui/ThemedLogo.vue";

withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        showThemeToggle?: boolean;
    }>(),
    {
        title: "Signing you out",
        subtitle: "See you again soon",
        showThemeToggle: false,
    },
);

const currentYear = new Date().getFullYear();
</script>

<template>
    <ClientOnly>
        <Teleport to="body">
            <Transition
                appear
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-page dark:bg-secondary-950"
                    role="status"
                    aria-live="polite"
                    aria-busy="true"
                >
                    <!-- Soft background: dot grid, glow, and slow waves -->
                    <div
                        class="pointer-events-none absolute inset-0 overflow-hidden"
                        aria-hidden="true"
                    >
                        <div
                            class="absolute inset-0 opacity-60 [background-image:radial-gradient(circle,rgb(var(--primary-500)/0.22)_1px,transparent_1px)] [background-size:26px_26px] [mask-image:radial-gradient(ellipse_70%_60%_at_50%_42%,#000_15%,transparent_75%)] dark:opacity-40"
                        />
                        <div
                            class="absolute left-1/2 top-[42%] h-[380px] w-[380px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-500/10 blur-3xl dark:bg-primary-500/15"
                        />
                        <svg
                            class="atx-wave atx-wave-back absolute bottom-0 left-0 h-28 w-[200%] text-primary-500/[0.07] dark:text-primary-400/[0.08]"
                            viewBox="0 0 1200 120"
                            preserveAspectRatio="none"
                            fill="currentColor"
                        >
                            <path
                                d="M0 60 Q150 0 300 60 T600 60 T900 60 T1200 60 V120 H0 Z"
                            />
                        </svg>
                        <svg
                            class="atx-wave atx-wave-front absolute bottom-0 left-0 h-20 w-[200%] text-primary-500/[0.1] dark:text-primary-400/[0.1]"
                            viewBox="0 0 1200 120"
                            preserveAspectRatio="none"
                            fill="currentColor"
                        >
                            <path
                                d="M0 70 Q150 20 300 70 T600 70 T900 70 T1200 70 V120 H0 Z"
                            />
                        </svg>
                    </div>

                    <ThemeToggle
                        v-if="showThemeToggle"
                        class="absolute right-6 top-6 z-10 text-slate-500 hover:bg-slate-900/5 dark:text-gray-400 dark:hover:bg-white/10"
                    />

                    <div
                        class="relative z-10 flex flex-col items-center px-6 text-center"
                    >
                        <!-- Logo on a white rectangle -->
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-white/10"
                        >
                            <ThemedLogo class="atx-breathe h-9 w-9" />
                        </div>

                        <p
                            class="mt-4 text-xl font-bold tracking-tight text-primary-500 dark:text-primary-400"
                        >
                            AMUMA
                        </p>

                        <p
                            class="mt-4 text-base font-semibold text-slate-900 dark:text-white"
                        >
                            {{ title }}
                        </p>

                        <p
                            v-if="subtitle"
                            class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                        >
                            {{ subtitle }}
                        </p>

                        <div
                            class="mt-6 flex items-center gap-1.5"
                            aria-hidden="true"
                        >
                            <span
                                v-for="i in 3"
                                :key="i"
                                class="h-1.5 w-1.5 animate-bounce rounded-full bg-primary-500 motion-reduce:animate-none dark:bg-primary-400"
                                :style="{ animationDelay: `${(i - 1) * 0.15}s` }"
                            />
                        </div>
                    </div>

                    <p
                        class="absolute bottom-6 z-10 text-xs text-slate-400 dark:text-gray-500"
                    >
                        © {{ currentYear }} AMUMA. All rights reserved.
                    </p>
                </div>
            </Transition>
        </Teleport>
    </ClientOnly>
</template>

<style scoped>
/* The logo breathes: a slow, smooth grow and shrink. */
.atx-breathe {
    transform-origin: center;
    will-change: transform;
    animation: atx-breathe 2.4s ease-in-out infinite;
}

@keyframes atx-breathe {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* Background waves drift sideways. The svg is twice the screen width and
   holds exactly two wave periods, so moving it by half its width loops
   without a jump. */
.atx-wave {
    animation: atx-drift 34s linear infinite;
}

.atx-wave-back {
    animation-duration: 48s;
    animation-direction: reverse;
}

@keyframes atx-drift {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-50%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .atx-breathe,
    .atx-wave {
        animation: none;
    }
}
</style>