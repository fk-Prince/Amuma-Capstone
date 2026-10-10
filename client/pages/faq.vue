<script setup lang="ts">
import PageHero from "~/components/sections/marketing/PageHero.vue";
import FaqSection from "~/components/sections/landing/FaqSection.vue";
import CtaSection from "~/components/sections/landing/CtaSection.vue";

useHead({ title: "FAQ - AMUMA" });

definePageMeta({
    layout: "default",
    navVariant: 3,
});
</script>

<template>
    <div class="min-h-screen w-full overflow-x-hidden">
        <PageHero
            title="Answers to what people ask before they start."
            highlight="Answers"
            subtitle="Whether you run a care agency or are looking for care for someone you love, start here."
        />

        <FaqSection
            searchable
            title="Frequently asked questions"
            subtitle="Search by topic, or open any question to read the answer."
        />

        <CtaSection
            title="Didn't find your answer?"
            text="See how it all fits together for agencies or for families."
            :primary="{ label: 'For agencies', to: '/agencies' }"
            :secondary="{ label: 'For families', to: '/families' }"
        />
    </div>
</template>