<script setup lang="ts">
import { Building2, Home, Layers } from "lucide-vue-next";
import Reveal from "~/components/ui/Reveal.vue";

const solutions = [
    {
        icon: Home,
        title: "Home Care Services",
        text: "Professional caregivers visit patients at home for personalized care, medication management, and daily living assistance.",
        to: "/solutions/home-care",
    },
    {
        icon: Building2,
        title: "In-House Facility",
        text: "Complete elderly care facility management with room assignments, 24/7 monitoring, and comprehensive patient care.",
        to: "/solutions/facility",
    },
    {
        icon: Layers,
        title: "Hybrid Solution",
        text: "Seamlessly manage both home care services and facility operations from a single integrated platform.",
        to: "/solutions/hybrid",
    },
];
</script>

<template>
    <section
        class="relative overflow-hidden bg-slate-50 py-24 text-center dark:bg-secondary"
    >
        <div
            class="pointer-events-none absolute top-1/2 left-1/2 h-[400px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-200 opacity-30 blur-[80px] dark:bg-primary dark:opacity-10"
        ></div>

        <div class="relative z-10 mx-auto w-[94%] max-w-[1600px] px-10 max-sm:px-4">
            <Reveal>
                <p
                    class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                >
                    ONE PLATFORM
                </p>

                <h2
                    class="mx-auto mb-4 max-w-4xl text-4xl font-black text-secondary md:text-5xl dark:text-white"
                >
                    Modular Solutions for
                    <span
                        class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent dark:to-primary-300"
                    >
                        Every Care
                    </span>
                    Model
                </h2>

                <p
                    class="mx-auto mb-14 max-w-2xl text-sm leading-7 text-muted dark:text-gray-400"
                >
                    Choose the modules your agency needs. Run home care,
                    facility care, or both — AMUMA adapts to your exact
                    workflow.
                </p>
            </Reveal>

            <div class="grid gap-6 lg:grid-cols-3">
                <Reveal
                    v-for="(solution, i) in solutions"
                    :key="solution.to"
                    :delay="i * 100"
                    class="h-full"
                >
                    <NuxtLink
                        :to="solution.to"
                        class="group flex h-full flex-col rounded-[20px] border border-gray-200 bg-white p-8 text-left transition-all duration-300 hover:-translate-y-1.5 hover:border-primary/25 hover:shadow-[0_20px_40px_rgba(49,130,237,0.08),0_4px_12px_rgba(15,23,42,0.04)] dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-primary/40"
                    >
                        <div
                            class="mb-6 flex h-14 w-14 items-center justify-center rounded-[14px] bg-light text-primary transition-colors duration-300 group-hover:bg-primary group-hover:text-white dark:bg-primary/15"
                        >
                            <component :is="solution.icon" class="h-7 w-7" />
                        </div>

                        <h3
                            class="mb-3 text-lg font-bold text-secondary dark:text-white"
                        >
                            {{ solution.title }}
                        </h3>

                        <p
                            class="mb-6 flex-1 text-sm leading-7 text-muted dark:text-gray-400"
                        >
                            {{ solution.text }}
                        </p>

                        <span
                            class="inline-flex items-center gap-2 font-semibold text-primary"
                        >
                            Explore
                            <span
                                class="transition-transform duration-200 group-hover:translate-x-1"
                            >
                                →
                            </span>
                        </span>
                    </NuxtLink>
                </Reveal>
            </div>
        </div>
    </section>
</template>