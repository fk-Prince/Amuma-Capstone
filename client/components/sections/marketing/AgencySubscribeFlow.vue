<template>
    <section
        id="get-started"
        class="scroll-mt-32 bg-slate-50 py-24 font-sans dark:bg-surface"
    >
        <div class="mx-auto w-[94%] max-w-[1200px] px-10 max-sm:px-4">
            <div class="mb-16 text-center">
                <div class="flex items-center justify-center gap-2.5">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex h-2 w-2 rounded-full bg-primary"
                        ></span>
                    </span>
                    <span
                        class="text-xs font-bold uppercase tracking-[0.16em] text-primary dark:text-primary-300"
                    >
                        How to get started
                    </span>
                </div>

                <h2
                    class="mt-3 text-2xl font-bold text-secondary md:text-3xl dark:text-white"
                >
                    Subscribe your agency in 6 steps
                </h2>

                <p
                    class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-muted dark:text-gray-400"
                >
                    From sign-up to your first booking in a few minutes. Start
                    with a 2-week free trial and pay only when you're ready.
                </p>
            </div>

            <div
                ref="stepsRef"
                class="relative"
                :class="{ 'steps-visible': stepsVisible }"
            >
                <div
                    class="relative grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
                >
                    <div
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="relative flex gap-4 sm:block"
                    >
                        <!-- Line to the next step in the same row; drawn in on scroll -->
                        <div
                            v-if="index < steps.length - 1"
                            class="step-line pointer-events-none absolute left-[4.5rem] top-7 hidden h-0.5 w-[calc(100%-4rem)] rounded-full bg-primary-100 dark:bg-primary-500/15"
                            :class="lineClasses(index)"
                            aria-hidden="true"
                        >
                            <span
                                class="step-line-fill absolute inset-0 rounded-full bg-gradient-to-r from-primary-300 to-primary dark:from-primary-500/40 dark:to-primary-400"
                                :style="{
                                    transitionDelay: `${index * STEP_DELAY + 300}ms`,
                                }"
                            />
                            <span
                                class="step-pulse absolute top-1/2 h-2 w-2 -translate-y-1/2 rounded-full bg-primary shadow-[0_0_0_4px_rgb(var(--primary-500)/0.2)] dark:bg-primary-300"
                                :style="{
                                    animationDelay: `${(steps.length - 1) * STEP_DELAY + 900 + index * 450}ms`,
                                }"
                            />
                        </div>

                        <!-- Same link, vertical, when the steps stack on phones -->
                        <div
                            v-if="index < steps.length - 1"
                            class="step-line step-line-v pointer-events-none absolute -bottom-[2.25rem] left-7 top-[4.25rem] w-0.5 -translate-x-1/2 rounded-full bg-primary-100 sm:hidden dark:bg-primary-500/15"
                            aria-hidden="true"
                        >
                            <span
                                class="step-line-fill absolute inset-0 rounded-full bg-gradient-to-b from-primary-300 to-primary dark:from-primary-500/40 dark:to-primary-400"
                                :style="{
                                    transitionDelay: `${index * STEP_DELAY + 300}ms`,
                                }"
                            />
                            <span
                                class="step-pulse absolute left-1/2 h-2 w-2 -translate-x-1/2 rounded-full bg-primary shadow-[0_0_0_4px_rgb(var(--primary-500)/0.2)] dark:bg-primary-300"
                                :style="{
                                    animationDelay: `${(steps.length - 1) * STEP_DELAY + 900 + index * 450}ms`,
                                }"
                            />
                        </div>

                        <div class="relative inline-flex shrink-0 self-start">
                            <div
                                class="step-icon flex h-14 w-14 items-center justify-center rounded-full border border-muted-light bg-white dark:border-white/10 dark:bg-secondary"
                                :style="{
                                    transitionDelay: `${index * STEP_DELAY}ms`,
                                    animationDelay: `${index * STEP_DELAY}ms`,
                                }"
                            >
                                <component
                                    :is="step.icon"
                                    class="h-5 w-5 text-primary dark:text-primary-300"
                                />
                            </div>

                            <span
                                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white ring-2 ring-white dark:ring-secondary"
                            >
                                {{ index + 1 }}
                            </span>
                        </div>

                        <div
                            class="step-text min-w-0"
                            :style="{ transitionDelay: `${index * STEP_DELAY}ms` }"
                        >
                            <h3
                                class="mt-1 text-sm font-bold text-secondary sm:mt-4 dark:text-white"
                            >
                                {{ step.title }}
                            </h3>

                            <p
                                class="mt-1.5 text-xs leading-relaxed text-muted dark:text-gray-400"
                            >
                                {{ step.text }}
                            </p>

                            <span
                                v-if="step.tag"
                                class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20"
                            >
                                <Check class="h-3 w-3" :stroke-width="3" />
                                {{ step.tag }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-14 flex flex-wrap items-center justify-center gap-3">
                <NuxtLink
                    to="/product"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-md shadow-primary/25 transition hover:bg-primary-600"
                >
                    <Sparkles class="h-4 w-4" />
                    Start free trial
                </NuxtLink>

                <a
                    href="#plans"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-secondary transition hover:border-primary hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-white"
                >
                    Compare plans
                </a>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import {
    Building2,
    Check,
    ClipboardCheck,
    Rocket,
    ShieldCheck,
    Sparkles,
    UserPlus,
} from "lucide-vue-next";

// Follows the real subscription path: sign in, the plan page, the setup
// wizard (agency, branch, configuration, review), then admin verification.
const steps = [
    {
        icon: UserPlus,
        title: "Create your account",
        text: "Sign up with the email you'll use to manage your agency.",
    },
    {
        icon: ClipboardCheck,
        title: "Choose a plan",
        text: "SME for one branch or Enterprise for up to 10: Homecare, Facility or Hybrid.",
    },
    {
        icon: Building2,
        title: "Set up agency & branch",
        text: "Add your documents, branch details, location and operating hours.",
    },
    {
        icon: Sparkles,
        title: "Start your free trial",
        text: "No payment needed to start. Or pay by card or GCash if you prefer.",
        tag: "2 weeks free",
    },
    {
        icon: ShieldCheck,
        title: "We verify your branch",
        text: "The AMUMA team reviews it while your trial runs, so you can use AMUMA right away.",
    },
    {
        icon: Rocket,
        title: "Go live",
        text: "Add your staff and start accepting bookings.",
    },
];

// A row's steps are joined by lines; where a row wraps, the line is left out.
// 2 columns (tablet), 3 (laptop), 6 (wide screens).
function lineClasses(index: number) {
    return [
        index % 2 === 0 ? "sm:block" : "sm:hidden",
        index % 3 === 2 ? "lg:hidden" : "lg:block",
        "xl:block",
    ];
}

// Steps light up one after another, and the lines between them draw in,
// the first time the section scrolls into view.
const STEP_DELAY = 550;
const stepsRef = ref<HTMLElement | null>(null);
const stepsVisible = ref(false);
let observer: IntersectionObserver | null = null;

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
        { threshold: 0.3 },
    );

    observer.observe(stepsRef.value);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<style scoped>
.step-line-fill {
    transform: scaleX(0);
    transform-origin: left center;
    transition: transform 600ms cubic-bezier(0.65, 0, 0.35, 1);
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
