<template>
    <section class="py-20 font-sans">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="flex items-center justify-center gap-2.5">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"
                        ></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                    </span>
                    <span
                        class="text-xs font-bold tracking-[0.16em] uppercase text-primary dark:text-primary-300"
                    >
                        Simple process
                    </span>
                </div>

                <h2
                    class="mt-3 text-2xl md:text-3xl font-bold text-secondary dark:text-white"
                >
                    Book care in 4 steps
                </h2>

                <p
                    class="mt-4 text-sm text-muted max-w-xl mx-auto leading-relaxed dark:text-gray-400"
                >
                    Easily find caregivers, book services, monitor care
                    activities, and manage payments all in one simple and secure
                    platform.
                </p>
            </div>

            <div ref="stepsRef" class="relative" :class="{ 'steps-visible': stepsVisible }">
                <div class="relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-10">
                    <div
                        v-for="(module, index) in modules"
                        :key="module.title"
                        class="relative flex gap-4 sm:block"
                    >
                        <!-- Joins this step's icon to the next one: drawn in when the section scrolls into view -->
                        <div
                            v-if="index < modules.length - 1"
                            class="step-line pointer-events-none absolute left-[4.5rem] top-7 hidden h-0.5 w-[calc(100%-4rem)] rounded-full bg-primary-100 dark:bg-primary-500/15"
                            :class="index % 2 === 0 ? 'sm:block' : 'lg:block'"
                            aria-hidden="true"
                        >
                            <span
                                class="step-line-fill absolute inset-0 rounded-full bg-gradient-to-r from-primary-300 to-primary dark:from-primary-500/40 dark:to-primary-400"
                                :style="{ transitionDelay: `${index * STEP_DELAY + 300}ms` }"
                            />
                            <span
                                class="step-pulse absolute top-1/2 h-2 w-2 -translate-y-1/2 rounded-full bg-primary shadow-[0_0_0_4px_rgb(var(--primary-500)/0.2)] dark:bg-primary-300"
                                :style="{ animationDelay: `${(modules.length - 1) * STEP_DELAY + 900 + index * 500}ms` }"
                            />
                        </div>

                        <!-- Same link, vertical, when the steps stack on phones -->
                        <div
                            v-if="index < modules.length - 1"
                            class="step-line step-line-v pointer-events-none absolute left-7 top-[4.25rem] -bottom-[2.25rem] w-0.5 -translate-x-1/2 rounded-full bg-primary-100 sm:hidden dark:bg-primary-500/15"
                            aria-hidden="true"
                        >
                            <span
                                class="step-line-fill absolute inset-0 rounded-full bg-gradient-to-b from-primary-300 to-primary dark:from-primary-500/40 dark:to-primary-400"
                                :style="{ transitionDelay: `${index * STEP_DELAY + 300}ms` }"
                            />
                            <span
                                class="step-pulse absolute left-1/2 h-2 w-2 -translate-x-1/2 rounded-full bg-primary shadow-[0_0_0_4px_rgb(var(--primary-500)/0.2)] dark:bg-primary-300"
                                :style="{ animationDelay: `${(modules.length - 1) * STEP_DELAY + 900 + index * 500}ms` }"
                            />
                        </div>

                        <div class="relative inline-flex shrink-0 self-start">
                            <div
                                class="step-icon flex h-14 w-14 items-center justify-center rounded-full border border-muted-light bg-white dark:bg-secondary dark:border-white/10"
                                :style="{
                                    transitionDelay: `${index * STEP_DELAY}ms`,
                                    animationDelay: `${index * STEP_DELAY}ms`,
                                }"
                            >
                                <component
                                    :is="module.icon"
                                    class="h-5 w-5 text-primary dark:text-primary-300"
                                />
                            </div>

                            <span
                                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white ring-2 ring-white dark:ring-secondary"
                            >
                                {{ index + 1 }}
                            </span>
                        </div>

                        <div class="step-text min-w-0" :style="{ transitionDelay: `${index * STEP_DELAY}ms` }">
                            <h3 class="mt-1 text-sm font-bold text-secondary sm:mt-4 dark:text-white">
                                {{ module.title }}
                            </h3>

                            <p class="mt-1.5 text-xs text-muted leading-relaxed dark:text-gray-400">
                                {{ module.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import { Search, CalendarCheck, Bell, CreditCard } from "lucide-vue-next";

// Steps light up one after another, and the lines between them draw in,
// the first time the section scrolls into view.
const STEP_DELAY = 650;
const stepsRef = ref(null);
const stepsVisible = ref(false);
let observer = null;

onMounted(() => {
    if (!("IntersectionObserver" in window) || !stepsRef.value) {
        stepsVisible.value = true;
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            if (!entries.some((entry) => entry.isIntersecting)) return;

            stepsVisible.value = true;
            observer?.disconnect();
        },
        { threshold: 0.35 },
    );

    observer.observe(stepsRef.value);
});

onBeforeUnmount(() => observer?.disconnect());

const modules = [
    {
        icon: Search,
        title: "Find care services",
        description:
            "Search for caregivers, agencies, and nearby care facilities based on your needs.",
    },
    {
        icon: CalendarCheck,
        title: "Book appointments",
        description:
            "Schedule homecare visits or reserve available care services quickly and easily.",
    },
    {
        icon: Bell,
        title: "Stay updated",
        description:
            "Receive updates about care activities, schedules, and patient information.",
    },
    {
        icon: CreditCard,
        title: "Payments",
        description:
            "View invoices and manage secure payments for care services.",
    },
];
</script>
<style scoped>
.step-line-fill {
    transform: scaleX(0);
    transform-origin: left center;
    transition: transform 650ms cubic-bezier(0.65, 0, 0.35, 1);
}

.step-line-v .step-line-fill {
    transform: scaleY(0);
    transform-origin: center top;
}

.steps-visible .step-line-fill {
    transform: none;
}

.step-pulse {
    left: 0;
    opacity: 0;
}

.step-line-v .step-pulse {
    top: 0;
}

.steps-visible .step-pulse {
    animation: step-pulse-x 2.6s ease-in-out infinite;
}

.steps-visible .step-line-v .step-pulse {
    animation-name: step-pulse-y;
}

@keyframes step-pulse-x {
    0% { left: 0; opacity: 0; }
    15% { opacity: 1; }
    70% { left: calc(100% - 0.5rem); opacity: 1; }
    85%, 100% { left: calc(100% - 0.5rem); opacity: 0; }
}

@keyframes step-pulse-y {
    0% { top: 0; opacity: 0; }
    15% { opacity: 1; }
    70% { top: calc(100% - 0.5rem); opacity: 1; }
    85%, 100% { top: calc(100% - 0.5rem); opacity: 0; }
}

.step-icon {
    transition:
        border-color 400ms ease,
        box-shadow 400ms ease,
        transform 400ms ease;
}

.steps-visible .step-icon {
    border-color: rgb(var(--primary-500));
    box-shadow: 0 0 0 6px rgb(var(--primary-500) / 0.12);
    animation: step-pop 450ms ease-out both;
}

.step-text {
    opacity: 0.45;
    transform: translateY(4px);
    transition:
        opacity 450ms ease,
        transform 450ms ease;
}

.steps-visible .step-text {
    opacity: 1;
    transform: none;
}

@keyframes step-pop {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}

@media (prefers-reduced-motion: reduce) {
    .step-line-fill,
    .step-text {
        transform: none;
        transition: none;
    }

    .step-text {
        opacity: 1;
    }

    .step-icon {
        transition: none;
        animation: none !important;
    }

    .step-pulse {
        display: none;
    }
}
</style>
