<script setup lang="ts">
import Reveal from "~/components/ui/Reveal.vue";

type Action = { label: string; to: string };

withDefaults(
    defineProps<{
        title?: string;
        text?: string;
        primary?: Action;
        secondary?: Action | null;
    }>(),
    {
        title: "Start your business with Amuma today.",
        text: "Create your business profile, manage your branches, and reach more customers—all in one place.",
        primary: () => ({ label: "Create agency account", to: "/product" }),
        secondary: () => ({ label: "Learn more", to: "/for-agencies" }),
    },
);
</script>

<template>
    <section
        class="relative overflow-hidden bg-gradient-to-br from-blue-100 to-light py-16 dark:from-secondary dark:to-secondary"
    >
        <div
            class="pointer-events-none absolute right-[-100px] top-[-50px] h-[400px] w-[400px] rounded-full bg-primary/30 blur-[120px]"
        ></div>

        <Reveal class="relative z-10 mx-auto w-[94%] max-w-[1600px] px-10 max-sm:px-4">
            <div
                class="flex flex-col items-center justify-between gap-8 rounded-[24px] border border-white/30 bg-primary px-8 py-12 backdrop-blur-xl md:flex-row md:px-14"
            >
                <div class="max-w-[620px]">
                    <h2
                        class="mb-3 text-center text-4xl font-black leading-tight text-white md:text-left md:text-5xl"
                    >
                        {{ title }}
                    </h2>

                    <p class="text-center text-white/80 md:text-left">
                        {{ text }}
                    </p>
                </div>

                <div class="flex flex-col items-stretch gap-3 sm:flex-row md:flex-col lg:flex-row">
                    <NuxtLink
                        :to="primary.to"
                        class="group flex items-center justify-center gap-2 rounded-xl bg-white px-10 py-4 font-bold text-primary transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                    >
                        {{ primary.label }}

                        <span
                            class="transition-transform duration-300 group-hover:translate-x-1 group-hover:-translate-y-1"
                        >
                            →
                        </span>
                    </NuxtLink>

                    <NuxtLink
                        v-if="secondary"
                        :to="secondary.to"
                        class="flex items-center justify-center rounded-xl border border-white/50 px-10 py-4 font-bold text-white transition-all duration-300 hover:bg-white/10"
                    >
                        {{ secondary.label }}
                    </NuxtLink>
                </div>
            </div>
        </Reveal>
    </section>
</template>