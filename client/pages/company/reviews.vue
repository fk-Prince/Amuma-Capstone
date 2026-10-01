<template>
    <div class="relative min-h-screen w-full overflow-hidden bg-white dark:bg-secondary">
        <div
            class="pointer-events-none absolute -top-24 left-[-100px] h-[380px] w-[380px] rounded-full bg-primary-200/40 blur-[100px] dark:bg-primary/20"
        />
        <div
            class="pointer-events-none absolute right-[-120px] top-[420px] h-[420px] w-[420px] rounded-full bg-accent-200/30 blur-[120px] dark:bg-primary/10"
        />

        <section class="relative z-10 px-6 pb-10 pt-32 sm:pb-12">
            <div class="mx-auto max-w-3xl text-center">
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-primary">
                    Reviews
                </p>

                <h1 class="text-3xl font-black text-secondary md:text-4xl dark:text-white">
                    What people say about AMUMA
                </h1>

                <p class="mt-4 text-sm leading-7 text-muted md:text-base dark:text-gray-400">
                    Honest feedback from the agencies, caregivers, and families
                    who use AMUMA every day.
                </p>
            </div>
        </section>

        <AppReviews class="relative z-10" />
    </div>
</template>

<script setup lang="ts">
import AppReviews from "~/components/sections/reviews/AppReviews.vue";

useHead({ title: "Reviews - AMUMA" });

definePageMeta({
    layout: "default",
    navVariant: 3,
    navTheme: "light",
});
</script>
